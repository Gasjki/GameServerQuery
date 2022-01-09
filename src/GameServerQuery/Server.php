<?php declare(strict_types = 1);

namespace GameServerQuery;

use GameServerQuery\Exception\DNS\DNSToIPConversionException;
use GameServerQuery\Exception\Protocol\ProtocolException;
use GameServerQuery\Interfaces\ProtocolInterface;
use GameServerQuery\Utils\DNSResolveHelper;

/**
 * Class Server
 * @package GameServerQuery
 */
class Server
{
    /**
     * Server protocol.
     *
     * @var ProtocolInterface
     */
    protected ProtocolInterface $protocol;

    /**
     * Server constructor.
     *
     * @param string   $protocol
     * @param string   $ipAddress
     * @param int      $port
     * @param int|null $queryPort
     *
     * @throws DNSToIPConversionException
     * @throws \InvalidArgumentException
     * @throws ProtocolException
     */
    public function __construct(string $protocol, protected string $ipAddress, protected int $port, protected ?int $queryPort = null)
    {
        if (!\in_array(ProtocolInterface::class, \class_implements($protocol))) {
            throw new ProtocolException(sprintf('"%s" does not implement ProtocolInterface.', $protocol));
        }

        $this->protocol  = new $protocol;
        $this->ipAddress = DNSResolveHelper::resolveAddress(\trim($this->ipAddress));

        if (!\filter_var($this->port, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1000, 'max_range' => 99999]])) {
            throw new \InvalidArgumentException(
                sprintf('Provided port "%d" is too lower / higher. You need to provide a port between 1000 and 99999.', $this->port)
            );
        }

        if ($this->queryPort || $this->protocol->isQueryPortMandatory()) {
            if (!\filter_var($this->queryPort, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1000, 'max_range' => 99999]])) {
                throw new \InvalidArgumentException(
                    sprintf('Provided query port "%d" is too lower / higher. You need to provide a port between 1000 and 99999.', $this->queryPort)
                );
            }
        }
    }

    /**
     * Returns server's protocol instance.
     *
     * @return ProtocolInterface
     */
    public function getProtocol(): ProtocolInterface
    {
        return $this->protocol;
    }

    /**
     * @return string
     */
    public function getIpAddress(): string
    {
        return $this->ipAddress;
    }

    /**
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * @return int|null
     */
    public function getQueryPort(): ?int
    {
        if (!$this->queryPort) {
            $this->queryPort = $this->protocol->calculateQueryPort($this->port);
        }

        return $this->queryPort;
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
        return $this->getIpAddress() . ':' . $this->getQueryPort();
    }
}