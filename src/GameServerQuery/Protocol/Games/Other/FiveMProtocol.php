<?php declare(strict_types = 1);

namespace GameServerQuery\Protocol\Games\Other;

use GameServerQuery\Buffer;
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
    protected array $packages = [
        self::PACKAGE_STATUS => "\xFF\xFF\xFF\xFFgetinfo xxx",
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
        $this->processInformation(new Buffer(\implode('', $packets['information'] ?? [])), $result);
        $this->processPlayers($packets['players'] ?? [], $result);

        return $result->toArray();
    }

    /**
     * Process server information.
     *
     * @param Buffer $buffer
     * @param Result $result
     */
    protected function processInformation(Buffer $buffer, Result $result): void
    {
        if (!$buffer->getBuffer()) {
            return;
        }

        $buffer->skip(17);
        if ($buffer->lookAhead() === '\\') {
            $buffer->skip();
        }

        $data     = explode('\\', $buffer->getBuffer());
        $info     = [];
        $savedKey = null;

        foreach ($data as $key => $value) {
            if (($key % 2) === 0) {
                $savedKey = $value;
            } else {
                $info[$savedKey] = $value;
            }
        }

        $result->addInformation(Result::GENERAL_ACTIVE_SUBCATEGORY, true);
        $result->addInformation(Result::GENERAL_SERVER_TYPE_SUBCATEGORY, 'd'); // Always.
        $result->addInformation(Result::GENERAL_HOSTNAME_SUBCATEGORY, $info['hostname'] ?? null);
        $result->addInformation(Result::GENERAL_MAP_SUBCATEGORY, $info['mapname'] ?? null);
        $result->addInformation(Result::GENERAL_ONLINE_PLAYERS_SUBCATEGORY, 0); // It will be overwritten under `processPlayers` method.
        $result->addInformation(Result::GENERAL_SLOTS_SUBCATEGORY, (int) $info['sv_maxclients']);
        $result->addInformation(Result::GENERAL_VERSION_SUBCATEGORY, $info['iv'] ?? null);

        $result->addRule('raw_information', $info);
    }

    /**
     * Process server players.
     *
     * @param array  $data
     * @param Result $result
     */
    protected function processPlayers(array $data, Result $result): void
    {
        if (null === $result->getInformation(Result::GENERAL_HOSTNAME_SUBCATEGORY)) {
            return; // Don't add any player information if we failed to fetch server information.
        }

        $result->addInformation(Result::GENERAL_ONLINE_PLAYERS_SUBCATEGORY, \count($data));

        foreach ($data as $player) {
            $result->addPlayer($player['name']);
        }
    }
}