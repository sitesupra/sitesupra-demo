<?php

namespace Project\FancyBlocks\Social;

use Supra\Controller\Pages\BlockController;
use Supra\Editable;

class SocialBlock extends BlockController
{

	/**
	 * @return array
	 */
	public static function getPropertyDefinition()
	{
		$properties = array();

        $youtubeLink = new Editable\Link('Youtube link');
        $properties['youtube'] = $youtubeLink;

        $twitterLink = new Editable\Link('Twitter link');
        $properties['twitter'] = $twitterLink;

		$rssLink = new Editable\Link('Rss link');
		$properties['rss'] = $rssLink;

		$faceBookLink = new Editable\Link('Facebook link');
		$properties['facebook'] = $faceBookLink;

		$flickrLink = new Editable\Link('Flickr link');
		$properties['flickr'] = $flickrLink;

		return $properties;
	}

	protected function doExecute()
	{
		$response = $this->getResponse();
		/* @var $response \Supra\Response\TwigResponse */

		$response->outputTemplate('index.html.twig');
	}

}
