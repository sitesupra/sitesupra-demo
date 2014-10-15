<?php

namespace Supra\Package\Cms\Pages\Markup\Abstraction;

abstract class SupraMarkupBlockConstructor extends SupraMarkupElement
{

	/**
	 * @var string
	 */
	private $startClass;

	/**
	 * @var string
	 */
	private $endClass;

	function __construct($signature, $startClass, $endClass)
	{
		$this->signature = $signature;
		$this->startClass = $startClass;
		$this->endClass = $endClass;
	}

	/**
	 * @param string $className
	 * @return SupraMarkupElement
	 */
	private function makePart($className)
	{
		/* @var $part SupraMarkupElement */
		$part = new $className();
		$part->setSignature($this->signature);

		return $part;
	}

	/**
	 * @return SupraMarkupElement
	 */
	public function makeStart()
	{
		return $this->makePart($this->startClass);
	}

	/**
	 * @return SupraMarkupElement
	 */
	public function makeEnd()
	{
		return $this->makePart($this->endClass);
	}

}

