<?php

namespace Supra\Controller\Response;

/**
 * Cli response object
 */
class Cli implements ResponseInterface
{
	/**
	 * Prepare response
	 */
	function prepare()
	{
		// Don't open any output buffering for CLI response
		ob_end_clean();
	}

	/**
	 * Flush the buffer
	 */
	function flush()
	{}

	/**
	 * Output method
	 * @param string $output
	 */
	public function output($output)
	{
		echo $output;
	}
}