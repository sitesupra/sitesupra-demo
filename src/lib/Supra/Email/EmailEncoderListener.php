<?php

namespace Supra\Email;

use Supra\Controller\Pages\Event\PostPrepareContentEventArgs;


class EmailEncoderListener implements \Doctrine\Common\EventSubscriber
{	
	const ENCODER_CONTEXT_KEY = '__hasEncodedEmails';
	
	/**
	 */
	const DECODER_JAVASCRIPT_FILE_URI = '/cms/lib/public/decipher-email.min.js';
	
	/**
	 * @var boolean
	 */
	private $decoderJsAttached = false;
	
	/**
	 * @return array
	 */
	public function getSubscribedEvents()
	{
		return array(
			\Supra\Controller\Pages\PageController::EVENT_POST_PREPARE_CONTENT,
		);
	}

	public function postPrepareContent(PostPrepareContentEventArgs $eventArgs)
	{
		if ( ! $this->decoderJsAttached) {
			
			$context = $eventArgs->response->getContext();
			
			if ($context->getValue(self::ENCODER_CONTEXT_KEY, false)) {
				$context->addJsUrlToLayoutSnippet('js', self::DECODER_JAVASCRIPT_FILE_URI);
			}
			
			$this->decoderJsAttached = true;
		}
	}
}
