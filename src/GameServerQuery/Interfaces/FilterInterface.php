<?php declare(strict_types = 1);

namespace GameServerQuery\Interfaces;

/**
 * Interface FilterInterface
 * @package GameServerQuery\Interfaces
 */
interface FilterInterface
{
    /**
     * FilterInterface constructor.
     *
     * @param array $response
     * @param array $options
     */
    public function __construct(array $response, array $options = []);

    /**
     * Apply filter.
     *
     * @return array
     */
    public function apply(): array;
}