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
 * Class GameSpy3Query
 * @package GameServerQuery\Query\Types
 */
class GameSpy3Query extends AbstractQuery
{
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
        $responses = [];

        $this->serverChallenge = $this->readServerChallenge($socket);
        $information           = $this->readServerAll($socket);
        $this->serverChallenge = null; // We need to reset it before fetch server's players and rules.

        \array_push($responses, ...$information);

        // No response caught. Stop the process and go the next server (if any!).
        if (!$responses) {
            // Close socket.
            $socket->close();

            unset($information, $this->serverChallenge);

            return $result->toArray();
        }

        // Process all information and create a new Result object.
        $response = $this->server->getProtocol()->handleResponse($result, $responses);

        // Close socket.
        $socket->close();

        unset($information, $responses, $this->serverChallenge);

        return $response;
    }

    /**
     * Read challenge from server.
     *
     * @param Socket $socket
     * @param int    $length
     *
     * @return string|null
     */
    protected function readServerChallenge(Socket $socket, int $length = 32768): ?string
    {
        $package   = $this->createPackage(ProtocolInterface::PACKAGE_CHALLENGE);
        $responses = $this->doRead($socket, $package, $length);
        $buffer    = new Buffer(\implode('', $responses));

        if (!$buffer->getLength()) {
            return ''; // Buffer is empty.
        }

        $challenge = substr(preg_replace("/[^0-9\-]/si", "", $buffer->getBuffer()), 1);
        if (!$challenge) {
            return null;
        }

        return sprintf(
            "%c%c%c%c",
            ($challenge >> 24),
            ($challenge >> 16),
            ($challenge >> 8),
            ($challenge >> 0)
        );
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
        $packageType = parent::createPackage($packageType);

        return sprintf($packageType, $this->serverChallenge);
    }

    /**
     * @inheritDoc
     */
    protected function readPackageFromServer(Socket $socket, string $packageType, int $length = 32768): array
    {
        $package   = $this->createPackage($packageType);
        $responses = $this->doRead($socket, $package, $length);
        $buffer    = new Buffer(\implode('', $responses), Buffer::NUMBER_TYPE_BIG_ENDIAN);

        if (!$buffer->getLength()) {
            return []; // Buffer is empty.
        }

        return [$buffer->getData()];
    }
}