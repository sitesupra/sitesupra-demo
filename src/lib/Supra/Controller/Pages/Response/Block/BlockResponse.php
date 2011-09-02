<?php

namespace Supra\Controller\Pages\Response\Block;

use Supra\Response\TwigResponse;
use Supra\Editable\EditableInterface;
use Supra\Controller\Pages\Entity;
use Supra\Log\Writer\WriterInterface;
use Supra\ObjectRepository\ObjectRepository;

/**
 * Response for block
 * @TODO: get rid of this classes, block should be able to choose it's own response object
 */
abstract class BlockResponse extends TwigResponse
{
	/**
	 * @var WriterInterface
	 */
	protected $log;
	
	/**
	 * @var Entity\Abstraction\Block
	 */
	private $block;
	
	public function __construct()
	{
		$this->log = ObjectRepository::getLogger($this);
	}
	
	/**
	 * @return Entity\Abstraction\Block
	 */
	public function getBlock()
	{
		return $this->block;
	}

	/**
	 * @param Block $block
	 */
	public function setBlock(Entity\Abstraction\Block $block)
	{
		$this->block = $block;
	}
	
}
