<?php

namespace Supra\Cms\ContentManager\Sitemaprecycle;

use Supra\Controller\SimpleController;
use Supra\Cms\ContentManager\PageManagerAction;
use Supra\Controller\Pages\Entity;
use Supra\Controller\Pages\Request\PageRequest;
use Supra\Controller\Pages\Exception\DuplicatePagePathException;
use Supra\Cms\Exception\CmsException;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Entity\PageRevisionData;

/**
 * Sitemap
 */
class SitemaprecycleAction extends PageManagerAction
{

	public function sitemapAction()
	{
		$response = $this->loadSitemapTree(Entity\PageLocalization::CN());

		$this->getResponse()
				->setResponseData($response);
	}

	public function templatesAction()
	{
		$response = $this->loadSitemapTree(Entity\TemplateLocalization::CN());

		$this->getResponse()
				->setResponseData($response);
	}

	public function restoreAction()
	{
		$this->restorePageVersion();
		$pageData =  $this->getPageLocalization();
		$this->writeAuditLog('recycle bin restore', '%item% restored', $pageData);
	}

	protected function loadSitemapTree($entity)
	{
		$pages = array();
		$response = array();

		$localeId = $this->getLocale()->getId();

		$auditEm = ObjectRepository::getEntityManager('#audit');

		$trashRevisions = $auditEm->getRepository(PageRevisionData::CN())
				->findByType(PageRevisionData::TYPE_TRASH);

		$trashRevisionsById = array();
		if ( ! empty($trashRevisions)) {
			// collecting ids
			$revisionsId = array();
			foreach ($trashRevisions as $revision) {
				$revisionIds[] = $revision->getId();
				$trashRevisionsById[$revision->getId()] = $revision;
			}

			$searchCriteria = array(
				'locale' => $localeId,
				'revision' => $revisionIds
			);

			$pageLocalizationRepository = $auditEm->getRepository($entity);
			$pageLocalizations = $pageLocalizationRepository->findBy($searchCriteria);

			foreach ($pageLocalizations as $pageLocalization) {

				$pageInfo = array();
				$pathPart = null;
				$templateId = null;

				if ($pageLocalization instanceof Entity\PageLocalization) {
					$pathPart = $pageLocalization->getPathPart();
				}

				if ($pageLocalization instanceof Entity\PageLocalization) {
					$template = $pageLocalization->getTemplate();

					if ($template instanceof Entity\Template) {
						$templateId = $template->getId();
					}
				}

				$pageRevisionId = $pageLocalization->getRevisionId();

				$dateCreated = null;
				if ($trashRevisionsById[$pageRevisionId] instanceof PageRevisionData) {
					$revision = $trashRevisionsById[$pageRevisionId];
					$dateCreated = $revision->getCreationTime()->format('Y-m-d');
				}

				$pageInfo = array(
					'id'		=> $pageLocalization->getId(),
					'title'		=> $pageLocalization->getTitle(),
					'template'	=> $templateId,
					'path'		=> $pathPart,
					// TODO: hardcoded	
					'published' => false,
					'scheduled' => true,
					'date'		=> $dateCreated,
					'version'	=> 1,
					'icon'		=> 'page',
					'preview'	=> '/cms/lib/supra/img/sitemap/preview/page-1.jpg',
				);

				$response[] = $pageInfo;
			}

			usort($response, array($this, 'sortByDeletionDateDesc'));
		}

		return $response;
	}

	/**
	 * Sorts page data by "revision creation date" (deletion date) 
	 * 
	 * @param array $a
	 * @param array $b
	 * @return array 
	 */
	public function sortByDeletionDateDesc($a, $b)
	{
		$a = $a['date'];
		$b = $b['date'];
		
		if ($a == $b) {
			return 0;
		}
		
		return ($a > $b) ? -1 : 1;
	}

}