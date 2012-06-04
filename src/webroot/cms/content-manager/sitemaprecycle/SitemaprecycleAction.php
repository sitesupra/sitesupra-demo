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
use Supra\Database\Doctrine\Hydrator\ColumnHydrator;

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
		// Main
		$this->restorePageVersion();
		
		// Audit log
		$pageData =  $this->getPageLocalization();
		$this->writeAuditLog('%item% restored', $pageData);
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
			$revisionIds = array();
			foreach ($trashRevisions as $revision) {
				$revisionIds[] = $revision->getId();
				$trashRevisionsById[$revision->getId()] = $revision;
			}

			$searchCriteria = array(
				'locale' => $localeId,
				'revision' => $revisionIds
			);
			
			$dql = 'SELECT l.master FROM page:Abstraction\Localization l
				WHERE l.locale = :locale
				AND l.revision IN (:revision)';
			
			$masterIds = $auditEm->createQuery($dql)
					->execute($searchCriteria, ColumnHydrator::HYDRATOR_ID);
			
			$masters = $auditEm->createQuery("SELECT m FROM page:Abstraction\AbstractPage m WHERE m.id IN (:id)")
					->execute(array('id' => $masterIds));

			foreach ($masters as $master) {

				/* @var $master Entity\Abstraction\AbstractPage */
				$pageLocalization = $master->getLocalization($localeId);
				
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
					'revision'	=> $pageLocalization->getRevisionId(),
					'localized' => true,
					// TODO: hardcoded	
					'published' => false,
					'scheduled' => true,
					'date'		=> $dateCreated,
					'icon'		=> 'page',
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