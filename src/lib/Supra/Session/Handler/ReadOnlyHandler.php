<?php

namespace Supra\Session\Handler;

/**
 * Description of ReadOnlyHandler
 */
class ReadOnlyHandler extends HandlerAbstraction
{
	public function close()
	{
		// do nothing
	}

	public function start()
	{
		// do nothing
	}


	protected function findSessionId()
	{
		return null;
	}

	protected function readSessionData()
	{
		return array();
	}

}
