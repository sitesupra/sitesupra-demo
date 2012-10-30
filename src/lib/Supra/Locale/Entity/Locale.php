<?php

namespace Supra\Locale\Entity;

use Supra\Locale\Locale as LocaleObject;
use Supra\Cms\CrudManager\CrudEntityInterface;
use Supra\Validator\FilteredInput;
use Supra\Cms\Exception\CmsException;

/**
 * @Entity(repositoryClass="Supra\Cms\LocaleManager\LocaleManagerCrudRepository")
 * @Table(indexes={@index(name="context_idx", columns={"context"})})
 */
class Locale extends LocaleObject implements CrudEntityInterface
{

	/**
	 * @Id
	 * @Column(type="string", nullable=false)
	 * @var string
	 */
	protected $context = self::DEFAULT_CONTEXT;

	/**
	 * @Id
	 * @Column(type="string", nullable=false)
	 * @var string
	 */
	protected $id;

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

	/**
	 * Loads full name of the class
	 * TODO: Decide is it smart
	 */
	public static function CN()
	{
		return get_called_class();
	}

}
