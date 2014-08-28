<?php

namespace Supra\Template\Parser\Twig;

use Twig_Environment;
use Twig_LoaderInterface;
use Supra\Template\Parser\TemplateParser;

/**
 * Twig environment override
 */
class Twig extends Twig_Environment implements TemplateParser
{
	private function transactional(\Closure $closure, Twig_LoaderInterface $loader = null)
	{
		$e = null;
		$contents = null;
		$oldLoader = null;
		
		if ( ! is_null($loader)) {
			if ($this->loader !== null) {
				$oldLoader = $this->getLoader();
			}

			$this->setLoader($loader);
		}

		try {
			$contents = $closure($this);
		} catch (\Exception $e) {}

		if ( ! is_null($oldLoader)) {
			$this->setLoader($oldLoader);
		}

		if ( ! empty($e)) {
			throw $e;
		}

		return $contents;
	}
	
	/**
	 * Can pass loader to use, the old loader is backed up and restored afterwards
	 * @param string $templateName
	 * @param array $templateParameters
	 * @param Twig_LoaderInterface $loader
	 * @return string
	 */
	public function parseTemplate($templateName, array $templateParameters = array(), Twig_LoaderInterface $loader = null)
	{
            $templateName = $this->fixPreComposerEnvironment($templateName, $templateParameters, $loader);
            
            $closure = function(Twig $self) use ($templateName, $templateParameters) {
                    $template = $self->loadTemplate($templateName);
                    $contents = $template->render($templateParameters);

                    return $contents;
            };

            $contents = $this->transactional($closure, $loader);

            return $contents;
	}
	
	/**
	 * Returns template filename if uses filesystem loader
	 * @param string $templateName
	 * @param Twig_LoaderInterface $loader
	 * @return string
	 */
	public function getTemplateFilename($templateName, Twig_LoaderInterface $loader = null)
	{
            $templateName = $this->fixPreComposerEnvironment($templateName, array(), $loader);
            
            $closure = function(Twig $self) use ($templateName) {
                    $loader = $self->getLoader();

                    if ( ! $loader instanceof \Twig_Loader_Filesystem) {
                            return;
                    }

                    $filename = $loader->getCacheKey($templateName);

                    return $filename;
            };

            $filename = $this->transactional($closure, $loader);

            return $filename;
	}
        
        protected function fixPreComposerEnvironment($name, $params, Twig_LoaderInterface $loader = null)
        {
            //workaround to load template templates from webroot
            if (is_null($loader)) {
                $loader = $this->getLoader();
            }
                
            if ($loader instanceof \Twig_LoaderInterface &&
                    method_exists($loader, 'addPath') &&
                    method_exists($loader, 'getPaths')
                    ) {
                $path = SUPRA_WEBROOT_PATH . 'cms';

                if (!in_array($path, $loader->getPaths())) {
                    $loader->addPath($path);
                }
            }
            
            //workaround to load CmsAction tempaltes
            if (isset($params['action'])) {
                $action = $params['action'];
                
                $class = get_class($action);
                
                if (strpos($class, 'Supra\Cms') !== false) {
                    $name = explode('/', $name);
                    
                    $name = array_map(function ($value) {
                        if (strpos($value, '.html.twig') === false) {
                            $value = ucfirst($value);
                        }
                        
                        return $value;
                    }, $name);
                    
                    $name = implode('/', $name);
                }
            }
            
            return $name;
        }

	/**
	 * Overriden just to set file permission mode
	 * @param string $file
	 * @param string $content
	 */
	protected function writeCacheFile($file, $content)
	{
		parent::writeCacheFile($file, $content);

		chmod($file, SITESUPRA_FILE_PERMISSION_MODE);
	}

}
