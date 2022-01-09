<?php declare(strict_types = 1);

namespace GameServerQuery\Interfaces;

/**
 * Interface QueryInterface
 * @package GameServerQuery\Interfaces
 */
interface QueryInterface
{
    /**
     * Query servers.
     *
     * @return array
     */
    public function execute(): array;
}