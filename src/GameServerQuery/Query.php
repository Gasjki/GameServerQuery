<?php declare(strict_types = 1);

namespace GameServerQuery;

use GameServerQuery\Interfaces\ProtocolInterface;

/**
 * Class Query
 * @package GameServerQuery
 */
class Query
{
    /**
     * Query configuration.
     *
     * @var Config|null
     */
    protected ?Config $config = null;

    /**
     * Query constructor.
     *
     * @param Server[]|array $servers
     */
    public function __construct(protected array $servers)
    {
        $this->config = new Config();
    }

    /**
     * Set configuration.
     *
     * @param Config $config
     *
     * @return $this
     */
    public function config(Config $config): Query
    {
        $this->config = $config;

        return $this;
    }

    /**
     * Read information from socket.
     *
     * @param Socket $socket
     *
     * @return array
     */
    protected function doStreamQuery(Socket $socket): array
    {
        $responses    = [];
        $isLoopActive = true;
        $i            = 0;

        $read   = [$socket = $socket->getSocket()];
        $write  = null;
        $except = null;

        // Check to make sure $read is not empty. Otherwise, we are done!
        if (empty($read)) {
            return $responses;
        }

        $timeToStop = microtime(true) + $this->config->get('timeout', 3);

        while ($isLoopActive && microtime(true) < $timeToStop) {
            // Check to make sure $read is not empty. Otherwise, we are done!
            if (empty($read)) {
                break;
            }

            $stream = stream_select($read, $write, $except, 0, $this->config->get('stream_timeout', 200000));
            if (false === $stream || ($stream <= 0)) {
                break;
            }

            foreach ($read as $streamingSocket) {
                if (($response = fread($streamingSocket, 4096)) === false) {
                    continue;
                }

                // Check to see if the response is empty. Otherwise we are done!
                if (strlen($response) === 0) {
                    break;
                }

                $responses[$i++] = $response;
            }

            $read = [$socket];
        }

        // Close socket and clean memory.
        unset($isLoopActive, $read, $write, $except, $timeToStop, $stream);

        return $responses;
    }

    /**
     * Do query to extract binary information from socket.
     *
     * @param Socket $socket
     * @param Server $server
     *
     * @return array
     */
    protected function doQuery(Socket $socket, Server $server): array
    {
        $packages = $server->getProtocol()->getAllPackagesExcept(ProtocolInterface::PACKAGE_CHALLENGE);

        foreach ($packages as $package) {
            $socket->write($package);
            usleep($this->config->get('write_wait', 500));
        }

        // Extract information.
        return $this->doStreamQuery($socket);
    }

    /**
     * Query servers.
     *
     * @return array
     * @throws \Exception
     */
    public function execute(): array
    {
        $response = [];

        foreach ($this->servers as $fullAddress => $server) {
            // Set default response format.
            $response[$fullAddress] = (new Result())->getResult();

            // Open socket for server.
            $socket = new Socket($server, $this->config->get('timeout', 3));

            // Check if protocol has challenge.
            if (!$server->getProtocol()->hasChallenge()) {
                continue;
            }

            // Write protocol challenge to socket.
            $challengePackage = $server->getProtocol()->getPackage(ProtocolInterface::PACKAGE_CHALLENGE);
            $socket->write($challengePackage);

            // Do stream query to extract server challenge.
            if (!$rawResponses = $this->doStreamQuery($socket)) {
                continue;
            }

            // Remove challenge package from response.
            $data = new Buffer(implode('', $rawResponses));
            $server->getProtocol()->updateQueryPackages($data);

            // Do query.
            $responses              = $this->doQuery($socket, $server);
            $response[$fullAddress] = $server->getProtocol()->handleResponse($responses);

            // Close socket.
            $socket->close();
        }

        return $response;
    }
}