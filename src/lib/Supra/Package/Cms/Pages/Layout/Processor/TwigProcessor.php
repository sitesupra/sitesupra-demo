<?php

namespace Supra\Package\Cms\Pages\Layout\Processor;

use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Core\Templating\Loader\TemplateLoader;
use Supra\Package\Cms\Pages\Response\PageResponse;

/**
 * Twig layout processor
 */
class TwigProcessor implements ProcessorInterface, ContainerAware
{
	/**
	 * @inheritDoc
	 */
	public function process(PageResponse $response, array $placeResponses, $layoutSrc)
	{
		$twig = $this->createTwigEnvironment();

		$twig->addFunction(new \Twig_SimpleFunction('placeHolder', function($name) use ($placeResponses) {
			if (isset($placeResponses[$name])) {
				echo $placeResponses[$name];
			}
		}));

		$template = $twig->loadTemplate($layoutSrc);

		$response->setContent(
				$template->render(array())
		);
	}

	/**
	 * @inheritDoc
	 */
	public function getPlaces($layoutSrc)
	{
		$twig = $this->createTwigEnvironment();

		$names = array();

		$twig->addFunction(new \Twig_SimpleFunction('placeHolder', function($name) use (&$names) {
			$names[] = $name;
		}));

		$template = $twig->loadTemplate($layoutSrc);

		$template->render(array());

		return $names;
	}

	/**
	 * TemplateLoader needs the container.
	 *
	 * @param ContainerInterface $container
	 */
	public function setContainer(ContainerInterface $container)
	{
		$this->container = $container;
	}

	/**
	 * @TODO: should obtain pre-configured Twig instance from somewhere else.
	 *
	 * @return \Twig_Environment
	 */
	protected function createTwigEnvironment()
	{
		$loader = new TemplateLoader();

		$loader->setContainer($this->container);

		$twig = new \Twig_Environment($loader);

		return $twig;
	}
}