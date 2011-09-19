<?php

namespace Supra\Console\Cron\Period;

/**
 * AbstractPeriod
 *
 */
abstract class AbstractPeriod implements PeriodInterface
{

	/**
	 * Parameter
	 *
	 * @var string
	 */
	protected $parameter;

	/**
	 * Accepted date string format of parameter
	 *
	 * @var string
	 */
	protected $parameterFormat;

	/**
	 * Time interval of period (iso-format string)
	 *
	 * @var string
	 */
	protected $period;
	
	/**
	 * Constructor
	 *
	 * @param string $parameter 
	 */
	public function __construct($parameter = null)
	{
		if ( ! empty($parameter)) {
			$parameterValid = $this->validateParameter($parameter);
			if ($parameterValid) {
				$this->parameter = $parameter;
			} else {
				throw new \RuntimeException('Parameter exceeds period or is invalid.');
			}
		}
	}

	/**
	 * Get current period parameter
	 *
	 * @return string
	 */
	public function getParameter()
	{
		return $this->parameter;
	}

	/**
	 * Get time interval of one period
	 *
	 * @return \DateInterval 
	 */
	public function getPeriod()
	{
		return new \DateInterval($this->period);
	}

	abstract protected function validateParameter(&$parameter);
	
}
