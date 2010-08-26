<?php

namespace Supra\Log\Logger;

use Doctrine\DBAL\Logging\SQLLogger,
		Supra\Log\Logger;

/**
 * Sql class
 */
class Sql implements SQLLogger
{
	/**
	 * {@inheritdoc}
	 */
	function logSQL($sql, array $params = null, $executionMS = null)
	{
		$message = "-- Query\n$sql\n/* has been run";
		if (count($params) > 0) {
			$message .= " with parameters [" . implode(', ', $params) . "]";
		}
		if ($executionMS !== null) {
			$executionMS = round(1000000 * $executionMS);
			$message .= ", execution time {$executionMS}ms*/";
		} else {
			$message .= "*/";
		}
		Logger::debug($message);
	}
}