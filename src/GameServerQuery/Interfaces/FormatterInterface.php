<?php declare(strict_types = 1);

namespace GameServerQuery\Interfaces;

/**
 * Interface FormatterInterface
 * @package GameServerQuery\Interfaces
 */
interface FormatterInterface
{
    /**
     * FormatterInterface constructor.
     *
     * @param array $response
     */
    public function __construct(array $response);

    /**
     * Format server raw response.
     *
     * @return mixed
     */
    public function format(): mixed;
}