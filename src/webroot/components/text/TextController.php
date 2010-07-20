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
	public function execute(Request\RequestInterface $request, Response\ResponseInterface $response)
	{
		parent::execute($request, $response);

		if ( ! ($response instanceof Response\Http)) {
			return;
		}
		/* @var $response Response\Http */
		$response->output('<div style="">Block</div>');
	}
}