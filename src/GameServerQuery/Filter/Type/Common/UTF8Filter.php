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
    protected static string $filterMethodName = 'convertTextToUtf8';

    /**
     * Convert existing text to UTF-8.
     *
     * @param mixed $text
     *
     * @return mixed
     */
    public function convertTextToUtf8(mixed $text): mixed
    {
        if (!is_string($text)) {
            return $text;
        }

        $text = \preg_replace('/[\x00-\x1f]/', '', $text);
        $text = trim($text);

        return \mb_convert_encoding($text, 'UTF-8');
    }
}