<?php declare(strict_types = 1);

namespace GameServerQuery\Protocol\Games\Other;

use GameServerQuery\Protocol\AbstractProtocol;
use GameServerQuery\Query\Types\SCUMQuery;
use GameServerQuery\Result;

/**
 * Class SCUMProtocol
 * @package GameServerQuery\Protocol\Games\Other
 */
class SCUMProtocol extends AbstractProtocol
{
    /**
     * Transport protocol.
     *
     * @var string
     */
    protected string $transportSchema = self::TRANSPORT_UDP;

    /**
     * Protocol packages.
     *
     * Packages to be sent to socket.
     *
     * @var array
     */
    protected array $packages = [
        self::PACKAGE_INFO => "\x01\x02\x03\x04getinfo xxx\x00",
    ];

    /**
     * @inheritDoc
     */
    public function getQueryClass(): string
    {
        return SCUMQuery::class;
    }

    /**
     * @inheritDoc
     */
    public function handleResponse(Result $result, array $responses): array
    {
        dd($responses);
    }
}