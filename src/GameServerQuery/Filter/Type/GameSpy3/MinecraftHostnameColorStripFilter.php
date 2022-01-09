<?php declare(strict_types = 1);

namespace GameServerQuery\Filter\Type\GameSpy3;

use GameServerQuery\Filter\AbstractFilter;

/**
 * Class MinecraftHostnameColorStripFilter
 * @package GameServerQuery\Filter\Type\GameSpy3
 */
class MinecraftHostnameColorStripFilter extends AbstractFilter
{
    /**
     * Filter method name.
     *
     * @var string
     */
    protected static string $filterName = 'stripColors';

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

        $text = \preg_replace('/\x1b.../', '', $text);
        $text = \preg_replace('/\xa7./', '', $text);
        $text = \filter_var($text, FILTER_SANITIZE_SPECIAL_CHARS, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $text = \preg_replace('/\s\s+/', ' ', $text);

        return \mb_convert_encoding(trim($text), 'UTF-8');
    }
}