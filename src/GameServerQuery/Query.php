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
     * Create new socket for given server.
     *
     * @param Server $server
     *
     * @return resource|mixed
     * @throws \Exception
     */
    protected function createSocket(Server $server): mixed
    {
        $address = sprintf('%s://%s', $server->getProtocol()->getTransportSchema(), $server->getFullAddressWithQueryPort());
        $context = stream_context_create(['socket' => ['bindto' => '0:0']]);

        // Try to create socket.
        $socket = stream_socket_client(
            $address,
            $errorNumber,
            $errorMessage,
            $this->config->get('timeout', 3),
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!$socket) {
            throw new \Exception(sprintf("Socket was not created for server (%s).", $server->getFullAddressWithQueryPort()));
        }

        stream_set_timeout($socket, $this->config->get('timeout', 3));
        stream_set_blocking($socket, $server->getProtocol()->isBlockingMode());

        return $socket;
    }

    /**
     * Write to open socket.
     *
     * @param        $socket
     * @param string $package
     *
     * @return int|bool
     */
    protected function writeSocket($socket, string $package): int|bool
    {
        // @TODO: To be removed when resource typehint will be available.
        if (!is_resource($socket)) {
            throw new \InvalidArgumentException("Invalid argument given. Expected a socket resource!");
        }

        return fwrite($socket, $package);
    }

    /**
     * Close socket connection.
     *
     * @param resource $socket
     */
    protected function closeSocket($socket): void
    {
        fclose($socket);
        unset($socket);
    }

    /**
     * Read information from socket.
     *
     * @param resource $socket
     *
     * @return array
     */
    protected function doStreamQuery($socket): array
    {
        $responses    = [];
        $isLoopActive = true;
        $i            = 0;

        $read   = [$socket];
        $write  = null;
        $except = null;

        // Check to make sure $read is not empty. Otherwise, we are done!
        if (empty($read)) {
            return $responses;
        }

        $timeToStop = microtime(true) + ($this->config->get('timeout', 3));

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
                if (($response = fread($streamingSocket, 32768)) === false) {
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
     * @param resource $socket
     * @param Server   $server
     *
     * @return array
     */
    protected function doQuery($socket, Server $server): array
    {
        $packages = $server->getProtocol()->getAllPackagesExcept(ProtocolInterface::PACKET_CHALLENGE);

        foreach ($packages as $package) {
            $this->writeSocket($socket, $package);
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
            $response[$fullAddress] = [];

            // Open socket for server.
            $socket = $this->createSocket($server);

            // Check if protocol has challenge.
            if (!$server->getProtocol()->hasChallenge()) {
                continue;
            }

            // Write protocol challenge to socket.
            $challengePackage = $server->getProtocol()->getPackage(ProtocolInterface::PACKET_CHALLENGE);
            $this->writeSocket($socket, $challengePackage);

            // Do stream query to extract server challenge.
            if (!$rawResponses = $this->doStreamQuery($socket)) {
                continue;
            }

            // Remove challenge package from response.
            $data = new Buffer(implode(array: $rawResponses));
            $server->getProtocol()->updatePackagesBasedOnChallengePackageResponse($data);

            // Do query.
            $response[$fullAddress] = $this->doQuery($socket, $server);

            $this->closeSocket($socket);
        }

        return $response;
    }
}