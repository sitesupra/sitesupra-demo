<?php

namespace Supra\Log\Logger;

use Doctrine\DBAL\Logging\SQLLogger;
use Supra\Log\Logger;

/**
 * Sql class
 */
class Sql implements SQLLogger
{
	/**
	 * {@inheritdoc}
	 */
	function logSQL($sql, array $params = null)
	{
		$message = "-- Query\n$sql\n/* has been run";
		if (count($params) > 0) {
			$message .= " with parameters [" . implode(', ', $params) . "]";
		}
		$message .= "*/";
		Logger::debug($message);
	}
}