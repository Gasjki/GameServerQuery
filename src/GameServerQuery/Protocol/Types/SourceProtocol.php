<?php declare(strict_types = 1);

namespace GameServerQuery\Protocol\Types;

use GameServerQuery\Buffer;
use GameServerQuery\Exception\Buffer\InvalidBufferContentException;
use GameServerQuery\Protocol\AbstractProtocol;
use GameServerQuery\Protocol\Games\Source\TheShipProtocol;
use GameServerQuery\Query\Types\SourceQuery;
use GameServerQuery\Result;

/**
 * Class SourceProtocol
 * @package GameServerQuery\Protocol\Types
 */
abstract class SourceProtocol extends AbstractProtocol
{
    protected const SOURCE_ENGINE      = 0;
    protected const GOLD_SOURCE_ENGINE = 1;

    /**
     * Protocol packages.
     *
     * Packages to be sent to socket.
     *
     * @var array
     */
    protected array $packages = [
        self::PACKAGE_INFO      => "\xFF\xFF\xFF\xFFTSource Engine Query\0", // A2S_INFO
        self::PACKAGE_PLAYERS   => "\xFF\xFF\xFF\xFF\x55", // A2S_PLAYER
        self::PACKAGE_RULES     => "\xFF\xFF\xFF\xFF\x56", // A2S_RULE
        self::PACKAGE_CHALLENGE => "\xFF\xFF\xFF\xFF\x57", // A2S_RULE
    ];

    /**
     * Socket responses and their corresponding protocol method.
     *
     * @var array
     */
    protected array $responses = [
        "\x49" => "processSourceInformation", // I
        "\x6d" => "processGoldSourceInformation", // m, goldsource
        "\x44" => "processPlayers", // D
        "\x45" => "processRules", // E
    ];

    /**
     * Current Valve Engine.
     *
     * @var int
     */
    protected int $engine = self::SOURCE_ENGINE;

    /**
     * SourceProtocol constructor.
     *
     * @throws \RuntimeException
     */
    public function __construct()
    {
        parent::__construct();

        if (!\function_exists('bzdecompress')) {
            throw new \RuntimeException('Bzip2 is not installed! See https://www.php.net/manual/en/book.bzip2.php for more details!');
        }
    }

    /**
     * @inheritDoc
     */
    public function getQueryClass(): string
    {
        return SourceQuery::class;
    }

    /**
     * Extract packets from raw server response.
     *
     * @param array $data
     *
     * @return array
     * @throws \Exception
     */
    protected function extractPackets(array $data): array
    {
        $packets = [];

        foreach ($data as $datum) {
            $buffer = new Buffer($datum);
            $header = $buffer->readInt32Signed();

            // Check if we have a single packet.
            if (-1 === $header) {
                if ($buffer->lookAhead() === SourceQuery::S2A_INFO_OLD) {
                    $this->engine = self::GOLD_SOURCE_ENGINE;
                }

                $packets[] = $buffer->getBuffer();

                continue;
            }

            $packetId             = $buffer->readInt32Signed() + 10;
            $packets[$packetId][] = $buffer->getBuffer();

            // Clear memory.
            unset($buffer, $header, $packetId);
        }

        return $packets;
    }

    /**
     * Pre-process special packets.
     *
     * @param int   $packetId
     * @param array $packets
     *
     * @return string
     * @throws \Exception
     */
    protected function preProcessPackets(int $packetId, array $packets): string
    {
        // Track them so we can order them.
        $data = [];
        foreach ($packets as $index => $pack) {
            $buffer = new Buffer($pack);

            // Gold Source engine is a little more special.
            if ($this->engine === self::GOLD_SOURCE_ENGINE) {
                $packetNumber = $buffer->readInt8();

                // We need to burn extra header (\xFF\xFF\xFF\xFF) on first loop.
                if (0 === $index) {
                    $buffer->read(4);
                }

                $data[$packetNumber] = $buffer->getBuffer();

                continue;
            }

            $buffer->readInt8(); // Number of packets in this set (byte)
            $packetNumber = $buffer->readInt8(); // Current packet number (byte)

            if ($packetId & 0x80000000) {
                $packetLength = $buffer->readInt32Signed(); // Get the length of the packet (long).
                $buffer->readInt32Signed(); // Checksum for the decompressed packet (long), burn it - doesn't work in split responses.
                $result = \bzdecompress($buffer->getBuffer()); // Try to decompress

                // Verify length.
                if (\strlen($result) !== $packetLength) {
                    throw new \RuntimeException(
                        sprintf("Checksum for compressed packet failed! Length expected: %d, length returned: %d.", $packetLength, \mb_strlen($result))
                    );
                }

                // We need to burn extra header (\xFF\xFF\xFF\xFF) on first loop.
                if ($index === 0) {
                    $result = \substr($result, 4);
                }

                $data[$packetNumber] = $result;

                continue;
            }

            // Get the packet length (short).
            $buffer->readInt16Signed();

            // We need to burn extra header (\xFF\xFF\xFF\xFF) on first loop.
            if ($index === 0) {
                $buffer->skip(4);
            }

            $result              = $buffer->getBuffer();
            $data[$packetNumber] = $result;
        }

        // Sort the packets by packet number
        \ksort($data);

        // Now combine the packs into one and return.
        return \implode('', $data);
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
        foreach ($packets as $packetId => $packet) {
            $buffer = new Buffer(\is_array($packet) ? $this->preProcessPackets($packetId, $packet) : $packet);

            // Get response letter.
            $responseType = $buffer->read();

            if (!\array_key_exists($responseType, $this->responses)) {
                throw new InvalidBufferContentException($responseType, $packets);
            }

            $this->{$this->responses[$responseType]}($buffer, $result);
            unset($buffer, $responseType);
        }

        return $result->toArray();
    }

    /**
     * Process server information for Source Engine servers.
     *
     * @param Buffer $buffer
     * @param Result $result
     *
     * @throws \Exception
     */
    protected function processSourceInformation(Buffer $buffer, Result $result): void
    {
        $buffer->skip(); // Skip protocol

        $result->addInformation(Result::GENERAL_ACTIVE_SUBCATEGORY, true);
        $result->addInformation(Result::GENERAL_HOSTNAME_SUBCATEGORY, $buffer->readString());
        $result->addInformation(Result::GENERAL_MAP_SUBCATEGORY, $buffer->readString());

        $result->addRule('game_dir', $buffer->readString());
        $result->addRule('game_descr', $buffer->readString());
        $result->addRule('steam_appid', $buffer->readInt16());

        $result->addInformation(Result::GENERAL_ONLINE_PLAYERS_SUBCATEGORY, $buffer->readInt8());
        $result->addInformation(Result::GENERAL_SLOTS_SUBCATEGORY, $buffer->readInt8());
        $result->addInformation(Result::GENERAL_BOTS_SUBCATEGORY, $buffer->readInt8());

        if ($dedicated = $buffer->read()) {
            $dedicated = \strtolower($dedicated);
        }

        $result->addInformation(Result::GENERAL_SERVER_TYPE_SUBCATEGORY, $dedicated);

        // l = Linux, w = Windows, m / o = MacOs
        if ($os = $buffer->read()) {
            $os = \strtolower($os);
        }

        $result->addInformation(Result::GENERAL_OS_SUBCATEGORY, $os);
        $result->addInformation(Result::GENERAL_PASSWORD_SUBCATEGORY, (bool) $buffer->readInt8());

        $result->addRule('vac_secured', (bool) $buffer->readInt8());

        // Only for The Ship.
        if ((int) $result->getRule('steam_appid') === TheShipProtocol::APP_ID) {
            $result->addRule('game_mode', $buffer->readInt8());
            $result->addRule('witness_count', $buffer->readInt8());
            $result->addRule('witness_time', $buffer->readInt8());
        }

        $result->addInformation(Result::GENERAL_VERSION_SUBCATEGORY, $buffer->readString());

        $EDFCheck = $buffer->lookAhead(); // Extra Data Flag.

        if (!empty($EDFCheck)) {
            $edf = $buffer->readInt8();

            if ($edf & 0x80) {
                $result->addRule('port', $buffer->readInt16Signed());
            }

            if ($edf & 0x10) {
                $result->addRule('steam_id', $buffer->readInt64());
            }

            if ($edf & 0x40) {
                $result->addRule('sourcetv_port', $buffer->readInt16Signed());
                $result->addRule('sourcetv_name', $buffer->readString());
            }

            if ($edf & 0x20) {
                $result->addRule('keywords', $buffer->readString());
            }

            if ($edf & 0x01) {
                $result->addRule('game_id', $buffer->readInt8());
            }
        }

        unset($EDFCheck);
    }

    /**
     * Process server information for Gold Source Engine servers.
     *
     * @param Buffer $buffer
     * @param Result $result
     *
     * @throws \Exception
     */
    protected function processGoldSourceInformation(Buffer $buffer, Result $result): void
    {
        $buffer->readString(); // Skip server address

        // General section -->
        $result->addInformation(Result::GENERAL_ACTIVE_SUBCATEGORY, true);
        $result->addInformation(Result::GENERAL_HOSTNAME_SUBCATEGORY, $buffer->readString());
        $result->addInformation(Result::GENERAL_MAP_SUBCATEGORY, $buffer->readString());

        $result->addRule('game_dir', $buffer->readString());
        $result->addRule('game_descr', $buffer->readString());

        $result->addInformation(Result::GENERAL_ONLINE_PLAYERS_SUBCATEGORY, $buffer->readInt8());
        $result->addInformation(Result::GENERAL_SLOTS_SUBCATEGORY, $buffer->readInt8());
        $result->addInformation(Result::GENERAL_VERSION_SUBCATEGORY, $buffer->readInt8());

        if ($dedicated = $buffer->read()) {
            $dedicated = \strtolower($dedicated);
        }

        $result->addInformation(Result::GENERAL_SERVER_TYPE_SUBCATEGORY, $dedicated);

        // l = Linux, w = Windows, m / o = MacOs
        if ($os = $buffer->read()) {
            $os = \strtolower($os);
        }

        $result->addInformation(Result::GENERAL_OS_SUBCATEGORY, $os);
        $result->addInformation(Result::GENERAL_PASSWORD_SUBCATEGORY, (bool) $buffer->readInt8());

        // Mode
        $mode = $buffer->readInt8();
        if ($mode === 1) {
            $result->addRule('mode_url_info', $buffer->readString());
            $result->addRule('mode_url_download', $buffer->readString());
            $buffer->skip(); // Skip - NULL byte (as per official documentation)
            $result->addRule('mode_version', $buffer->readInt32Signed());
            $result->addRule('mode_size', $buffer->readInt32Signed());
            $result->addRule('mode_type', $buffer->readInt8());
            $result->addRule('mode_dll', $buffer->readInt8());
        }

        $result->addRule('vac_secured', (bool) $buffer->readInt8());
        $result->addInformation(Result::GENERAL_BOTS_SUBCATEGORY, $buffer->readInt8());
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

        if (!$buffer->readInt8()) {
            return;
        }

        while ($buffer->getLength()) {
            $buffer->readInt8(); // Skip player ID.

            $result->addPlayer($buffer->readString(), $buffer->readInt32Signed(), $buffer->readFloat32());
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

        if (!$buffer->readInt16Signed()) {
            return;
        }

        while ($buffer->getLength()) {
            $result->addRule($buffer->readString(), $buffer->readString());
        }

        if ($result->hasRule('sv_version')) {
            $result->addInformation(Result::GENERAL_VERSION_SUBCATEGORY, $result->getRule('sv_version'));
        }
    }

    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function handleResponse(Result $result, array $responses): array
    {
        $responses = \array_filter($responses);

        // No data to be parsed.
        if (!\count($responses)) {
            return $result->toArray();
        }

        // Extract packets.
        if (!$packets = $this->extractPackets($responses)) {
            return $result->toArray();
        }

        // Process packets.
        return $this->processPackets($result, $packets);
    }
}
