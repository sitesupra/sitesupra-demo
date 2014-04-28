<?php

namespace Supra\Controller\Pages\Email;

use Supra\Controller\Pages\Event\PostPrepareContentEventArgs;

/**
 */
class EncoderEventListener
{	
	/**
	 * @var boolean
	 */
	private $binded = false;
	
	public function postPrepareContent(PostPrepareContentEventArgs $eventArgs)
	{
		if ( ! $this->binded) {
			
			EmailEncoder::getInstance()
					->bindResponseContext($eventArgs->response->getContext());
			
			$this->binded = true;
		}
	}
}
