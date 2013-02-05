<?php

namespace Supra\Statistics\GoogleAnalytics\Authentication\Storage;

interface StorageInterface
{
	public function get($key, $default = null);
	public function set($key, $value);
	public function flush();
}