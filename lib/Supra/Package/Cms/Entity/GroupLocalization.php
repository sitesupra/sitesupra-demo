<?php

namespace Supra\Package\Cms\Entity;

/**
 * @Entity
 * @method GroupPage getMaster()
 */
class GroupLocalization extends Abstraction\Localization
{

	const DISCRIMINATOR = self::GROUP_DISCR;

	/**
	 * Overrides the default value
	 * @var boolean
	 */
	protected $visibleInSitemap = false;

	/**
	 * Overrides the default value
	 * @var boolean
	 */
	protected $visibleInMenu = false;

	/**
	 * Overrides the default value
	 * @var boolean
	 */
	protected $includedInSearch = false;

	/**
	 * Flag which marks the entity created automatically on miss
	 * @var boolean
	 */
	private $persistent = true;

	/**
	 * Creates new group localization object, sets ID equal to master ID, will
	 * regenerate if persisted
	 * @param string $locale
	 * @param GroupPage $groupPage
	 */
	public function __construct($locale, GroupPage $groupPage)
	{
		parent::__construct($locale);
		$this->id = $groupPage->getId();
		$this->title = $groupPage->getTitle();
		$this->master = $groupPage;
		$this->persistent = false;
	}

	public function getPathPart()
	{
		return null;
	}

	public function getPath()
	{
		return null;
	}

	public function getParentPath()
	{
		$parent = $this->getParent();

		if (empty($parent)) {
			return null;
		}

		$path = $parent->getPath();

		if (is_null($path)) {
			$path = $parent->getParentPath();
		}

		return $path;
	}

	/**
	 * Update the title for master as well
	 * @param string $title
	 */
	public function setTitle($title)
	{
		parent::setTitle($title);

		$this->master->setTitle($title);
	}

	/**
	 * @return boolean
	 */
	public function isPersistent()
	{
		return $this->persistent;
	}

	/**
	 * Sets the entity as persisted
	 */
	public function setPersistent()
	{
		$this->persistent = true;
	}

	/**
	 * Don't allow setting this
	 * @param boolean $includedInSearch
	 */
	public function includedInSearch($includedInSearch)
	{
		
	}

	/**
	 * Don't allow setting this
	 * @param boolean $visibleInMenu
	 */
	public function setVisibleInMenu($visibleInMenu)
	{
		
	}

	/**
	 * Don't allow setting this
	 * @param boolean $visibleInSitemap
	 */
	public function setVisibleInSitemap($visibleInSitemap)
	{
		
	}

	public function isVisibleInSitemap()
	{
		return false;
	}

	public function isVisibleInMenu()
	{
		return false;
	}

	public function isIncludedInSearch()
	{
		return false;
	}

	public static function getPreviewFilenameForLocalizationAndRevision($localizationId, $revisionId)
	{
		return FALSE;
	}

	public static function getPreviewUrlForLocalizationAndRevision($localizationId, $revisionId)
	{
		return '/cms/lib/supra/img/sitemap/preview/blank.jpg';
	}

}
