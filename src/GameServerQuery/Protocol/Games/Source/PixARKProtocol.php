<?php declare(strict_types = 1);

namespace GameServerQuery\Protocol\Games\Source;

use GameServerQuery\Protocol\Types\SourceProtocol;

/**
 * Class PixARKProtocol
 * @package GameServerQuery\Protocol\Games\Source
 */
class PixARKProtocol extends SourceProtocol
{
    /**
     * Port to query port difference.
     * @see https://pixark.fandom.com/wiki/Server_configuration
     *
     * @var int
     */
    protected int $portToQueryPortStep = 1;
}