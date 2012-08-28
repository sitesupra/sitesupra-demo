<?php

namespace Project\FancyBlocks\Slideshow;

use Supra\Controller\Pages\BlockController;

class SlideshowBlock extends BlockController
{

	/**
	 * @return array
	 */
	public static function getPropertyDefinition()
	{
		$properties = array();

		return $properties;
	}

	protected function doExecute()
	{
		$response = $this->getResponse();
		/* @var $response \Supra\Response\TwigResponse */

		$response->outputTemplate('index.html.twig');
	}

}
