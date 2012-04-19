<?php

namespace Supra\Search\Result;

interface SearchResultItemInterface
{

	public function getUniqueId();

	public function setUniqueId($id);

	public function getClass();

	public function setClass($class);
}
