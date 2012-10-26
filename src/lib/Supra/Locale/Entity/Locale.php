<?php

namespace Supra\Locale\Entity;

use Supra\Database;
use Supra\Locale\Exception;
use Supra\Locale\Locale as LocaleInterface;
use Supra\Cms\CrudManager\CrudEntityInterface;
use Supra\Validator\FilteredInput;
use Supra\Cms\Exception\CmsException;

/**
 * @Entity(repositoryClass="Supra\Cms\LocaleManager\LocaleManagerCrudRepository")
 * @Table(indexes={@index(name="context_idx", columns={"context"})})
 */
class Locale extends Database\Entity implements LocaleInterface, CrudEntityInterface
{

	const DEFAULT_CONTEXT = 'unified';

	/**
	 * @Id
	 * @Column(type="string", nullable=false)
	 * @var string
	 */
	protected $context = self::DEFAULT_CONTEXT;

	/**
	 * @Column(type="string", nullable=false)
	 * @var string
	 */
	protected $title;

	/**
	 * @Column(type="string", nullable=false)
	 * @var string
	 */
	protected $country;

	/**
	 * @Column(type="array", nullable=false)
	 * @var array
	 */
	protected $properties = array();

	/**
	 * @Column(type="boolean", nullable=false)
	 * @var boolean
	 */
	protected $active = true;

	/**
	 * @Column(name="is_default", type="boolean", nullable=false)
	 * @var boolean
	 */
	protected $default = false;

	/**
	 * 
	 */
	function __construct($context = self::DEFAULT_CONTEXT)
	{
// Do nothing - parent constructor calls $this->regenerateId(), 
// which is not what we want here.

		$this->context = $context;
	}

	/**
	 * @return string
	 * @throws Exception\RuntimeException
	 */
	public function getId()
	{
		if (empty($this->id)) {
			throw new Exception\RuntimeException('Locale id not set.');
		}

		return $this->id;
	}

	/**
	 * @param string $id
	 */
	public function setId($id)
	{
		$this->id = $id;
	}

	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->title;
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
	public function getCountry()
	{
		return $this->country;
	}

	/**
	 * @param string $country
	 */
	public function setCountry($country)
	{
		$this->country = $country;
	}

	/**
	 * @return array
	 */
	public function getProperties()
	{
		return $this->properties;
	}

	/**
	 * @param array $properties
	 */
	public function setProperties($properties)
	{
		$this->properties = $properties;
	}

	/**
	 * Sets property
	 * @param string $name
	 * @param mixed $value 
	 */
	public function addProperty($name, $value)
	{
		$this->properties[$name] = $value;
	}

	/**
	 * Returns propery
	 * @param string $name
	 * @return mixed
	 */
	public function getProperty($name)
	{
		if (isset($this->properties[$name])) {
			return $this->properties[$name];
		}
	}

	/**
	 * 
	 * @return boolean
	 */
	public function isActive()
	{
		return $this->active;
	}

	/**
	 * 
	 * @param boolean $active
	 */
	public function setActive($active)
	{
		$this->active = $active;
	}

	/**
	 * @return boolean
	 */
	public function isDefault()
	{
		return $this->default;
	}

	/**
	 * @param boolean $default
	 */
	public function setDefault($default)
	{
		$this->default = $default;
	}

	/**
	 * @return string
	 */
	public function getContext()
	{
		return $this->context;
	}

	/**
	 * @return array
	 */
	public function getEditValues()
	{
		return array(
			'id' => $this->getId(),
			'dummyId' => $this->getId(),
			'title' => $this->getTitle(),
			'country' => $this->getCountry(),
			'active' => $this->isActive(),
			'default' => $this->isDefault(),
			'flagProperty' => $this->getProperty('flag'),
			'languageProperty' => $this->getProperty('language')
		);
	}

	/**
	 * @return array
	 */
	public function getListValues()
	{
		return array(
			'id' => $this->getId(),
			'title' => $this->getTitle(),
			'country' => $this->getCountry(),
			'active' => $this->isActive(),
			'default' => $this->isDefault(),
			'flagProperty' => $this->getProperty('flag'),
			'languageProperty' => $this->getProperty('language')
		);
	}

	/**
	 * @param FilteredInput $editValues
	 * @param mixed $locale
	 */
	public function setEditValues(FilteredInput $editValues, $isNew = null)
	{
		if ($isNew == true) {

			$this->setId($editValues->get('dummyId'));
		} else {

			// Nothing for now.
		}

		$this->setTitle($editValues->get('title'));
		$this->setCountry($editValues->get('country'));

		$newIsActive = $editValues->get('active') == 'true';
		if ($this->isActive() == true && $newIsActive == false && $this->isDefault()) {
			throw new CmsException(null, 'Can not deactivate default locale!');
		}

		$this->setActive($newIsActive);

		$this->addProperty('flag', $editValues->get('flagProperty'));
		$this->addProperty('language', $editValues->get('languageProperty'));
	}

}
