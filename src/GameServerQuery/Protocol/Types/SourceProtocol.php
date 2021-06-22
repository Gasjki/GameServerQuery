<?php declare(strict_types = 1);

namespace GameServerQuery\Protocol\Types;

use GameServerQuery\Buffer;
use GameServerQuery\Protocol\AbstractProtocol;
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
     * Packages to be send to socket.
     *
     * @var array
     */
    protected array $packages = [
        self::PACKET_CHALLENGE => "\xFF\xFF\xFF\xFF\x56\x00\x00\x00\x00",
        self::PACKET_DETAILS   => "\xFF\xFF\xFF\xFFTSource Engine Query\x00",
        self::PACKET_PLAYERS   => "\xFF\xFF\xFF\xFF\x55%s",
        self::PACKET_RULES     => "\xFF\xFF\xFF\xFF\x56%s",
    ];

    /**
     * Socket responses and their corresponding protocol method.
     *
     * @var array
     */
    protected array $responses = [
        "\x49" => "processSourceInformation", // I
        //        "\x6d" => "processGoldSourceInformation", // m, goldsource
        //        "\x44" => "processPlayers", // D
        //        "\x45" => "processRules", // E
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
     * @throws \Exception
     */
    public function __construct()
    {
        parent::__construct();

        if (!function_exists('bzdecompress')) {
            throw new \Exception('Bzip2 is not installed! See http://www.php.net/manual/en/book.bzip2.php for more details!');
        }
    }

    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function updatePackagesBasedOnChallengePackageResponse(Buffer $buffer): void
    {
        $buffer->skip(5);

        $challenge = $buffer->read(4);
        foreach ($this->packages as $type => $package) {
            $this->packages[$type] = sprintf($package, $challenge);
        }
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
                if ($buffer->lookAhead() === "\x6d") {
                    $this->engine = self::GOLD_SOURCE_ENGINE;
                }

                $packets[] = $buffer->getBuffer();

                continue;
            }

            $packetId           = $buffer->readInt32Signed() + 10;
            $packets[$packetId] = $buffer->getBuffer();
        }

        // Clear memory.
        unset($data, $buffer, $header, $packetId);

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
        foreach ($packets as $packetId => $packet) {
            $buffer = new Buffer(is_array($packet) ? $this->preProcessPackets($packetId, $packet) : $packet);

            // Get response letter.
            $responseType = $buffer->read();

            if (!array_key_exists($responseType, $this->responses)) {
                throw new \BadMethodCallException('Request method does not exist for current protocol!');
            }

            call_user_func_array([$this, $this->responses[$responseType]], [$buffer, $result]);
            unset($buffer, $responseType);
        }

        return $result->getResult();
    }

    /**
     * Post process special packets.
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
                if ($index === 0) {
                    $buffer->read(4);
                }

                $data[$packetNumber] = $buffer->getBuffer();

                continue;
            }

            // Number of packets in this set (byte)
            $buffer->readInt8();

            // Current packet number (byte)
            $packetNumber = $buffer->readInt8();

            if ($packetId & 0x80000000) {
                $packetLength = $buffer->readInt32Signed(); // Get the length of the packet (long).

                $buffer->readInt32Signed(); // Checksum for the decompressed packet (long), burn it - doesn't work in split responses.
                $result = bzdecompress($buffer->getBuffer()); // Try to decompress

                // Verify length.
                if (strlen($result) != $packetLength) {
                    throw new \Exception(
                        sprintf("Checksum for compressed packet failed! Length expected: %d, length returned: %d.", $packetLength, mb_strlen($result))
                    );
                }

                // We need to burn extra header (\xFF\xFF\xFF\xFF) on first loop.
                if ($index === 0) {
                    $result = substr($result, 4);
                }

                $data[$packetNumber] = $result;

                continue;
            }

            // Get the packet length (short).
            $buffer->readInt16Signed();

            // We need to burn extra header (\xFF\xFF\xFF\xFF) on first loop.
            if ($index == 0) {
                $buffer->read(4);
            }

            $result              = $buffer->getBuffer();
            $data[$packetNumber] = $result;
        }

        // Free some memory
        unset($packets, $packet);

        // Sort the packets by packet number
        ksort($data);

        // Now combine the packs into one and return.
        return implode("", $data);
    }

    /**
     * Process server information for Source Engine servers.
     *
     * @param Buffer $buffer
     * @param Result $result
     *
     * @return Result
     * @throws \Exception
     */
    protected function processSourceInformation(Buffer $buffer, Result $result): Result
    {
        $buffer->skip(); // Skip protocol

        $result->addInformation(Result::GENERAL_ACTIVE_SUBCATEGORY, true);
        $result->addInformation(Result::GENERAL_HOSTNAME_SUBCATEGORY, $buffer->readString());
        $result->addInformation(Result::GENERAL_MAP_SUBCATEGORY, $buffer->readString());

        $buffer->readString(); // Skip game_dir
        $buffer->readString(); // Skip game_descr
        $steamAppId = $buffer->readInt16(); // Skip appId

        $result->addInformation(Result::GENERAL_ONLINE_PLAYERS_SUBCATEGORY, $buffer->readInt8());
        $result->addInformation(Result::GENERAL_SLOTS_SUBCATEGORY, $buffer->readInt8());
        $result->addInformation(Result::GENERAL_BOTS_SUBCATEGORY, $buffer->readInt8());

        if ($dedicated = $buffer->read()) {
            $dedicated = strtolower($dedicated);
            $dedicated = $dedicated === 'd' ? 'Dedicated' : ($dedicated === 'l' ? 'Non-dedicated' : 'Proxy');
        }

        $result->addInformation(Result::GENERAL_DEDICATED_SUBCATEGORY, $dedicated);

        // l = Linux, w = Windows, m / o = MacOs
        if ($os = $buffer->read()) {
            $os = strtolower($os);
            $os = $os === 'l' ? 'Linux' : ($os === 'w' ? 'Windows' : 'MacOs');
        }

        $result->addInformation(Result::GENERAL_OS_SUBCATEGORY, $os);
        $result->addInformation(Result::GENERAL_PASSWORD_SUBCATEGORY, (bool) $buffer->readInt8());

        $buffer->readInt8(); // Skip secure.

        // Only for The Ship.
        if ($steamAppId === 2400) {
            $result->addRule('game_mode', strval($buffer->readInt8()));
            $result->addRule('witness_count', strval($buffer->readInt8()));
            $result->addRule('witness_time', strval($buffer->readInt8()));
        }

        $result->addInformation(Result::GENERAL_VERSION_SUBCATEGORY, $buffer->readString());

        unset($buffer); // Clear buffer from memory.

        return $result;
    }

    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function handleResponse(array $responses): array
    {
        $result = new Result();
        $result->addInformation(Result::GENERAL_APPLICATION_SUBCATEGORY, get_class($this));

        // No data to be parsed.
        if (!count($responses)) {
            return $result->getResult();
        }

        // Extract packets.
        if (!$packets = $this->extractPackets($responses)) {
            return $result->getResult();
        }

        // Process packets.
        $this->processPackets($result, $packets);

        return $result->getResult();
    }
}