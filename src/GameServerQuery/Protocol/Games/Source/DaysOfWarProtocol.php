<?php declare(strict_types = 1);

namespace GameServerQuery\Protocol\Games\Source;

use GameServerQuery\Buffer;
use GameServerQuery\Protocol\Types\SourceProtocol;
use GameServerQuery\Result;

/**
 * Class DaysOfWarProtocol
 * @package GameServerQuery\Protocol\Games\Source
 */
class DaysOfWarProtocol extends SourceProtocol
{
    /**
     * Port to query port difference.
     *
     * @var int
     */
    protected int $portToQueryPortStep = 19238;

    /**
     * @inheritDoc
     */
    protected function processRules(Buffer $buffer, Result $result): void
    {
        parent::processRules($buffer, $result);

        // Get server version from rules.
        if ($result->hasRule('V_s')) {
            $result->addInformation(Result::GENERAL_VERSION_SUBCATEGORY, $result->getRule('V_s'));
        }
    }
}