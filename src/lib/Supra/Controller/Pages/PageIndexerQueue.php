<?php

namespace Supra\Controller\Pages;

use Supra\Search\IndexerQueue;

class PageIndexerQueue extends IndexerQueue
{
	public function getIndexerQueueItem($object)
	{
		throw new \RuntimeException("Not implemented yet");
	}
}
