<?php

namespace Supra\Controller\Pages\Listener;

use Supra\Controller\Pages\Event\PostPrepareContentEventArgs;
use Supra\ObjectRepository\ObjectRepository;

/**
 * Adds HTML meta tag with product version
 */
class VersionMetadataHtmlInjection
{
	public function postPrepareContent(PostPrepareContentEventArgs $eventArgs)
	{
		$info = ObjectRepository::getSystemInfo($this);

		if ( ! empty($info->version)) {
			$html = '<meta name="version" content="' . htmlspecialchars($info->version) . '" />';

			$eventArgs->response
					->getContext()
					->addToLayoutSnippet('meta', $html);
		}
	}
}
