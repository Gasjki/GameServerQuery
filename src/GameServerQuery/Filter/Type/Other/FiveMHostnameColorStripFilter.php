<?php declare(strict_types = 1);

namespace GameServerQuery\Filter\Type\Other;

use GameServerQuery\Filter\AbstractFilter;

/**
 * Class FiveMHostnameColorStripFilter
 * @package GameServerQuery\Filter\Type\Other
 */
class FiveMHostnameColorStripFilter extends AbstractFilter
{
    /**
     * Filter method name.
     *
     * @var string
     */
    protected static string $filterMethodName = 'stripColors';

    /**
     * Convert existing text to UTF-8.
     *
     * @param string|null $text
     *
     * @return string|null
     */
    public function stripColors(?string $text): ?string
    {
        if (!$text) {
            return $text;
        }

        $text = \preg_replace('/\^\d/', '', $text);

        return \trim($text);
    }
}