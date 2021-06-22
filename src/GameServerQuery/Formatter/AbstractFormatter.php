<?php declare(strict_types = 1);

namespace GameServerQuery\Formatter;

use GameServerQuery\Interfaces\FormatterInterface;

/**
 * Class AbstractFormatter
 * @package GameServerQuery\Formatter
 */
abstract class AbstractFormatter implements FormatterInterface
{
    /**
     * AbstractFormatter constructor.
     *
     * @param array $response
     */
    public function __construct(protected array $response)
    {
    }
}