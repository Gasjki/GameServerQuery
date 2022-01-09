<?php declare(strict_types = 1);

namespace GameServerQuery\Query;

use GameServerQuery\Config;
use GameServerQuery\Interfaces\QueryInterface;
use GameServerQuery\Server;

/**
 * Class QueryManager
 * @package GameServerQuery\Query
 */
class QueryManager
{
    /**
     * QueryManager constructor.
     *
     * @param Server[]|array $servers
     * @param Config         $config
     */
    public function __construct(protected array $servers, protected Config $config)
    {
    }

    /**
     * Query all servers.
     *
     * @return array
     */
    public function execute(): array
    {
        $results = [];

        /** @var Server $server */
        foreach ($this->servers as $server) {
            $queryClassName                     = $server->getProtocol()->getQueryClass();
            /** @var QueryInterface $query */
            $query                              = new $queryClassName($server, $this->config);
            $results[$server->getFullAddress()] = $query->execute();
        }

        return $results;
    }
}