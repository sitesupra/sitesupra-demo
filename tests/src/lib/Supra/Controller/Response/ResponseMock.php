<?php

namespace Supra\Tests\Controller\Response;

use Supra\Controller\Response\Http;

/**
 * 
 */
class ResponseMock extends Http
{
	public function flush()
	{
		$content = implode('', $this->output);
		$this->output = array();
		return $content;
	}
}