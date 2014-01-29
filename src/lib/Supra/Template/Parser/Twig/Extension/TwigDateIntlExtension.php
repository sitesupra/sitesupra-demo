<?php

namespace Supra\Template\Parser\Twig\Extension;

use \IntlDateFormatter;

/**
 * 
 */
class TwigDateIntlExtension extends \Twig_Extension
{
    public function __construct()
    {
        if ( ! class_exists('IntlDateFormatter')) {
            throw new \RuntimeException('The intl extension is needed to use intl-based filters.');
        }
    }

    public function getFilters()
    {
        return array(
            'dateintl' => new \Twig_Filter_Function('\Supra\Template\Parser\Twig\Extension\twig_localized_date_pattern_filter'),
        );
    }

    public function getName()
    {
        return 'dateintl';
    }
}

function twig_localized_date_pattern_filter($date, $pattern, $locale = null)
{
	$formatter = IntlDateFormatter::create(
		$locale !== null ? $locale : \Locale::getDefault(),
		IntlDateFormatter::NONE,
		IntlDateFormatter::NONE,
		date_default_timezone_get()
	);

	$formatter->setPattern($pattern);

    if ( ! $date instanceof \DateTime) {
        if (\ctype_digit((string) $date)) {
            $date = new \DateTime('@'.$date);
            $date->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        } else {
            $date = new \DateTime($date);
        }
    }

    return $formatter->format($date->getTimestamp());
}