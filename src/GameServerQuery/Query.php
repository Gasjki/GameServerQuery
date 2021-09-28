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
     * List of servers.
     *
     * @var Server[]|array
     */
    protected array $servers;

    /**
     * Query configuration.
     *
     * @var Config
     */
    protected Config $config;

    /**
     * Query constructor.
     *
     * @param Server[]|array $servers
     * @param Config         $config
     */
    public function __construct(array $servers, Config $config)
    {
        $this->servers = $servers;
        $this->config  = $config;
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
        $responses  = [];
        $sockets    = [$socket->getSocket()];
        $timeToStop = microtime(true) + $this->config->get('timeout', 3);

        while (microtime(true) < $timeToStop) {
            if (empty($sockets)) {
                break;
            }

            $stream = stream_select($sockets, $write, $except, 0, $this->config->get('stream_timeout', 200000));
            if (false === $stream || ($stream <= 0)) {
                break;
            }

            foreach ($sockets as $socket) {
                if (($response = fread($socket, 4096)) === false) {
                    continue;
                }

                if (strlen($response) === 0) {
                    break;
                }

                $responses[] = $response;
            }
        }

        // Close socket and clean memory.
        unset($sockets, $write, $except, $timeToStop, $stream);

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
            $result = new Result();
            $result->addInformation(Result::GENERAL_APPLICATION_SUBCATEGORY, get_class($server->getProtocol()));

            $response[$fullAddress] = $result->toArray();

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