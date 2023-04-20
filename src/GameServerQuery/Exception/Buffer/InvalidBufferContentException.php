<?php declare(strict_types = 1);

namespace GameServerQuery\Exception\Buffer;

class InvalidBufferContentException extends BufferException
{
    public function __construct(private string $responseType, private string $buffer)
    {
        parent::__construct(\sprintf('Requested parser method for response \'%s\' does not exist for current protocol!', $this->responseType));
    }

    /**
     * @return string
     */
    public function getResponseType(): string
    {
        return $this->responseType;
    }

    /**
     * @return string
     */
    public function getBuffer(): string
    {
        return $this->buffer;
    }
}