<?php declare(strict_types = 1);

namespace GameServerQuery;

/**
 * Class Buffer
 * @package GameServerQuery
 */
class Buffer
{
    const NUMBER_TYPE_BIG_ENDIAN    = 'be';
    const NUMBER_TYPE_LITTLE_ENDIAN = 'le';

    /**
     * Number type used to read integers.
     *
     * Default: Little Endian
     *
     * @var string
     */
    protected string $numberType = self::NUMBER_TYPE_LITTLE_ENDIAN;

    /**
     * The original data.
     *
     * @var string
     */
    protected string $data = '';

    /**
     * The original data length.
     *
     * @var int
     */
    protected int $length = 0;

    /**
     * Current position of the pointer.
     *
     * @var int
     */
    protected int $index = 0;

    /**
     * Buffer constructor.
     *
     * @param string $data
     * @param string $numberType
     */
    public function __construct(string $data, string $numberType = self::NUMBER_TYPE_LITTLE_ENDIAN)
    {
        $this->numberType = $numberType;
        $this->data       = $data;
        $this->length     = strlen($data);
    }

    /**
     * Convert buffer to big endian.
     *
     * @return $this
     */
    public function convertToBigEndian(): Buffer
    {
        $this->numberType = self::NUMBER_TYPE_BIG_ENDIAN;

        return $this;
    }

    /**
     * Returns data.
     *
     * @return string
     */
    public function getData(): string
    {
        return $this->data;
    }

    /**
     * Returns the current data in buffer.
     *
     * @return string
     */
    public function getBuffer(): string
    {
        return substr($this->data, $this->index);
    }

    /**
     * Returns the number of bytes in the buffer.
     *
     * @return int
     */
    public function getLength(): int
    {
        return max($this->length - $this->index, 0);
    }

    /**
     * Read data from the buffer.
     *
     * @param int $length
     *
     * @return string
     * @throws \Exception
     */
    public function read(int $length = 1): string
    {
        if (($length + $this->index) > $this->length) {
            throw new \Exception("Unable to read $length from buffer!");
        }

        $string      = substr($this->data, $this->index, $length);
        $this->index += $length;

        return $string;
    }

    /**
     * Read the last character from the buffer.
     *
     * Unlike the other `read()` function, this function actually removes
     * the character from the buffer.
     *
     * @return string
     */
    public function readLastCharacter(): string
    {
        $length       = strlen($this->data);
        $string       = $this->data[strlen($this->data) - 1];
        $this->data   = substr($this->data, 0, $length - 1);
        $this->length -= 1;

        return $string;
    }

    /**
     * Look at the buffer, but don't remove anything.
     *
     * @param int $length
     *
     * @return string
     */
    public function lookAhead(int $length = 1): string
    {
        return substr($this->data, $this->index, $length);
    }

    /**
     * Skip bytes in buffer.
     *
     * @param int $length
     */
    public function skip(int $length = 1): void
    {
        $this->index += $length;
    }

    /**
     * Jump to a given position in buffer.
     *
     * Note: This will not pass over the buffer length.
     *
     * @param int $index
     */
    public function jumpTo(int $index): void
    {
        $this->index = min($index, $this->length - 1);
    }

    /**
     * Get the current pointer position.
     *
     * @return int
     */
    public function getCurrentPosition(): int
    {
        return $this->index;
    }

    /**
     * Read from buffer until delimiter is reached.
     *
     * Note: If delimiter is not found, it will return all string.
     *
     * @param string $delimiter
     *
     * @return string
     * @throws \Exception
     */
    public function readString(string $delimiter = "\x00"): string
    {
        $length = strpos($this->data, $delimiter, min($this->index, $this->length));

        // If it is not found then return whole buffer.
        if (false === $length) {
            return $this->read(strlen($this->data) - $this->index);
        }

        // Read the string and remove the delimiter
        $string = $this->read($length - $this->index);
        ++$this->index;

        return $string;
    }

    /**
     * Reads a pascal string from the buffer.
     *
     * @param int  $offset
     * @param bool $readOffset
     *
     * @return string
     * @throws \Exception
     */
    public function readPascalString(int $offset = 0, bool $readOffset = false): string
    {
        $length = $this->readInt8();
        $offset = max($length - $offset, 0);

        if ($readOffset) {
            return $this->read($offset);
        }

        return substr($this->read($length), 0, $offset);
    }

    /**
     * Read an 8-bit unsigned integer.
     *
     * @return int
     * @throws \Exception
     */
    public function readInt8(): int
    {
        $int = unpack('Cint', $this->read());

        return $int['int'];
    }

    /**
     * Read and 8-bit signed integer.
     *
     * @return int
     * @throws \Exception
     */
    public function readInt8Signed(): int
    {
        $int = unpack('cint', $this->read());

        return $int['int'];
    }

    /**
     * Read a 16-bit unsigned integer.
     *
     * @return int
     * @throws \Exception
     */
    public function readInt16(): int
    {
        $type = match ($this->numberType) {
            self::NUMBER_TYPE_BIG_ENDIAN => 'nint',
            self::NUMBER_TYPE_LITTLE_ENDIAN => 'vint',
            default => 'Sint',
        };

        $int = unpack($type, $this->read(2));

        return $int['int'];
    }

    /**
     * Read a 16-bit signed integer.
     *
     * @return mixed
     * @throws \Exception
     */
    public function readInt16Signed(): int
    {
        $string = $this->read(2);

        // For big endian we need to reverse the bytes.
        if ($this->numberType === self::NUMBER_TYPE_BIG_ENDIAN) {
            $string = strrev($string);
        }

        $int = unpack('sint', $string);

        return $int['int'];
    }

    /**
     * Read a 32-bit unsigned integer.
     *
     * @return int
     * @throws \Exception
     */
    public function readInt32(): int
    {
        $type = match ($this->numberType) {
            self::NUMBER_TYPE_BIG_ENDIAN => 'Nint',
            self::NUMBER_TYPE_LITTLE_ENDIAN => 'Vint',
            default => 'Lint',
        };

        $int = unpack($type, $this->read(4));

        return $int['int'];
    }

    /**
     * Read a 32-bit signed integer.
     *
     * @return int
     * @throws \Exception
     */
    public function readInt32Signed(): int
    {
        $string = $this->read(4);

        // For big endian we need to reverse the bytes.
        if ($this->numberType === self::NUMBER_TYPE_BIG_ENDIAN) {
            $string = strrev($string);
        }

        $int = unpack('lint', $string);

        return $int['int'];
    }

    /**
     * Read a 64-bit unsigned integer.
     *
     * @return int
     * @throws \Exception
     */
    public function readInt64(): int
    {
        $type = match ($this->numberType) {
            self::NUMBER_TYPE_BIG_ENDIAN => 'Jint',
            self::NUMBER_TYPE_LITTLE_ENDIAN => 'Pint',
            default => 'Qint',
        };

        $int64 = unpack($type, $this->read(8));

        return $int64['int'];
    }

    /**
     * Read a 32-bit float.
     *
     * @return float
     * @throws \Exception
     */
    public function readFloat32(): float
    {
        $string = $this->read(4);

        // For big endian we need to reverse the bytes.
        if ($this->numberType === self::NUMBER_TYPE_BIG_ENDIAN) {
            $string = strrev($string);
        }

        $float = unpack('ffloat', $string);

        return $float['float'];
    }
}