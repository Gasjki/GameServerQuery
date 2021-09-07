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
     */
    public function calculateQueryPort(int $port): int
    {
        // Query port is always 27015.
        return 27015;
    }
}