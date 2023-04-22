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
    public const S2C_CHALLENGE = "\x41";
    public const S2A_INFO_OLD  = "\x6D"; // Old GoldSource, HLTV uses it (actually called S2A_INFO_DETAILED)

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

        return $package . $string . ($this->serverChallenge ?? "\xFF\xFF\xFF\xFF");
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
        $responses = $this->handleSplitPackets($responses);
        $buffer    = new Buffer(\implode('', $responses));

        if (!$buffer->getLength()) {
            return []; // Buffer is empty.
        }

        $buffer->skip(4); // Skip some bytes.
        $type = $buffer->lookAhead();

        // Servers may respond with the data immediately.
        // However, since this reply is larger than the request, it makes the server
        // vulnerable to a reflection amplification attack. Instead, the server may reply
        // with a challenge to the client using S2C_CHALLENGE ('A' or 0x41). In that case,
        // the client should repeat the request by appending the challenge number. This change
        // was introduced in December 2020 to address the reflection attack vulnerability,
        // and all clients are encouraged to support the new protocol.
        if (self::S2C_CHALLENGE === $type) {
            $buffer->skip();
            $this->serverChallenge = $buffer->read(4);
            $responses             = $this->readPackageFromServer($socket, $packageType, $length);
            $buffer                = new Buffer(\implode('', $responses));
        }

        return [$buffer->getData()];
    }

    protected function handleSplitPackets(array $responses): array
    {
        if (count($responses) === 1) {
            return $responses;
        }

        $data = [];

        foreach ($responses as $index => $item) {
            if ($index === 0) {
                $data[] = $item;
                continue;
            }

            $buffer = new Buffer($item);
            $buffer->skip(9);
            $data[] = $buffer->getBuffer();
        }

        return $data;
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
     * Read A2S_SERVERQUERY_GETCHALLENGE from server.
     *
     * @param Socket $socket
     * @param int    $length
     *
     * @return string
     */
    protected function readServerChallenge(Socket $socket, int $length = 32768): string
    {
        $package   = $this->createPackage(ProtocolInterface::PACKAGE_CHALLENGE);
        $package   = \substr($package, 0, 5); // Remove "\xFF\xFF\xFF\xFF".
        $responses = $this->doRead($socket, $package, $length);
        $buffer    = new Buffer(\implode('', $responses));

        if (!$buffer->getLength()) {
            return ''; // Buffer is empty.
        }

        $buffer->skip(5);

        return $buffer->getBuffer();
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
    protected function readServerRules(Socket $socket, int $length = 32768): array
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

        // Fetch server data.
        $responses = [];

        $this->serverChallenge = $this->readServerChallenge($socket);
        $information           = $this->readServerInformation($socket);
        $this->serverChallenge = null; // We need to reset it before fetch server's players and rules.
        $players               = $this->readServerPlayers($socket);
        $rules                 = $this->readServerRules($socket);

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