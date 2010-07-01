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
		Logger::debug("Query '$sql' has been run with parameters ", $params);
	}
}