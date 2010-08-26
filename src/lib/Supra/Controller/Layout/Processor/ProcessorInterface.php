<?php

namespace Supra\Controller\Layout\Processor;

use Supra\Response\ResponseInterface,
		Supra\Controller\Pages\Entity\Layout;

/**
 * Layout processor interface
 */
interface ProcessorInterface
{
	/**
	 * Process the layout
	 * @param ResponseInterface $response
	 * @param array $placeResponses
	 * @param string $layoutSrc
	 */
	public function process(ResponseInterface $response, array $placeResponses, $layoutSrc);

	/**
	 * Return list of place names inside the layout
	 * @param string $layoutSrc
	 * @return array
	 */
	public function getPlaces($layoutSrc);
}