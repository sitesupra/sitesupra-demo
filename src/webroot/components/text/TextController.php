<?php

namespace Project\Text;

use Supra\Controller\Pages\BlockController,
		Supra\Controller\Request,
		Supra\Controller\Response;

/**
 * Simple text block
 */
class TextController extends \Supra\Controller\Pages\BlockController
{
	public function execute()
	{
		$response = $this->getResponse();
		if ( ! ($response instanceof Response\Http)) {
			return;
		}
		/* @var $response Response\Http */
		$response->output('<div style="">TEXT: ' . $this->getProperty('html', 'defaultText') . '</div>');
	}
}