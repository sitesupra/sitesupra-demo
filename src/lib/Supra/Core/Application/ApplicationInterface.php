<?php

namespace Supra\Core\Application;

interface ApplicationInterface
{
	public function getId();
	public function getTitle();
	public function getUrl();
	public function getIcon();
	public function getRoute();
	public function isPublic();
	public function isPrivate();
}