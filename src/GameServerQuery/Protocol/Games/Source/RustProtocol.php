<?php declare(strict_types = 1);

namespace GameServerQuery\Protocol\Games\Source;

use GameServerQuery\Buffer;
use GameServerQuery\Protocol\Types\SourceProtocol;
use GameServerQuery\Result;

/**
 * Class RustProtocol
 * @package GameServerQuery\Protocol\Games\Source
 */
class RustProtocol extends SourceProtocol
{
    /**
     * Port to query port difference.
     *
     * @var int
     */
    protected int $portToQueryPortStep = 1;

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

        if (!$result->hasRule('keywords')) {
            return;
        }

        $keywords = $result->getRule('keywords');

        // Get max players from mp of keywords and num players from cp keyword.
        preg_match_all('/(mp|cp)(\d+)/', $keywords, $matches);

        $result->addInformation(Result::GENERAL_SLOTS_SUBCATEGORY, (int) $matches[2][0]);
        $result->addInformation(Result::GENERAL_ONLINE_PLAYERS_SUBCATEGORY, (int) $matches[2][1]);
    }
}