<?php
namespace Project\FancyBlocks\Tagline;
		
use Supra\Controller\Pages\BlockController;
use Supra\Editable;

class TaglineBlock extends BlockController
{

	public static function getPropertyDefinition()
	{
		$properties = array();
		
		return $properties;
	}

	protected function doExecute()
	{
		$request = $this->getRequest();
		/* @var $request \Supra\Request\HttpRequest */
		$response = $this->getResponse();
		/* @var $response \Supra\Response\TwigResponse */
		
		// code
		
		$response->outputTemplate('index.html.twig');
	}
}
