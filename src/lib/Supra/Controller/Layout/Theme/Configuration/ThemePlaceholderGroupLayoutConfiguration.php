<?php

namespace Supra\Controller\Layout\Theme\Configuration;


class ThemePlaceholderGroupLayoutConfiguration extends ThemeConfigurationAbstraction
{
	/**
	 * @var string
	 */
	public $name;
	
	/**
	 * @var string
	 */
	public $title;
	
	/**
	 * @var string
	 */
	public $filename;
	
	/**
	 * @TODO: this option should be removed from layout configuration
	 * @var string
	 */
	public $iconHtml;
	
	/**
	 * @var \Supra\Controller\Pages\Entity\Theme\ThemePlaceholderGroupLayout
	 */
	protected $layout;
	
	
	/**
	 * @return \Supra\Controller\Pages\Entity\Theme\ThemePlaceholderGroupLayout
	 */
	public function getLayout()
	{
		return $this->layout;
	}
	
	/**
	 * 
	 */
	protected function readConfiguration()
	{
		$theme = $this->getTheme();

		$layouts = $theme->getPlaceholderGroupLayouts();

		$layout = null;

		if (empty($layouts[$this->name])) {
			$layout = new \Supra\Controller\Pages\Entity\Theme\ThemePlaceholderGroupLayout($this->name);
			$theme->addPlaceholderGroupLayout($layout);
		} else {
			$layout = $layouts[$this->name];
		}

		$layout->setTitle($this->title);
		$layout->setFilename($this->filename);
		$layout->setIconHtml($this->iconHtml);
		
		$this->layout = $layout;
	}
}
