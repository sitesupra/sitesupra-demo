<?php

namespace Supra\Controller\Pages\Entity;

use Supra\Controller\Pages\Exception,
		Supra\Controller\Pages\Entity\Abstraction\Entity,
		Supra\Controller\Pages\Entity\Abstraction\Data,
		Supra\Controller\Pages\Entity\Abstraction\Block;

/**
 * Block property class.
 * @Entity
 * @Table(name="block_property")
 */
class BlockProperty extends Entity
{
	/**
	 * @Id
	 * @Column(type="integer")
	 * @GeneratedValue
	 * @var integer
	 */
	protected $id;

	/**
	 * @ManyToOne(targetEntity="Supra\Controller\Pages\Entity\Abstraction\Data")
	 * @JoinColumn(name="data_id", referencedColumnName="id", nullable=false)
	 * @var Data
	 */
	protected $data;

	/**
	 * @ManyToOne(targetEntity="Supra\Controller\Pages\Entity\Abstraction\Block")
	 * @JoinColumn(name="block_id", referencedColumnName="id", nullable=false)
	 * @var Block
	 */
	protected $block;

	/**
	 * Content type (class name of Supra\Editable\EditableInterface class)
	 * @TODO remove nullable
	 * @Column(type="string", nullable="true")
	 * @var string
	 */
	protected $type;
	
	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $name;

	/**
	 * @Column(type="text", nullable=true)
	 * @var string
	 */
	protected $value;

	/**
	 * Constructor
	 * @param string $name
	 * @param string $type
	 */
	public function __construct($name, $type)
	{
		$this->name = $name;
		$this->type = $type;
	}

	/**
	 * @return integer
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return Data
	 */
	public function getData()
	{
		return $this->data;
	}

	/**
	 * @param Data $data
	 */
	public function setData(Data $data)
	{
		if ($this->writeOnce($this->data, $data)) {
			$this->checkScope($this->data);
			$data->addBlockProperty($this);
		}
	}

	/**
	 * @return Block
	 */
	public function getBlock()
	{
		return $this->block;
	}

	/**
	 * @param Block $block
	 */
	public function setBlock(Block $block)
	{
		if ($this->writeOnce($this->block, $block)) {
			$this->checkScope($this->block);
			$block->addBlockProperty($this);
		}
	}
	
	/**
	 * Get content type
	 * @return string
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * Set content type
	 * @param string $type 
	 */
	public function setType($type)
	{
		$this->type = $type;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 * TODO: should we validate the value? should we serialize arrays passed?
	 * @param string $value
	 */
	public function setValue($value)
	{
		$this->value = $value;
	}

	/**
	 * Checks if associations scopes are matching
	 * @param Entity $object
	 */
	private function checkScope(Entity &$object)
	{
		if ( ! empty($this->data) && ! empty($this->block)) {
			try {
				// do not-strict match (allows page data with template block)
				$this->data->matchDiscriminator($this->block, false);
			} catch (Exception $e) {
				$object = null;
				throw $e;
			}
		}
	}

}