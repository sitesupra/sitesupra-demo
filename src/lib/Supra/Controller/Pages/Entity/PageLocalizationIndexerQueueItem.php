<?php

namespace Supra\Controller\Pages\Entity;

use Supra\Search\Entity\Abstraction\IndexerQueueItem;
use Supra\Controller\Pages\Entity\PageLocalization;
use Supra\Search\IndexerService;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Search\IndexedDocument;
use Supra\Controller\Pages\Request\PageRequestView;
use Supra\Controller\Pages\Entity\TemplateBlock;
use Supra\Controller\Pages\Markup;
use Supra\Controller\Pages\Entity\ReferencedElement\ImageReferencedElement;
use Supra\Controller\Pages\Entity\ReferencedElement\LinkReferencedElement;
use Supra\Controller\Pages\PageController;
use Supra\Search\Exception\IndexerRuntimeException;
use Supra\Controller\Pages\Search\PageLocalizationFindRequest;
use Supra\Search\SearchService;

/**
 * @Entity
 */
class PageLocalizationIndexerQueueItem extends IndexerQueueItem
{
	const DISCRIMITATOR_VALUE = 'pageLocalization';

	/**
	 * @Column(type="supraId")
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

	public function __construct(PageLocalization $pageLocalization)
	{
		parent::__construct();

		$this->pageLocalizationId = $pageLocalization->getId();
		$this->revisionId = $pageLocalization->getRevisionId();
		$this->schemaName = PageController::SCHEMA_DRAFT;
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
		}
		else {
			$id = implode('-', array($pageLocalizationId, $schemaName, $revisionId));
		}

		return $id;
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

	/**
	 * @return array of IndexedDocument
	 */
	public function getIndexedDocuments()
	{
		$result = array();
		$em = ObjectRepository::getEntityManager($this->schemaName);
		$pr = $em->getRepository(PageLocalization::CN());

		/* @var $pageLocalization PageLocalization */
		$pageLocalization = $pr->find($this->pageLocalizationId);

		$isVisible = $pageLocalization->isActive();
		$reindexChildren = true;

		$previousIndexedDocument = $this->findPageLocalizationIndexedDocument($pageLocalization->getId());

		if ( ! empty($previousIndexedDocument)) {

			$ancestorIds = $previousIndexedDocument->ancestorIds;

			$previousParentId = array_shift($ancestorIds);

			$parent = $pageLocalization->getParent();
			
			if ( ! empty($parent)) {
				$currentParentId = $parent->getId();

				if ($previousParentId != $currentParentId) {

					$currentParentIndexedDocument = $this->findPageLocalizationIndexedDocument($currentParentId);

					$isVisible = $pageLocalization->isActive() && $currentParentIndexedDocument->visible;
				}

				$reindexChildren = $isVisible != $previousIndexedDocument->visible;
			}
		}

		$result[] = $this->makeIndexedDocument($pageLocalization, $isVisible);

		if ($reindexChildren) {

			$children = $pageLocalization->getChildren();

			foreach ($children as $child) {

				if ( ! $child instanceof GroupLocalization) {

					$result[] = $this->makeIndexedDocument($child, $isVisible);
				}
			}
		}

		return $result;
	}

	protected function findPageLocalizationIndexedDocument($pageLocalizationId)
	{
		$findRequest = new PageLocalizationFindRequest();

		$findRequest->setSchemaName($this->schemaName);
		$findRequest->setPageLocalizationId($pageLocalizationId);

		$searchService = new SearchService();

		$results = $searchService->processRequest($findRequest);

		foreach ($results as $result) {
			if ($result->pageLocalizationId == $pageLocalizationId) {
				return $result;
			}
		}

		return null;
	}

	/**
	 * @param PageLocalization $pageLocalization
	 * @return IndexedDocument 
	 */
	protected function makeIndexedDocument(PageLocalization $pageLocalization, $visible)
	{
		$lm = ObjectRepository::getLocaleManager($this);

		$locale = $lm->getLocale($pageLocalization->getLocale());

		$languageCode = $locale->getProperty('language');

		$id = self::getUniqueId($this->schemaName, $pageLocalization->getId(), $pageLocalization->getRevisionId());

		$class = PageLocalization::CN();

		$indexedDocument = new IndexedDocument($class, $id);

		$indexedDocument->schemaName = $this->schemaName;
		$indexedDocument->revisionId = $this->revisionId;

		$indexedDocument->pageId = $pageLocalization->getMaster()->getId();
		$indexedDocument->pageLocalizationId = $pageLocalization->getId();
		$indexedDocument->localeId = $locale->getId();

		$indexedDocument->title_general = $indexedDocument->formatText($pageLocalization->getTitle());
		$indexedDocument->__set('title_' . $languageCode, $indexedDocument->title_general);

		$indexedDocument->active = $pageLocalization->isActive() ? 'true' : 'false';

		$indexedDocument->keywords = $pageLocalization->getMetaKeywords();
		$indexedDocument->description = $pageLocalization->getMetaDescription();

		$indexedDocument->pageWebPath = $pageLocalization->getPath();

		$indexedDocument->visible = $visible ? 'true' : 'false';

		$ancestors = $pageLocalization->getAuthorizationAncestors();
		$ancestorIds = array();
		foreach ($ancestors as $ancestor) {
			/* @var $ancestor Page */
			if ($ancestor instanceof PageLocalization) {
				$ancestorIds[] = $ancestor->getId();
			}
		}

		$indexedDocument->ancestorIds = $ancestorIds;

		$dummyHttpRequest = new \Supra\Request\HttpRequest();

		$pageRequestView = new PageRequestView($dummyHttpRequest);
		$pageRequestView->setLocale($pageLocalization->getLocale());
		$pageRequestView->setPageLocalization($pageLocalization);

		$em = ObjectRepository::getEntityManager($pageLocalization);

		$pageRequestView->setDoctrineEntityManager($em);
		$blockPropertySet = $pageRequestView->getBlockPropertySet($em);

		$pageContents = array();

		foreach ($blockPropertySet as $blockProperty) {
			/* @var $blockProperty BlockProperty */

			if ( ! $blockProperty->getLocalization() instanceof TemplateLocalization) {

				$blockContents = $this->getIndexableContentFromBlockProperty($blockProperty);
				$pageContents[] = $indexedDocument->formatText($blockContents);
			}
		}

		$indexedDocument->text_general = join(' ', $pageContents);
		$indexedDocument->__set('text_' . $languageCode, $indexedDocument->text_general);

		$indexedDocument->active = $pageLocalization->isActive();

		\Log::debug('LLL makeIndexedDocument: ', $indexedDocument->pageLocalizationId, ' visible: ', $indexedDocument->visible);

		return $indexedDocument;
	}

	public function getIndexableContentFromBlockProperty(BlockProperty $blockProperty)
	{
		$tokenizer = new Markup\DefaultTokenizer($blockProperty->getValue());

		$tokenizer->tokenize();

		$result = array();
		foreach ($tokenizer->getElements() as $element) {

			if ($element instanceof Markup\HtmlElement) {
				$result[] = $element->getContent();
			}
			else if ($element instanceof Markup\SupraMarkupImage) {

				$metadata = $blockProperty->getMetadata();

				/* @var $metadataItem Supra\Controller\Pages\Entity\BlockPropertyMetadata */
				$metadataItem = $metadata[$element->getId()];

				$image = $metadataItem->getReferencedElement();

				if ($image instanceof ImageReferencedElement) {
					$result[] = $image->getAlternativeText();
				}
			}
			else if ($element instanceof Markup\SupraMarkupLinkStart) {

				$metadata = $blockProperty->getMetadata();

				/* @var $metadataItem Supra\Controller\Pages\Entity\BlockPropertyMetadata */
				$metadataItem = $metadata[$element->getId()];

				if ( ! empty($metadataItem)) {

					$link = $metadataItem->getReferencedElement();

					if ($link instanceof LinkReferencedElement) {
						$result[] = $link->getTitle();
					}
				}
				else {
					\Log::debug('EMPTY REFERENCED LINK?');
				}
			}
		}

		return implode(' ', $result);
	}

}
