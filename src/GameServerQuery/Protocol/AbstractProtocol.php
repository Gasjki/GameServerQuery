<?php declare(strict_types = 1);

namespace GameServerQuery\Protocol;

use GameServerQuery\Interfaces\ProtocolInterface;

/**
 * Class AbstractProtocol
 * @package GameServerQuery\Protocol
 */
abstract class AbstractProtocol implements ProtocolInterface
{
    /**
     * Transport protocol.
     *
     * @var string
     */
    protected string $transportSchema = self::TRANSPORT_UDP;

    /**
     * Port to query port difference.
     *
     * @var int
     */
    protected int $portToQueryPortStep = 0;

    /**
     * Is query port required for this protocol?
     *
     * @var bool
     */
    protected bool $queryPortMandatory = false;

    /**
     * Has blocking mode?
     *
     * @var bool
     */
    protected bool $blockingMode = false;

    /**
     * Protocol packages.
     *
     * Packages to be sent to socket.
     *
     * @var array
     */
    protected array $packages = [];

    /**
     * Socket responses and their corresponding protocol method.
     *
     * @var array
     */
    protected array $responses = [];

    /**
     * AbstractProtocol constructor.
     */
    public function __construct()
    {
        if (!$this->packages) {
            throw new \InvalidArgumentException('You need to provide at least one query package!');
        }
    }

    /**
     * @inheritDoc
     */
    public function calculateQueryPort(int $port): int
    {
        return $this->portToQueryPortStep + $port;
    }

    /**
     * @inheritDoc
     */
    public function getTransportSchema(): string
    {
        return $this->transportSchema;
    }

    /**
     * @inheritDoc
     */
    public function isBlockingMode(): bool
    {
        return $this->blockingMode;
    }

    /**
     * @inheritDoc
     */
    public function isQueryPortMandatory(): bool
    {
        return $this->queryPortMandatory;
    }

    /**
     * @inheritDoc
     */
    public function hasChallenge(): bool
    {
        return array_key_exists(ProtocolInterface::PACKAGE_CHALLENGE, $this->packages);
    }

    /**
     * @inheritDoc
     */
    public function getPackage(string $packageName): string
    {
        return $this->packages[$packageName];
    }

    /**
     * @inheritDoc
     */
    public function getAllPackagesExcept(array|string $exceptions): array
    {
        $data = [];

        if (is_string($exceptions)) {
            $data = $this->packages;
            unset($data[$exceptions]);

            return $data;
        }

        foreach ($this->packages as $package) {
            if (in_array($package, $exceptions)) {
                continue;
            }

            $data[] = $package;
        }

        return $data;
    }

    /**
     * @inheritDoc
     */
    public function applyChallenge(string $challenge): void
    {
        foreach ($this->packages as $type => $package) {
            $this->packages[$type] = sprintf($package, $challenge);
        }
    }
}