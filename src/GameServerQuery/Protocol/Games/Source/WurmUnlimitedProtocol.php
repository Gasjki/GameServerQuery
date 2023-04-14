<?php declare(strict_types = 1);

namespace GameServerQuery\Protocol\Games\Source;

use GameServerQuery\Buffer;
use GameServerQuery\Protocol\Types\SourceProtocol;
use GameServerQuery\Result;

/**
 * Class WurnUnlimitedProtocol
 * @package GameServerQuery\Protocol\Games\Source
 */
class WurnUnlimitedProtocol extends SourceProtocol
{
    /**
     * Port to query port difference.
     *
     * @var int
     */
    protected int $portToQueryPortStep = 23292;

    /**
     * @inheritDoc
     */
    protected function processPlayers(Buffer $buffer, Result $result): void
    {
        // Players list is not supported by this game.
        // To fast things up, let's skip the entire process.
    }
}