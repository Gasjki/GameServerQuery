<?php declare(strict_types = 1);

namespace GameServerQuery\Filter;

use GameServerQuery\Interfaces\FilterInterface;
use GameServerQuery\Interfaces\ProtocolInterface;
use GameServerQuery\Result;

/**
 * Class AbstractFilter
 * @package GameServerQuery\Filter
 */
abstract class AbstractFilter implements FilterInterface
{
    /**
     * Filter method name.
     *
     * @var string
     */
    protected static string $filterMethodName = '';

    /**
     * Sections which must be parsed by the filter.
     *
     * @var array
     */
    protected array $sections;

    /**
     * Affected protocols by this filter.
     *
     * @var string[]|array
     */
    protected array $protocols;

    /**
     * AbstractFilter constructor.
     *
     * @param array $response
     * @param array $options
     *
     * @throws \LogicException
     */
    public function __construct(protected array $response, protected array $options = [])
    {
        if (!\method_exists($this, static::$filterMethodName)) {
            throw new \LogicException(
                \sprintf('Filter method "%s" does not exist!', static::$filterMethodName)
            );
        }

        $this->protocols = $options['protocols'] ?? [];
        $this->sections  = $options['sections'] ?? [];
    }

    /**
     * @inheritDoc
     */
    public function apply(): array
    {
        // Skip this step if there's no section provided.
        if (!\count($this->sections)) {
            return $this->response;
        }

        // Validate that current server supports allowed protocols.
        if ($this->protocols) {
            /** @var ProtocolInterface $serverProtocol */
            $serverProtocol     = $this->response[Result::GENERAL_CATEGORY][Result::GENERAL_APPLICATION_SUBCATEGORY];
            $supportedProtocols = \array_filter($this->protocols, static function($protocol) use ($serverProtocol) {
                return \is_subclass_of($serverProtocol, $protocol) || \is_a($serverProtocol, $protocol);
            });

            if (!\count($supportedProtocols)) {
                return $this->response;
            }
        }

        foreach ($this->sections as $section => $values) {
            if (null === $values) {
                continue; // Continue if our section is set as NULL.
            }

            // Check that we have current section present in server response.
            if (!\array_key_exists($section, $this->response)) {
                continue;
            }

            // Empty array provided. Filter all information for the current section.
            if (!\count($values)) {
                foreach ($this->response[$section] as $key => $value) {
                    $this->response[$section][$key] = $this->{static::$filterMethodName}($value);
                }

                continue;
            }

            foreach ($values as $value) {
                // This is created for players array because each player is kept under an array that  we need to parse it correctly.
                if (\count($this->response[$section]) > 0 && isset($this->response[$section][0]) && \is_array($this->response[$section][0])) {
                    foreach ($this->response[$section] as $key => $row) {
                        if (!\array_key_exists($value, $row)) {
                            throw new \InvalidArgumentException(
                                \sprintf("Invalid key '%s' provided for filter. Available keys: %s.", $value, \implode(', ', \array_keys($row)))
                            );
                        }

                        $this->response[$section][$key][$value] = $this->{static::$filterMethodName}($row[$value]);
                    }

                    continue;
                }

                if (!\array_key_exists($value, $this->response[$section])) {
                    continue;
                }

                $this->response[$section][$value] = $this->{static::$filterMethodName}($this->response[$section][$value]);
            }
        }

        // Clean memory.
        unset($this->sections, $this->protocols, $values);

        return $this->response;
    }
}