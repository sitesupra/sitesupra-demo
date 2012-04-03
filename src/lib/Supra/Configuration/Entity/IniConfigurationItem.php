<?php

namespace Supra\Configuration\Entity;

use Supra\Database;

/**
 * @Entity
 * @Table(indexes={
 * 		@index(name="filename_idx", columns={"filename"}),
 * 		@index(name="sectionName_idx", columns={"section", "name"})
 * })
 */
class IniConfigurationItem extends Database\Entity
{

	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $filename;

	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $section;

	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $name;

	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $value;

	public function getSection()
	{
		return $this->section;
	}

	public function setSection($section)
	{
		$this->section = $section;
	}

	public function getName()
	{
		return $this->name;
	}

	public function setName($name)
	{
		$this->name = $name;
	}

	public function getValue()
	{
		return $this->value;
	}

	public function setValue($value)
	{
		$this->value = $value;
	}

	public function getFilename()
	{
		return $this->filename;
	}

	public function setFilename($filename)
	{
		$this->filename = $filename;
	}

	public function getUniqueName()
	{
		return self::makeUniqueName($this->filename, $this->section, $this->name);
	}

	public static function makeUniqueName($filename, $section, $name)
	{
		return md5($filename . $section . $name);
	}

}
