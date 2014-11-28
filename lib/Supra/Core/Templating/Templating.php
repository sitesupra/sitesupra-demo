<?php

namespace Supra\Core\Templating;

interface Templating
{
	public function render($template, $parameters);

	public function addGlobal($name, $value);

	public function addExtension($extension);

	public function getExtension($name);
}