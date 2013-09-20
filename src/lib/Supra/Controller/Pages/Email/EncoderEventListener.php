<?php

namespace Supra\Controller\Pages\Email;

use Supra\Controller\Pages\Event\PostPrepareContentEventArgs;

/**
 */
class EncoderEventListener
{	
	const CONTEXT_PERSISTENCE_OFFSET = '__encoderContextKey';
	const DECODER_JAVASCRIPT_FILE_URI = '/cms/lib/public/decipher-email.min.js';
	
	/**
	 * @var boolean
	 */
	private $flushed = false;
	
	public function postPrepareContent(PostPrepareContentEventArgs $eventArgs)
	{
		if ( ! $this->flushed) {
			
			$context = $eventArgs->response->getContext();
			
			if ($context->getValue(self::CONTEXT_PERSISTENCE_OFFSET, false)) {
				$context->addJsUrlToLayoutSnippet('js', self::DECODER_JAVASCRIPT_FILE_URI);
				$this->flushed = true;
			}
		}
	}
}
