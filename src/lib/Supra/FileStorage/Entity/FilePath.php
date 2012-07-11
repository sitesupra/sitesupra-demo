<?php

namespace Supra\FileStorage\Entity;

/**
 * @Entity
 * @Table(name="file_path")
 */
class FilePath extends Abstraction\Entity
{

	/**
	 * @Column(type="string", name="system_path", nullable=true)
	 * @var integer
	 */
	protected $systemPath;

	/**
	 * @Column(type="string", name="web_path", nullable=true)
	 * @var integer
	 */
	protected $webPath;

	public function setId($id)
	{
		$this->id = $id;
	}

	/**
	 * Returns file system path
	 * 
	 * @return string 
	 */
	public function getSystemPath()
	{
		return $this->systemPath;
	}

	public function setSystemPath($systemPath)
	{
		$this->systemPath = $systemPath;
	}

	public function getWebPath()
	{
		return $this->webPath;
	}

	public function setWebPath($webPath)
	{
		$this->webPath = $webPath;
	}

}