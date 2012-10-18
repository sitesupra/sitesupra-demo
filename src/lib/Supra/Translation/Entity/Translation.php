<?php

namespace Supra\Translation\Entity;

use Supra\Database\Entity;

/**
 * @Entity(repositoryClass="Supra\Translation\Crud\TranslationCrudRepository")
 */
class Translation extends Entity implements \Supra\Cms\CrudManager\CrudEntityInterface
{
	const STATUS_FOUND = 'Found';
	const STATUS_MANUAL = 'Manual';
	const STATUS_IMPORT = 'Imported';
	const STATUS_CHANGED = 'Changed';

	/**
	 * @Column(type="string", nullable=true)
	 * @var string
	 */
	private $resource;

	/**
	 * @Column(type="string", nullable=false)
	 * @var string
	 */
	private $status;

	/**
	 * @Column(type="string", nullable=false)
	 * @var string
	 */
	private $locale;

	/**
	 * @Column(type="string", nullable=false)
	 * @var string
	 */
	private $domain;
	
	/**
	 * @Column(type="text", nullable=false)
	 * @var string
	 */
	private $name;

	/**
	 * @Column(type="text", nullable=false)
	 * @var string
	 */
	private $value;

	/**
	 * @Column(type="text", nullable=true)
	 * @var string
	 */
	private $comment;

	public function getResource()
	{
		return $this->resource;
	}

	public function setResource($resource)
	{
		$this->resource = $resource;
		return $this;
	}

	public function getLocale()
	{
		return $this->locale;
	}

	public function setLocale($locale)
	{
		$this->locale = $locale;
		return $this;
	}

	public function getDomain()
	{
		return $this->domain;
	}

	public function setDomain($domain)
	{
		$this->domain = $domain;
		return $this;
	}

	public function getName()
	{
		return $this->name;
	}

	public function setName($name)
	{
		$this->name = $name;
		return $this;
	}

	public function getValue()
	{
		return $this->value;
	}

	public function setValue($value)
	{
		$this->value = $value;
		return $this;
	}

	public function getEditValues()
	{
		foreach ($this as $property => $value) {
			$data[$property] = $value;
		}

		$data['value_short'] = $data['value'];

		if (mb_strlen($data['value_short']) > 102) {
			$data['value_short'] = mb_substr($data['value'], 0, 100) . '...';
		}

		return $data;
	}

	public function getListValues()
	{
		$data = $this->getEditValues();

		return $data;
	}

	public function setEditValues(\Supra\Validator\FilteredInput $editValues, $locale = null)
	{
		$changed = false;

		foreach ($editValues as $property => $value) {
			if (property_exists($this, $property)) {
				if ((string) $this->$property !== (string) $value) {
					$this->$property = $value;
					$changed = true;
				}
			}
		}

		if ($changed) {

			// Change the status
			switch ((string) $this->status) {
				case self::STATUS_FOUND:
					$this->status = self::STATUS_MANUAL;
					break;
				case self::STATUS_IMPORT:
					$this->status = self::STATUS_CHANGED;
					break;
				case '':
					$this->status = self::STATUS_MANUAL;
					break;
			}
		}
	}

	public function getStatus()
	{
		return $this->status;
	}

	public function setStatus($status)
	{
		$this->status = $status;
		return $this;
	}

	public function getComment()
	{
		return $this->comment;
	}

	public function setComment($comment)
	{
		$this->comment = $comment;
		return $this;
	}

}
