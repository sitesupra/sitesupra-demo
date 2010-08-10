<?php

namespace Supra\NestedSet\Node;

/**
 * 
 */
abstract class Node implements NodeInterface
{
	public static function output(array $nodes)
	{
		$array = array();
		foreach ($nodes as $item) {
			$array[$item->getStart()] = $item;
		}
		
		ksort($array);

		$tree = '';
		foreach ($array as $item) {
			$tree .= $item->dump();
			$tree .= "\n";
		}
		return $tree;
	}

	public function __toString()
	{
		return $this->getTitle();
	}

	public function dump()
	{
		$string = '';
		$string .= \str_repeat('  ', $this->getDepth());
		$string .= '(';
		$string .= $this->getStart();
		$string .= '; ';
		$string .= $this->getEnd();
		$string .= ') ';
		$string .= $this->getDepth();
		$string .= ' ';
		$string .= $this->getTitle();
		return $string;
	}

}