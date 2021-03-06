<?php declare(strict_types = 1);

namespace GameServerQuery;

use GameServerQuery\Filter\Type\Common\UTF8Filter;
use GameServerQuery\Formatter\Types\ArrayFormatter;
use GameServerQuery\Interfaces\FilterInterface;
use GameServerQuery\Interfaces\FormatterInterface;

/**
 * Class GameServerQuery
 * @package GameServerQuery
 */
class GameServerQuery
{
    /**
     * Current script version.
     */
    private const VERSION = '1.0.0';

    /**
     * GameServerQuery configuration.
     *
     * @var Config|null
     */
    protected ?Config $config = null;

    /**
     * List of servers.
     *
     * @var Server[]|array
     */
    protected array $servers = [];

    /**
     * List of filters.
     *
     * @var FilterInterface[]|array
     */
    protected array $filters = [];

    /**
     * Response formatter.
     *
     * @var string
     */
    protected string $formatter = ArrayFormatter::class;

    /**
     * GameServerQuery constructor.
     */
    public function __construct()
    {
        $this->filter(UTF8Filter::class, [
            // Note:
            // If *protocols = []* -> Filter applies to all games
            // If *protocols = [game class #1, game class #2 ...]* -> Filter applies only to those games
            // If *protocols = [protocol class]* -> Filter applies to all derived game protocols
            // (e.g.: If value is *Source*, then CS, HL and all classes will apply this filter)
            'protocols' => [],

            // Filter sections.
            // If 'sections' is an empty array, then the filter will be skipped.
            'sections'  => [
                // [] = all keys, ['hostname', 'map', ...] - applies only to these keys, null = don't filter this section
                Result::GENERAL_CATEGORY => [Result::GENERAL_HOSTNAME_SUBCATEGORY, Result::GENERAL_MAP_SUBCATEGORY],
                Result::PLAYERS_CATEGORY => [Result::PLAYERS_NAME_SUBCATEGORY],
                Result::RULES_CATEGORY   => [],
            ],

            // Set extra parameters (if necessary).
            'options'   => [],
        ]);
    }

    /**
     * Set configuration.
     *
     * @param Config $config
     *
     * @return $this
     */
    public function config(Config $config): GameServerQuery
    {
        $this->config = $config;

        return $this;
    }

    /**
     * Add server.
     *
     * @param Server $server
     *
     * @return $this
     */
    public function server(Server $server): GameServerQuery
    {
        $this->servers[$server->getFullAddress()] = $server;

        return $this;
    }

    /**
     * Add servers.
     *
     * @param Server[]|array $servers
     *
     * @return $this
     */
    public function servers(array $servers): GameServerQuery
    {
        foreach ($servers as $server) {
            $this->server($server);
        }

        return $this;
    }

    /**
     * Add new filter.
     *
     * @param string $filterClass
     * @param array  $options
     *
     * @return $this
     */
    public function filter(string $filterClass, array $options = []): GameServerQuery
    {
        $this->filters[$filterClass] = $options;

        return $this;
    }

    /**
     * Set formatter class.
     * @throws \Exception
     */
    public function formatter(string $formatter): GameServerQuery
    {
        if (!in_array(FormatterInterface::class, class_implements($formatter))) {
            throw new \Exception(
                sprintf('Your formatter class (%s) does not implement FormatterInterface.', $formatter)
            );
        }

        $this->formatter = $formatter;

        return $this;
    }

    /**
     * Process servers.
     *
     * @return mixed
     * @throws \Exception
     */
    public function process(): mixed
    {
        if (!$this->servers) {
            throw new \InvalidArgumentException('No server provided!');
        }

        // Check if there's no config class and initiate a new one.
        if (!$this->config) {
            $this->config = new Config();
        }

        // Set configuration and query server(-s).
        $results = (new Query($this->servers))
            ->config($this->config)
            ->execute();

        // Apply filters.
        $servers = [];

        foreach ($results as $fullAddress => $response) {
            foreach ($this->filters as $filter => $options) {
                /** @var FilterInterface $filter */
                $filter                = new $filter($response, $options);
                $servers[$fullAddress] = $filter->apply();
            }
        }

        // Format response.
        $formatter = new $this->formatter($servers);

        return $formatter->format();
    }
}