<?php declare(strict_types = 1);

namespace GameServerQuery\Protocol\Games\Source;

use GameServerQuery\Protocol\Types\SourceProtocol;

/**
 * Class RisingStorm2Protocol
 * @package GameServerQuery\Protocol\Games\Source
 */
class RisingStorm2Protocol extends SourceProtocol
{
    /**
     * @inheritDoc
     * @Note Query port is always 27015.
     */
    public function calculateQueryPort(int $port): int
    {
        return 27015;
    }
}