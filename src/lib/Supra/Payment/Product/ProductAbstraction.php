<?php

namespace Supra\Payment\Product;

use Supra\Payment\Entity\Currency\Currency;
use \DateTime;

abstract class ProductAbstraction
{
	abstract public function getId();
	
	abstract public function getPrice($amount, Currency $currency, DateTime $when = null);
}
