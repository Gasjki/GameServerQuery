<?php declare(strict_types = 1);

namespace GameServerQuery\Interfaces;

use GameServerQuery\Result;

/**
 * Interface ProtocolInterface
 * @package GameServerQuery\Interfaces
 */
interface ProtocolInterface
{
    public const TRANSPORT_UDP = 'udp';
    public const TRANSPORT_TCP = 'tcp';
    public const TRANSPORT_SSL = 'ssl';
    public const TRANSPORT_TLS = 'tls';

    public const PACKAGE_ALL       = 'all'; // Some protocols allow all data to be sent back in one call.
    public const PACKAGE_BASIC     = 'basic';
    public const PACKAGE_CHALLENGE = 'challenge';
    public const PACKAGE_CHANNELS  = 'channels'; // Voice servers (soon)
    public const PACKAGE_DETAILS   = 'details';
    public const PACKAGE_INFO      = 'info';
    public const PACKAGE_PLAYERS   = 'players';
    public const PACKAGE_STATUS    = 'status';
    public const PACKAGE_RULES     = 'rules';
    public const PACKAGE_VERSION   = 'version';

    /**
     * Returns query class path used by this protocol.
     *
     * @return string
     */
    public function getQueryClass(): string;

    /**
     * Calculate query port.
     *
     * @param int $port
     *
     * @return int
     */
    public function calculateQueryPort(int $port): int;

    /**
     * Returns protocol transport schema.
     *
     * @return string
     */
    public function getTransportSchema(): string;

    /**
     * Checks if its blocking mode enabled.
     *
     * @return bool
     */
    public function isBlockingMode(): bool;

    /**
     * Checks if we should enforce user to give us the query port.
     *
     * @return bool
     */
    public function isQueryPortMandatory(): bool;

    /**
     * Checks if current protocol has a challenge package.
     *
     * @return bool
     */
    public function hasChallenge(): bool;

    /**
     * Returns package.
     *
     * @param string $packageType
     *
     * @return string
     */
    public function getPackage(string $packageType): string;

    /**
     * Handle socket response.
     *
     * @param Result $result
     * @param array  $responses
     *
     * @return array
     */
    public function handleResponse(Result $result, array $responses): array;
}