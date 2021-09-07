<?php declare(strict_types = 1);

namespace GameServerQuery\Protocol\Games\Source;

use GameServerQuery\Protocol\Types\SourceProtocol;

/**
 * Class CallOfDutyModernWarfare3Protocol
 * @package GameServerQuery\Protocol\Games\Source
 */
class CallOfDutyModernWarfare3Protocol extends SourceProtocol
{
    /**
     * Port to query port difference.
     *
     * @var int
     */
    protected int $portToQueryPortStep = 2;
}