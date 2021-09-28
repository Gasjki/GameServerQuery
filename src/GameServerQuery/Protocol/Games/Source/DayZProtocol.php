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
     * Possible keys for parsing server rules.
     *
     * @var string[]
     */
    private array $possibleKeys = [
        "\x01\v",
        "\x02\v",
        "\x03\v",
        "\x04\v",
        "\x05\v",
        "\x06\v",
        "\x07\v",
        "\x08\v",
        "\x09\v",
        "\x10\v",
        "\x11\v",
        "\x12\v",
        "\x13\v",
        "\x14\v",
        "\x15\v",
        "\x16\v",
        "\x17\v",
        "\x18\v",
        "\x19\v",
        "\x20\v",
        "\t\v",
        "\n\v",
        "\v\v",
    ];

    /**
     * @inheritDoc
     */
    public function calculateQueryPort(int $port): int
    {
        /*
         * Port layout:
         * 2302 - 27016
         * 2402 - 27017
         * 2502 - 27018
         * 2602 - 27019
         * 2702 - 27020
         * ...
         */

        return 27016 + (($port - 2302) / 100);
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

        unset($buffer, $tempBuffer);
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
        parent::processRules($buffer, $result);

        if (!$result[Result::RULES_CATEGORY]) {
            return;
        }

        // Remove strange results.
        for ($i = 0; $i < count($this->possibleKeys); $i++) {
            if (array_key_exists($this->possibleKeys[$i], $result['rules'])) {
                unset($result['rules'][$this->possibleKeys[$i]]);
            }
        }
    }
}