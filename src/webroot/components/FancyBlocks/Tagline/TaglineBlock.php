<?php
namespace Project\FancyBlocks\Tagline;
		
use Supra\Controller\Pages\BlockController;

class TaglineBlock extends BlockController
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
