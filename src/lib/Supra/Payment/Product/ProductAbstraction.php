<?php

namespace Supra\Payment\Product;

use Supra\Payment\Entity\Currency\Currency;
use \DateTime;
use Supra\Locale\Locale;

interface ProductAbstraction
{

	public function getId();

	public function getProviderClass();

	public function getPrice($amount, Currency $currency, DateTime $when = null);
	
	public function getDescription(Locale $locale);
}
