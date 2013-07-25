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
     * @var string
     */
    public $icon = '';
    
    /**
     * @var string
     */
    public $buttonStyle = '';
	
	/**
	 * @var array
	 */
	public $parameters = array();
	
	/**
	 * @var string
	 */
	public $visibleFor = 'all';
	
	/**
	 * Contains the JS element selector value which will define,
	 * which element should be highlighted when you hover parameter group
	 * @var string
	 */
	public $highlightElement;
	
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
