<?php

namespace Supra\Search;

use Supra\Search\Entity\Abstraction\IndexerQueueItem;

class NullIndexer extends AbstractIndexer
{
	/**
	 * {@inheritDoc}
	 */
	public function processItem(IndexerQueueItem $queueItem)
	{
	}

	/**
	 * {@inheritDoc}
	 */
	public function getDocumentCount()
	{
		return 0;
	}

	/**
	 * {@inheritDoc}
	 */
	public function removeFromIndex($id)
	{
		return false;
	}

	/**
	 * {@inheritDoc}
	 */
	public function remove($pageLocalizationId)
	{
		return false;
	}

	/**
	 * {@inheritDoc}
	 */
	public function removeAllFromIndex()
	{
		return null;
	}
}