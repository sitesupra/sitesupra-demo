<?php

namespace Supra\Controller\Layout\Theme\Configuration;


class ThemePlaceholderSetContainerConfiguration implements \Supra\Configuration\ConfigurationInterface
{
	/**
	 * Container name
	 * @var string
	 */
	public $name;
	
	public $title;
	
	public $allowedSets = array();
	
	public $default;
	
	
	public function configure() {}
}
