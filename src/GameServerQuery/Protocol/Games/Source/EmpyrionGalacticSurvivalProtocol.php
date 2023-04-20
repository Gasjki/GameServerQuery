<?php declare(strict_types = 1);

namespace GameServerQuery\Protocol\Games\Source;

use GameServerQuery\Buffer;
use GameServerQuery\Protocol\Types\SourceProtocol;
use GameServerQuery\Result;

/**
 * Class EmpyrionGalacticSurvivalProtocol
 * @package GameServerQuery\Protocol\Games\Source
 */
class EmpyrionGalacticSurvivalProtocol extends SourceProtocol
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

        // Get server version from rules.
        if ($result->hasRule('Version')) {
            $result->addInformation(Result::GENERAL_VERSION_SUBCATEGORY, $result->getRule('Version'));
        }
    }
}