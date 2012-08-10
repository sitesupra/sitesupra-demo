<?php

namespace Project\FancyBlocks\Copyright;

use Supra\Controller\Pages\BlockController;
use Supra\Editable;

class CopyrightBlock extends BlockController
{

	/**
	 * @return array
	 */
	public static function getPropertyDefinition()
	{
		$properties = array();

		$copyrightText = new Editable\InlineString('Copyright');
		$copyrightText->setDefaultValue('Copyright © 2011 Lorem ipsum');
		$properties['copyright'] = $copyrightText;

		$themeText = new Editable\InlineString('Theme');
		$themeText->setDefaultValue('Theme by Lorem ipsum');
		$properties['theme'] = $themeText;

		$imagesText = new Editable\InlineString('Images');
		$imagesText->setDefaultValue('All images are copyrighted to their respective owners.');
		$properties['images'] = $imagesText;

		return $properties;
	}

	protected function doExecute()
	{
		$response = $this->getResponse();
		/* @var $response \Supra\Response\TwigResponse */

		$response->outputTemplate('index.html.twig');
	}

}
