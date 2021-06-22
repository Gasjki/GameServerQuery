<?php declare(strict_types = 1);

namespace GameServerQuery\Formatter\Types;

use GameServerQuery\Formatter\AbstractFormatter;

/**
 * Class ArrayFormatter
 * @package GameServerQuery\Formatter\Types
 */
class ArrayFormatter extends AbstractFormatter
{
    /**
     * @inheritDoc
     */
    public function format(): array
    {
        return $this->response;
    }
}