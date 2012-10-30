<?php

namespace Supra\Payment\Product;

use Supra\Payment\Entity\Currency\Currency;
use \DateTime;
use Supra\Locale\LocaleInterface;

interface ProductAbstraction
{

	public function getId();

	public function getProviderClass();

	public function getPrice($quantity, Currency $currency, DateTime $when = null);
	
	public function getDescription(LocaleInterface $locale);
}
