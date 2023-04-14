<?php declare(strict_types = 1);

namespace GameServerQuery\Protocol\Games\Source;

use GameServerQuery\Protocol\Types\SourceProtocol;

/**
 * Class HellLetLooseProtocol
 * @package GameServerQuery\Protocol\Games\Source
 */
class HellLetLooseProtocol extends SourceProtocol
{
    /**
     * Port to query port difference.
     *
     * @var int
     */
    protected int $portToQueryPortStep = 15;
}