<?php

namespace Supra\Controller\Pages\Entity\ReferencedElement;

use Supra\Controller\Pages\Entity\PageLocalization;
use Supra\FileStorage\Entity\File;
use Supra\ObjectRepository\ObjectRepository;

/**
 * @Entity
 */
class LinkReferencedElement extends ReferencedElementAbstract
{
	const TYPE_ID = 'link';
	
	const RESOURCE_PAGE = 'page';
	const RESOURCE_FILE = 'file';
	const RESOURCE_LINK = 'link';
	
	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $resource;
	
	/**
	 * @Column(type="string", nullable="true")
	 * @var string
	 */
	protected $href;
	
	/**
	 * @Column(type="string", nullable="true")
	 * @var string
	 */
	protected $target;
	
	/**
	 * @Column(type="string", nullable="true")
	 * @var string
	 */
	protected $title;
	
	/**
	 * Page ID to keep link data without existant real page.
	 * SQL naming for CMS usage, should be fixed (FIXME).
	 * @Column(type="sha1", nullable="true")
	 * @var string
	 */
	protected $pageId;
	
	/**
	 * File ID to keep link data without existant real file.
	 * SQL naming for CMS usage, should be fixed (FIXME).
	 * @Column(type="sha1", nullable="true")
	 * @var string
	 */
	protected $fileId;
	
	/**
	 * @return string
	 */
	public function getResource()
	{
		return $this->resource;
	}

	/**
	 * @param string $resource
	 */
	public function setResource($resource)
	{
		$this->resource = $resource;
	}

	/**
	 * @return string
	 */
	public function getHref()
	{
		return $this->href;
	}

	/**
	 * @param string $resource
	 */
	public function setHref($href)
	{
		$this->href = $href;
	}
	
	/**
	 * @return string
	 */
	public function getTarget()
	{
		return $this->target;
	}

	/**
	 * @param string $target
	 */
	public function setTarget($target)
	{
		$this->target = $target;
	}

	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * @param string $resource
	 */
	public function setTitle($title)
	{
		$this->title = $title;
	}

	/**
	 * @return string
	 */
	public function getPageId()
	{
		return $this->pageId;
	}

	/**
	 * @param string $pageId
	 */
	public function setPageId($pageId)
	{
		$this->pageId = $pageId;
	}

	/**
	 * @return string
	 */
	public function getFileId()
	{
		return $this->fileId;
	}

	/**
	 * @param string $fileId
	 */
	public function setFileId($fileId)
	{
		$this->fileId = $fileId;
	}
	
	/**
	 * {@inheritdoc}
	 * @return array
	 */
	public function toArray()
	{
		$array = array(
			'type' => self::TYPE_ID,
			'resource' => $this->resource,
			'title' => $this->title,
			'target' => $this->target,
			'page_id' => $this->pageId,
			'file_id' => $this->fileId,
			'href' => $this->href,
		);
		
		return $array;
	}
	
	/**
	 * {@inheritdoc}
	 * @param array $array
	 */
	public function fillArray(array $array)
	{
		$this->resource = $array['resource'];
		$this->title = $array['title'];
		$this->target = $array['target'];
		$this->pageId = $array['page_id'];
		$this->fileId = $array['file_id'];
		$this->href = $array['href'];
	}
	
	/**
	 * Get URL of the link using $context for ObjectRepository calls
	 * @param mixed $context
	 * @param string $localeId
	 */
	public function getUrl($context)
	{
		$url = null;
		$localeManager = ObjectRepository::getLocaleManager($context);
		$localeId = $localeManager->getCurrent()->getId();
		
		switch ($this->getResource()) {
			
			case self::RESOURCE_PAGE:
				$pageId = $this->getPageId();

				$em = ObjectRepository::getEntityManager($context);

				$pageDataEntity = PageLocalization::CN();

				$query = $em->createQuery("SELECT d FROM $pageDataEntity d
						WHERE d.locale = ?0 AND d.master = ?1");

				$params = array(
					0 => $localeId,
					1 => $pageId,
				);

				$query->execute($params);

				try {
					/* @var $page PageLocalization */
					$pageData = $query->getSingleResult();
					$url = '/' . $pageData->getPath();
				} catch (\Doctrine\ORM\NoResultException $noResults) {
					//ignore
				}
				break;
			
			case self::RESOURCE_FILE:
				$fileId = $this->getFileId();
				$fs = ObjectRepository::getFileStorage($context);
				$em = $fs->getDoctrineEntityManager();
				$file = $em->find(File::CN(), $fileId);

				if ($file instanceof File) {
					$url = $fs->getWebPath($file);
				}

				break;
				
			case self::RESOURCE_LINK:
				$url = $this->getHref();
				break;

			default:
				$this->log()->warn("Unrecognized resource for supra html markup link tag, data: $this");
		}
		
		return $url;
	}

}
