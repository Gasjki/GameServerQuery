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
    public const DLC_NAME_KART      = 'Karts';
    public const DLC_NAME_MARKSMEN  = 'Marksmen';
    public const DLC_NAME_HELI      = 'Helicopters';
    public const DLC_NAME_CURATOR   = 'Curator';
    public const DLC_NAME_EXPANSION = 'Expansion';
    public const DLC_NAME_JETS      = 'Jets';
    public const DLC_NAME_ORANGE    = 'Laws of War';
    public const DLC_NAME_ARGO      = 'Malden';
    public const DLC_NAME_TACOPS    = 'Tac-Ops';
    public const DLC_NAME_TANKS     = 'Tanks';
    public const DLC_NAME_CONTACT   = 'Contact';
    public const DLC_NAME_ENOCH     = 'Contact (Platform)';
    public const DLC_NAME_AOW       = 'Art of War';  // Special

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
    private array $dlcMapping = [
        0b0000000000000001 => self::DLC_NAME_KART,
        0b0000000000000010 => self::DLC_NAME_MARKSMEN,
        0b0000000000000100 => self::DLC_NAME_HELI,
        0b0000000000001000 => self::DLC_NAME_CURATOR,
        0b0000000000010000 => self::DLC_NAME_EXPANSION,
        0b0000000000100000 => self::DLC_NAME_JETS,
        0b0000000001000000 => self::DLC_NAME_ORANGE,
        0b0000000010000000 => self::DLC_NAME_ARGO,
        0b0000000100000000 => self::DLC_NAME_TACOPS,
        0b0000001000000000 => self::DLC_NAME_TANKS,
        0b0000010000000000 => self::DLC_NAME_CONTACT,
        0b0000100000000000 => self::DLC_NAME_ENOCH,
        0b0001000000000000 => self::DLC_NAME_AOW,
        0b0010000000000000 => 'Unknown',
        0b0100000000000000 => 'Unknown',
        0b1000000000000000 => 'Unknown',
    ];

    /**
     * @inheritDoc
     */
    protected function processRules(Buffer $buffer, Result $result): void
    {
        $buffer->readInt16Signed();
        $bufferString = '';

        while ($buffer->getLength()) {
            $buffer->readString(); // Burn the delimiters (e.g.: \x01\x04\x00)
            $bufferString .= $buffer->readString();
        }

        // Restore escaped sequences.
        $bufferString = \str_replace(["\x01\x01", "\x01\x02", "\x01\x03"], ["\x01", "\x00", "\xFF"], $bufferString);

        // Let's make a new buffer with reassembled data.
        $buffer = new Buffer($bufferString);
        unset($bufferString);

        $result->addRule('rules_protocol_version', $buffer->readInt8());
        $result->addRule('overflow', $buffer->readInt8());

        // Extract DLCs information for later.
        $dlcFirstByte  = $buffer->readInt8();
        $dlcSecondByte = $buffer->readInt8();
        $dlcBits       = ($dlcSecondByte << 8) | $dlcFirstByte;

        // Difficulty.
        $difficulty = $buffer->readInt8();
        $result->addRule('3rd_person', ($difficulty >> 7));
        $result->addRule('advanced_flight_mode', (($difficulty >> 6) & 1));
        $result->addRule('difficulty_ai', (($difficulty >> 3) & 3));
        $result->addRule('difficulty_level', ($difficulty & 3));
        unset($difficulty);

        // Crosshair.
        $result->addRule('crosshair', $buffer->readInt8());

        // Process DLCs.
        $dlcs = [];
        foreach ($this->dlcMapping as $flag => $name) {
            if (($dlcBits & $flag) === $flag) {
                $dlcs[] = [
                    'name' => $name,
                    'hash' => dechex($buffer->readInt32()),
                ];
            }
        }

        $result->addRule('dlcs', $dlcs);
        unset($dlcFirstByte, $dlcSecondByte, $dlcBits, $dlcs);

        // Mods.
        $mods     = [];
        $nbOfMods = $buffer->readInt8();
        $result->addRule('mod_count', $nbOfMods);

        while ($nbOfMods > 0) {
            $hash     = dechex($buffer->readInt32());
            $infoByte = $buffer->readInt8();
            $dlc      = ($infoByte & 0b00010000) === 0b00010000;
            $steamId  = $buffer->readInt32($infoByte & 0x0F);
            $name     = $buffer->readPascalString(0, true) ?: 'Unknown';

            $mods[] = [
                'name'     => $name,
                'hash'     => $hash,
                'dlc'      => $dlc,
                'steam_id' => $steamId,
            ];

            $nbOfMods--;
        }

        $result->addRule('mods', $mods);
        unset($mods, $nbOfMods, $hash, $infoByte, $dlc, $steamId, $name);

        // Signature.
        $signatures     = [];
        $nbOfSignatures = $buffer->readInt8();
        $result->addRule('signature_count', $nbOfSignatures);

        for ($x = 0; $x < $nbOfSignatures; $x++) {
            $signatures[] = $buffer->readPascalString(0, true);
        }

        $result->addRule('signatures', $signatures);
        unset($signatures, $nbOfSignatures);
    }
}