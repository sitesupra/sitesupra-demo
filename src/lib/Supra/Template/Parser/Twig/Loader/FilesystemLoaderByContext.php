<?php

namespace Supra\Template\Parser\Twig\Loader;

use Twig_Loader_Filesystem;
use Supra\Loader\Loader;

/**
 * Filesystem template loader by context object
 */
class FilesystemLoaderByContext extends Twig_Loader_Filesystem
{
	/**
	 * @param mixed $context
	 * @throws \InvalidArgumentException
	 */
	public function __construct($context = null, $loader = null)
	{
		if (is_null($context)) {
			return;
		}
		
		if (is_object($context)) {
			$context = get_class($context);
		}
		
		if ( ! is_string($context)) {
			throw new \InvalidArgumentException("Caller must be object or string");
		}
                
		//$classPath = Loader::getInstance()->findClassPath($context);
                $classPath = \ComposerAutoloaderInitSupra::getLoader()->findFile($context);
		
		if (empty($classPath)) {
			throw new \InvalidArgumentException("Caller class '$context' path was not found by autoloader");
		}
		
		$classPath = dirname($classPath);
		$this->setTemplatePath($classPath);

		// Add paths from the parent loader as well
		if ($loader instanceof Twig_Loader_Filesystem) {
			$paths = $loader->getPaths();
			foreach ($paths as $path) {
				$this->addPath($path);
			}
		}
	}
	
	/**
	 * Set template path, will make it relative to supra path for Twig usage
	 * @param string $templatePath
	 * @throws \RuntimeException if template path is outside the supra path
	 */
	private function setTemplatePath($templatePath)
	{
		$supraPath = realpath(SUPRA_PATH) . DIRECTORY_SEPARATOR;
		$templatePath = realpath($templatePath);
		
		$this->setPaths($templatePath);
	}

}
