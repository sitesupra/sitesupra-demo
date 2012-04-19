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
	public function getNext(\DateTime $previousTime = null) 
	{
		// First run default time
		$nextTime = time();
		
		$parameter = (int) $this->parameter;
		
		// Parameter not specified, previous run exists
		if (is_null($parameter) && $previousTime instanceof \DateTime) {
			$parameter = $previousTime->format('i');
		}
		
		// Minutes specified
		if ( ! is_null($parameter)) {
			$nextTime = strtotime(date('H:') . $parameter);
			
			if ($nextTime < time()) {
				$nextTime += 3600;
			}
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
