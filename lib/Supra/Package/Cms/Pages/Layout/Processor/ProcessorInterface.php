<?php

namespace Supra\Package\Cms\Pages\Layout\Processor;

use Symfony\Component\HttpFoundation\Response;

/**
 * Layout processor interface
 */
interface ProcessorInterface
{
	/**
	 * Process the layout.
	 * 
	 * @param string $layoutSrc
	 * @param Response $response
	 * @param array $placeResponses
	 */
	public function process($layoutSrc, Response $response, array $placeResponses);

	/**
	 * Return list of place names inside the layout
	 * @param string $layoutSrc
	 * @return array
	 */
	public function getPlaces($layoutSrc);
}