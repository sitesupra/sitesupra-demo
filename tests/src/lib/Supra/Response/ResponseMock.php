<?php

namespace Supra\Tests\Response;

use Supra\Response\HttpResponse;

/**
 * 
 */
class ResponseMock extends HttpResponse
{
	public function flush()
	{
		$content = implode('', $this->output);
		$this->output = array();
		return $content;
	}
}