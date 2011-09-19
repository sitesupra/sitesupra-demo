<?php

namespace Supra\Console\Cron\Period;

/**
 * EveryHourPeriod
 *
 */
class EveryHourPeriod extends AbstractPeriod
{

	protected $parameter = null;

	protected $parameterFormat =  'i';

	protected $period = 'P1H';

	/**
	 * Get closest suitable point in time (future)
	 * 
	 * @return \DateTime
	 */
	public function getNext() 
	{
		$nextTime = date('H:i');
		if ( ! is_null($this->parameter)) {
			$nextTime = date('H') . ':' . $this->parameter;
		}
		$nextTime = strtotime($nextTime);
		
		if ($nextTime < time()) {
			$nextTime += 3600;
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
		if ( ! is_numeric($parameter)) {
			return false;
		}

		$parameter = intval($parameter);

		if (($parameter < 0) || ($parameter >= 60)) {
			return false;
		}

		return true;
	}
	
}
