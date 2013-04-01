<?php

namespace Supra\Controller\Layout\Theme\Configuration;

use Supra\Configuration\Exception;

/**
 * @TODO: this class relies on order inside the theme conf file
 *		must be fixed
 */
class ThemeIconSetConfiguration extends ThemeConfigurationAbstraction
{
	/**
	 * @var string
	 */
	public $path;
	
	/**
	 * @var array
	 */
	public $icons = array();
	
	/**
	 * 
	 */
	public function readConfiguration()
	{
		$configuration = $this->loader->getThemeConfiguration();
		
		if ( ! $configuration instanceof ThemeConfiguration) {
			throw new Exception\InvalidConfiguration("Wrong ThemeConfiguration object is received from theme configuration loader");
		}
		
		if (empty($this->path)) {
			throw new Exception\InvalidConfiguration("Path must not be empty");
		}
				
		foreach ($this->icons as $setName => $icons) {
			if (empty($icons) || ! is_array($icons)) {
				throw new Exception\InvalidConfiguration("Icon group {$setName} is empty");
			}
			
			foreach ($icons as $iconId) {
				$path = $this->getIconSvnFilePath($iconId);
				if ( ! file_exists($path)) {
//					throw new Exception\InvalidConfiguration("Icon {$iconId} is missing the SVG file (or file is not accessible)");
				}
			}
		}
		
		$configuration->setIconConfiguration($this);
	}
	
	/**
	 * 
	 */
	public function fetchConfiguration()
	{
		$configuration = $this->loader->getThemeConfiguration();
		
		if ( ! $configuration instanceof ThemeConfiguration) {
			throw new Exception\InvalidConfiguration("Wrong ThemeConfiguration object is received from theme configuration loader");
		}
		
		$configuration->setIconConfiguration($this);
	}
	
	/**
	 * @param string $iconId
	 * @return boolean
	 */
	public function isIconDefined($iconId)
	{
		foreach ($this->icons as $iconSet) {
			if (in_array($iconId, $iconSet)) {
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * @param string $iconId
	 */
	public function getIconSvgContent($iconId)
	{
		if ($this->isIconDefined($iconId)) {
			$path = $this->getIconSvnFilePath($iconId);
			if ( ! file_exists($path)) {
				throw new Exception\InvalidConfiguration("Icon {$iconId} is missing the SVG file (or file is not accessible)");
			}
			
			$content = file_get_contents($path);
			return $content;
		}
		
		return null;
	}
	
	/**
	 * @param string $iconId
	 * @return string
	 */
	private function getIconSvnFilePath($iconId)
	{
		return SUPRA_WEBROOT_PATH . $this->path . DIRECTORY_SEPARATOR . 'svg'
				. DIRECTORY_SEPARATOR . $iconId . '.svg';
	}
	
}
