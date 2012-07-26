<?php

namespace Project\FancyBlocks\Logotype;

use Supra\Controller\Pages\BlockController;
use Supra\Editable;

class LogotypeBlock extends BlockController
{

	/**
	 * @return string
	 */
	public function getDefaultLogotypeUrl()
	{
		/* @var $configuration LogotypeBlockConfiguration */
		$configuration = $this->getConfiguration();

		return $configuration->defaultLogotypeUrl;
	}

	/**
	 * @return array
	 */
	public static function getPropertyDefinition()
	{
		$properties = array();

		$image = new Editable\Image('Logotype');
		$properties['logotype'] = $image;

		return $properties;
	}

	public function getPropertyValue($name)
	{
		if ($name == 'defaultLogotypeUrl') {
			
			/* @var $request \Supra\Controller\Pages\Request\PageRequest */
			
			$request = $this->getRequest();
			
			$request->getLayoutPlaceHolderNames();
		} else {
			$value = parent::getPropertyValue($name);
		}
		return $value;
	}

	protected function doExecute()
	{
		$response = $this->getResponse();
		/* @var $response \Supra\Response\TwigResponse */

		$response->assign('defaultLogotypeUrl', $this->getDefaultLogotypeUrl());

		$response->outputTemplate('index.html.twig');
	}

}
