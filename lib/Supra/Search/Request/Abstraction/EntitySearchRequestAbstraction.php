<?php

namespace Supra\Search\Request\Abstraction;

use Supra\Search\Request\SearchRequestInterface;

abstract class EntitySearchRequestAbstraction extends SearchRequestAbstraction
{

	function __construct($class)
	{
		$this->addSimpleFilter('class', $class);
	}

}
