<?php

namespace Supra\Search\Mysql;
use Supra\Search\Mysql\Adapter;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\PageController;
use Supra\Search\IndexerServiceAbstract;
use Supra\Search\Entity\Abstraction\IndexerQueueItem;
use Supra\Search\IndexerQueueItemStatus;

class IndexerService extends IndexerServiceAbstract
{
	/**
	 * Adds $queueItem to Mysql.
	 * @param IndexerQueueItem $queueItem 
	 */
	public function processItem(\Supra\Search\Entity\Abstraction\IndexerQueueItem $queueItem)
	{
		$documents = array();

		try {
			
			$systemId = $this->getSystemId();
			
			$mysqlDocumentWriter = function ($document) use ($systemId) {
				
				$document->uniqueId = $document->systemId . '-'
						. $document->class . '-'
						. $document->getLocalId();
				
				$em = ObjectRepository::getEntityManager(PageController::SCHEMA_PUBLIC);
				
				$sqlSelect = "SELECT * FROM " . Adapter::TABLE_NAME . " WHERE localizationId = :localizationId AND uniqueId = :uniqueId";
				$query = $em->getConnection()->prepare( $sqlSelect );
					
				try {
					$data = $query->execute( array(
						':localizationId' => $document->pageLocalizationId,
						':uniqueId' => $document->uniqueId,
					) );
				}
				catch ( Exception\BadSchemaException $e )
				{
					throw new Exception\RuntimeException('Got bad status in selectresult: ' . $query);
				}
				
				if ( ! $query->rowCount() )
				{
					$sql = "INSERT INTO " . Adapter::TABLE_NAME . " (
							localizationId, 
							pageContent, 
							localeId, 
							uniqueId, 
							pageWebPath, 
							pageTitle, 
							entityClass,
							createDate,
							updateDate,
							ancestorId
						) VALUES (
							:localizationId, 
							:pageContent, 
							:localeId, 
							:uniqueId, 
							:pageWebPath, 
							:pageTitle, 
							:entityClass,
							:date,
							:date,
							:ancestorId
						)";
				}
				else
				{
					$sql = "UPDATE " . Adapter::TABLE_NAME . " SET 
						pageContent = :pageContent, 
						pageWebPath = :pageWebPath, 
						pageTitle = :pageTitle, 
						entityClass = :entityClass,
						updateDate = :date,
						ancestorId = :ancestorId
						WHERE 
							localizationId = :localizationId 
							AND localeId = :localeId 
							AND uniqueId = :uniqueId";
				}
				
				$params = array(
					':localizationId' => $document->pageLocalizationId,
					':pageContent' => $document->text_general,
					':localeId' => $document->localeId,
					':uniqueId' => $document->uniqueId,
					':pageWebPath' => (string)$document->pageWebPath,
					':pageTitle' => $document->title_general,
					':entityClass' => $document->class,
					':date' => date( 'Y-m-d H:i:s', time() ),
					':ancestorId' => serialize($document->ancestorIds),
				);
				
				$query = $em->getConnection()->prepare( $sql );
				
				try {
					$data = $query->execute( $params );
				}
				catch ( Exception\BadSchemaException $e )
				{
					throw new Exception\RuntimeException('Got bad status in update result: ' . $query);
				}
			};
			
			$queueItem->writeIndexedDocuments($mysqlDocumentWriter);
			
			$queueItem->setStatus(IndexerQueueItemStatus::INDEXED);
		} catch (Exception\BadSchemaException $e) {
			throw $e;
		} catch (Exception\RuntimeException $e) {
			$queueItem->setStatus(IndexerQueueItemStatus::FAILED);
		}
		
		return count($documents);
	}

	/**
	 * Returns count of documents indexed for this system
	 * @return integer
	 */
	public function getDocumentCount()
	{
		return 0;
	}

	/**
	 * @param string $uniqueId 
	 */
	public function removeFromIndex($localizationId)
	{
		/** @Object EntityManager */
		$em = ObjectRepository::getEntityManager(PageController::SCHEMA_PUBLIC);
				
		$sql = "DELETE FROM " . Adapter::TABLE_NAME . " WHERE localizationId = :localizationId";
		$query = $em->getConnection()->prepare($sql);

		try {
			$data = $query->execute( array(
				':localizationId' => $localizationId,
			) );
		}
		catch ( Exception\BadSchemaException $e ) {
			throw $e;
		}
	}
	
	/**
	 * Remove item from search index
	 * @param type $pageLocalizationId
	 */
	public function remove($pageLocalizationId)
	{
		$this->removeFromIndex($pageLocalizationId);
	}

}
