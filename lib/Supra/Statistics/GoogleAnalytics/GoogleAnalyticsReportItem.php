<?php

namespace Supra\Statistics\GoogleAnalytics;

use Supra\Statistics\Exception\RuntimeException;


class GoogleAnalyticsReportItem {
	
	protected $dimensions;
	
	protected $metrics;
	
	/**
	 * @param array $metrics
	 * @param array $dimensions
	 */
	public function __construct(array $metrics, array $dimensions = array())
	{
		$this->metrics = $metrics;
		$this->dimensions = $dimensions;
	}
	
	public function getDimensions()
	{
		return $this->dimensions;
	}
	
	public function getMetrics()
	{
		return $this->metrics;
	}
	
	public function __call($name, $parameters)
	{
    	if ( ! preg_match('/^get/', $name)) {
			return;
		}
    
		$name = strtolower(str_replace('get', '', $name));
		
		if (isset($this->metrics[$name])) {
			return $this->metrics[$name];
		} else if (isset($this->dimensions[$name])) {
			return $this->dimensions[$name];
		}
		
		throw new RuntimeException("No metric/dimension found by name {$name}");
	}
}
