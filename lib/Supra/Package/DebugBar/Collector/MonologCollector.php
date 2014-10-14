<?php

namespace Supra\Package\DebugBar\Collector;

use DebugBar\Bridge\MonologCollector as BaseCollector;

class MonologCollector extends BaseCollector
{
	protected $skipPatterns = array(
		'/^Processing event/',
		'/^DOCTRINE:/'
	);

	protected function write(array $record)
	{
		foreach ($this->skipPatterns as $pattern) {
			if (preg_match($pattern, $record['message'])) {
				return;
			}
		}

		parent::write($record);
	}
}

