<?php

namespace Supra\Controller\Pages\Entity\Abstraction;

use Doctrine\Common\Collections\ArrayCollection;
use Supra\Controller\Pages\Entity\BlockProperty;
use Supra\Authorization\AuthorizedEntityInterface;
use Supra\User\Entity\Abstraction\User;
use Supra\Authorization\PermissionType;

/**
 * @Entity
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({"template" = "Supra\Controller\Pages\Entity\TemplateData", "page" = "Supra\Controller\Pages\Entity\PageData"})
 * @Table(name="page_localization", uniqueConstraints={@UniqueConstraint(name="locale_path_idx", columns={"locale", "path"})}))
 */
abstract class Data extends Entity implements AuthorizedEntityInterface
{
	const ACTION_EDIT_PAGE_NAME = 'edit_page';
	const ACTION_EDIT_PAGE_MASK = 4; // ==> MaskBuilder::MASK_EDIT 
	
	const ACTION_PUBLISH_PAGE_NAME = 'publish_page';
	const ACTION_PUBLISH_PAGE_MASK = 256; // ==> MaskBuilder.MASK_OWNER >> 1
	
	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $locale;

	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $title;

	/**
	 * Duplicate FK, still needed for DQL when it's not important what type the entity is
	 * @ManyToOne(targetEntity="Page", cascade={"persist"}, inversedBy="data", fetch="EAGER")
	 * @JoinColumn(name="master_id", referencedColumnName="id", nullable=true)
	 * @var Page
	 */
	protected $master;

	/**
	 * Construct
	 * @param string $locale
	 */
	public function __construct($locale)
	{
		parent::__construct();
		$this->setLocale($locale);
	}

	/**
	 * @param string $locale
	 */
	protected function setLocale($locale)
	{
		$this->locale = $locale;
	}

	/**
	 * @return string
	 */
	public function getLocale()
	{
		return $this->locale;
	}

	/**
	 * @param string $title
	 */
	public function setTitle($title)
	{
		$this->title = $title;
	}

	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * Set master object (page/template)
	 * @param Page $master
	 */
	public function setMaster(Page $master)
	{
		$this->matchDiscriminator($master);
		
//		if ($this->writeOnce($this->master, $master)) {
			$this->master = $master;
			$master->setData($this);
//		}
	}
	
	/**
	 * Get master object (page/template)
	 * @return Page
	 */
	public function getMaster()
	{
		return $this->master;
	}
	
	/**
	 * Get page and it's template hierarchy starting with the root template
	 * @return PageSet
	 * @throws Exception\RuntimeException
	 */
	abstract public function getTemplateHierarchy();
	
	
	public function authorize(User $user, $permissionType) 
	{
		return true;
	}
	
	/**
	 * @return string
	 */
	public function getAuthorizationId() 
	{
		return $this->getId();
	}
	
	/**
	 * @return string
	 */
	public function getAuthorizationClass() 
	{
		return __CLASS__;
	}	
	
	public function getAuthorizationAncestors($includingSelf = true) 
	{
		return $this->getAncestors(0, $includeSelf);
	}
	
	public function getPermissionTypes() 
	{
		return array(
			self::ACTION_EDIT_PAGE_NAME => new PermissionType(self::ACTION_EDIT_PAGE_NAME, self::ACTION_EDIT_PAGE_MASK),
			self::ACTION_PUBLISH_PAGE_NAME => new PermissionType(self::ACTION_PUBLISH_PAGE_NAME, self::ACTION_PUBLISH_PAGE_MASK)
		);
	}	
}