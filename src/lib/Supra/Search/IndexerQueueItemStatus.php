<?php

namespace Supra\Search;

class IndexerQueueItemStatus
{
	const FRESH = 100;
	const PROCESSED = 200;
	const FAILED = 300;
	const DISABLED = 400;
}
