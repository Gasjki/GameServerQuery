<?php declare(strict_types = 1);

namespace GameServerQuery\Protocol\Games\Source;

use GameServerQuery\Protocol\Types\SourceProtocol;

/**
 * Class ConanExilesProtocol
 * @package GameServerQuery\Protocol\Games\Source
 */
class ConanExilesProtocol extends SourceProtocol
{
    /**
     * Port to query port difference.
     *
     * @var int
     */
    protected int $portToQueryPortStep = 19238;

    /**
     * Is query port required for this protocol?
     *
     * @var bool
     */
    protected bool $queryPortMandatory = true;
}