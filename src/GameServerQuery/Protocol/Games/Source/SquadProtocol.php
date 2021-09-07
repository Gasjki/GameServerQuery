<?php declare(strict_types = 1);

namespace GameServerQuery\Protocol\Games\Source;

use GameServerQuery\Buffer;
use GameServerQuery\Protocol\Types\SourceProtocol;
use GameServerQuery\Result;

/**
 * Class SquadProtocol
 * @package GameServerQuery\Protocol\Games\Source
 */
class SquadProtocol extends SourceProtocol
{
    /**
     * Port to query port difference.
     *
     * @var int
     */
    protected int $portToQueryPortStep = 19378;

    /**
     * @inheritDoc
     */
    protected function processRules(Buffer $buffer, Result $result): void
    {
        parent::processRules($buffer, $result);

        if ($result->hasRule('GameVersion_s')) {
            $result->addInformation(Result::GENERAL_VERSION_SUBCATEGORY, $result->getRule('GameVersion_s'));
        }
    }
}