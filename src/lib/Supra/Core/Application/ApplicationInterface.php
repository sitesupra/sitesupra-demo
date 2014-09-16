<?php

namespace Supra\Core\Application;

interface ApplicationInterface
{
	public function getId();
	public function getTitle();
	public function getUrl();
}