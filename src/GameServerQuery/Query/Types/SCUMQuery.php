<?php declare(strict_types = 1);

namespace GameServerQuery\Query\Types;

use GameServerQuery\Buffer;
use GameServerQuery\Exception\Buffer\BufferException;
use GameServerQuery\Exception\Socket\SocketCreationFailedException;
use GameServerQuery\Query\AbstractQuery;
use GameServerQuery\Result;
use GameServerQuery\Socket;

/**
 * Class SCUMQuery
 * @package GameServerQuery\Query\Types
 */
class SCUMQuery extends AbstractQuery
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

        $information = $this->readServerInformation($socket);
        \array_push($responses, ...$information);
        dd($information);

        // No response caught. Stop the process and go the next server (if any!).
        if (!$responses) {
            // Close socket.
            $socket->close();
            unset($information);

            return $result->toArray();
        }

        // Process all information and create a new Result object.
        $response = $this->server->getProtocol()->handleResponse($result, $responses);

        // Close socket.
        $socket->close();
        unset($information);

        return $response;
    }

    /**
     * @inheritDoc
     */
    protected function readPackageFromServer(Socket $socket, string $packageType, int $length = 32768): array
    {
        $package   = $this->server->getProtocol()->getPackage($packageType);
        $responses = $this->doRead($socket, $package, $length);
        $buffer    = new Buffer(\implode('', $responses));

        if (!$buffer->getLength()) {
            return []; // Buffer is empty.
        }

        return [$buffer->getData()];
    }
}