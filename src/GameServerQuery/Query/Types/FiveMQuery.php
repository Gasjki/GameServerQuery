<?php declare(strict_types = 1);

namespace GameServerQuery\Query\Types;

use GameServerQuery\Query\AbstractQuery;
use GameServerQuery\Result;
use GameServerQuery\Socket;

/**
 * Class FiveMQuery
 * @package GameServerQuery\Query\Types
 */
class FiveMQuery extends AbstractQuery
{
    /**
     * @inheritDoc
     * @throws \JsonException
     */
    public function execute(): array
    {
        $result = new Result(parent::execute());

        // Fetch server data.
        $responses = [];

        $responses['information'] = $this->readServerInformationUrl();
        $responses['players']     = $this->readServerPlayersUrl();
        $responses                = array_filter($responses);

        // No response caught. Stop the process and go the next server (if any!).
        if (!$responses) {
            \unset($information, $players);

            return $result->toArray();
        }

        // Process all information and create a new Result object.
        $response = $this->server->getProtocol()->handleResponse($result, $responses);

        \unset($information, $players, $responses);

        return $response;
    }

    /**
     * @inheritDoc
     */
    protected function readPackageFromServer(Socket $socket, string $packageType, int $length = 32768): array
    {
        return [];
    }

    /**
     * Read information from given URL and convert it to JSON.
     *
     * @param string $url
     *
     * @return array
     * @throws \JsonException
     */
    private function readFromUrl(string $url): array
    {
        $opts = [
            'http' => [
                'method' => "GET",
            ],
        ];

        $data = @\file_get_contents($url, false, stream_context_create($opts));

        if (\is_bool($data)) {
            return [];
        }

        return \json_decode($data, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Read server's information.
     *
     * @return array
     * @throws \JsonException
     */
    private function readServerInformationUrl(): array
    {
        $url = \sprintf("http://%s:%s/info.json", $this->server->getIpAddress(), $this->server->getPort());

        return $this->readFromUrl($url);
    }

    /**
     * Read server's players.
     *
     * @return array
     * @throws \JsonException
     */
    private function readServerPlayersUrl(): array
    {
        $url = \sprintf("http://%s:%s/players.json", $this->server->getIpAddress(), $this->server->getPort());

        return $this->readFromUrl($url);
    }
}