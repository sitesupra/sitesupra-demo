<?php

namespace Supra\Controller\Response;

/**
 * Cli response object
 */
class Cli implements ResponseInterface
{
	function prepare()
	{
		ob_end_clean();
		// don't open any output buffering for CLI response
	}

	function flush()
	{
		
	}
}