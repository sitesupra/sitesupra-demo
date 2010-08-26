<?php

namespace Supra\Tests\NestedSet\Reflection;

/**
 * Creates list of magic methods to be included in the doctrine entity class
 * php documentation
 */
class DoctrineNode extends \PHPUnit_Framework_TestCase
{
	const CLASS_NAME = 'Supra\NestedSet\Node\DoctrineNode';

	protected function getCommentValue($comment, $name, $limit = 1, $size = 1)
	{
		if (preg_match_all('!@' . $name . '\s+([^@\r\n]+)!i', $comment, $match)) {
			$strings = \array_map(array($this, 'splitString'), $match[1]);
		} else {
			$strings = array();
		}

		$strings = $this->sizeArray($strings, $limit);
		\array_walk($strings, array($this, 'sizeArray'), $size);

		if ($size == 1) {
			foreach ($strings as &$string) {
				$string = $string[0];
			}
		}
		
		if ($limit == 1) {
			$strings = $strings[0];
		}
		return $strings;
	}

	protected function splitString($string)
	{
		$string = trim($string);
		$list = \preg_split('!\s+!', $string);
		return $list;
	}

	protected function sizeArray($array, $size)
	{
		if ($size == 0) {
			return $array;
		}
		$count = count($array);
		if ($count < $size) {
			$array = \array_pad($array, $size, null);
		} elseif ($count > $size) {
			$array = \array_slice($array, 0, $size);
		}
		return $array;
	}

	public function testMethods()
	{
		$reflection = new \ReflectionClass(static::CLASS_NAME);

		$methodReflections = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);

		$methods = array();
		/* @var $method \ReflectionMethod */
		foreach ($methodReflections as $method) {
			$methodDoc = array();
			$methodDoc['name'] = $method->getName();

			$comment = $method->getDocComment();
			$inner = $this->getCommentValue($comment, 'nestedSetMethod', 0);
			if (count($inner) == 0) {
				\Log::debug("Skip {$methodDoc['name']}");
				continue;
			}
			
			$return = $this->getCommentValue($comment, 'return');
			if ($return !== null) {
				$methodDoc['return'] = $return;
			} else {
				$methodDoc['return'] = 'void';
			}

			$methodDoc['arguments'] = $this->getCommentValue($comment, 'param', 0, 2);

			$arguments = $method->getParameters();

			if (count($arguments) != count($methodDoc['arguments'])) {
				self::fail("Count of arguments differs for ", $methodDoc);
			}
//			foreach ($arguments as $argument) {
//
//			}

			array_push($methods, $methodDoc);
		}

		foreach ($methods as &$methodDoc) {
			$arguments = array();
			foreach ($methodDoc['arguments'] as $argument) {
				$arguments[] = "{$argument[0]} {$argument[1]}";
			}
			$arguments = implode(', ', $arguments);
			$methodDoc = " * @method {$methodDoc['return']} {$methodDoc['name']}({$arguments})";
		}

		\Log::debug(implode("\n", $methods));
	}
}