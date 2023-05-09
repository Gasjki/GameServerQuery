<?php declare(strict_types = 1);

namespace GameServerQuery\Protocol\Games\Other;

use GameServerQuery\Buffer;
use GameServerQuery\Exception\Buffer\BufferException;
use GameServerQuery\Exception\Buffer\InvalidBufferContentException;
use GameServerQuery\Protocol\AbstractProtocol;
use GameServerQuery\Query\Types\SanAndreasMultiplayerQuery;
use GameServerQuery\Result;

/**
 * Class SanAndreasMultiplayerProtocol
 * @package GameServerQuery\Protocol\Games\Other
 */
class SanAndreasMultiplayerProtocol extends AbstractProtocol
{
    /**
     * Protocol packages.
     *
     * Packages to be sent to socket.
     *
     * @var array
     */
    protected array $packages = [
        self::PACKAGE_STATUS  => "SAMP", // + server challenge + "i"
        self::PACKAGE_PLAYERS => "SAMP", // + server challenge + "d"
        self::PACKAGE_RULES   => "SAMP", // + server challenge + "r"
    ];

    /**
     * Socket responses and their corresponding protocol method.
     *
     * @var array
     */
    protected array $responses = [
        "\x69" => "processStatus", // i
        "\x64" => "processPlayers", // d
        "\x72" => "processRules", // r
    ];

    /**
     * @inheritDoc
     */
    public function getQueryClass(): string
    {
        return SanAndreasMultiplayerQuery::class;
    }

    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function handleResponse(Result $result, array $responses): array
    {
        // No data to be parsed.
        if (!\count($responses)) {
            return $result->toArray();
        }

        // Extract packets.
        $ipAddress       = $result->getInformation(Result::GENERAL_IP_ADDRESS_SUBCATEGORY);
        $clientPort      = $result->getInformation(Result::GENERAL_PORT_SUBCATEGORY);
        $serverChallenge = SanAndreasMultiplayerQuery::computeServerChallenge($ipAddress, $clientPort);

        if (!$packets = $this->extractPackets($responses, $serverChallenge)) {
            return $result->toArray();
        }

        // Process packets.
        $this->processPackets($result, $packets);

        return $result->toArray();
    }

    /**
     * Process server status.
     *
     * @param Buffer $buffer
     * @param Result $result
     *
     * @throws \Exception
     */
    protected function processStatus(Buffer $buffer, Result $result): void
    {
        if (!$buffer->getBuffer()) {
            return;
        }

        $result->addInformation(Result::GENERAL_ACTIVE_SUBCATEGORY, true);
        $result->addInformation(Result::GENERAL_PASSWORD_SUBCATEGORY, $buffer->readInt8());
        $result->addInformation(Result::GENERAL_ONLINE_PLAYERS_SUBCATEGORY, $buffer->readInt16());
        $result->addInformation(Result::GENERAL_SLOTS_SUBCATEGORY, $buffer->readInt16());
        $result->addInformation(Result::GENERAL_HOSTNAME_SUBCATEGORY, \utf8_encode($buffer->read($buffer->readInt32())));
        $result->addInformation(Result::GENERAL_SERVER_TYPE_SUBCATEGORY, 'd'); // Always `dedicated`.

        $result->addRule('gametype', $buffer->read($buffer->readInt32()));
        $result->addRule('language', $buffer->read($buffer->readInt32()));
    }

    /**
     * Process server online players.
     *
     * @param Buffer $buffer
     * @param Result $result
     *
     * @throws \Exception
     */
    protected function processPlayers(Buffer $buffer, Result $result): void
    {
        if (!$buffer->getBuffer()) {
            return;
        }

        if (!$buffer->readInt16()) {
            return;
        }

        while ($buffer->getLength()) {
            $buffer->readInt8(); // Skip player ID.
            $result->addPlayer(\utf8_encode($buffer->readPascalString()), $buffer->readInt32());
            $buffer->readInt32(); // Skip player ping.
        }
    }

    /**
     * Process server rules.
     *
     * @param Buffer $buffer
     * @param Result $result
     *
     * @throws \Exception
     */
    protected function processRules(Buffer $buffer, Result $result): void
    {
        if (!$buffer->getBuffer()) {
            return;
        }

        if (!$buffer->readInt16()) {
            return;
        }

        while ($buffer->getLength()) {
            $result->addRule($buffer->readPascalString(), $buffer->readPascalString());
        }

        if ($result->hasRule('mapname')) {
            $result->addInformation(Result::GENERAL_MAP_SUBCATEGORY, $result->getRule('mapname'));
        }

        if ($result->hasRule('version')) {
            $result->addInformation(Result::GENERAL_VERSION_SUBCATEGORY, $result->getRule('version'));
        }
    }

    /**
     * Extract packets from raw server response.
     *
     * @param array  $data
     * @param string $serverChallenge
     *
     * @return array
     * @throws \Exception
     */
    protected function extractPackets(array $data, string $serverChallenge): array
    {
        $packets = [];

        foreach ($data as $datum) {
            $buffer = new Buffer($datum);

            // Check the header. Should be `SAMP`.
            if (($header = $buffer->read(4)) !== 'SAMP') {
                throw new BufferException("Server header verification failed!");
            }

            // Check to make sure the server response code matches what we sent
            if ($buffer->read(\strlen($serverChallenge)) !== $serverChallenge) {
                throw new BufferException("Server challenge verification failed!");
            }

            $packets[] = $buffer->getBuffer();
        }

        // Clear memory.
        unset($buffer, $header);

        return $packets;
    }

    /**
     * Process all extracted packets.
     *
     * @param Result $result
     * @param array  $packets
     *
     * @return array
     * @throws \Exception
     */
    protected function processPackets(Result $result, array $packets): array
    {
        foreach ($packets as $packet) {
            $buffer = new Buffer($packet);

            // Get response letter.
            $responseType = $buffer->read();

            if (!\array_key_exists($responseType, $this->responses)) {
                throw new InvalidBufferContentException($responseType, \bin2hex($buffer->getData()));
            }

            $this->{$this->responses[$responseType]}($buffer, $result);
            unset($buffer, $responseType);
        }

        return $result->toArray();
    }
}