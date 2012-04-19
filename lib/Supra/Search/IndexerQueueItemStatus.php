<?php

namespace Supra\Search;

use Supra\Search\Exception\IndexerRuntimeException;

class IndexerQueueItemStatus
{
	const FRESH = 100;
	const INDEXED = 200;
	const FAILED = 300;
	const DISABLED = 400;
	const PROCESSING = 500;

	private static $knownStatuses = array(self::FRESH, self::INDEXED, self::FAILED, self::DISABLED, self::PROCESSING);

	/**
	 * Validates value to be one of known statuses for indexer queue. Throws IndexerRuntimeException on bad values.
	 * @param integer $number
	 * @throws IndexerRuntimeException
	 * @return integer
	 */
	public static function validate($status)
	{
		if ( ! in_array($status, self::$knownStatuses)) {
			throw new IndexerRuntimeException('Unkown indexer queue item status value. Use constants from IndexerQueueItemStatus.');
		}

		return $status;
	}

	/**
	 * Returns arll known stauses.
	 * @return array
	 */
	public static function getKnownStatuses()
	{
		return self::$knownStatuses;
	}

}
