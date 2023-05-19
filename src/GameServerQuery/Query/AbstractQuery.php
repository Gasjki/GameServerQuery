<?php declare(strict_types = 1);

namespace GameServerQuery\Query;

use GameServerQuery\Buffer;
use GameServerQuery\Config;
use GameServerQuery\Exception\Buffer\BufferException;
use GameServerQuery\Interfaces\ProtocolInterface;
use GameServerQuery\Interfaces\QueryInterface;
use GameServerQuery\Result;
use GameServerQuery\Server;
use GameServerQuery\Socket;

/**
 * Class AbstractQuery
 * @package GameServerQuery\Query
 */
abstract class AbstractQuery implements QueryInterface
{
    /**
     * Challenge obtained from server.
     *
     * @var mixed|null
     */
    protected mixed $serverChallenge = null;

    /**
     * AbstractQuery constructor.
     *
     * @param Server $server
     * @param Config $config
     */
    public function __construct(protected Server $server, protected Config $config)
    {
    }

    /**
     * @inheritDoc
     */
    public function execute(): array
    {
        // Set default response format.
        $result = new Result();
        $result->addInformation(Result::GENERAL_APPLICATION_SUBCATEGORY, \get_class($this->server->getProtocol()));
        $result->addInformation(Result::GENERAL_IP_ADDRESS_SUBCATEGORY, $this->server->getIpAddress());
        $result->addInformation(Result::GENERAL_PORT_SUBCATEGORY, $this->server->getPort());
        $result->addInformation(Result::GENERAL_QUERY_PORT_SUBCATEGORY, $this->server->getQueryPort());

        return $result->toArray();
    }

    /**
     * Read from server.
     *
     * @param Socket $socket
     * @param string $package
     * @param int    $length
     *
     * @return array
     */
    protected function doRead(Socket $socket, string $package, int $length): array
    {
        $socket->write($package);

        return $this->doSocketQuery($socket, $length);
    }

    /**
     * Read information from socket.
     *
     * @param Socket $socket
     * @param int    $length
     *
     * @return array
     */
    protected function doSocketQuery(Socket $socket, int $length = 32768): array
    {
        $responses  = [];
        $sockets    = [$socket->getSocket()];
        $timeToStop = \microtime(true) + $this->config->get('timeout', 3);

        while (\microtime(true) < $timeToStop) {
            if (empty($sockets)) {
                break;
            }

            $stream = \stream_select($sockets, $write, $except, 0, $this->config->get('stream_timeout', 200000));
            if (false === $stream) {
                break;
            }

            foreach ($sockets as $socket) {
                if (($response = \fread($socket, $length)) === false) {
                    continue;
                }

                if (empty($response)) {
                    break;
                }

                $responses[] = $response;
            }
        }

        // Close socket and clean memory.
        unset($sockets, $write, $except, $timeToStop, $stream);

        return $responses;
    }

    /**
     * Read specific package from given servers' socket.
     *
     * @param Socket $socket
     * @param string $packageType
     * @param int    $length
     *
     * @return array
     * @throws BufferException
     */
    protected function readPackageFromServer(Socket $socket, string $packageType, int $length = 32768): array
    {
        $package   = $this->createPackage($packageType);
        $responses = $this->doRead($socket, $package, $length);
        $buffer    = new Buffer(\implode('', $responses));

        if (!$buffer->getLength()) {
            return []; // Buffer is empty.
        }

        return [$buffer->getData()];
    }

    /**
     * Create package.
     *
     * @param string $packageType
     *
     * @return string
     */
    protected function createPackage(string $packageType): string
    {
        if (!$package = $this->server->getProtocol()->getPackage($packageType)) {
            throw new \InvalidArgumentException(sprintf("Package '%s' not found for current protocol!", $packageType));
        }

        return $package;
    }

    /**
     * Read ALL (information) from server.
     *
     * @param Socket $socket
     * @param int    $length
     *
     * @return array
     * @throws BufferException
     */
    protected function readServerAll(Socket $socket, int $length = 32768): array
    {
        return $this->readPackageFromServer($socket, ProtocolInterface::PACKAGE_ALL, $length);
    }

    /**
     * Read information from server.
     *
     * @param Socket $socket
     * @param int    $length
     *
     * @return array
     * @throws BufferException
     */
    protected function readServerInformation(Socket $socket, int $length = 32768): array
    {
        return $this->readPackageFromServer($socket, ProtocolInterface::PACKAGE_INFO, $length);
    }

    /**
     * Read status from server.
     *
     * @param Socket $socket
     * @param int    $length
     *
     * @return array
     * @throws BufferException
     */
    protected function readServerStatus(Socket $socket, int $length = 32768): array
    {
        return $this->readPackageFromServer($socket, ProtocolInterface::PACKAGE_STATUS, $length);
    }

    /**
     * Read players from server.
     *
     * @param Socket $socket
     * @param int    $length
     *
     * @return array
     * @throws BufferException
     */
    protected function readServerPlayers(Socket $socket, int $length = 32768): array
    {
        return $this->readPackageFromServer($socket, ProtocolInterface::PACKAGE_PLAYERS, $length);
    }

    /**
     * Read rules from server.
     *
     * @param Socket $socket
     * @param int    $length
     *
     * @return array
     * @throws BufferException
     */
    protected function readServerRules(Socket $socket, int $length = 32768): array
    {
        return $this->readPackageFromServer($socket, ProtocolInterface::PACKAGE_RULES, $length);
    }
}