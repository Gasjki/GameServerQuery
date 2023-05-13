<?php declare(strict_types = 1);

namespace GameServerQuery\Protocol\Types;

use GameServerQuery\Buffer;
use GameServerQuery\Protocol\AbstractProtocol;
use GameServerQuery\Query\Types\GameSpy3Query;
use GameServerQuery\Result;

/**
 * Class GameSpy3Protocol
 * @package GameServerQuery\Protocol\Types
 */
class GameSpy3Protocol extends AbstractProtocol
{
    protected const PACKET_SPLITTER = "/\\x00\\x00\\x01/m";

    /**
     * Protocol packages.
     *
     * Packages to be sent to socket.
     *
     * @var array
     */
    protected array $packages = [
        self::PACKAGE_ALL       => "\xFE\xFD\x00\x10\x20\x30\x40%s\xFF\xFF\xFF\x01",
        self::PACKAGE_CHALLENGE => "\xFE\xFD\x09\x10\x20\x30\x40",
    ];

    /**
     * Has blocking mode?
     *
     * @var bool
     */
    protected bool $blockingMode = true;

    /**
     * @inheritDoc
     */
    public function getQueryClass(): string
    {
        return GameSpy3Query::class;
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

        if (!$packets = $this->extractPackets($responses)) {
            return $result->toArray();
        }

        if (!$packets = $this->preProcessPackets($packets)) {
            return $result->toArray();
        }

        // Pre-process packets if they are split.
        return $this->processPackets($result, $packets);
    }

    /**
     * Extract packets from raw server response.
     *
     * @param array $data
     *
     * @return array
     */
    protected function extractPackets(array $data): array
    {
        $packets = [];

        foreach ($data as $datum) {
            $buffer = new Buffer($datum, Buffer::NUMBER_TYPE_BIG_ENDIAN);
            $buffer->readInt8(); // Package type = 0.
            $buffer->readInt32(); // Session ID.
            $buffer->skip(9); // Burn 'splitnum\0';
            $id = $buffer->readInt8();
            $buffer->skip();
            $packets[$id] = $buffer->getBuffer();
        }

        return $packets;
    }

    /**
     * Pre-process split packets.
     *
     * @param array $packets
     *
     * @return array
     */
    protected function preProcessPackets(array $packets): array
    {
        $nbOfPackets = \count($packets);

        for ($i = 0, $x = $nbOfPackets; $i < $x - 1; $i++) {
            $first  = \substr($packets[$i], 0, -1);
            $second = $packets[$i + 1];

            $firstVar  = \substr($first, \strrpos($first, "\x00") + 1);
            $second    = \substr($second, \strpos($second, "\x00") + 2);
            $secondVar = \substr($second, 0, \strpos($second, "\x00"));

            if (!empty($firstVar) && \str_contains($secondVar, $firstVar)) {
                $packets[$i] = \preg_replace("#(\\x00[^\\x00]+\\x00)$#", "\x00", $packets[$i]);
            }
        }

        for ($x = 1; $x < $nbOfPackets; $x++) {
            $buffer = new Buffer($packets[$x], Buffer::NUMBER_TYPE_BIG_ENDIAN);
            $prefix = $buffer->readString();

            if (\str_contains($packets[($x - 1)], $prefix)) {
                $packets[$x] = \substr(\str_replace($prefix, '', $packets[$x]), 2);
            }

            unset($buffer);
        }

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
        $data = \preg_split(self::PACKET_SPLITTER, \implode('', $packets));
        $this->processInformation(new Buffer($data[0], Buffer::NUMBER_TYPE_BIG_ENDIAN), $result);

        if (\array_key_exists(1, $data)) {
            $this->processPlayers(new Buffer($data[1], Buffer::NUMBER_TYPE_BIG_ENDIAN), $result);
        }

        return $result->toArray();
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
        while ($buffer->getLength()) {
            $key = $buffer->readString();
            if ($key === '') {
                break; // We iterate until we hit an empty key.
            }

            $result->addRule($key, \utf8_encode($buffer->readString()));
        }
    }

    /**
     * Process server players.
     *
     * @param Buffer $buffer
     * @param Result $result
     *
     * @throws \Exception
     */
    protected function processPlayers(Buffer $buffer, Result $result): void
    {
        $data      = \explode("\x00\x00", $buffer->getBuffer());
        $nbOfData  = \count($data);
        $itemGroup = '';

        for ($x = 0; $x < $nbOfData - 1; $x++) {
            $item = $data[$x];

            if ($item === '' || $item === "\x00") {
                continue;
            }

            if (\str_ends_with($item, '_')) {
                $itemGroup = 'players';

                continue;
            }

            if (\str_ends_with($item, '_t')) {
                $itemGroup = 'teams';

                continue;
            }

            $temporaryBuffer = new Buffer($item, Buffer::NUMBER_TYPE_BIG_ENDIAN);

            while ($temporaryBuffer->getLength()) {
                if (($value = $temporaryBuffer->readString()) === '') {
                    break;
                }

                if ($itemGroup === 'players') {
                    $result->addPlayer(\utf8_encode(\trim($value)));
                }
            }

            unset($temporaryBuffer);
        }

        unset($data, $nbOfData, $itemGroup, $item);
    }
}