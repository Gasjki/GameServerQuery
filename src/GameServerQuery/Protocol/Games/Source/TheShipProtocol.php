<?php declare(strict_types = 1);

namespace GameServerQuery\Protocol\Games\Source;

use GameServerQuery\Buffer;
use GameServerQuery\Protocol\Types\SourceProtocol;
use GameServerQuery\Result;

/**
 * Class TheShipProtocol
 * @package GameServerQuery\Protocol\Games\Source
 */
class TheShipProtocol extends SourceProtocol
{
    public const APP_ID = 2400;

    /**
     * @inheritDoc
     */
    protected function processPlayers(Buffer $buffer, Result $result): void
    {
        $nbOfPlayers = $buffer->readInt8();
        $result->addInformation(Result::GENERAL_ONLINE_PLAYERS_SUBCATEGORY, $nbOfPlayers);

        if ($nbOfPlayers === 0) {
            return;
        }

        for ($player = 0; $player < $nbOfPlayers; $player++) {
            $buffer->readInt8(); // Skip player ID.
            $result->addPlayer($buffer->readString(), $buffer->readInt32Signed(), $buffer->readFloat32());
        }

        unset($nbOfPlayers);
    }
}