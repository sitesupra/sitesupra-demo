<?php

namespace Project\FancyBlocks\SlideshowAdvanced;

use Supra\Controller\Pages\BlockController;

class SlideshowAdvancedBlock extends BlockController
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
