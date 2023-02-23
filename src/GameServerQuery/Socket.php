<?php declare(strict_types = 1);

namespace GameServerQuery;

use GameServerQuery\Exception\Socket\SocketCreationFailedException;

/**
 * Class Socket
 * @package GameServerQuery
 */
class Socket
{
    /**
     * Server socket.
     *
     * @var resource|null
     */
    protected $socket;

    /**
     * Socket constructor.
     *
     * @param Server $server
     * @param int    $timeout
     *
     * @throws SocketCreationFailedException
     */
    public function __construct(Server $server, int $timeout = 3)
    {
        $this->create($server, $timeout);
    }

    /**
     * Create new socket for given server.
     *
     * @param Server $server
     * @param int    $timeout
     *
     * @return void
     * @throws SocketCreationFailedException
     */
    protected function create(Server $server, int $timeout): void
    {
        $address = \sprintf('%s://%s', $server->getProtocol()->getTransportSchema(), $server->getFullAddressWithQueryPort());
        $context = \stream_context_create(['socket' => ['bindto' => '0:0']]);

        // Try to create socket.
        $this->socket = @\stream_socket_client($address, $errorNumber, $errorMessage, $timeout, STREAM_CLIENT_CONNECT, $context);

        if (!$this->socket) {
            throw new SocketCreationFailedException(sprintf('Socket was not created for "%s".', $server->getFullAddressWithQueryPort()));
        }

        \stream_set_timeout($this->socket, $timeout);
        \stream_set_blocking($this->socket, $server->getProtocol()->isBlockingMode());
        \stream_set_read_buffer($this->socket, 0);
        \stream_set_write_buffer($this->socket, 0);
    }

    /**
     * Write to open socket.
     *
     * @param string $package
     *
     * @return int|bool
     */
    public function write(string $package): int|bool
    {
        return \fwrite($this->socket, $package);
    }

    /**
     * Returns socket resource.
     *
     * @return resource
     */
    public function getSocket(): mixed
    {
        return $this->socket;
    }

    /**
     * Close socket connection.
     */
    public function close(): void
    {
        if (\is_resource($this->socket)) {
            \fclose($this->socket);
            $this->socket = null;
        }
    }

    /**
     * Destruct object.
     */
    public function __destruct()
    {
        $this->close();
    }
}