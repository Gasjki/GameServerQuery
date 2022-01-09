<?php declare(strict_types = 1);

namespace GameServerQuery\Filter\Type\Common;

use GameServerQuery\Filter\AbstractFilter;

/**
 * Class UTF8Filter
 * @package GameServerQuery\Filter\Type\Common
 */
class UTF8Filter extends AbstractFilter
{
    /**
     * Filter method name.
     *
     * @var string
     */
    protected static string $filterName = 'convertTextToUtf8';

    /**
     * Convert existing text to UTF-8.
     *
     * @param string|null $text
     *
     * @return string|null
     */
    public function convertTextToUtf8(?string $text): ?string
    {
        if (!$text) {
            return $text;
        }

        $text = trim($text);
        $text = \preg_replace('/[\x00-\x1f]/', '', $text);

        return \mb_convert_encoding($text, 'UTF-8');
    }
}