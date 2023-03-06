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
 * Class SourceQuery
 * @package GameServerQuery\Query\Types
 */
class SourceQuery extends AbstractQuery
{
    // Packets received.
    public const S2C_CHALLENGE = 0x41;
    public const S2A_INFO_OLD  = 0x6D; // Old GoldSource, HLTV uses it (actually called S2A_INFO_DETAILED)

    /**
     * Create package.
     *
     * @param string $packageType
     * @param string $string
     *
     * @return string
     */
    protected function createPackage(string $packageType, string $string = ''): string
    {
        if (!$package = $this->server->getProtocol()->getPackage($packageType)) {
            throw new \InvalidArgumentException(sprintf("Package '%s' not found for current protocol!", $packageType));
        }

        return \sprintf($package, $string . ($this->serverChallenge ?? ''));
    }

    /**
     * Extract servers' challenge.
     *
     * @param Socket $socket
     *
     * @return void
     * @throws BufferException
     */
    protected function extractChallenge(Socket $socket): void
    {
        $package  = $this->createPackage(ProtocolInterface::PACKAGE_PLAYERS, "\xFF\xFF\xFF\xFF");
        $response = $this->doRead($socket, $package, 32768);

        if (!$response) {
            return; // Stop query execution.
        }

        $buffer = new Buffer(\implode('', $response));
        $buffer->skip(5);

        $this->serverChallenge = $buffer->read(4);
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
        if (ProtocolInterface::PACKAGE_INFO !== $packageType && !$this->serverChallenge) {
            $this->extractChallenge($socket);
        }

        $package  = $this->createPackage($packageType);
        $response = $this->doRead($socket, $package, $length);
        $buffer   = new Buffer(\implode('', $response));

        if (!$buffer->getLength()) {
            return []; // Buffer is empty.
        }

        $buffer->skip(4); // Skip some bytes.
        $type = \ord($buffer->lookAhead());

        // Sometimes,
        if (self::S2C_CHALLENGE === $type) {
            $buffer->skip();
            $this->serverChallenge = $buffer->read(4);
            $package               = $this->createPackage($packageType); // Reuse this method to generate a package using server challenge.
            $response              = $this->doRead($socket, $package, $length);
            $buffer                = new Buffer(\implode('', $response));
        }

        return [$buffer->getData()];
    }

    /**
     * Read from server.
     *
     * @param Socket $socket
     * @param string $package
     * @param int    $length
     *
     * @return array
     */
    protected function doRead(Socket $socket, string $package, int $length): array
    {
        $socket->write($package);

        return $this->doSocketQuery($socket, $length);
    }

    /**
     * Read A2S_INFO from server.
     *
     * @param Socket $socket
     * @param int    $length
     *
     * @return array
     * @throws BufferException
     */
    protected function readServerInformation(Socket $socket, int $length = 32768): array
    {
        return $this->readPackageFromServer($socket, ProtocolInterface::PACKAGE_INFO, $length);
    }

    /**
     * Read A2S_PLAYER from server.
     *
     * @param Socket $socket
     * @param int    $length
     *
     * @return array
     * @throws BufferException
     */
    protected function readServerPlayers(Socket $socket, int $length = 32768): array
    {
        return $this->readPackageFromServer($socket, ProtocolInterface::PACKAGE_PLAYERS, $length);
    }

    /**
     * Read A2S_RULES from server.
     *
     * @param Socket $socket
     * @param int    $length
     *
     * @return array
     * @throws BufferException
     */
    protected function readServerCvars(Socket $socket, int $length = 32768): array
    {
        return $this->readPackageFromServer($socket, ProtocolInterface::PACKAGE_RULES, $length);
    }

    /**
     * @inheritDoc
     * @throws SocketCreationFailedException
     * @throws BufferException
     */
    public function execute(): array
    {
        // Set default response format.
        $result = new Result();
        $result->addInformation(Result::GENERAL_APPLICATION_SUBCATEGORY, \get_class($this->server->getProtocol()));
        $result->addInformation(Result::GENERAL_IP_ADDRESS_SUBCATEGORY, $this->server->getIpAddress());
        $result->addInformation(Result::GENERAL_PORT_SUBCATEGORY, $this->server->getPort());
        $result->addInformation(Result::GENERAL_QUERY_PORT_SUBCATEGORY, $this->server->getQueryPort());

        // Open socket for server.
        $socket = new Socket($this->server, $this->config->get('timeout', 3));

        // Check if we have any static challenge.
        if ($this->server->getProtocol()->hasChallenge()) {
            $this->serverChallenge = $this->server->getProtocol()->getPackage(ProtocolInterface::PACKAGE_CHALLENGE);
        }

        // Fetch server data.
        $responses = [];

        $information = $this->readServerInformation($socket);
        $players     = $this->readServerPlayers($socket);
        $rules       = $this->readServerCvars($socket);

        \array_push($responses, ...$information, ...$players, ...$rules);

        // No response caught. Stop the process and go the next server (if any!).
        if (!$responses) {
            // Close socket.
            $socket->close();

            unset($information, $players, $rules, $this->serverChallenge);

            return $result->toArray();
        }

        // Process all information and create a new Result object.
        $response = $this->server->getProtocol()->handleResponse($result, $responses);

        // Close socket.
        $socket->close();

        unset($information, $players, $rules, $responses, $this->serverChallenge);

        return $response;
    }
}