<?php

namespace Supra\Controller\Pages\Entity\Theme;

use Supra\Database;

/**
 * @Entity 
 * ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 * Table(uniqueConstraints={@UniqueConstraint(name="unique_name_in_layout_idx", columns={"name", "layout_id"})}))
 */
class ThemeLayoutPlaceholderSetContainer extends Database\Entity
{

	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $name;

	/**
	 * @ManyToOne(targetEntity="ThemeLayout", inversedBy="containers")
	 * @JoinColumn(name="layout_id", referencedColumnName="id")
	 * @var ThemeLayout
	 */
	protected $layout;

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param string $name 
	 */
	public function setName($name)
	{
		$this->name = $name;
	}

	/**
	 * @return ThemeLayout
	 */
	public function getLayout()
	{
		return $this->layout;
	}

	/**
	 * @param ThemeLayout $layout 
	 */
	public function setLayout(ThemeLayout $layout = null)
	{
		$this->layout = $layout;
	}
}
