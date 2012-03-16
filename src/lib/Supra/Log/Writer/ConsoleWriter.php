<?php

namespace Supra\Log\Writer;

use Supra\Log\LogEvent;

/**
 * Console log Writer
 */
class ConsoleWriter extends StreamWriter
{

	private $stdout;
	private $stderr;

	public function __construct(array $parameters = array())
	{
		parent::__construct($parameters);

		$this->coloredLogs = true;
	}
	
	public function __destruct()
	{
		if (is_resource($this->stream)) {
			fclose($this->stdout);
		}
		if (is_resource($this->stream)) {
			fclose($this->stderr);
		}
		
		parent::__destruct();
	}

	public function __wakeup()
	{
		$this->stdout = null;
		$this->stderr = null;
		
		parent::__wakeup();
	}

	protected function getStream(LogEvent $event)
	{
		if ($event->getLevelPriority() > 20) {
			if (is_null($this->stderr)) {
				$this->stderr = fopen('php://stderr', 'w');
			}

			return $this->stderr;
		} else {
			if (is_null($this->stdout)) {
				$this->stdout = fopen('php://stdout', 'w');
			}

			return $this->stdout;
		}
	}

}
