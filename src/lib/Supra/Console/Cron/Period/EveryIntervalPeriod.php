<?php

namespace Supra\Console\Cron\Period;

/**
 * EveryIntervalPeriod
 */
class EveryIntervalPeriod extends AbstractPeriod
{
	/**
	 * Get closest suitable point in time (future)
	 * @return \DateTime
	 */
	public function getNext(\DateTime $previousTime = null) 
	{
		$nextTime = 0;
		
		if ($previousTime instanceof \DateTime) {
			$prevTime = $previousTime->getTimestamp();
			$nextTime = strtotime($this->parameter, $prevTime);
		}
		
		// First run, parameter invalid or previous time too old
		if ($nextTime < time()) {
			$nextTime = time();
		}
		
		$dateTime = new \DateTime();
		$dateTime->setTimestamp($nextTime);
		return $dateTime;		
	}

	/**
	 * Validate parameter value
	 *
	 * @param string $parameter
	 * @return boolean
	 */
	protected function validateParameter(&$parameter)
	{
		$now = time();
		$next = strtotime($parameter, $now);
		
		if ($next <= $now) {
			return false;
		}
		
		return true;
	}
	
}
