<?php declare(strict_types = 1);

namespace GameServerQuery;

use GameServerQuery\Exception\Formatter\FormatterException;
use GameServerQuery\Filter\Type\Common\UTF8Filter;
use GameServerQuery\Formatter\Types\ArrayFormatter;
use GameServerQuery\Interfaces\FilterInterface;
use GameServerQuery\Interfaces\FormatterInterface;
use GameServerQuery\Query\QueryManager;

/**
 * Class GameServerQuery
 * @package GameServerQuery
 */
class GameServerQuery
{
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
            ],

            // Set extra parameters (if necessary).
            'options'   => [],
        ]);
    }

    /**
     * Set configuration.
     *
     * @param Config|null $config
     *
     * @return GameServerQuery
     */
    public function config(?Config $config = null): GameServerQuery
    {
        $this->config = $config;

        return $this;
    }

    /**
     * Add server.
     *
     * @param Server $server
     *
     * @return GameServerQuery
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
     * @return GameServerQuery
     */
    public function servers(Server ...$servers): GameServerQuery
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
     * @return GameServerQuery
     */
    public function filter(string $filterClass, array $options = []): GameServerQuery
    {
        $this->filters[$filterClass] = $options;

        return $this;
    }

    /**
     * Set formatter class.
     *
     * @param string $formatter
     *
     * @return GameServerQuery
     * @throws FormatterException
     */
    public function formatter(string $formatter): GameServerQuery
    {
        if (!\in_array(FormatterInterface::class, \class_implements($formatter), true)) {
            throw new FormatterException(
                \sprintf('"%s" does not implement FormatterInterface.', $formatter)
            );
        }

        $this->formatter = $formatter;

        return $this;
    }

    /**
     * Process servers.
     *
     * @return mixed
     * @throws \InvalidArgumentException
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
        $queryManager = new QueryManager($this->servers, $this->config);
        $results      = $queryManager->execute();

        // Apply filters.
        $processedServers = [];

        foreach ($results as $fullAddress => $response) {
            foreach ($this->filters as $filter => $options) {
                /** @var FilterInterface $filter */
                $processedServers[$fullAddress] = (new $filter($response, $options))->apply();
            }
        }

        /** @var FormatterInterface $formatter */
        $formatter = new $this->formatter($processedServers);

        return $formatter->format();
    }
}