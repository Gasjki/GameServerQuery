<?php declare(strict_types = 1);

namespace GameServerQuery\Formatter\Types;

use GameServerQuery\Exception\Formatter\FormatterException;
use GameServerQuery\Formatter\AbstractFormatter;
use GameServerQuery\Result;

/**
 * Class XMLFormatter
 * @package GameServerQuery\Formatter\Types
 */
class XMLFormatter extends AbstractFormatter
{
    /**
     * @inheritDoc
     * @throws FormatterException
     */
    public function format(): string
    {
        $xml = new \SimpleXMLElement('<game-server-query/>');

        foreach ($this->response as $fullAddress => $data) {
            // Register server node.
            $server = $xml->addChild('server');
            if (null === $server) {
                throw new FormatterException(sprintf("Node creation failed: '%s'.", 'server'));
            }

            $server->addAttribute('address', $fullAddress);

            // Information.
            $information = $server->addChild(Result::GENERAL_CATEGORY);
            if (null === $information) {
                throw new FormatterException(sprintf("Node creation failed: '%s'.", Result::GENERAL_CATEGORY));
            }

            $general = $data[Result::GENERAL_CATEGORY];

            $information->addChild(Result::GENERAL_ACTIVE_SUBCATEGORY, (string) (int) $general[Result::GENERAL_ACTIVE_SUBCATEGORY]);
            $information->addChild(Result::GENERAL_HOSTNAME_SUBCATEGORY, $general[Result::GENERAL_HOSTNAME_SUBCATEGORY]);
            $information->addChild(Result::GENERAL_IP_ADDRESS_SUBCATEGORY, $general[Result::GENERAL_IP_ADDRESS_SUBCATEGORY]);
            $information->addChild(Result::GENERAL_PORT_SUBCATEGORY, $general[Result::GENERAL_PORT_SUBCATEGORY]);
            $information->addChild(Result::GENERAL_QUERY_PORT_SUBCATEGORY, $general[Result::GENERAL_QUERY_PORT_SUBCATEGORY]);
            $information->addChild(Result::GENERAL_MAP_SUBCATEGORY, $general[Result::GENERAL_MAP_SUBCATEGORY]);
            $information->addChild(Result::GENERAL_VERSION_SUBCATEGORY, $general[Result::GENERAL_VERSION_SUBCATEGORY]);
            $information->addChild(Result::GENERAL_BOTS_SUBCATEGORY, (string) $general[Result::GENERAL_BOTS_SUBCATEGORY]);
            $information->addChild(Result::GENERAL_SERVER_TYPE_SUBCATEGORY, $general[Result::GENERAL_SERVER_TYPE_SUBCATEGORY]);
            $information->addChild(Result::GENERAL_OS_SUBCATEGORY, $general[Result::GENERAL_OS_SUBCATEGORY]);
            $information->addChild(Result::GENERAL_SLOTS_SUBCATEGORY, (string) $general[Result::GENERAL_SLOTS_SUBCATEGORY]);
            $information->addChild(self::convertKey(Result::GENERAL_ONLINE_PLAYERS_SUBCATEGORY), (string) $general[Result::GENERAL_ONLINE_PLAYERS_SUBCATEGORY]);
            $information->addChild(Result::GENERAL_PASSWORD_SUBCATEGORY, (string) (int) $general[Result::GENERAL_PASSWORD_SUBCATEGORY]);

            // Players.
            $players = $xml->addChild(Result::PLAYERS_CATEGORY);
            if (null === $players) {
                throw new FormatterException(sprintf("Node creation failed: '%s'.", Result::PLAYERS_CATEGORY));
            }

            foreach ($data[Result::PLAYERS_CATEGORY] as $playerInformation) {
                $playerInformation = \array_map(static fn(mixed $value): string => (string) $value, $playerInformation);
                $player            = $players->addChild('player');
                if (null === $player) {
                    throw new FormatterException(sprintf("Node creation failed: '%s'.", 'player'));
                }

                $player->addChild(Result::PLAYERS_NAME_SUBCATEGORY, $playerInformation[Result::PLAYERS_NAME_SUBCATEGORY]);
                $player->addChild(Result::PLAYERS_SCORE_SUBCATEGORY, $playerInformation[Result::PLAYERS_SCORE_SUBCATEGORY]);
                $player->addChild(self::convertKey(Result::PLAYERS_ONLINE_TIME_SUBCATEGORY), $playerInformation[Result::PLAYERS_ONLINE_TIME_SUBCATEGORY]);
            }

            // Rules.
            $rules = $xml->addChild(Result::RULES_CATEGORY);
            if (null === $rules) {
                throw new FormatterException(sprintf("Node creation failed: '%s'.", Result::RULES_CATEGORY));
            }

            foreach ($data[Result::RULES_CATEGORY] as $name => $value) {
                $rule = $rules->addChild('rule');
                if (null === $rule) {
                    throw new FormatterException(sprintf("Node creation failed: '%s'.", 'rule'));
                }

                $rule->addChild(Result::RULES_NAME_SUBCATEGORY, $name);
                $rule->addChild(Result::RULES_VALUE_SUBCATEGORY, $value);
            }
        }

        return $xml->asXML();
    }

    /**
     * Converts key to XML format.
     *
     * @param string $key
     *
     * @return string
     */
    private static function convertKey(string $key): string
    {
        return \str_replace('_', '-', $key);
    }
}