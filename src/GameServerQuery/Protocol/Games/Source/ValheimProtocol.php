<?php declare(strict_types = 1);

namespace GameServerQuery\Protocol\Games\Source;

use GameServerQuery\Buffer;
use GameServerQuery\Protocol\Types\SourceProtocol;
use GameServerQuery\Result;

/**
 * Class ValheimProtocol
 * @package GameServerQuery\Protocol\Games\Source
 */
class ValheimProtocol extends SourceProtocol
{
    /**
     * Port to query port difference.
     *
     * @var int
     */
    protected int $portToQueryPortStep = 1;

    /**
     * @inheritDoc
     */
    protected function processPlayers(Buffer $buffer, Result $result): void
    {
        // Players list is not supported by this game.
        // To fast things up, let's skip the entire process.
    }
}