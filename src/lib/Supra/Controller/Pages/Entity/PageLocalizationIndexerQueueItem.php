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

/**
 * @Entity
 */
class PageLocalizationIndexerQueueItem extends IndexerQueueItem
{
	const DISCRIMITATOR_VALUE = 'pageLocalization';
	
	/**
	 * @Column(type="sha1")
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
	 * Sets schema name to be used for this queue item.
	 * @param string $schemaName 
	 */
	public function setSchemaName($schemaName)
	{
		if ( ! in_array($schemaName, PageController::$knownSchemaNames)) {
			throw new IndexerRuntimeException('Unknown schema name "' . $schemaName . '". Use constants from PageControler.');
		}

		$this->schemaName = $schemaName;
	}

	/**
	 * @return array of IndexedDocument
	 */
	public function getIndexedDocuments()
	{
		$result = array();
		$class = PageLocalization::CN();

		$em = ObjectRepository::getEntityManager($this->schemaName);
		$pr = $em->getRepository($class);

		/* @var $pageLocalization PageLocalization */
		$pageLocalization = $pr->find($this->pageLocalizationId);
		
		$lm = ObjectRepository::getLocaleManager($this);
		
		$locale = $lm->getLocale($pageLocalization->getLocale());
		
		$languageCode = $locale->getProperty("language");

		$id = implode('-', array($this->pageLocalizationId, $this->schemaName, $this->revisionId));
		
		$indexedDocument = new IndexedDocument($class, $id);

		$indexedDocument->schemaName = $this->schemaName;
		$indexedDocument->revisionId = $this->revisionId;

		$indexedDocument->pageId = $pageLocalization->getMaster()->getId();
		$indexedDocument->pageLocalizationId = $pageLocalization->getId();
		$indexedDocument->localeId = $locale->getId();

		$indexedDocument->title_general = $indexedDocument->formatText($pageLocalization->getTitle());
		$indexedDocument->__set('title_' . $languageCode, $indexedDocument->title_general);

		$indexedDocument->keywords = $pageLocalization->getMetaKeywords();
		$indexedDocument->description = $pageLocalization->getMetaDescription();
		
		$indexedDocument->pageWebPath = $pageLocalization->getPath();

		$ancestors = $pageLocalization->getAuthorizationAncestors();
		$ancestorIds = array();
		foreach($ancestors as $ancestor) {
			/* @var $ancestor Page */
			
			$ancestorIds[] = $ancestor->getId();
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

		$result[] = $indexedDocument;

		return $result;
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

//					$result[] = '[[[IMAGE ';
//					$result[] = $metadataItem->getName();
//					$result[] = '===';
					$result[] = $image->getAlternativeText();
//					$result[] = ']]]';
				}
			}
			else if ($element instanceof Markup\SupraMarkupLinkStart) {

				$metadata = $blockProperty->getMetadata();

				/* @var $metadataItem Supra\Controller\Pages\Entity\BlockPropertyMetadata */
				$metadataItem = $metadata[$element->getId()];

				$link = $metadataItem->getReferencedElement();

				if ($link instanceof LinkReferencedElement) {

//					$result[] = '[[[LINK ';
//					$result[] = $metadataItem->getName();
//					$result[] = '===';
					$result[] = $link->getTitle();
//					$result[] = '===';
//
//					$result[] = $link->getHref() ? : $link->getPageId();
//
//					$result[] = ']]]';
				}
			}
		}

		return implode(' ', $result);
	}

}
