<?php declare(strict_types = 1);

namespace GameServerQuery\Protocol\Games\Source;

use GameServerQuery\Protocol\Types\SourceProtocol;

/**
 * Class ARKSurvivalEvolvedProtocol
 * @package GameServerQuery\Protocol\Games\Source
 */
class ARKSurvivalEvolvedProtocol extends SourceProtocol
{
    /**
     * Port to query port difference.
     *
     * @var int
     */
    protected int $portToQueryPortStep = 19238;
}