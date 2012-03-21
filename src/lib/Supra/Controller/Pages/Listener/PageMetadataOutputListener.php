<?php

namespace Supra\Controller\Pages\Listener;

use Supra\Controller\Pages\Event\PostPrepareContentEventArgs;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Request\PageRequestView;
use Supra\Response\HttpResponse;
use Supra\Controller\Pages\Entity\PageLocalization;

class PageMetadataOutputListener
{

	/**
	 * @var boolean
	 */
	protected $useParentOnEmptyMetadata = false;

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

	public function postPrepareContent(PostPrepareContentEventArgs $eventArgs)
	{
		$request = $eventArgs->request;

		if ( ! ($request instanceof PageRequestView)) {
			return;
		}

		$pageLocalization = $request->getPageLocalization();

		$metaNames = array(
			'metaDescription', 'metaKeywords'
		);

		$metaData = array();

		foreach ($metaNames as $name) {

			$metaData[$name] = $this->getMetaContent($pageLocalization, $name);
		}

		$response = $eventArgs->response;
		/* @var $response HttpResponse */

		foreach ($metaData as $name => $content) {

			if ($name == 'metaKeywords') {
				$content = join(', ', explode(';', $content));
			}

			$response->getContext()->addToLayoutSnippet($name, $contents);
		}
	}

	private function getMetaContent(PageLocalization $pageLocalization, $metaName)
	{
		$useParent = $this->useParentOnEmptyMetadata();

		$value = $pageLocalization->getProperty($metaName);

		if (empty($value) && $useParent) {

			$ancestors = $pageLocalization->getAuthorizationAncestors();

			foreach ($ancestors as $ancestor) {

				if ($ancestor instanceof PageLocalization) {

					$value = $ancestor->getProperty($metaName);

					if ( ! empty($value)) {
						break;
					}
				}
			}
		}

		return $value;
	}

}
