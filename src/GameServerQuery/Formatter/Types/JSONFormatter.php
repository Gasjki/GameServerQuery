<?php declare(strict_types = 1);

namespace GameServerQuery\Formatter\Types;

use GameServerQuery\Exception\Formatter\FormatterException;
use GameServerQuery\Formatter\AbstractFormatter;

/**
 * Class JSONFormatter
 * @package GameServerQuery\Formatter\Types
 */
class JSONFormatter extends AbstractFormatter
{
    /**
     * @inheritDoc
     * @throws FormatterException
     */
    public function format(): string
    {
        try {
            return \json_encode($this->response, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new FormatterException($exception->getMessage(), previous: $exception);
        }
    }
}