<?php declare(strict_types = 1);

namespace GameServerQuery\Filter\Type\Common;

use GameServerQuery\Filter\AbstractFilter;

/**
 * Class ColorFilter
 * @package GameServerQuery\Filter\Type\Common
 */
class ColorStripFilter extends AbstractFilter
{
    /**
     * Filter method name.
     *
     * @var string
     */
    protected static string $filterMethodName = 'stripColors';

    /**
     * Strip colors.
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