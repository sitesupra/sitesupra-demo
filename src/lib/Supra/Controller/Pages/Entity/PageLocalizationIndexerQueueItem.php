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

/**
 * @Entity
 */
class PageLocalizationIndexerQueueItem extends IndexerQueueItem
{

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
		$this->schemaName = 'draft';
	}

	/**
	 * @return array of IndexedDocument
	 */
	public function getIndexedDocuments()
	{
		$result = array();

		$em = ObjectRepository::getEntityManager($this);
		$pr = $em->getRepository(PageLocalization::CN());

		/* @var $pageLocalization PageLocalization */
		$pageLocalization = $pr->find($this->pageLocalizationId);

		$indexedDocument = new IndexedDocument();

		$indexedDocument->uniqueId = join('-', array($this->pageLocalizationId, $this->schemaName, $this->revisionId));
		$indexedDocument->schemaName = $this->schemaName;
		$indexedDocument->revisionId = $this->revisionId;
		$indexedDocument->class = PageLocalization::CN();

		$indexedDocument->pageId = $pageLocalization->getMaster()->getId();
		$indexedDocument->pageLocalizationId = $pageLocalization->getId();
		$indexedDocument->locale = $pageLocalization->getLocale();

		$indexedDocument->title = $indexedDocument->formatText($pageLocalization->getTitle());
		$indexedDocument->__set('title_' . $pageLocalization->getLocale(), $indexedDocument->title);

		$indexedDocument->keywords = $pageLocalization->getMetaKeywords();
		$indexedDocument->description = $pageLocalization->getMetaDescription();

		$dummyHttpRequest = new \Supra\Request\HttpRequest();

		$pageRequestView = new PageRequestView($dummyHttpRequest);
		$pageRequestView->setLocale($pageLocalization->getLocale());
		$pageRequestView->setPageLocalization($pageLocalization);

		$em = ObjectRepository::getEntityManager($pageLocalization);

		$pageRequestView->setDoctrineEntityManager($em);
		$blockPropertySet = $pageRequestView->getBlockPropertySet($em);

		$pageContents = array();

		foreach ($blockPropertySet as $blockProperty) {
			/* @var $block BlockProperty */

			if ( ! $blockProperty->getBlock() instanceof TemplateBlock) {

				$blockContents = $this->getIndexableContentFromBlockProperty($blockProperty);

				//$pageContents[] = '[' . get_class($blockProperty->getBlock());
				//$pageContents[] = $indexedDocument->formatText($blockProperty->getValue());
				$pageContents[] = $indexedDocument->formatText($blockContents);
				//$pageContents[] = ']';
			}
		}

		$pageContents = join(' ', $pageContents);

		//$pageContents = preg_replace('@\{/?supra\..*?\}@ims', ' ', $pageContents);

		$indexedDocument->text = $pageContents;
		$indexedDocument->__set('text_' . $pageLocalization->getLocale(), $indexedDocument->text);

		$result[] = $indexedDocument;

		return $result;
	}

	public function getIndexableContentFromBlockProperty(BlockProperty $blockProperty)
	{
		$tokenizer = new Markup\Tokenizer($blockProperty->getValue());

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

					$result[] = '[[[IMAGE ';
					$result[] = $metadataItem->getName();
					$result[] = '===';
					$result[] = $image->getAlternativeText();
					$result[] = ']]]';
				}
			}
			else if ($element instanceof Markup\SupraMarkupLinkStart) {
				
				$metadata = $blockProperty->getMetadata();

				/* @var $metadataItem Supra\Controller\Pages\Entity\BlockPropertyMetadata */
				$metadataItem = $metadata[$element->getId()];
				
				$link = $metadataItem->getReferencedElement();
				
				if ($link instanceof LinkReferencedElement) {

					$result[] = '[[[LINK ';
					$result[] = $metadataItem->getName();
					$result[] = '===';
					$result[] = $link->getTitle();
					$result[] = '===';
					
					$result[] = $link->getHref() ?: $link->getPageId(); 
					
					$result[] = ']]]';
				}
			}
		}

		return join(' ', $result);
	}

}
