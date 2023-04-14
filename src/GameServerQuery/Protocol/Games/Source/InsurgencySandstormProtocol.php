<?php declare(strict_types = 1);

namespace GameServerQuery\Protocol\Games\Source;

use GameServerQuery\Protocol\Types\SourceProtocol;

/**
 * Class InsurgencySandstormProtocol
 * @package GameServerQuery\Protocol\Games\Source
 */
class InsurgencySandstormProtocol extends SourceProtocol
{
    /**
     * Port to query port difference.
     *
     * @var int
     */
    protected int $portToQueryPortStep = 29;
}
