<?php declare(strict_types = 1);

namespace GameServerQuery\Query;

use GameServerQuery\Config;
use GameServerQuery\Interfaces\QueryInterface;
use GameServerQuery\Server;
use GameServerQuery\Socket;

/**
 * Class AbstractQuery
 * @package GameServerQuery\Query
 */
abstract class AbstractQuery implements QueryInterface
{
    /**
     * Challenge obtained from server.
     *
     * @var mixed|null
     */
    protected mixed $serverChallenge = null;

    /**
     * AbstractQuery constructor.
     *
     * @param Server $server
     * @param Config $config
     */
    public function __construct(protected Server $server, protected Config $config)
    {
    }

    /**
     * @inheritDoc
     */
    abstract public function execute(): array;

    /**
     * Read information from socket.
     *
     * @param Socket $socket
     * @param int    $length
     *
     * @return array
     */
    protected function doSocketQuery(Socket $socket, int $length = 1024): array
    {
        $responses  = [];
        $sockets    = [$socket->getSocket()];
        $timeToStop = \microtime(true) + $this->config->get('timeout', 3);

        while (\microtime(true) < $timeToStop) {
            if (empty($sockets)) {
                break;
            }

            $stream = \stream_select($sockets, $write, $except, 0, $this->config->get('stream_timeout', 200000));
            if (false === $stream || ($stream <= 0)) {
                break;
            }

            foreach ($sockets as $socket) {
                if (($response = \fread($socket, $length)) === false) {
                    continue;
                }

                if (\strlen($response) === 0) {
                    break;
                }

                $responses[] = $response;
            }
        }

        // Close socket and clean memory.
        unset($sockets, $write, $except, $timeToStop, $stream);

        return $responses;
    }
}