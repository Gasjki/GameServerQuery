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
     */
    public function execute(): array
    {
        $result = new Result(parent::execute());

        // Open socket for server.
        $socket = new Socket($this->server, $this->config->get('timeout', 3));

        // Fetch server data.
        $responses                = [];
        $responses['information'] = $this->readServerStatus($socket);
        $responses['players']     = $this->readServerPlayersUrl();

        // No response caught. Stop the process and go the next server (if any!).
        if (!$responses) {
            unset($responses);

            return $result->toArray();
        }

        // Process all information and create a new Result object.
        $response = $this->server->getProtocol()->handleResponse($result, $responses);
        unset($responses);

        return $response;
    }

    /**
     * Read server's players.
     *
     * @return array
     */
    private function readServerPlayersUrl(): array
    {
        $url = \sprintf("http://%s:%s/players.json", $this->server->getIpAddress(), $this->server->getPort());

        $opts = [
            'http' => [
                'method'  => "GET",
                'timeout' => $this->config->get('timeout', 3),
            ],
        ];

        try {
            $data     = @\file_get_contents($url, false, stream_context_create($opts));
            $response = \json_decode($data, true, 512, JSON_THROW_ON_ERROR);

            if (!\is_array($response)) {
                return [];
            }

            return $response;
        } catch (\JsonException) {
            return [];
        }
    }
}
