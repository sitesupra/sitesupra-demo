<?php

namespace Project\FancyBlocks\Copyright;

use Supra\Controller\Pages\BlockController;
use Supra\Editable;

class CopyrightBlock extends BlockController
{

	public static function getPropertyDefinition()
	{
		$properties = array();

		$text = new Editable\InlineString('Copyright');
		$text->setDefaultValue('Copyright © 2011 Ovid Theme is proudly powered by Wordpress.');
		$properties['copyright'] = $text;

		$text = new Editable\InlineString('Theme');
		$text->setDefaultValue('Wordpress theme by Peerapong.');
		$properties['theme'] = $text;

		$text = new Editable\InlineString('Images');
		$text->setDefaultValue('All images are copyrighted to their respective owners.');
		$properties['images'] = $text;

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
