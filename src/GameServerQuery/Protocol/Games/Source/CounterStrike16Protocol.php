<?php declare(strict_types = 1);

namespace GameServerQuery\Protocol\Games\Source;

use GameServerQuery\Protocol\Types\SourceProtocol;

/**
 * Class CounterStrike16Protocol
 * @package GameServerQuery\Protocol\Games\Source
 */
class CounterStrike16Protocol extends SourceProtocol
{
    /**
     * @inheritDoc
     */
    protected function preProcessPackets(int $packetId, array $packets): string
    {
        $this->engine = self::GOLD_SOURCE_ENGINE;
        $packets      = parent::preProcessPackets($packetId, $packets);
        $this->engine = self::SOURCE_ENGINE;

        return $packets;
    }
}