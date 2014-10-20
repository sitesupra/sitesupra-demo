<?php

// @FIXME

namespace Supra\NestedSet\Command;

/**
 * Description of ValidationArrayNode
 */
class ValidationArrayNode extends \Supra\NestedSet\Node\ArrayNode
{
	public $originalData;
	
	/**
	 * 
	 */
	public function __construct(\Supra\NestedSet\Node\NodeInterface $entity)
	{
		$this->originalData = array(
			'id' => $entity->getId(),
			'left' => $entity->getLeftValue(),
			'right' => $entity->getRightValue(),
			'level' => $entity->getLevel(),
			'isLeafInterface' => false,
		);
		
		if ($entity instanceof \Supra\FileStorage\Entity\File
				|| $entity instanceof \Supra\FileStorage\Entity\Image) {
			
			$this->originalData['isLeafInterface'] = true;
		}
	}

	public function getNodeTitle()
	{
		$id = $this->originalData['id'];

		$leftStatus = $rightStatus = $levelStatus = null;

		if ($this->originalData['left'] != $this->left) {
			$leftStatus = sprintf('LEFT %4d --> %4s', $this->originalData['left'], $this->left);
		}
		if ($this->originalData['right'] != $this->right) {
			$rightStatus = sprintf('RIGHT %4d --> %4s', $this->originalData['right'], $this->right);
		}
		if ($this->originalData['level'] != $this->level) {
			$levelStatus = sprintf('LEVEL %4d --> %4s', $this->originalData['level'], $this->level);
		}
		
		if ($this->originalData['isLeafInterface'] && ! $this->isLeaf()) {
			$leafStatus = 'should be LEAF but has children';
		}
		
		return sprintf('%20s   %20s   %20s   %20s   %20s',
				$id,
				$leftStatus,
				$rightStatus,
				$levelStatus,
				$leafStatus
		);
	}

	public function isOk()
	{
		return $leftStatus = $this->originalData['left'] == $this->left &&
				$rightStatus = $this->originalData['right'] == $this->right &&
				$levelStatus = $this->originalData['level'] == $this->level
				&& ( $this->isLeaf() || ! $this->originalData['isLeafInterface'] )
				;
	}

	public function getId()
	{
		return $this->originalData['id'];
	}
	
	/**
	 * @return boolean
	 */
	public function isOriginallyWithLeafInterface()
	{
		return ($this->originalData['isLeafInterface'] === true);
	}
}
