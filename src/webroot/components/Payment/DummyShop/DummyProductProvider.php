<?php

namespace Project\Payment\DummyShop;

use Supra\Payment\Product\ProductProviderAbstraction;

class DummyProductProvider extends ProductProviderAbstraction
{
	/**
	 * @param type $id
	 * @return DummyProduct 
	 */
	public function getById($id)
	{
		return new DummyProduct($id);
	}

}
