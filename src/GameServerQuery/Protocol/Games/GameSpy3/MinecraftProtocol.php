<?php declare(strict_types = 1);

namespace GameServerQuery\Protocol\Games\GameSpy3;

use GameServerQuery\Buffer;
use GameServerQuery\Protocol\Types\GameSpy3Protocol;
use GameServerQuery\Result;

/**
 * Class MinecraftProtocol
 * @package GameServerQuery\Protocol\Games\GameSpy3
 */
class MinecraftProtocol extends GameSpy3Protocol
{
    /**
     * Process server information for Source Engine servers.
     *
     * @param Buffer $buffer
     * @param Result $result
     *
     * @throws \Exception
     */
    protected function processInformation(Buffer $buffer, Result $result): void
    {
        parent::processInformation($buffer, $result);

        if ($result->hasRule('hostname')) {
            $result->addInformation(Result::GENERAL_HOSTNAME_SUBCATEGORY, $result->getRule('hostname'));
        }

        if ($result->hasRule('version')) {
            $result->addInformation(Result::GENERAL_VERSION_SUBCATEGORY, $result->getRule('version'));
        }

        if ($result->hasRule('map')) {
            $result->addInformation(Result::GENERAL_MAP_SUBCATEGORY, $result->getRule('map'));
        }

        if ($result->hasRule('numplayers')) {
            $result->addInformation(Result::GENERAL_ONLINE_PLAYERS_SUBCATEGORY, (int) $result->getRule('numplayers'));
        }

        if ($result->hasRule('maxplayers')) {
            $result->addInformation(Result::GENERAL_SLOTS_SUBCATEGORY, (int) $result->getRule('maxplayers'));
        }

        if ($result->hasInformation(Result::GENERAL_HOSTNAME_SUBCATEGORY) && null !== $result->getInformation(Result::GENERAL_HOSTNAME_SUBCATEGORY)) {
            $result->addInformation(Result::GENERAL_ACTIVE_SUBCATEGORY, true);
            $result->addInformation(Result::GENERAL_SERVER_TYPE_SUBCATEGORY, 'd');
        }
    }
}
