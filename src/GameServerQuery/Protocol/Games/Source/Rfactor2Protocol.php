<?php declare(strict_types = 1);

namespace GameServerQuery\Protocol\Games\Source;

use GameServerQuery\Protocol\Types\SourceProtocol;

/**
 * Class Rrfactor2Protocol
 * @package GameServerQuery\Protocol\Games\Source
 */
class Rrfactor2Protocol extends SourceProtocol
{
    /**
     * Port to query port difference.
     *
     * @var int
     */
    protected int $portToQueryPortStep = 2;
}