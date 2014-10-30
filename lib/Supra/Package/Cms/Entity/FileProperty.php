<?php

namespace Supra\Package\Cms\Entity;

use Supra\Editable\EditableAbstraction;

/**
 * @Entity
 */
class FileProperty extends Abstraction\Entity
{
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

	/**
	 * @ManyToOne(targetEntity="Supra\Package\Cms\Entity\Abstraction\File", inversedBy="properties")
	 * @var Abstraction\File
	 */
	protected $file;
	
	/**
	 * @param string $name
	 * @param Abstraction\File $file
	 */
	public function __construct($name, Abstraction\File $file)
	{
		parent::__construct();
		
		$this->name = $name;
		$this->file = $file;
	}
	
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
	 * @return Abstraction\File
	 */
	public function getFile()
	{
		return $this->file;
	}
	
	/**
	 * @param Abstraction\File $file
	 */
	public function setFile(Abstraction\File $file)
	{
		$this->file = $file;
	}
	
	/**
	 * @return mixed
	 */
	public function getValue()
	{
		return $this->value;
	}
	
	/**
	 * @param mixed $value
	 * @param EditableAbstraction $editable
	 */
	public function setEditableValue($value, EditableAbstraction $editable)
	{
		$editable->setContent($value);		
		$this->value = $editable->getContent();
	}
}
