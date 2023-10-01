<?php declare(strict_types = 1);

namespace GameServerQuery\Exception\Buffer;

class InvalidBufferContentException extends BufferException
{
    public function __construct(string $responseType, private array $packets)
    {
        parent::__construct(
            \sprintf(
                'Requested parser method for response \'%s\' does not exist for current protocol!',
                $responseType,
            )
        );
    }

    /**
     * @return array
     */
    public function getPackets(): array
    {
        return $this->packets;
    }
}