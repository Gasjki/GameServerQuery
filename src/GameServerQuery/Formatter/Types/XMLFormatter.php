<?php declare(strict_types = 1);

namespace GameServerQuery\Formatter\Types;

use GameServerQuery\Formatter\AbstractFormatter;
use GameServerQuery\Result;

/**
 * Class XMLFormatter
 * @package GameServerQuery\Formatter\Types
 */
class XMLFormatter extends AbstractFormatter
{
    /**
     * Converts key to XML format.
     *
     * @param string $key
     *
     * @return string
     */
    private static function convertKey(string $key): string
    {
        return str_replace('_', '-', $key);
    }

    /**
     * @inheritDoc
     */
    public function format(): string
    {
        $xml = new \SimpleXMLElement('<game-server-query-response/>');

        foreach ($this->response as $fullAddress => $data) {
            // Register server node.
            $server = $xml->addChild('server');
            $server->addAttribute('address', $fullAddress);

            // Information.
            $information = $server->addChild(Result::GENERAL_CATEGORY);
            $general     = $data[Result::GENERAL_CATEGORY];

            $information->addChild(Result::GENERAL_ACTIVE_SUBCATEGORY, strval(intval($general[Result::GENERAL_ACTIVE_SUBCATEGORY])));
            $information->addChild(Result::GENERAL_HOSTNAME_SUBCATEGORY, $general[Result::GENERAL_HOSTNAME_SUBCATEGORY]);
            $information->addChild(Result::GENERAL_MAP_SUBCATEGORY, $general[Result::GENERAL_MAP_SUBCATEGORY]);
            $information->addChild(Result::GENERAL_VERSION_SUBCATEGORY, $general[Result::GENERAL_VERSION_SUBCATEGORY]);
            $information->addChild(Result::GENERAL_BOTS_SUBCATEGORY, strval($general[Result::GENERAL_BOTS_SUBCATEGORY]));
            $information->addChild(Result::GENERAL_DEDICATED_SUBCATEGORY, $general[Result::GENERAL_DEDICATED_SUBCATEGORY]);
            $information->addChild(Result::GENERAL_OS_SUBCATEGORY, $general[Result::GENERAL_OS_SUBCATEGORY]);
            $information->addChild(Result::GENERAL_SLOTS_SUBCATEGORY, strval($general[Result::GENERAL_SLOTS_SUBCATEGORY]));
            $information->addChild(self::convertKey(Result::GENERAL_ONLINE_PLAYERS_SUBCATEGORY), strval($general[Result::GENERAL_ONLINE_PLAYERS_SUBCATEGORY]));
            $information->addChild(Result::GENERAL_PASSWORD_SUBCATEGORY, strval(intval($general[Result::GENERAL_PASSWORD_SUBCATEGORY])));

            // Players.
            $players = $xml->addChild(Result::PLAYERS_CATEGORY);
            foreach ($data[Result::PLAYERS_CATEGORY] as $player) {
                $player    = array_map('strval', $player);
                $playerXml = $players->addChild('player');

                $playerXml->addChild(Result::PLAYERS_NAME_SUBCATEGORY, $player[Result::PLAYERS_NAME_SUBCATEGORY]);
                $playerXml->addChild(Result::PLAYERS_SCORE_SUBCATEGORY, $player[Result::PLAYERS_SCORE_SUBCATEGORY]);
                $playerXml->addChild(self::convertKey(Result::PLAYERS_ONLINE_TIME_SUBCATEGORY), $player[Result::PLAYERS_ONLINE_TIME_SUBCATEGORY]);
            }

            // Rules.
            $rules = $xml->addChild(Result::RULES_CATEGORY);
            foreach ($data[Result::RULES_CATEGORY] as $rule => $value) {
                $ruleXml = $rules->addChild('rule');

                $ruleXml->addChild(Result::RULES_NAME_SUBCATEGORY, $rule);
                $ruleXml->addChild(Result::RULES_VALUE_SUBCATEGORY, $value);
            }
        }

        return $xml->asXML();
    }
}