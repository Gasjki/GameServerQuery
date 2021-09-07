<?php declare(strict_types = 1);

namespace GameServerQuery;

/**
 * Class Result
 * @package GameServerQuery
 */
class Result
{
    // Category.
    public const GENERAL_CATEGORY = 'general';
    public const PLAYERS_CATEGORY = 'players';
    public const RULES_CATEGORY   = 'rules';

    // General - subcategories.
    public const GENERAL_ACTIVE_SUBCATEGORY         = 'active';
    public const GENERAL_HOSTNAME_SUBCATEGORY       = 'hostname';
    public const GENERAL_MAP_SUBCATEGORY            = 'map';
    public const GENERAL_VERSION_SUBCATEGORY        = 'version';
    public const GENERAL_BOTS_SUBCATEGORY           = 'bots';
    public const GENERAL_DEDICATED_SUBCATEGORY      = 'dedicated';
    public const GENERAL_OS_SUBCATEGORY             = 'os';
    public const GENERAL_SLOTS_SUBCATEGORY          = 'slots';
    public const GENERAL_ONLINE_PLAYERS_SUBCATEGORY = 'online_players';
    public const GENERAL_PASSWORD_SUBCATEGORY       = 'password';

    // Players - subcategories.
    public const PLAYERS_NAME_SUBCATEGORY        = 'name';
    public const PLAYERS_SCORE_SUBCATEGORY       = 'score';
    public const PLAYERS_ONLINE_TIME_SUBCATEGORY = 'online_time';

    // Rules - subcategories.
    public const RULES_NAME_SUBCATEGORY  = 'name';
    public const RULES_VALUE_SUBCATEGORY = 'value';

    // Categories list.
    public const RESULT_CATEGORIES = [
        self::GENERAL_CATEGORY,
        self::PLAYERS_CATEGORY,
        self::RULES_CATEGORY,
    ];

    // GENERAL - subcategories list.
    public const GENERAL_SUBCATEGORY_LIST = [
        self::GENERAL_ACTIVE_SUBCATEGORY,
        self::GENERAL_HOSTNAME_SUBCATEGORY,
        self::GENERAL_MAP_SUBCATEGORY,
        self::GENERAL_VERSION_SUBCATEGORY,
        self::GENERAL_BOTS_SUBCATEGORY,
        self::GENERAL_DEDICATED_SUBCATEGORY,
        self::GENERAL_OS_SUBCATEGORY,
        self::GENERAL_SLOTS_SUBCATEGORY,
        self::GENERAL_ONLINE_PLAYERS_SUBCATEGORY,
        self::GENERAL_PASSWORD_SUBCATEGORY,
    ];

    // PLAYERS - subcategories list.
    public const PLAYERS_SUBCATEGORY_LIST = [
        self::PLAYERS_NAME_SUBCATEGORY,
        self::PLAYERS_SCORE_SUBCATEGORY,
        self::PLAYERS_ONLINE_TIME_SUBCATEGORY,
    ];

    // RULES - subcategories list.
    public const RULES_SUBCATEGORY_LIST = [
        self::RULES_NAME_SUBCATEGORY,
        self::RULES_VALUE_SUBCATEGORY
    ];

    /**
     * Result constructor.
     *
     * @param Result|array $result
     */
    public function __construct(protected self|array $result = [])
    {
        if ($this->result instanceof self) {
            $this->result = $this->result->getResult();
        }

        if (!count($this->result)) {
            $this->addAllSections(); // Add all sections by default.

            $this->addInformation(Result::GENERAL_ACTIVE_SUBCATEGORY, false);
            $this->addInformation(Result::GENERAL_HOSTNAME_SUBCATEGORY, null);
            $this->addInformation(Result::GENERAL_MAP_SUBCATEGORY, null);
            $this->addInformation(Result::GENERAL_ONLINE_PLAYERS_SUBCATEGORY, 0);
            $this->addInformation(Result::GENERAL_SLOTS_SUBCATEGORY, 0);
            $this->addInformation(Result::GENERAL_VERSION_SUBCATEGORY, null);
            $this->addInformation(Result::GENERAL_BOTS_SUBCATEGORY, 0);
            $this->addInformation(Result::GENERAL_DEDICATED_SUBCATEGORY, null);
            $this->addInformation(Result::GENERAL_OS_SUBCATEGORY, null);
            $this->addInformation(Result::GENERAL_PASSWORD_SUBCATEGORY, false);
        }
    }

    /**
     * Add all available categories.
     *
     * @return $this
     */
    public function addAllSections(): Result
    {
        foreach (self::RESULT_CATEGORIES as $section) {
            $this->addSection($section);
        }

        return $this;
    }

    /**
     * Add new section.
     *
     * @param string $name
     *
     * @return $this
     */
    public function addSection(string $name): Result
    {
        if (array_key_exists($name, $this->result)) {
            return $this;
        }

        if (!$this->isValidSection($name)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid section name. Available sections: %s.', implode(', ', self::RESULT_CATEGORIES))
            );
        }

        $this->result[$name] = [];

        return $this;
    }

    /**
     * Add server information.
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return $this
     */
    public function addInformation(string $name, mixed $value = null): Result
    {
        // Add section if not exists.
        $this->addSection(self::GENERAL_CATEGORY);


        $this->result[self::GENERAL_CATEGORY][$name] = $value;

        return $this;
    }

    /**
     * Returns specific server information.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getInformation(string $name): mixed
    {
        if (!array_key_exists($name, $this->result[self::GENERAL_CATEGORY])) {
            throw new \InvalidArgumentException(
                sprintf('[ERROR] %s was not found. Available keys: %s', $name, implode(', ', array_keys($this->result[self::GENERAL_CATEGORY])))
            );
        }

        return $this->result[self::GENERAL_CATEGORY][$name];
    }

    /**
     * Add player to server.
     *
     * @param string     $name
     * @param int        $score
     * @param float|null $time
     *
     * @return $this
     */
    public function addPlayer(string $name, int $score = 0, ?float $time = null): Result
    {
        // Add section if not exists.
        $this->addSection(self::PLAYERS_CATEGORY);

        if (array_key_exists($name, $this->result[self::PLAYERS_CATEGORY])) {
            return $this;
        }

        if (empty(trim($name))) {
            $name  = null; // Player is in the process of connection.
            $score = 0;
            $time  = 0;
        }

        $this->result[self::PLAYERS_CATEGORY][] = [
            self::PLAYERS_NAME_SUBCATEGORY        => $name,
            self::PLAYERS_SCORE_SUBCATEGORY       => $score,
            self::PLAYERS_ONLINE_TIME_SUBCATEGORY => $time,
        ];

        return $this;
    }

    /**
     * Get specific player from server.
     *
     * @param string $name
     *
     * @return array
     */
    public function getPlayer(string $name): array
    {
        $key = array_search($name, array_column($this->result[self::PLAYERS_CATEGORY], self::PLAYERS_NAME_SUBCATEGORY));

        if (!$key) {
            return [];
        }

        return $this->result[self::PLAYERS_CATEGORY][$name];
    }

    /**
     * @inheritDoc
     */
    public function addRule(string $key, mixed $value): Result
    {
        // Add section if not exists.
        $this->addSection(self::RULES_CATEGORY);

        $this->result[self::RULES_CATEGORY][$key] = $value;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getRule(string $key): mixed
    {
        if (!array_key_exists($key, $this->result[self::RULES_CATEGORY])) {
            return null;
        }

        return $this->result[self::RULES_CATEGORY][$key];
    }

    /**
     * Check if result has a specific rule.
     *
     * @param string $key
     *
     * @return bool
     */
    public function hasRule(string $key): bool
    {
        return array_key_exists($key, $this->result[self::RULES_CATEGORY]);
    }

    /**
     * Check if given section name is valid and can be used.
     *
     * @param string $name
     *
     * @return bool
     */
    protected function isValidSection(string $name): bool
    {
        return in_array($name, self::RESULT_CATEGORIES);
    }

    /**
     * Returns result array.
     *
     * @return array
     */
    public function getResult(): array
    {
        return $this->result;
    }
}