<?php

namespace Supra\Package\Cms\Entity;

use Supra\Search\Entity\Abstraction\IndexerQueueItem;
use Supra\Controller\Pages\Entity\Abstraction\Localization;
use Supra\Search\IndexerService;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Search\IndexedDocument;
use Supra\Controller\Pages\Request\PageRequestView;
use Supra\Controller\Pages\Markup;
use Supra\Controller\Pages\Entity\ReferencedElement\ImageReferencedElement;
use Supra\Controller\Pages\Entity\ReferencedElement\LinkReferencedElement;
use Supra\Controller\Pages\PageController;
use Supra\Search\Exception\IndexerRuntimeException;
use Supra\Controller\Pages\Search\PageLocalizationFindRequest;
use Supra\Search\SearchService;
//use Supra\Controller\Pages\Search\PageLocalizationSearchResultItem;
use Supra\Search\Solarium\PageLocalizationSearchResultItem;
use Doctrine\ORM\EntityManager;

/**
 * @Entity
 * @DetachedDiscriminatorValue("pageLocalization")
 */
class PageLocalizationIndexerQueueItem extends IndexerQueueItem
{

	const DISCRIMITATOR_VALUE = 'pageLocalization';

	/**
	 * @Column(type="supraId20")
	 * @var string
	 */
	protected $pageLocalizationId;

	/**
	 * @Column(type="string") 
	 * @var string
	 */
	protected $revisionId;

	/**
	 * @Column(type="string") 
	 * @var string
	 */
	protected $schemaName;

	/**
	 * @Column(type="boolean")
	 * @var boolean
	 */
	protected $removal = false;

	/**
	 * @var PageLocalization
	 */
	protected $localization;

	/**
	 * @var PageLocalization
	 */
	protected $previousLocalization;

	/**
	 * @var boolean
	 */
	protected $isActive;

	/**
	 * @var boolean
	 */
	protected $reindexChildren;

	/**
	 * @var array
	 */
	static $indexedLocalizationIds = array();

	/**
	 * @var \Doctrine\ORM\EntityManager
	 */
	protected $em;

	/**
	 * @var boolean
	 */
	protected $ignoreChildren;

	/**
	 * @param PageLocalization $pageLocalization 
	 */
	public function __construct(PageLocalization $pageLocalization)
	{
		parent::__construct();

		$this->pageLocalizationId = $pageLocalization->getId();
		$this->revisionId = $pageLocalization->getRevisionId();
		$this->schemaName = PageController::SCHEMA_DRAFT;

		$this->ignoreChildren = false;
	}

	/**
	 * @return boolean
	 */
	public function isRemoval()
	{
		return $this->removal;
	}

	/**
	 * @param boolean $removal
	 */
	public function setRemoval($removal)
	{
		$this->removal = $removal;
	}

	/**
	 * @param string $schemaName
	 * @param string $pageLocalizationId
	 * @param string $revisionId
	 * @return string
	 */
	static function getUniqueId($schemaName, $pageLocalizationId, $revisionId = null)
	{
		$id = null;

		if ($schemaName == PageController::SCHEMA_PUBLIC) {
			$id = implode('-', array($pageLocalizationId, $schemaName));
		} else {
			$id = implode('-', array($pageLocalizationId, $schemaName, $revisionId));
		}

		return $id;
	}

	/**
	 * @return EntityManager
	 */
	public function getEntityManager()
	{
		if (empty($this->em)) {

			$em = ObjectRepository::getEntityManager($this->schemaName);
			$this->setEntityManager($em);
		}

		return $this->em;
	}

	/**
	 * @param EntityManager $em 
	 */
	public function setEntityManager(EntityManager $em)
	{
		$this->em = $em;
	}

	/**
	 * @return boolean
	 */
	public function getIgnoreChildren()
	{
		return $this->ignoreChildren;
	}

	/**
	 * @param boolean $ignoreChildren 
	 */
	public function setIgnoreChildren($ignoreChildren)
	{
		$this->ignoreChildren = $ignoreChildren;
	}

	/**
	 * Sets schema name to be used for this queue item.
	 * @param string $schemaName 
	 */
	public function setSchemaName($schemaName)
	{
		if ( ! in_array($schemaName, PageController::$knownSchemaNames)) {
			throw new IndexerRuntimeException('Unknown schema name "' . $schemaName . '". Use constants from PageController.');
		}

		$this->schemaName = $schemaName;
	}

	static function addToIndexed($pageLocalizationId, $revisionId)
	{
		$mockId = self::makeMockId($pageLocalizationId, $revisionId);

		self::$indexedLocalizationIds[] = $mockId;

		\Log::debug('QQQQQ: ADD TO INDEXED: ', $mockId);
	}

	static function isIndexed($pageLocalizationId, $revisionId)
	{
		$mockId = self::makeMockId($pageLocalizationId, $revisionId);

		$result = in_array($mockId, self::$indexedLocalizationIds);

		\Log::debug('QQQQQ: IS INDEXED?: ', $mockId, ': ', $result);

		return $result;
	}

	static function makeMockId($pageLocalizationId, $revisionId)
	{
		return $pageLocalizationId . '-' . $revisionId;
	}

	public function getPreviousPublishedPageLocalization(PageLocalization $pageLocalization)
	{
		$auditEm = ObjectRepository::getEntityManager('#audit');

		$query = $auditEm->createQuery('SELECT prd FROM ' . PageRevisionData::CN() . ' prd WHERE prd.reference = :pageLocalizationId AND prd.type = ' . PageRevisionData::TYPE_HISTORY . ' ORDER BY prd.id DESC');
		$query->setMaxResults(1);
		$query->setParameter('pageLocalizationId', $pageLocalization->getId());

		$pageRevisionData = $query->getOneOrNullResult();
		/* @var $pageRevisionData PageRevisionData */

		if (empty($pageRevisionData)) {
			return null;
		}

		$pageLocalizationRepo = $auditEm->getRepository(PageLocalization::CN());

		$criteria = array(
			'id' => $pageLocalization->getId(),
			'revision' => $pageRevisionData->getId()
		);

		$previousPublishedPageLocalization = $pageLocalizationRepo->findOneBy($criteria);

		return $previousPublishedPageLocalization;
	}

	public function getIndexedDocuments()
	{
		
	}

	/**
	 * Does removal of the document..
	 *
	protected function remove()
	{
		/*
		// moved from CmsPageLocalizationIndexerQueueListener
		if ( ! ObjectRepository::isSolariumConfigured($this)) {
			\Log::debug(Configuration::FAILED_TO_GET_CLIENT_MESSAGE);
			return;
		}*
		IndexerService::getAdapter()->remove($this->pageLocalizationId);
	}*/

	/**
	 * @return array of IndexedDocument
	 */
	public function writeIndexedDocuments($solariumDocumentWriter)
	{
		if ($this->removal) {

			$indexerService = ObjectRepository::getIndexerService($this);
			
			$indexerService->remove($this->pageLocalizationId);
			
			return array();
		}

		$result = array();

		if (self::isIndexed($this->pageLocalizationId, $this->revisionId)) {

			\Log::debug('LLL hit cache BIGTIME!!! ', self::makeMockId($this->pageLocalizationId, $this->revisionId));
			return array();
		}

		$em = $this->getEntityManager();
		$pr = $em->getRepository(PageLocalization::CN());

		$criteria = array(
			'id' => $this->pageLocalizationId,
			'revision' => $this->revisionId
		);

		$localization = $pr->findOneBy($criteria);
		/* @var $localization PageLocalization */

		if (empty($localization)) {
			return $result;
		}

		\Log::debug('I:I:I:I:I:I:I: ', $localization->getTitle() . ' / ' . $localization->getRevisionId());

		$previousLocalization = $this->getPreviousPublishedPageLocalization($localization);

		$solariumDocument = $this->makeIndexedDocument($localization);
		$result[] = $solariumDocument;
		$solariumDocumentWriter($solariumDocument);

		$currentIndexedDocument = $this->findPageLocalizationIndexedDocument($localization->getId());

		$localizationMoved = true;

		$localizationFullPath = $this->getPageLocalizationFullPath($localization);

		$previousLocalizationFullPath = null;
		if ($previousLocalization) {
			$previousLocalizationFullPath = $this->getPageLocalizationFullPath($previousLocalization);
		}

		if ($localizationFullPath == $previousLocalizationFullPath) {
			$localizationMoved = false;
		}
		// If "Is Active" has been chagned 
		// OR page localization has been moved
		// OR there is no previous indexed document 
		// OR previous indexed document revision is not last published page localization revision
		// AND children are not ignored per se
		// then we have to reindex children too.
		if (
				(
				empty($previousLocalization) ||
				($localization->isActive() != $previousLocalization->isActive()) ||
				($localizationMoved) ||
				empty($currentIndexedDocument) ||
				($currentIndexedDocument->revisionId != $previousLocalization->getRevisionId())
				)
				&&
				$this->getIgnoreChildren() == false
		) {
			$childrenIds = $localization->getAllChildrenIds();

			foreach ($childrenIds as $childId) {

				$child = $pr->find($childId);

				if ( ! self::isIndexed($child->getId(), $child->getRevisionId())) {

					$solariumDocument = $this->makeIndexedDocument($child);
					$result[] = $solariumDocument;
					
					//\Log::error($solariumDocument->text_general);
					
					$solariumDocumentWriter($solariumDocument);
				} else {
					\Log::debug('LLL hit cache!!! ', self::makeMockId($child->getId(), $child->getRevisionId()));
				}

				$em->clear();
				
				//$m4 = memory_get_usage();
				//\Log::error('M4: ', $m4);
			}
		}

		return $result;
	}

	/**
	 * @param PageLocalization $pageLocalization
	 * @return type 
	 */
	private function getPageLocalizationFullPath(PageLocalization $pageLocalization)
	{
		$localizationFullPath = null;

		$pathEntity = $pageLocalization->getPathEntity();
		if ( ! empty($pathEntity)) {

			$path = null;
			try {
				$path = $pathEntity->getPath();
			} catch (\Doctrine\ORM\EntityNotFoundException $e) {
				// WTH IS THIS EVEN HAPPENING?
			}

			if ( ! empty($path)) {
				$localizationFullPath = $path->getFullPath();
			}
		}

		return $localizationFullPath;
	}

	/**
	 *
	 * @param string $pageLocalizationId
	 * @return Solarium_Document_ReadOnly
	 */
	protected function findPageLocalizationIndexedDocument($pageLocalizationId)
	{
		$findRequest = new PageLocalizationFindRequest();

		$findRequest->setSchemaName($this->schemaName);
		$findRequest->setPageLocalizationId($pageLocalizationId);

		$searchService = ObjectRepository::getSearchService($this);

		$resultSet = $searchService->processRequest($findRequest);

		$items = $resultSet->getItems();

		foreach ($items as $item) {

			if ($item instanceof PageLocalizationSearchResultItem) {

				if ($item->getPageLocalizationId() == $pageLocalizationId) {
					return $item->getIndexedDocument();
				}
			}
		}

		return null;
	}

	/**
	 * @param PageLocalization $pageLocalization
	 * @return IndexedDocument 
	 */
	protected function makeIndexedDocument(PageLocalization $pageLocalization)
	{
		$lm = ObjectRepository::getLocaleManager($this);

		$locale = $lm->getLocale($pageLocalization->getLocale());

		$languageCode = $locale->getProperty('language');

		$id = self::getUniqueId($this->schemaName, $pageLocalization->getId(), $pageLocalization->getRevisionId());

		$class = PageLocalization::CN();

		$indexedDocument = new IndexedDocument($class, $id);

		$indexedDocument->schemaName = $this->schemaName;
		$indexedDocument->revisionId = $pageLocalization->getRevisionId();

		$indexedDocument->pageId = $pageLocalization->getMaster()->getId();
		$indexedDocument->pageLocalizationId = $pageLocalization->getId();
		$indexedDocument->localeId = $locale->getId();

		$indexedDocument->title_general = $indexedDocument->formatText($pageLocalization->getTitle());
		$indexedDocument->__set('title_' . $languageCode, $indexedDocument->title_general);

		$indexedDocument->active = $pageLocalization->isActive() ? 'true' : 'false';

		$indexedDocument->keywords = $pageLocalization->getMetaKeywords();
		$indexedDocument->description = $pageLocalization->getMetaDescription();
		$indexedDocument->includeInSearch = $pageLocalization->isIncludedInSearch();
        $indexedDocument->pageWebPath = $pageLocalization->getPath();

		$pageLocalizationPathEntity = $pageLocalization->getPathEntity();
		$isActive = 'true';
		if (empty($pageLocalizationPathEntity)) {
			$isActive = 'false';
		} else if ( ! $pageLocalizationPathEntity->isActive()) {
			$isActive = 'false';
		}
		$indexedDocument->isActive = $isActive;

		$redirect = $pageLocalization->getRedirect();
		$isRedirected = 'true';
		if (empty($redirect)) {
			$isRedirected = 'false';
		}
		$indexedDocument->isRedirected = $isRedirected;

		$isLimited = $pageLocalizationPathEntity->isLimited();
		$indexedDocument->isLimited = $isLimited ? 'true' : 'false';

		$ancestors = $pageLocalization->getAuthorizationAncestors();
		$ancestorIds = array();
		foreach ($ancestors as $ancestor) {
			/* @var $ancestor Page */
			if ($ancestor instanceof PageLocalization) {
				$ancestorIds[] = $ancestor->getId();
			}
		}

		$indexedDocument->ancestorIds = $ancestorIds;

		// Include general title in the text
		$pageContents = array();
		$pageContents[] = $indexedDocument->title_general;

		$dummyHttpRequest = new \Supra\Request\HttpRequest();

		$em = $this->getEntityManager();

		$pageRequestView = new PageRequestView($dummyHttpRequest);

		$pageRequestView->setLocale($pageLocalization->getLocale());
		$pageRequestView->setPageLocalization($pageLocalization);
		$pageRequestView->setDoctrineEntityManager($em);

		$blockPropertySet = $pageRequestView->getBlockPropertySet($em);

		$indexedEditableClasses = array(
			\Supra\Editable\Html::CN(),
			\Supra\Editable\String::CN(),
			\Supra\Editable\InlineString::CN()
		);

		foreach ($blockPropertySet as $blockProperty) {
			/* @var $blockProperty BlockProperty */

			if ( ! ($blockProperty->getLocalization() instanceof TemplateLocalization) &&
					in_array($blockProperty->getType(), $indexedEditableClasses)
			) {
				$blockContents = $this->getIndexableContentFromBlockProperty($blockProperty);		
				$pageContents[] = $indexedDocument->formatText($blockContents);
			}
		}

		$indexedDocument->text_general = join(' ', $pageContents);
		$indexedDocument->__set('text_' . $languageCode, $indexedDocument->text_general);

		\Log::debug('LLL makeIndexedDocument: ', $indexedDocument->pageLocalizationId . '-' . $indexedDocument->revisionId, '; isActive: ', $indexedDocument->isActive, '; active: ', $indexedDocument->active);

		self::addToIndexed($pageLocalization->getId(), $pageLocalization->getRevisionId());

		return $indexedDocument;
	}

	public function getIndexableContentFromBlockProperty(BlockProperty $blockProperty)
	{
		if ($blockProperty->getEditable() instanceof \Supra\Editable\Html) {
			$value = @unserialize($blockProperty->getValue());
			if (is_array($value) && isset($value['html'])) {
				$value = $value['html'];
			}
		} else {
			$value = $blockProperty->getValue();
		}
		
		$tokenizer = new Markup\DefaultTokenizer($value);
		
		$tokenizer->tokenize();

		$result = array();
		foreach ($tokenizer->getElements() as $element) {

			if ($element instanceof Markup\HtmlElement) {
				$result[] = $element->getSafeContent();
			} else if ($element instanceof Markup\SupraMarkupImage) {

				$metadata = $blockProperty->getMetadata();

				/* @var $metadataItem BlockPropertyMetadata */
				$metadataItem = $metadata[$element->getId()];

				$image = $metadataItem->getReferencedElement();

				if ($image instanceof ImageReferencedElement) {
					$result[] = $image->getAlternativeText();
				}
			} else if ($element instanceof Markup\SupraMarkupLinkStart) {

				$metadata = $blockProperty->getMetadata();

				/* @var $metadataItem BlockPropertyMetadata */
				$metadataItem = $metadata[$element->getId()];

				if ( ! empty($metadataItem)) {

					$link = $metadataItem->getReferencedElement();

					if ($link instanceof LinkReferencedElement) {
						$result[] = $link->getTitle();
					}
				} else {
					\Log::debug('EMPTY REFERENCED LINK?');
				}
			}
		}

		return implode(' ', $result);
	}

	public function getPageLocalizationId()
	{
		return $this->pageLocalizationId;
	}

	public function setPageLocalizationId($pageLocalizationId)
	{
		$this->pageLocalizationId = $pageLocalizationId;
	}

	public function getRevisionId()
	{
		return $this->revisionId;
	}

	public function setRevisionId($revisionId)
	{
		$this->revisionId = $revisionId;
	}

}
