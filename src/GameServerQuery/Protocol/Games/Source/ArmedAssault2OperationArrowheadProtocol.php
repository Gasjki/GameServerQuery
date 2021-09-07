<?php declare(strict_types = 1);

namespace GameServerQuery\Protocol\Games\Source;

use GameServerQuery\Protocol\Types\SourceProtocol;

/**
 * Class ArmedAssault2OperationArrowheadProtocol
 * @package GameServerQuery\Protocol\Games\Source
 */
class ArmedAssault2OperationArrowheadProtocol extends SourceProtocol
{
    /**
     * Port to query port difference.
     *
     * @var int
     */
    protected int $portToQueryPortStep = 1;
}