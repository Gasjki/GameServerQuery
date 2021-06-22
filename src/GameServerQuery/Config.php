<?php declare(strict_types = 1);

namespace GameServerQuery;

/**
 * Class Config
 * @package GameServerQuery
 */
class Config
{
    /**
     * Default options of GameServerQuery.
     *
     * Note: Do not change. Use `$configs` to configure your script.
     */
    protected const DEFAULT_CONFIGS = [
        'timeout'        => 3, // seconds,
        'stream_timeout' => 200000, // See http://www.php.net/manual/en/function.stream-select.php
        'write_wait'     => 500, // milliseconds
        'extra_configs'  => []
    ];

    /**
     * Script configuration.
     *
     * @var array
     */
    protected array $configs = self::DEFAULT_CONFIGS;

    /**
     * Set timeout time.
     *
     * @param int $timeout
     *
     * @return $this
     */
    public function timeout(int $timeout = 3): Config
    {
        if ($timeout < 0) {
            throw new \InvalidArgumentException('Your timeout value is too low. You need to set a value equal or higher than 0.');
        }

        $this->configs['timeout'] = $timeout;

        return $this;
    }

    /**
     * Set stream timeout time.
     *
     * @param int $streamTimeout
     *
     * @return $this
     */
    public function streamTimeout(int $streamTimeout = 200000): Config
    {
        if ($streamTimeout < 0) {
            throw new \InvalidArgumentException('Your stream timeout value is too low. You need to set a value equal or higher than 0, but the recommended value is 200000.');
        }

        $this->configs['stream_timeout'] = $streamTimeout;

        return $this;
    }

    /**
     * Set write wait time.
     *
     * @param int $writeWait
     *
     * @return $this
     */
    public function writeWait(int $writeWait = 500): Config
    {
        if ($writeWait < 0) {
            throw new \InvalidArgumentException('Your write wait value is too low. You need to set a value equal or higher than 0, but the recommended value is 500 (ms).');
        }

        $this->configs['write_wait'] = $writeWait;

        return $this;
    }

    /**
     * Set extra configs.
     *
     * @param array $extraConfigs
     *
     * @return $this
     */
    public function extraConfigs(array $extraConfigs): Config
    {
        $this->configs['extra_configs'] = $extraConfigs;

        return $this;
    }

    /**
     * Returns configuration value by key.
     *
     * @param string     $key
     * @param mixed|null $default
     *
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (!array_key_exists($key, $this->configs)) {
            return $default;
        }

        return $this->configs[$key];
    }
}