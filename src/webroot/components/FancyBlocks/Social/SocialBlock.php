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
        $youtubeLink->setManagerMode('page-external');
        $properties['youtube'] = $youtubeLink;

        $twitterLink = new Editable\Link('Twitter link');
        $twitterLink->setManagerMode('page-external');
        $properties['twitter'] = $twitterLink;

		$rssLink = new Editable\Link('Rss link');
        $rssLink->setManagerMode('page-external');
		$properties['rss'] = $rssLink;

		$faceBookLink = new Editable\Link('Facebook link');
        $faceBookLink->setManagerMode('page-external');
		$properties['facebook'] = $faceBookLink;

		$flickrLink = new Editable\Link('Flickr link');
        $flickrLink->setManagerMode('page-external');
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
