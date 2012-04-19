<?php

namespace Supra\Payment\Product;

use Supra\Payment\Entity\Currency\Currency;
use \DateTime;
use Supra\Locale\Locale;

abstract class ProductAbstraction
{

	abstract public function getId();

	abstract public function getProviderClass();

	abstract public function getPrice($amount, Currency $currency, DateTime $when = null);
	
	abstract public function getDescription(Locale $locale);
}
