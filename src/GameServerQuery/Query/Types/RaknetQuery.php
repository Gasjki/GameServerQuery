<?php declare(strict_types = 1);

namespace GameServerQuery\Query\Types;

use GameServerQuery\Buffer;
use GameServerQuery\Query\AbstractQuery;
use GameServerQuery\Result;
use GameServerQuery\Socket;

class RaknetQuery extends AbstractQuery
{
    public const ID_UNCONNECTED_PONG     = "\x1C";
    public const OFFLINE_MESSAGE_DATA_ID = "\x00\xFF\xFF\x00\xFE\xFE\xFE\xFE\xFD\xFD\xFD\xFD\x12\x34\x56\x78";

    /**
     * @inheritDoc
     */
    public function execute(): array
    {
        $result = new Result(parent::execute());

        // Open socket for server.
        $socket = new Socket($this->server, $this->config->get('timeout', 3));

        // Fetch server data.
        $responses = [];

        $information = $this->readServerStatus($socket);
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

        \unset($information, $responses, $this->serverChallenge);

        return $response;
    }

    /**
     * @inheritDoc
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

    /**
     * @inheritDoc
     */
    protected function createPackage(string $packageType): string
    {
        $package = parent::createPackage($packageType);

        return \sprintf($package, \pack('Q', \time()), self::OFFLINE_MESSAGE_DATA_ID);
    }
}