<?php
namespace Project\FancyBlocks\Logotype;
		
use Supra\Controller\Pages\BlockController;
use Supra\Editable;

class LogotypeBlock extends BlockController
{

	public static function getPropertyDefinition()
	{
		$properties = array();
		
		$image = new Editable\Image('Logotype');
		$properties['logotype'] = $image;
		
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
