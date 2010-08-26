<?php

namespace Supra\Tests\Response;

use Supra\Response\Http;

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