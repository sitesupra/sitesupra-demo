<?php

namespace Supra\Console\Cron\Period;

/**
 * EveryWeekPeriod
 *
 */
class EveryWeekPeriod extends AbstractPeriod
{

	protected $parameter = 'Monday 00:00';

	protected $parameterFormat = 'l H:i';
	
	protected $period = 'P1W';

	/**
	 * Get closest suitable point in time (future)
	 * 
	 * @return \DateTime
	 * 
	 * @TODO: implement $previousTime usage
	 */
	public function getNext(\DateTime $previousTime = null) 
	{
		$now = new \DateTime();
		$dateTime = new \DateTime($this->parameter);

		if ($dateTime <= $now) {
			$dateTime->add(new \DateInterval($this->period));
		}
		
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

		if (preg_match('/^[[:alpha:]]+day$/', $parameter)) {
			$parameter .= ' 00:00';
		} else if (
			! preg_match('/^[[:alpha:]]+day\s+\d{1,2}:\d{2}$/', $parameter)
		) {
			return false;
		}

		try {
			$date = new \DateTime($parameter);
		} catch (\Exception $e) {
			return false;
		}

		return true;
	}
	
}
