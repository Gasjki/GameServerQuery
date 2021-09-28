<?php declare(strict_types = 1);

namespace GameServerQuery;

use GameServerQuery\Interfaces\ProtocolInterface;
use GameServerQuery\Utils\DNSResolveHelper;

/**
 * Class Server
 * @package GameServerQuery
 */
class Server
{
    public const TYPE_PROTOCOL   = 'protocol';
    public const TYPE_IP_ADDRESS = 'ip_address';
    public const TYPE_PORT       = 'port';
    public const TYPE_QUERY_PORT = 'query_port';

    /**
     * Server protocol.
     *
     * @var ProtocolInterface
     */
    protected ProtocolInterface $protocol;

    /**
     * Server IP address.
     *
     * @var string
     */
    protected string $ipAddress;

    /**
     * Server port.
     *
     * @var int
     */
    protected int $port;

    /**
     * Server query port.
     *
     * @var int|null
     */
    protected ?int $queryPort = null;

    /**
     * Server constructor.
     *
     * @param string   $protocol
     * @param string   $ipAddress
     * @param int      $port
     * @param int|null $queryPort
     *
     * @throws \Exception
     */
    public function __construct(string $protocol, string $ipAddress, int $port, ?int $queryPort = null)
    {
        if (!in_array(ProtocolInterface::class, class_implements($protocol))) {
            throw new \Exception(
                sprintf('"%s" does not implement ProtocolInterface.', $protocol)
            );
        }

        $this->protocol  = new $protocol;
        $this->ipAddress = DNSResolveHelper::resolveAddress(trim($ipAddress));

        if (!filter_var($port, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1000, 'max_range' => 99999]])) {
            throw new \InvalidArgumentException(
                sprintf('Provided port ("%d") is too lower / higher. You need to provide a port between 1000 and 99999.', $port)
            );
        }

        $this->port = $port;

        if ($queryPort || $this->protocol->isQueryPortMandatory()) {
            if (!filter_var($queryPort, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1000, 'max_range' => 99999]])) {
                throw new \InvalidArgumentException(
                    sprintf('Provided query port ("%d") is too lower / higher. You need to provide a port between 1000 and 99999.', $queryPort)
                );
            }

            $this->queryPort = $queryPort;
        }
    }

    /**
     * @return ProtocolInterface
     */
    public function getProtocol(): mixed
    {
        return $this->protocol;
    }

    /**
     * Returns server's full address.
     *
     * @return string
     */
    public function getFullAddress(): string
    {
        return $this->ipAddress . ':' . $this->port;
    }

    /**
     * Returns server's full address with the query port instead of port.
     *
     * @return string
     */
    public function getFullAddressWithQueryPort(): string
    {
        if (!$this->queryPort) {
            $this->queryPort = $this->protocol->calculateQueryPort($this->port);
        }

        return $this->ipAddress . ':' . $this->queryPort;
    }
}