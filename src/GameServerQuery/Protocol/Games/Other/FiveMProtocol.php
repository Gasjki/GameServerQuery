<?php declare(strict_types = 1);

namespace GameServerQuery\Protocol\Games\Other;

use GameServerQuery\Exception\Buffer\InvalidBufferContentException;
use GameServerQuery\Protocol\AbstractProtocol;
use GameServerQuery\Query\Types\FiveMQuery;
use GameServerQuery\Result;

/**
 * Class FiveMProtocol
 * @package GameServerQuery\Protocol\Games\Other
 */
class FiveMProtocol extends AbstractProtocol
{
    /**
     * Protocol packages.
     *
     * Packages to be sent to socket.
     *
     * @var array
     */
    protected array $packages = ['NoPackageForThisGame'];

    /**
     * Socket responses and their corresponding protocol method.
     *
     * @var array
     */
    protected array $responses = [
        "information" => "processInformation",
        "players"     => "processPlayers",
    ];

    /**
     * @inheritDoc
     */
    public function getQueryClass(): string
    {
        return FiveMQuery::class;
    }

    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function handleResponse(Result $result, array $responses): array
    {
        $responses = \array_filter($responses);

        // No data to be parsed.
        if (!\count($responses)) {
            return $result->toArray();
        }

        // Process packets.
        return $this->processPackets($result, $responses);
    }

    /**
     * Process all packets.
     *
     * @param Result $result
     * @param array  $packets
     *
     * @return array
     * @throws \Exception
     */
    protected function processPackets(Result $result, array $packets): array
    {
        foreach ($packets as $key => $packet) {
            if (!\array_key_exists($key, $this->responses)) {
                throw new InvalidBufferContentException($key, $packet);
            }

            $this->{$this->responses[$key]}($packet, $result);
            unset($key, $packet);
        }

        return $result->toArray();
    }

    /**
     * Process server information.
     *
     * @param array  $data
     * @param Result $result
     *
     */
    protected function processInformation(array $data, Result $result): void
    {
        $vars = $data['vars'] ?? [];

        $result->addInformation(Result::GENERAL_ACTIVE_SUBCATEGORY, true);
        $result->addInformation(Result::GENERAL_SERVER_TYPE_SUBCATEGORY, 'd'); // Always.
        $result->addInformation(Result::GENERAL_HOSTNAME_SUBCATEGORY, $vars['sv_projectName']);
        $result->addInformation(Result::GENERAL_ONLINE_PLAYERS_SUBCATEGORY, 0); // It will be overwritten under `processPlayers` method.
        $result->addInformation(Result::GENERAL_SLOTS_SUBCATEGORY, (int) $vars['sv_maxClients']);
        $result->addInformation(Result::GENERAL_VERSION_SUBCATEGORY, $data['version']);

        $result->addRule('raw_information', $data);
    }

    /**
     * Process server players.
     *
     * @param array  $data
     * @param Result $result
     */
    protected function processPlayers(array $data, Result $result): void
    {
        $result->addInformation(Result::GENERAL_ONLINE_PLAYERS_SUBCATEGORY, \count($data));

        foreach ($data as $player) {
            $result->addPlayer($player['name']);
        }
    }
}