<?php declare(strict_types = 1);

namespace GameServerQuery\Protocol\Types;

use GameServerQuery\Buffer;
use GameServerQuery\Protocol\AbstractProtocol;
use GameServerQuery\Result;

/**
 * Class GameSpy3Protocol
 * @package GameServerQuery\Protocol\Types
 */
abstract class GameSpy3Protocol extends AbstractProtocol
{
    /**
     * Protocol packages.
     *
     * Packages to be send to socket.
     *
     * @var array
     */
    protected array $packages = [
        self::PACKAGE_CHALLENGE => "\xFE\xFD\x09\x10\x20\x30\x40",
        self::PACKAGE_ALL       => "\xFE\xFD\x00\x10\x20\x30\x40%s\xFF\xFF\xFF\x01",
    ];

    /**
     * This defines the split between the server info and player / team information.
     * This value can vary by game.
     *
     * @var string
     */
    protected string $packageSplitter = "/\\x00\\x00\\x01/m";

    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function updateQueryPackages(Buffer $buffer): void
    {
        $challenge       = substr(preg_replace("/[^0-9\-]/si", "", $buffer->getBuffer()), 1);
        $challengeResult = '';

        if ($challenge) {
            $challengeResult = sprintf("%c%c%c%c", ($challenge >> 24), ($challenge >> 16), ($challenge >> 8), ($challenge >> 0));
        }

        $this->applyChallenge($challengeResult);
    }

    /**
     * Clean packets.
     *
     * @param array $packets
     *
     * @return array
     * @throws \Exception
     */
    protected function cleanPackets(array $packets): array
    {
        // Get the number of packets.
        $packetCount = count($packets);

        // Compare last var of current packet with first var of next packet
        // On a partial match, remove last var from current packet,
        // variable header from next packet
        for ($i = 0, $x = $packetCount; $i < $x - 1; $i++) {
            // First packet
            $fst = substr($packets[$i], 0, -1);
            // Second packet
            $snd = $packets[$i + 1];
            // Get last variable from first packet
            $fstvar = substr($fst, strrpos($fst, "\x00") + 1);
            // Get first variable from last packet
            $snd    = substr($snd, strpos($snd, "\x00") + 2);
            $sndvar = substr($snd, 0, strpos($snd, "\x00"));
            // Check if fstvar is a substring of sndvar
            // If so, remove it from the first string
            if (!empty($fstvar) && strpos($sndvar, $fstvar) !== false) {
                $packets[$i] = preg_replace("#(\\x00[^\\x00]+\\x00)$#", "\x00", $packets[$i]);
            }
        }

        // Now let's loop the return and remove any dupe prefixes
        for ($x = 1; $x < $packetCount; $x++) {
            $buffer = new Buffer($packets[$x], Buffer::NUMBER_TYPE_BIG_ENDIAN);

            $prefix = $buffer->readString();

            // Check to see if the return before has the same prefix present
            if ($prefix != null && strstr($packets[($x - 1)], $prefix)) {
                // Update the return by removing the prefix plus 2 chars
                $packets[$x] = substr(str_replace($prefix, '', $packets[$x]), 2);
            }

            unset($buffer);
        }

        unset($x, $i, $snd, $sndvar, $fst, $fstvar);

        // Return cleaned packets
        return $packets;
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
            $buffer = new Buffer($datum, Buffer::NUMBER_TYPE_BIG_ENDIAN);
            $buffer->readInt8(); // Packet type = 0.
            $buffer->readInt32(); // Session Id.
            $buffer->skip(9); // We need to burn the splitnum\0 because it is not used.
            $id = $buffer->readInt8(); // Get the ID.
            $buffer->skip(); // Burn next byte not sure what it is used for
            $packets[$id] = $buffer->getBuffer(); // Add this packet to the processed

            unset($buffer, $id);
        }

        ksort($packets); // Sort packets, reset index.

        // Offload cleaning up the packets if they happen to be split.
        return $this->cleanPackets(array_values($packets));
    }

    /**
     * Process server information.
     *
     * @param Buffer $buffer
     * @param Result $result
     *
     * @throws \Exception
     */
    protected function processInformation(Buffer $buffer, Result $result): void
    {
        $data = [];
        while ($buffer->getLength()) {
            $key = $buffer->readString();
            if (!mb_strlen($key)) {
                break;
            }

            $data[$key] = utf8_encode($buffer->readString());
        }

        $result->addInformation(Result::GENERAL_ACTIVE_SUBCATEGORY, true);
        $result->addInformation(Result::GENERAL_HOSTNAME_SUBCATEGORY, $data['hostname'] ?? null);
        $result->addInformation(Result::GENERAL_MAP_SUBCATEGORY, $data['map'] ?? null);
        $result->addInformation(Result::GENERAL_ONLINE_PLAYERS_SUBCATEGORY, $data['numplayers'] ?? 0);
        $result->addInformation(Result::GENERAL_SLOTS_SUBCATEGORY, $data['maxplayers'] ?? 0);
        $result->addInformation(Result::GENERAL_BOTS_SUBCATEGORY, 0);
        $result->addInformation(Result::GENERAL_DEDICATED_SUBCATEGORY, null);
        $result->addInformation(Result::GENERAL_OS_SUBCATEGORY, null);
        $result->addInformation(Result::GENERAL_PASSWORD_SUBCATEGORY, false);
        $result->addInformation(Result::GENERAL_VERSION_SUBCATEGORY, $data['version'] ?? null);

        if (isset($data['plugins']) && mb_strlen($data['plugins'])) {
            $result->addRule('plugins', $data['plugins']);
        }

        unset($buffer); // Clear buffer from memory.
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
        $data  = explode("\x00\x00", $buffer->getBuffer());
        $count = count($data);

        for ($x = 0; $x < $count - 1; $x++) {
            $item = $data[$x];

            if ($item === '' || $item === "\x00") {
                continue;
            }

            $temporaryBuffer = new Buffer($item, Buffer::NUMBER_TYPE_BIG_ENDIAN);

            while ($temporaryBuffer->getLength()) {
                $name = trim($temporaryBuffer->readString());

                if (!$name || $name === 'player_') {
                    continue;
                }

                $result->addPlayer($name);
            }

            unset($temporaryBuffer);
        }

        unset($data, $count, $item);
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
        $split  = preg_split($this->packageSplitter, implode('', $packets));
        $buffer = new Buffer($split[0], Buffer::NUMBER_TYPE_BIG_ENDIAN);

        $this->processInformation($buffer, $result);

        if (array_key_exists(1, $split)) {
            $buffer = new Buffer($split[1], Buffer::NUMBER_TYPE_BIG_ENDIAN);
            $this->processPlayers($buffer, $result);
        }

        return $result->getResult();
    }

    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function handleResponse(array $responses): array
    {
        $result = new Result();

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