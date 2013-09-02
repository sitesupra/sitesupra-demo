<?php

namespace Supra\Email;

use Supra\Controller\Pages\Event\PostPrepareContentEventArgs;


class EmailEncoderListener implements \Doctrine\Common\EventSubscriber
{	
	/**
	 */
	const DECODER_JAVASCRIPT_FILE_URI = '/cms/lib/public/decipher-email.min.js';
	
	/**
	 */
	const EVENT_POST_ENCODER_USE = 'postEncoderUse';
		
	/**
	 * @var use
	 */
	private $encoderUsed = false;
	
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
			self::EVENT_POST_ENCODER_USE,
		);
	}
	
	public function postEncoderUse(\Supra\Event\EventArgs $eventArgs)
	{
		$this->encoderUsed = true;
	}

	public function postPrepareContent(PostPrepareContentEventArgs $eventArgs)
	{
		if ($this->encoderUsed && ! $this->decoderJsAttached) {
			
			$eventArgs->response
					->getContext()
					->addJsUrlToLayoutSnippet('js', self::DECODER_JAVASCRIPT_FILE_URI);
			
			$this->decoderJsAttached = true;
		}
	}
}
