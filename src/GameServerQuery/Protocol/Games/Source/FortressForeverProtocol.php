<?php declare(strict_types = 1);

namespace GameServerQuery\Protocol\Games\Source;

use GameServerQuery\Buffer;
use GameServerQuery\Protocol\Types\SourceProtocol;
use GameServerQuery\Result;

/**
 * Class FortressForeverProtocol
 * @package GameServerQuery\Protocol\Games\Source
 */
class FortressForeverProtocol extends SourceProtocol
{
    /**
     * @inheritDoc
     */
    protected function processRules(Buffer $buffer, Result $result): void
    {
        parent::processRules($buffer, $result);

        // Get server version from rules.
        if ($result->hasRule('sb_version')) {
            $result->addInformation(Result::GENERAL_VERSION_SUBCATEGORY, $result->getRule('sb_version'));
        }
    }
}