<?php

namespace Supra\Controller\Layout\Theme\Configuration;


class ThemePlaceholderSetConfiguration implements \Supra\Configuration\ConfigurationInterface
{
	/**
	 * Group name
	 * @var string
	 */
	public $name;
	
	/**
	 * Group title
	 * @var string
	 */
	public $title;
	
	/**
	 * Layout filename
	 * @var string
	 */
	public $layout;
	
	
	public function configure() {}
}
