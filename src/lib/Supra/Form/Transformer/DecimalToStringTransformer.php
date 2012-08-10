<?php

namespace Supra\Form\Transformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * DecimalToStringTransformer
 */
class DecimalToStringTransformer implements DataTransformerInterface
{
    protected $precision;

	protected $ignoreSymbols;

	protected $decimalSeparatorSymbols;

    public function __construct($precision = null, $ignoreSymbols = null, $decimalSeparatorSymbols = null)
    {
        $this->precision = $precision;
		$this->ignoreSymbols = $ignoreSymbols;
		$this->decimalSeparatorSymbols = $decimalSeparatorSymbols;
    }

    /**
     * Transforms a number type into localized number.
     *
     * @param integer|float $value Number value.
     *
     * @return string Localized value.
     *
     * @throws UnexpectedTypeException if the given value is not numeric
     * @throws TransformationFailedException if the value can not be transformed
     */
    public function transform($value)
    {
        if (null === $value) {
            return '';
        }

        if ( ! is_numeric($value)) {
            throw new UnexpectedTypeException($value, 'numeric');
        }

        $formatter = $this->getNumberFormatter();
        $value = $formatter->format($value);

        if (intl_is_failure($formatter->getErrorCode())) {
            throw new TransformationFailedException($formatter->getErrorMessage());
        }

        return $value;
    }

    /**
     * Transforms a localized number into an integer or float
     *
     * @param string $value The localized value
     *
     * @return integer|float The numeric value
     *
     * @throws UnexpectedTypeException if the given value is not a string
     * @throws TransformationFailedException if the value can not be transformed
     */
    public function reverseTransform($value)
    {
        if (!is_string($value)) {
            throw new UnexpectedTypeException($value, 'string');
        }

        if ('' === $value) {
            return null;
        }

        if ('NaN' === $value) {
            throw new TransformationFailedException('"NaN" is not a valid number');
        }

		// Remove any whitespace
		$value = str_replace(array("\xC2\xA0", ' ', "\t", "\n", "\r"), '', $value);
		
		if ($this->ignoreSymbols != '') {
			$value = str_replace(str_split($this->ignoreSymbols), array(''), $value);
		}

		if ($this->decimalSeparatorSymbols != '') {
			$value = str_replace(str_split($this->decimalSeparatorSymbols), '.', $value);
		}

		if ( ! is_numeric($value)) {
			throw new TransformationFailedException("Input isn't numeric");
		}

		// Using root locale
        $formatter = $this->getNumberFormatter('en_US');

        $value = $formatter->parse($value);

        if (intl_is_failure($formatter->getErrorCode())) {
			$message = $formatter->getErrorMessage();
            throw new TransformationFailedException($formatter->getErrorMessage());
        }

        if ($value >= INF || $value <= -INF) {
            throw new TransformationFailedException('I don\'t have a clear idea what infinity looks like');
        }

		$value = round($value, $this->precision);

        return $value;
    }

    /**
     * Returns a preconfigured \NumberFormatter instance
     *
     * @return \NumberFormatter
     */
    protected function getNumberFormatter($locale = null)
    {
		if (is_null($locale)) {
			$locale = \Locale::getDefault();
		}

        $formatter = new \NumberFormatter($locale, \NumberFormatter::DECIMAL);

        if (null !== $this->precision) {
            $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, $this->precision);
            $formatter->setAttribute(\NumberFormatter::ROUNDING_MODE, \NumberFormatter::ROUND_HALFUP);
        }

        $formatter->setAttribute(\NumberFormatter::GROUPING_USED, true);

        return $formatter;
    }
}
