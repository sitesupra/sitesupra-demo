<?php

namespace Supra\Controller\Layout\Theme\Configuration\Parameter;

use Supra\Controller\Layout\Theme\Configuration\ThemeConfigurationAbstraction;

class GroupConfiguration extends ThemeConfigurationAbstraction
{
	
	/**
	 * @var string
	 */
	public $id;

	/**
	 * @var string
	 */
	public $label;

	/**
	 * @var array
	 */
	public $parameters = array();
	
	/**
	 * @var string
	 */
	public $visibleFor = 'all';
	
	/**
	 * 
	 */
	public function readConfiguration()
	{
		if (empty($this->parameters)) {
			throw new \RuntimeException('Parameter group should have at least one parameter inside');
		}
	}

}
