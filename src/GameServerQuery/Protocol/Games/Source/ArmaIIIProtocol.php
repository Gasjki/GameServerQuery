<?php declare(strict_types = 1);

namespace GameServerQuery\Protocol\Games\Source;

use GameServerQuery\Buffer;
use GameServerQuery\Protocol\Types\SourceProtocol;
use GameServerQuery\Result;

/**
 * Class ArmaIIIProtocol
 * @package GameServerQuery\Protocol\Games\Source
 */
class ArmaIIIProtocol extends SourceProtocol
{
    /**
     * Port to query port difference.
     *
     * @var int
     */
    protected int $portToQueryPortStep = 1;

    /**
     * Defines the names for the specific game DLCs
     *
     * @var array
     */
    private array $dlcNames = [
        '3a3f5ff9' => 'Karts',
        '2d4eada7' => 'Marksmen',
        '873ada67' => 'Helicopters',
        'b6d4451'  => 'Zeus',
        'e5ad6f6c' => 'Apex',
        '1f2e3b6f' => 'Jets',
        '128b066b' => 'Laws of War',
        '1954e272' => 'Malden',
        '70a109b7' => 'Tac-Ops',
        'dfc0778f' => 'Tanks',
        '2dd9b92b' => 'Contact',
        '2930da71' => 'Art of War',
    ];

    /**
     * @inheritDoc
     */
    protected function processRules(Buffer $buffer, Result $result): void
    {
        if (!$buffer->readInt16Signed()) {
            return;
        }

        $bufferString = '';

        while ($buffer->getLength()) {
            $buffer->readString();
            $bufferString .= $buffer->readString();
        }

        // Restore escaped sequences.
        $bufferString = str_replace(["\x01\x01", "\x01\x02", "\x01\x03"], ["\x01", "\x00", "\xFF"], $bufferString);

        // Let's make a new buffer with reassembled data.
        $buffer = new Buffer($bufferString);
        $result
            ->addRule('rules_protocol_version', strval($buffer->readInt8()))
            ->addRule('overflow', strval($buffer->readInt8()));

        // Process DLCs
        $dlcFirstByte  = decbin($buffer->readInt8()); // Grab DLC bit 1 and use it later.
        $dlcSecondByte = decbin($buffer->readInt8()); // Grab DLC bit 2 and use it later.
        $dlcCount      = substr_count($dlcFirstByte, '1') + substr_count($dlcSecondByte, '1'); // Count the DLCs
        $dlcs          = [];

        for ($x = 0; $x < $dlcCount; $x++) {
            $dlcHash = dechex($buffer->readInt32());
            $dlcs[]  = $this->dlcNames[$dlcHash] ?? null;
        }

        $result->addRule('dlcs', implode(',', array_unique($dlcs))); // Add DLCs to result.

        // Process difficulty
        $difficulty = $buffer->readInt8();
        $result
            ->addRule('3rd_person', (string) ($difficulty >> 7))
            ->addRule('advanced_flight_mode', (string) (($difficulty >> 6) & 1))
            ->addRule('difficulty_ai', (string) (($difficulty >> 3) & 3))
            ->addRule('difficulty_level', (string) ($difficulty & 3));

        // Crosshair
        $result->addRule('crosshair', (string) $buffer->readInt8());

        // Process mods.
        $nbOfMods = $buffer->readInt8();
        $result->addRule('mod_count', (string) $nbOfMods);

        $mods = [];
        for ($i = 0; $i < $nbOfMods; $i++) {
            $mods[] = $buffer->readPascalString(0, true);
        }

        $result->addRule('mods', implode(',', array_unique($mods)));

        unset($nbOfMods, $mods);

        // Process signatures.
        $nbOfSignatures = $buffer->readInt8();
        $result->addRule('signature_count', (string) $nbOfSignatures);

        $signatures = [];
        for ($i = 0; $i < $nbOfSignatures; $i++) {
            $signatures[] = $buffer->readPascalString(0, true);
        }

        $result->addRule('signatures', implode(',', array_unique($signatures)));

        unset($buffer, $bufferString, $dlcFirstByte, $dlcSecondByte, $dlcs, $dlcCount, $dlcHash, $difficulty, $nbOfMods, $mods, $nbOfSignatures, $signatures); // Clear buffer from memory.
    }
}