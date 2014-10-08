<?php

namespace Supra\Package\Cms\Pages\Layout\Processor;

use Supra\Package\Cms\Pages\Request\PageRequest;
use Supra\Package\Cms\Pages\Response\PageResponse;

/**
 * Layout processor interface
 */
interface ProcessorInterface
{
	/**
	 * Process the layout
	 * @param PageResponse $response
	 * @param array $placeResponses
	 * @param string $layoutSrc
	 */
	public function process(PageResponse $response, array $placeResponses, $layoutSrc);

	/**
	 * Return list of place names inside the layout
	 * @param string $layoutSrc
	 * @return array
	 */
	public function getPlaces($layoutSrc);
}