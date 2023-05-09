<?php declare(strict_types = 1);

namespace GameServerQuery\Query\Types;

use GameServerQuery\Buffer;
use GameServerQuery\Exception\Buffer\BufferException;
use GameServerQuery\Exception\Socket\SocketCreationFailedException;
use GameServerQuery\Interfaces\ProtocolInterface;
use GameServerQuery\Query\AbstractQuery;
use GameServerQuery\Result;
use GameServerQuery\Socket;

/**
 * Class SanAndreasMultiplayerQuery
 * @package GameServerQuery\Query\Types
 */
class SanAndreasMultiplayerQuery extends AbstractQuery
{
    /**
     * Returns server code.
     *
     * @param string $ipAddress
     * @param int    $clientPort
     *
     * @return string
     */
    public static function computeServerChallenge(string $ipAddress, int $clientPort): string
    {
        return implode('', array_map('chr', explode('.', $ipAddress))) . pack('S', $clientPort);
    }

    /**
     * @inheritDoc
     * @throws SocketCreationFailedException
     * @throws BufferException
     */
    public function execute(): array
    {
        $result = new Result(parent::execute());

        // Open socket for server.
        $socket = new Socket($this->server, $this->config->get('timeout', 3));

        // Fetch server data.
        $responses             = [];
        $this->serverChallenge = self::computeServerChallenge($this->server->getIpAddress(), $this->server->getPort());

        $status  = $this->readServerStatus($socket);
        $players = $this->readServerPlayers($socket);
        $rules   = $this->readServerRules($socket);

        \array_push($responses, ...$status, ...$players, ...$rules);

        // No response caught. Stop the process and go the next server (if any!).
        if (!$responses) {
            // Close socket.
            $socket->close();

            unset($status, $players, $rules, $this->serverChallenge);

            return $result->toArray();
        }

        // Process all information and create a new Result object.
        $response = $this->server->getProtocol()->handleResponse($result, $responses);

        // Close socket.
        $socket->close();

        unset($status, $players, $rules, $responses, $this->serverChallenge);

        return $response;
    }

    /**
     * Create package.
     *
     * @param string $packageType
     *
     * @return string
     */
    protected function createPackage(string $packageType): string
    {
        $package = parent::createPackage($packageType);

        $type = match ($packageType) {
            ProtocolInterface::PACKAGE_STATUS  => 'i',
            ProtocolInterface::PACKAGE_PLAYERS => 'd',
            ProtocolInterface::PACKAGE_RULES   => 'r',
        };

        return $package . $this->serverChallenge . $type;
    }

    /**
     * Read specific package from given servers' socket.
     *
     * @param Socket $socket
     * @param string $packageType
     * @param int    $length
     *
     * @return array
     * @throws BufferException
     */
    protected function readPackageFromServer(Socket $socket, string $packageType, int $length = 32768): array
    {
        $package   = $this->createPackage($packageType);
        $responses = $this->doRead($socket, $package, $length);
        $buffer    = new Buffer(\implode('', $responses));

        if (!$buffer->getLength()) {
            return []; // Buffer is empty.
        }

        return [$buffer->getData()];
    }
}