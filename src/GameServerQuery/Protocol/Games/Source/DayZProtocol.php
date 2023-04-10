<?php declare(strict_types = 1);

namespace GameServerQuery\Protocol\Games\Source;

use GameServerQuery\Buffer;
use GameServerQuery\Protocol\Types\SourceProtocol;
use GameServerQuery\Result;

/**
 * Class DayZProtocol
 * @package GameServerQuery\Protocol\Games\Source
 */
class DayZProtocol extends SourceProtocol
{
    /**
     * @inheritDoc
     */
    public function calculateQueryPort(int $port): int
    {
        return (int) (27016 + ($port - 2302) / 100);
    }

    /**
     * @inheritDoc
     */
    protected function processSourceInformation(Buffer $buffer, Result $result): void
    {
        $temporaryBuffer = clone $buffer;
        parent::processSourceInformation($temporaryBuffer, $result);

        // Get server version.
        $buffer->skip(); // Skip protocol
        $buffer->readString(); // Skip hostname
        $buffer->readString(); // Skip map name
        $buffer->readString(); // Skip game_dir
        $buffer->readString(); // Skip game_descr
        $buffer->readInt16(); // Skip appId
        $buffer->skip(7); // Skip empty bytes

        $result->addInformation(Result::GENERAL_VERSION_SUBCATEGORY, $buffer->readString());

        unset($temporaryBuffer);
    }

    /**
     * @inheritDoc
     */
    protected function processPlayers(Buffer $buffer, Result $result): void
    {
        // Players list is not supported by this game.
        // To fast things up, let's skip the entire process.
    }

    /**
     * @inheritDoc
     */
    protected function processRules(Buffer $buffer, Result $result): void
    {
        // Server cvars list is a little bit too strange to be parsed.
    }
}