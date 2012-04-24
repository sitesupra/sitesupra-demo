<?php

namespace Supra\Controller\Layout\Theme;

interface ThemeInterface
{

	public function getName();

	public function getDescription();

	public function getLayoutsDirectory();

	public function isEnabled();

	public function getParameterConfigurations();

	public function setParameterConfigurations($configurations);

	public function getCurrentParameterOutputValues();
	
	public function setCurrentParameterSet(ThemeParameterSet $parameterSet);
	
	public function generateCssFiles();
}
