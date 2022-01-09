<?php declare(strict_types = 1);

namespace GameServerQuery\Formatter\Types;

use GameServerQuery\Formatter\AbstractFormatter;

/**
 * Class JSONFormatter
 * @package GameServerQuery\Formatter\Types
 */
class JSONFormatter extends AbstractFormatter
{
    /**
     * JSON error messages.
     *
     * @var array
     */
    private static array $messages = [
        JSON_ERROR_NONE           => 'No error has occurred.',
        JSON_ERROR_DEPTH          => 'The maximum stack depth has been exceeded.',
        JSON_ERROR_STATE_MISMATCH => 'Invalid or malformed JSON.',
        JSON_ERROR_CTRL_CHAR      => 'Control character error, possibly incorrectly encoded.',
        JSON_ERROR_SYNTAX         => 'Syntax error.',
        JSON_ERROR_UTF8           => 'Malformed UTF-8 characters, possibly incorrectly encoded.'
    ];

    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function format(): string
    {
        if (!$result = \json_encode($this->response)) {
            throw new \Exception(self::$messages[\json_last_error()]);
        }

        return $result;
    }
}