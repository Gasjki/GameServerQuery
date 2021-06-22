<?php declare(strict_types = 1);

namespace GameServerQuery\Interfaces;

use GameServerQuery\Buffer;

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

    public const PACKET_ALL       = 'all'; // Some protocols allow all data to be sent back in one call.
    public const PACKET_BASIC     = 'basic';
    public const PACKET_CHALLENGE = 'challenge';
    public const PACKET_CHANNELS  = 'channels'; // Voice servers (soon)
    public const PACKET_DETAILS   = 'details';
    public const PACKET_INFO      = 'info';
    public const PACKET_PLAYERS   = 'players';
    public const PACKET_STATUS    = 'status';
    public const PACKET_RULES     = 'rules';
    public const PACKET_VERSION   = 'version';

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
     * Update packages based on challenge package response.
     *
     * @param Buffer $buffer
     */
    public function updatePackagesBasedOnChallengePackageResponse(Buffer $buffer): void;

    /**
     * Returns all packages excepting those given as argument.
     *
     * @param array|string $exceptions
     *
     * @return array
     */
    public function getAllPackagesExcept(array|string $exceptions): array;

    /**
     * Handle socket response.
     *
     * @param array $responses
     *
     * @return array
     */
    public function handleResponse(array $responses): array;
}