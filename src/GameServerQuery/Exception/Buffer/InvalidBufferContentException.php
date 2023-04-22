<?php declare(strict_types = 1);

namespace GameServerQuery\Exception\Buffer;

class InvalidBufferContentException extends BufferException
{
    public function __construct(string $responseType, string $buffer)
    {
        parent::__construct(
            \sprintf(
                'Requested parser method for response \'%s\' does not exist for current protocol! Buffer received: %s',
                $responseType,
                $buffer
            )
        );
    }
}