<?php

namespace Supra\Console\Cron\Period;

/**
 * EveryDayPeriod
 *
 */
class EveryDayPeriod extends AbstractPeriod
{

	protected $parameter = '00:00';

	protected $parameterFormat = 'H:i';

	protected $period = 'P1D';

	/**
	 * Get closest suitable point in time (future)
	 * 
	 * @return \DateTime
	 */
	public function getNext() 
	{
		$nextTime = strtotime($this->parameter);
		if ($nextTime < time()) {
			$nextTime += 86400;
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
	protected function validateParameter(&$parameter) {
		if ( ! is_string($parameter)) {
			return false;
		}

		$parameter = trim($parameter);
		
		if ( ! preg_match('/^\d{1,2}:\d{2}$/', $parameter)) {
			return false;
		}

		$today = strtotime("today");
		$tomorrow = strtotime("tomorrow");
		$parameter = strtotime($parameter);

		if (($parameter < $today) || ($parameter >= $tomorrow)) {
			return false;
		}

		return true;
	}
	
}
