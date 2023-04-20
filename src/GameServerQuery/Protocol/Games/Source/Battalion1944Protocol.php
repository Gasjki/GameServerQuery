<?php declare(strict_types = 1);

namespace GameServerQuery\Protocol\Games\Source;

use GameServerQuery\Buffer;
use GameServerQuery\Protocol\Types\SourceProtocol;
use GameServerQuery\Result;

/**
 * Class Battalion1944Protocol
 * @package GameServerQuery\Protocol\Games\Source
 */
class Battalion1944Protocol extends SourceProtocol
{
    /**
     * Port to query port difference.
     *
     * @var int
     */
    protected int $portToQueryPortStep = 3;

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
        parent::processRules($buffer, $result);

        // Update hostname from server rules.
        if ($result->hasRule('bat_name_s')) {
            $result->addInformation(Result::GENERAL_HOSTNAME_SUBCATEGORY, $result->getRule('bat_name_s'));
        }

        // Update online players.
        if($result->hasRule('bat_player_count_s')) {
            $result->addInformation(Result::GENERAL_ONLINE_PLAYERS_SUBCATEGORY, (int) $result->getRule('bat_player_count_s'));
        }

        // Update server slots.
        if($result->hasRule('bat_max_players_i')) {
            $result->addInformation(Result::GENERAL_SLOTS_SUBCATEGORY, (int) $result->getRule('bat_max_players_i'));
        }

        // Update password from server rules.
        if ($result->hasRule('bat_has_password_s')) {
            $result->addInformation(Result::GENERAL_PASSWORD_SUBCATEGORY, "N" !== $result->getRule('bat_has_password_s'));
        }
    }
}