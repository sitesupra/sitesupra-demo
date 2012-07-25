<?php

namespace Supra\NestedSet\Command;

/**
 * Description of ValidationArrayNode
 */
class ValidationArrayNode extends \Supra\NestedSet\Node\ArrayNode
{
	public $originalData;

	public function __construct(array $original)
	{
		$this->originalData = $original;
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
		
		return sprintf('%20s   %20s   %20s   %20s',
				$id,
				$leftStatus,
				$rightStatus,
				$levelStatus);
	}

	public function isOk()
	{
		return $leftStatus = $this->originalData['left'] == $this->left &&
				$rightStatus = $this->originalData['right'] == $this->right &&
				$levelStatus = $this->originalData['level'] == $this->level;
	}

	public function getId()
	{
		return $this->originalData['id'];
	}
}
