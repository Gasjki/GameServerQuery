<?php declare(strict_types = 1);

namespace GameServerQuery\Protocol\Types;

use GameServerQuery\Buffer;
use GameServerQuery\Exception\Buffer\BufferException;
use GameServerQuery\Protocol\AbstractProtocol;
use GameServerQuery\Query\Types\RaknetQuery;
use GameServerQuery\Result;

/**
 * Class RaknetProtocol
 * @package GameServerQuery\Protocol\Types
 */
class RaknetProtocol extends AbstractProtocol
{
    /**
     * Protocol packages.
     *
     * Packages to be sent to socket.
     *
     * @var array
     */
    protected array $packages = [
        self::PACKAGE_STATUS => "\x01%s%s\x02\x00\x00\x00\x00\x00\x00\x00",
    ];

    /**
     * @inheritDoc
     */
    public function getQueryClass(): string
    {
        return RaknetQuery::class;
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

        // Pre-process packets if they are split.
        return $this->processPackets($result, $responses);
    }

    /**
     * Process all extracted packets.
     *
     * @param Result $result
     * @param array  $packets
     *
     * @return array
     * @throws \Exception
     */
    protected function processPackets(Result $result, array $packets): array
    {
        $buffer = new Buffer(\implode('', $packets));
        $header = $buffer->read();

        if ($header !== RaknetQuery::ID_UNCONNECTED_PONG) {
            throw new BufferException("Server header verification failed!");
        }

        $buffer->skip(8);
        $buffer->readInt64(); // Server GUID.
        $magicCheck = $buffer->read(16);

        if ($magicCheck !== RaknetQuery::OFFLINE_MESSAGE_DATA_ID) {
            throw new BufferException("Server magic verification failed!");
        }

        $buffer->skip(2);
        $this->processInformation($buffer, $result);

        return $result->toArray();
    }

    /**
     * Process server information.
     *
     * @param Buffer $buffer
     * @param Result $result
     *
     * @throws \Exception
     */
    protected function processInformation(Buffer $buffer, Result $result): void
    {
        $data = \explode(';', $buffer->getBuffer());

        $information = array_filter([
            'edition'          => $data[0] ?? null,
            'motd_line_1'      => $data[1] ?? null,
            'protocol_version' => $data[2] ?? null,
            'version'          => $data[3] ?? null,
            'num_players'      => $data[4] ?? null,
            'max_players'      => $data[5] ?? null,
            'server_uid'       => $data[6] ?? null,
            'motd_line_2'      => $data[7] ?? null,
            'gamemode'         => $data[8] ?? null,
            'gamemode_numeric' => $data[9] ?? null,
            'port_ipv4'        => $data[10] ?? null,
            'port_ipv6'        => $data[11] ?? null,
        ]);

        $result->addInformation(Result::GENERAL_ACTIVE_SUBCATEGORY, true);
        $result->addInformation(Result::GENERAL_SERVER_TYPE_SUBCATEGORY, 'd'); // Always.
        $result->addInformation(Result::GENERAL_HOSTNAME_SUBCATEGORY, $information['motd_line_1']);
        $result->addInformation(Result::GENERAL_ONLINE_PLAYERS_SUBCATEGORY, (int) $information['num_players']);
        $result->addInformation(Result::GENERAL_SLOTS_SUBCATEGORY, (int) $information['max_players']);
        $result->addInformation(Result::GENERAL_VERSION_SUBCATEGORY, $information['version']);

        foreach ($information as $key => $info) {
            $result->addRule($key, $info);
        }
    }
}