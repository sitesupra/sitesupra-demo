<?php

namespace Supra\Controller\Pages\Email;

use Supra\Controller\Pages\Event\BlockEventsArgs;
use Supra\Controller\Pages\Listener\BlockExecuteListener;

/**
 */
class EncoderEventListener
{

	const DECODER_JAVASCRIPT_FILE_URI = '/cms/lib/public/decipher-email.min.js';

	/**
	 * @var boolean
	 */
	private $flushed = false;

	/**
	 * @param BlockEventsArgs $eventArgs
	 */
	public function blockEndExecuteEvent(BlockEventsArgs $eventArgs)
	{
		if ( ! $this->flushed
				&& $eventArgs->actionType === BlockExecuteListener::ACTION_CONTROLLER_EXECUTE) {

			$context = $eventArgs->blockController->getResponse()
					->getContext();

			$context->addJsUrlToLayoutSnippet('js', self::DECODER_JAVASCRIPT_FILE_URI);
			$this->flushed = true;
		}
	}
}