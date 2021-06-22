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
            $information
                ->addChild(Result::GENERAL_ACTIVE_SUBCATEGORY, $data[Result::GENERAL_ACTIVE_SUBCATEGORY])
                ->addChild(Result::GENERAL_APPLICATION_SUBCATEGORY, $data[Result::GENERAL_APPLICATION_SUBCATEGORY])
                ->addChild(Result::GENERAL_HOSTNAME_SUBCATEGORY, $data[Result::GENERAL_HOSTNAME_SUBCATEGORY])
                ->addChild(Result::GENERAL_MAP_SUBCATEGORY, $data[Result::GENERAL_MAP_SUBCATEGORY])
                ->addChild(Result::GENERAL_VERSION_SUBCATEGORY, $data[Result::GENERAL_VERSION_SUBCATEGORY])
                ->addChild(Result::GENERAL_BOTS_SUBCATEGORY, $data[Result::GENERAL_BOTS_SUBCATEGORY])
                ->addChild(Result::GENERAL_DEDICATED_SUBCATEGORY, $data[Result::GENERAL_DEDICATED_SUBCATEGORY])
                ->addChild(Result::GENERAL_OS_SUBCATEGORY, $data[Result::GENERAL_OS_SUBCATEGORY])
                ->addChild(Result::GENERAL_SLOTS_SUBCATEGORY, $data[Result::GENERAL_SLOTS_SUBCATEGORY])
                ->addChild(self::convertKey(Result::GENERAL_ONLINE_PLAYERS_SUBCATEGORY), $data[Result::GENERAL_ONLINE_PLAYERS_SUBCATEGORY])
                ->addChild(Result::GENERAL_PASSWORD_SUBCATEGORY, $data[Result::GENERAL_PASSWORD_SUBCATEGORY]);

            // Players.
            $players = $xml->addChild(Result::PLAYERS_CATEGORY);
            foreach ($data[Result::PLAYERS_CATEGORY] as $key => $player) {
                $player = $players->addChild($key);
                $player
                    ->addChild(Result::PLAYERS_NAME_SUBCATEGORY, $player[Result::PLAYERS_NAME_SUBCATEGORY])
                    ->addChild(Result::PLAYERS_SCORE_SUBCATEGORY, $player[Result::PLAYERS_SCORE_SUBCATEGORY])
                    ->addChild(self::convertKey(Result::PLAYERS_ONLINE_TIME_SUBCATEGORY), $player[Result::PLAYERS_ONLINE_TIME_SUBCATEGORY]);
            }

            // Rules.
            $rules = $xml->addChild(Result::RULES_CATEGORY);
            foreach ($data[Result::RULES_CATEGORY] as $key => $rule) {
                $rule = $rules->addChild($key);
                $rule
                    ->addChild(Result::RULES_NAME_SUBCATEGORY, $rule[Result::RULES_NAME_SUBCATEGORY])
                    ->addChild(Result::RULES_VALUE_SUBCATEGORY, $rule[Result::RULES_VALUE_SUBCATEGORY]);
            }
        }

        return $xml->asXML();
    }
}