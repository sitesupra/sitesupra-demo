<?php

namespace Supra\Log\Writer;

use Supra\Log\LogEvent;

/**
 * Daily file log writer
 */
class DailyFileWriter extends FileWriter
{
	/**
	 * Default configuration
	 * @var array
	 */
	public static $defaultParameters = array(
		'folder' => \SUPRA_LOG_PATH,
		'file' => 'supra.%date%.log',
		'dateFormat' => 'Ymd',
	);
	
	/**
	 * Replace URL to contain current date
	 * @param string $url
	 * @return string
	 */
	protected function formatUrl($url)
	{
		// replacement arrays
		$replaceWhat = $replaceWith = array();

		// date handling
		if (strpos($url, '%date%') !== false) {
			$date = LogEvent::getDateInDefaultTimezone($this->parameters['dateFormat']);
			$replaceWhat[] = '%date%';
			$replaceWith[] = $date;
		}

		if ( ! empty($replaceWhat)) {
			$url = str_replace($replaceWhat, $replaceWith, $url);
		}
		
		return $url;
	}

}