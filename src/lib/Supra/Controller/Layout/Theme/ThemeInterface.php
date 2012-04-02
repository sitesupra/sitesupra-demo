<?php

namespace Supra\Controller\Layout\Theme;

interface ThemeInterface
{

	public function getName();

	public function getDescription();

	public function getLayoutRoot();

	public function isEnabled();

	public function getActiveParameters();

	public function getPreviewParameters();

	public function makePreviewParametersActive();

	public function setPreviewParameters($parameters);

	public function getParameterConfigurations();

	public function setParameterConfigurations($configurations);

	public function setPreviewParametersAsCurrentParameters();

	public function getCurrentParameterValues();
}
