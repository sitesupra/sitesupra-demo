<?php

namespace Supra\Search\Mysql;

use Supra\Search\AbstractIndexer;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Search\Entity\Abstraction\IndexerQueueItem;
use Supra\Search\IndexerQueueItemStatus;

class MysqlIndexer extends AbstractIndexer
{
	/**
	 * @var \Doctrine\DBAL\Connection
	 */
	private $connection;
	
	/**
	 * Inserts queued item into MySQL table
	 * @param IndexerQueueItem $queueItem 
	 */
	public function processItem(IndexerQueueItem $queueItem)
	{
		$tableName = $this->getIndexedContentTableName();
		$connection = $this->getConnection();
		$systemId = $this->getSystemId();
		
		$writer = function ($document) use ($tableName, $connection, $systemId) {
			
			$uniqueId = $systemId . '-'
								. $document->class . '-'
								. $document->getLocalId();

			$query = "INSERT INTO {$tableName} (localizationId, content,
						locale, uniqueId, path, title, entityClass, 
						created, updated, ancestorIds) 
					VALUES (:localizationId, 
						:content, :locale, :uniqueId, :path, :title, :entityClass,
						:date, :date, :ancestorIds) 
						
					ON DUPLICATE KEY UPDATE content = VALUES(content),
					locale = VALUES(locale), path = VALUES(path), title = VALUES(title),
					entityClass = VALUES(entityClass), updated = VALUES(updated),
					ancestorIds = VALUES(ancestorIds)";
			
			$statement = $connection->prepare($query);
			
			$statement->execute(array(
				':localizationId' => $document->pageLocalizationId,
				':content' => $document->text_general,
				':locale' => $document->localeId,
				':uniqueId' => $uniqueId,
				':path' => (string) $document->pageWebPath,
				':title' => $document->title_general,
				':entityClass' => $document->class,
				':date' => date('Y-m-d H:i:s', time()),
				':ancestorIds' => serialize($document->ancestorIds),
			));
		};

		try {
			$queueItem->writeIndexedDocuments($writer);
			$queueItem->setStatus(IndexerQueueItemStatus::INDEXED);
			
		} catch (\Doctrine\DBAL\DBALException $e) {
			
			throw $e;
			
		} catch (Exception\RuntimeException $e) {
			
			$queueItem->setStatus(IndexerQueueItemStatus::FAILED);
		}
	}

	/**
	 * Returns count of documents indexed for this system
	 * @return integer
	 */
	public function getDocumentCount()
	{
		$connection = $this->getConnection();
		
		$statement = $connection->prepare("SELECT COUNT(t.id) AS count FROM {$this->getIndexedContentTableName()} t");
		$statement->execute();
		
		return (int) $statement->fetchColumn();
	}

	/**
	 * @param string $id
	 * @return bool
	 */
	public function removeFromIndex($id)
	{
		if (empty($id)) {
			throw new \InvalidArgumentException('ID must not be empty');
		}
		
		$connection = $this->getConnection();
		
		$statement = $connection->prepare("DELETE FROM {$this->getIndexedContentTableName()} WHERE localizationId = :id");
		return $statement->execute(array(':id' => $id));
	}

	/**
	 * Remove item from search index
	 * @param type $pageLocalizationId
	 */
	public function remove($pageLocalizationId)
	{
		$this->removeFromIndex($pageLocalizationId);
	}

	
	/**
	 * @return \Doctrine\DBAL\Connection
	 */
	protected function getConnection()
	{
		if ($this->connection === null) {
			$this->connection = ObjectRepository::getEntityManager($this)
					->getConnection();
			
			if ( ! $this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MySqlPlatform) {
				throw new \RuntimeException('MySQL indexer requires connection with MySQL database');
			}
		}
		
		return $this->connection;
	}
	
	/**
	 * @return string
	 */
	protected function getIndexedContentTableName()
	{
		$searcher = ObjectRepository::getSearchService($this)
				->getSearcher();
		
		if ( ! $searcher instanceof MysqlSearcher) {
			throw new \RuntimeException('Invalid configuration');
		}
		
		return $searcher->getIndexedContentTableName();
	}

	/**
	 */
	public function removeAllFromIndex()
	{
		$this->getConnection()
				->executeQuery("TRUNCATE TABLE {$this->getIndexedContentTableName()}");
	}
}