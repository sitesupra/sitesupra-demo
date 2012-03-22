<?php

namespace Supra\Controller\Pages\Listener;

use Supra\Controller\Pages\Event\PostPrepareContentEventArgs;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Request\PageRequestView;
use Supra\Response\HttpResponse;
use Supra\Controller\Pages\Entity\PageLocalization;
use Supra\Html\HtmlTag;
use Supra\Controller\Pages\Finder\LocalizationFinder;
use Supra\Controller\Pages\Finder\PageFinder;
use Doctrine\ORM\EntityManager;

class PageMetadataOutputListener
{

	/**
	 * @var boolean
	 */
	protected $useParentOnEmptyMetadata = false;

	/**
	 * @var EntityManager
	 */
	protected $em;

	/**
	 * @var LocalizationFinder
	 */
	protected $localizationFinder;

	/**
	 * @return boolean
	 */
	public function useParentOnEmptyMetadata()
	{
		return $this->useParentOnEmptyMetadata;
	}

	/**
	 * @param boolean $useParentOnEmptyMetadata 
	 */
	public function setUseParentOnEmptyMetadata($useParentOnEmptyMetadata)
	{
		$this->useParentOnEmptyMetadata = $useParentOnEmptyMetadata;
	}

	/**
	 * @return EntityManager
	 */
	public function getEntityManager()
	{
		if (empty($this->em)) {
			throw new Exception\RuntimeException('Entity manager not set.');
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
	 * @return LocalizationFinder
	 */
	public function getLocalizationFinder()
	{
		if (empty($this->localizationFinder)) {

			$em = $this->getEntityManager();

			$pageFinder = new PageFinder($em);

			$this->localizationFinder = new LocalizationFinder($pageFinder);
		}

		return $this->localizationFinder;
	}

	/**
	 * @param LocalizationFinder $localizationFinder
	 */
	public function setLocalizationFinder(LocalizationFinder $localizationFinder)
	{
		$this->localizationFinder = $localizationFinder;
	}

	public function postPrepareContent(PostPrepareContentEventArgs $eventArgs)
	{
		$request = $eventArgs->request;

		if ( ! ($request instanceof PageRequestView)) {
			return;
		}

		$pageLocalization = $request->getPageLocalization();

		$metaNames = array(
			'metaDescription' => 'description', 'metaKeywords' => 'keywords'
		);

		$metaTagHtml = array();

		foreach ($metaNames as $propertyName => $tagName) {

			$content = $this->getMetaContent($pageLocalization, $propertyName);

			// Special case for "metaKeywords" - replace ";" with ", "
			if ($propertyName == 'metaKeywords') {
				$content = join(', ', explode(';', $content));
			}

			if ( ! empty($content)) {

				$metaTag = new HtmlTag('meta');
				$metaTag->setAttribute('name', $tagName);
				$metaTag->setAttribute('content', $content);

				$metaTagHtml[] = $metaTag->toHtml();
			}
		}

		$responseContext = $eventArgs->response->getContext();

		$responseContext->addToLayoutSnippet('meta', join("\n", $metaTagHtml));
	}

	private function getMetaContent(PageLocalization $pageLocalization, $metaName)
	{
		$useParent = $this->useParentOnEmptyMetadata();

		$value = $pageLocalization->getProperty($metaName);

		if (empty($value) && $useParent) {

			$ancestors = $this->getLocalizationFinder()->getAncestors($pageLocalization);

			foreach ($ancestors as $ancestor) {
				
				$value = $ancestor->getProperty($metaName);
				if ( ! empty($value)) {
					break;
				}
			}
		}

		return $value;
	}

}
