<?php

/*
 * Copyright (C) SiteSupra SIA, Riga, Latvia, 2015
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 */

namespace Supra\Package\Cms\Pages\Response;

use Supra\Package\Cms\Html\HtmlTag;

class ResponseContext extends \ArrayIterator
{
	/**
	 * @var array
	 */
	protected $layoutSnippetResponses = array();

	/**
	 * @param string $key
	 * @param mixed $value
	 * @return ResponseContext
	 */
	public function setValue($key, $value)
	{
		$this[$key] = $value;

		return $this;
	}

	/**
	 * @param string $key
	 * @param mixed $defaultValue
	 * @return mixed
	 */
	public function getValue($key, $defaultValue = null)
	{
		if (empty($this[$key])) {
			return $defaultValue;
		}

		return $this[$key];
	}

	/**
	 * @return array
	 */
	public function getAllValues()
	{
		return $this->getArrayCopy();
	}

	/**
	 * @param string $key
	 * @param mixed $snippet
	 * @return ResponseContext
	 */
	public function addToLayoutSnippet($key, $snippet)
	{
		$snippet = (string) $snippet;

		$hash = md5($key . '_' . $snippet);

		if (empty($this->layoutSnippetResponses[$key])) {
			$this->layoutSnippetResponses[$key] = array();
		}

		if (! isset($this->layoutSnippetResponses[$key][$hash])) {
			$this->layoutSnippetResponses[$key][$hash] = $snippet;
		}

		return $this;
	}

	/**
	 * @param string $key
	 * @return string
	 */
	public function getLayoutSnippetContents($key)
	{
		if (isset($this->layoutSnippetResponses[$key])) {
			return implode('', $this->layoutSnippetResponses[$key]);
		}

		return '';
	}

	/**
	 * @param string $key
	 * @param string $js
	 * @param string $type
	 * @return ResponseContext
	 */
	public function addJsToLayoutSnippet($key, $js, $type = 'text/javascript')
	{
		$js = (string) $js;

		$scriptTag = new HtmlTag('script', $js);
		$scriptTag->setAttribute('type', $type);

		$this->addToLayoutSnippet($key, $scriptTag->toHtml());

		return $this;
	}

	/**
	 * @param string $key
	 * @param string $url
	 * @param string $type
	 * @return ResponseContext
	 */
	public function addJsUrlToLayoutSnippet($key, $url, $type = 'text/javascript')
	{
		$scriptTag = new HtmlTag('script', '');
		$scriptTag->setAttribute('src', $url);
		$scriptTag->setAttribute('type', $type);

		$this->addToLayoutSnippet($key, $scriptTag->toHtml());

		return $this;
	}

	/**
	 * @param string $key
	 * @param string $url
	 * @return ResponseContext
	 */
	public function addCssLinkToLayoutSnippet($key, $url)
	{
		$linkTag = new HtmlTag('link');

		$linkTag->setAttribute('rel', 'stylesheet');
		$linkTag->setAttribute('type', 'text/css');
		$linkTag->setAttribute('href', $url);

		$this->addToLayoutSnippet($key, $linkTag->toHtml());

		return $this;
	}

	/**
	 * Flushes all data to another response context
	 * @param ResponseContext $mainContext
	 */
	public function flushToContext(ResponseContext $mainContext)
	{
		foreach ($this->getAllValues() as $key => $value) {
			$mainContext->setValue($key, $value);
		}

		foreach ($this->layoutSnippetResponses as $key => $responses) {
			foreach	($responses as $snippet) {
				$mainContext->addToLayoutSnippet($key, $snippet);
			}
		}
	}

	/**
	 * Whether the value key exists.
	 *
	 * @param mixed $index
	 * @return boolean
	 */
	public function has($index)
	{
		return $this->offsetExists($index);
	}

	/**
	 * @param mixed $index
	 * @param string $default
	 * @return mixed
	 *
	 * @throws \RuntimeException
	 */
	public function get($index, $default = null)
	{
		if ($this->has($index)) {
			return $this->offsetGet($index);
		} elseif (! (func_num_args() > 1)) {
			throw new \RuntimeException('No such offset.');
		}

		return $default;
	}

	/**
	 * Loads next value in scalar value input and advances the iterator pointer.
	 * @return mixed
	 * @throws \OutOfBoundsException
	 */
	public function getNext()
	{
		if (! $this->valid()) {
			throw new \OutOfBoundsException('End of iterator reached.');
		}

		$value = $this->get($this->key());

		$this->next();

		return $value;
	}

	/**
	 * If the next value is scalar
	 * @return bool
	 */
	public function hasNext()
	{
		return $this->has($this->key());
	}

	/**
	 * Whether the value in the index is empty.
	 * "0" is treated empty only if $strict is off.
	 *
	 * @param string $index
	 * @param bool $strict
	 * @return bool
	 */
	public function isEmpty($index, $strict = true)
	{
		$value = $this->get($index, null);
		$empty = null;

		if ($strict) {
			$empty = ($value == '');
		} else {
			$empty = empty($value);
		}

		return $empty;
	}
}
