<?php declare(strict_types = 1);

namespace GameServerQuery\Utils;

use GameServerQuery\Exception\DNS\DNSToIPConversionException;

/**
 * Class DNSResolveHelper
 * @package GameServerQuery\Utils
 */
class DNSResolveHelper
{
    /**
     * Checks if given address is an IP address.
     *
     * @param string $address
     *
     * @return bool
     */
    public static function isIpAddress(string $address): bool
    {
        return false !== \filter_var($address, FILTER_VALIDATE_IP);
    }

    /**
     * Converts DNS address to IP.
     *
     * @param string $address
     *
     * @return string
     */
    public static function convertDNSToIP(string $address): string
    {
        return \gethostbyname($address);
    }

    /**
     * Resolve DNS / IP address.
     *
     * @param string $address
     *
     * @return string
     * @throws DNSToIPConversionException
     */
    public static function resolveAddress(string $address): string
    {
        if (self::isIpAddress($address)) {
            return $address;
        }

        $ipAddress = self::convertDNSToIP($address);

        if ($address === $ipAddress) {
            throw new DNSToIPConversionException(sprintf('Could not convert your DNS address (%s) to IP address.', $address));
        }

        return $ipAddress;
    }
}