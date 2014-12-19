<?php

namespace Supra\Package\Cms\Pages\Layout\Processor;

use Symfony\Component\HttpFoundation\Response;
use Supra\Package\Cms\Pages\Twig\PlaceHolderNodeCollector;

/**
 * Twig layout processor
 */
class TwigProcessor implements ProcessorInterface
{
	/**
	 * @var \Twig_Environment
	 */
	protected $twig;

	/**
	 * @param \Twig_Environment $twig
	 */
	public function __construct(\Twig_Environment $twig)
	{
		$this->twig = $twig;
	}

	/**
	 * {@inheritDoc}
	 */
	public function process($layoutSrc, Response $response, array $placeResponses)
	{
		if (! $this->twig->hasExtension('supraPage')) {
			throw new \UnexpectedValueException('Missing for Supra Page extension.');
		}

		$response->setContent(
				$this->twig->render($layoutSrc, array('responses' => $placeResponses))
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getPlaces($layoutSrc)
	{
		$tokenStream = $this->twig->tokenize(
				$this->twig->getLoader()->getSource($layoutSrc)
		);

		$collector = new PlaceHolderNodeCollector();
		$traverser = new \Twig_NodeTraverser($this->twig, array($collector));

		$traverser->traverse($this->twig->parse($tokenStream));

		return $collector->getCollectedNames();
	}
}