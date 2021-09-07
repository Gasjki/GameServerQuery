<?php declare(strict_types = 1);

namespace GameServerQuery\Protocol\Games\Source;

use GameServerQuery\Buffer;
use GameServerQuery\Protocol\Types\SourceProtocol;
use GameServerQuery\Result;

/**
 * Class UnturnedProtocol
 * @package GameServerQuery\Protocol\Games\Source
 */
class UnturnedProtocol extends SourceProtocol
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
    protected function processRules(Buffer $buffer, Result $result): void
    {
        parent::processRules($buffer, $result);

        if ($result->hasRule('unturned')) {
            $result->addInformation(Result::GENERAL_VERSION_SUBCATEGORY, $result->getRule('unturned'));
        }
    }
}