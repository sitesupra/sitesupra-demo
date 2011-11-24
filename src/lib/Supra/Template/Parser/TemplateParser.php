<?php

namespace Supra\Template\Parser;

/**
 * Common interface for template parsers
 */
interface TemplateParser
{
	public function parseTemplate($templateName, array $templateParameters = array());
}
