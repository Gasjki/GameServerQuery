<?php declare(strict_types = 1);

namespace GameServerQuery\Tests;

use GameServerQuery\Buffer;
use PHPUnit\Framework\TestCase;

final class BufferTest extends TestCase
{
    private const GAME_SERVER_QUERY = 'GameServerQuery';

    public static function initClass(string $data = self::GAME_SERVER_QUERY, string $numberType = Buffer::NUMBER_TYPE_LITTLE_ENDIAN): Buffer
    {
        return new Buffer($data, $numberType);
    }

    public function testInit(): void
    {
        $buffer = self::initClass();

        $this->assertInstanceOf(Buffer::class, $buffer);
        $this->assertSame(0, $buffer->getCurrentPosition());
    }

    public function testBufferAndLength(): void
    {
        $buffer = self::initClass();

        $this->assertSame(\mb_strlen(self::GAME_SERVER_QUERY), $buffer->getLength());
        $this->assertSame(self::GAME_SERVER_QUERY, $buffer->getBuffer());
        $this->assertSame(self::GAME_SERVER_QUERY, $buffer->getData());
    }

    public function testRead(): void
    {
        $buffer = self::initClass();

        $this->assertSame('G', $buffer->read(1));
        $this->assertSame('ame', $buffer->read(3));
        $this->assertSame('y', $buffer->readLastCharacter());

        $buffer->reset(); // Reset buffer index.
        $this->assertSame(71, $buffer->readInt8());
        $this->assertSame(97, $buffer->readInt8Signed());
        $this->assertSame(25965, $buffer->readInt16());
        $this->assertSame(25939, $buffer->readInt16Signed());
        $this->assertSame(1919252082, $buffer->readInt32());
        $this->assertSame(1919251793, $buffer->readInt32Signed());

        $buffer->reset(); // Reset buffer index.
        $this->assertSame(8534995652678869319, $buffer->readInt64());

        $buffer->reset(); // Reset buffer index.

        $this->assertSame('7.00622066', substr((string) $buffer->readFloat32(), 0, 10));
        $this->assertSame(4, $buffer->getCurrentPosition());
        $buffer->reset();
        $this->assertSame(0, $buffer->getCurrentPosition());
    }

    public function testSkipLookAhead(): void
    {
        $buffer = self::initClass();

        $this->assertSame('G', $buffer->read(1));
        $buffer->skip(1);
        $this->assertSame('meS', $buffer->lookAhead(3));
    }

    public function testJumpTo(): void
    {
        $buffer = self::initClass();

        $this->assertSame('G', $buffer->read(1));
        $buffer->jumpTo(4);
        $this->assertSame('Server', $buffer->read(6));
    }

    public function testReadString(): void
    {
        $buffer = self::initClass();

        $this->assertSame('Game', $buffer->readString('S'));
    }
}