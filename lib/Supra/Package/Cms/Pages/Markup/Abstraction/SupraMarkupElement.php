<?php

namespace Supra\Package\Cms\Pages\Markup\Abstraction;

use Supra\Package\Cms\Pages\Markup\Exception;

abstract class SupraMarkupElement extends ElementAbstraction
{

	/**
	 * @var string
	 */
	protected $signature;

	/**
	 * @return string
	 */
	public function getSignature()
	{
		return $this->signature;
	}

	/**
	 * @param string $signature 
	 */
	public function setSignature($signature)
	{
		$this->signature = $signature;
	}

	public function setSource($source)
	{
		$this->source = $source;
	}

	public function getSource($source)
	{
		return $this->source;
	}

	protected function extractValueFromSource($key)
	{
		$key = preg_quote($key);

		$match = array();

		preg_match('@' . $key . '="(?<value>.*?)"@ims', $this->source, $match);

		if (empty($match['value'])) {
			throw new Exception\RuntimeException('Could not extract value for key "' . $key . '" from source.');
		}

		return $match['value'];
	}

	public function parseSource()
	{
		return true;
	}

}
