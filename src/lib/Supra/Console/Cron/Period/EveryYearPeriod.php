<?php

namespace Supra\Console\Cron\Period;

/**
 * EveryYearPeriod
 *
 */
class EveryYearPeriod extends AbstractPeriod
{

	protected $parameter = '01 January';

	protected $parameterFormat = 'd F H:i';

	protected $period = 'P1Y';

	/**
	 * Get closest suitable point in time (future)
	 * 
	 * @return \DateTime
	 */
	public function getNext() 
	{
		$now = new \DateTime();
		$dateTime = \DateTime::createFromFormat($this->parameterFormat, $this->parameter);

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

		if (preg_match('/^\d{1,2}\s+[[:alpha:]]+$/', $parameter)) {
			$parameter .= ' 00:00';
		} else if (
			! preg_match('/^\d{1,2}\s+[[:alpha:]]+\s+\d{1,2}:\d{2}$/', $parameter)
		) {
			return false;
		}

		try {
			$date = \DateTime::createFromFormat($this->parameterFormat, $parameter);
			if ( ! $date instanceof \DateTime) {
				return false;
			}
		} catch (\Exception $e) {
			return false;
		}

		return true;
	}
	
}
