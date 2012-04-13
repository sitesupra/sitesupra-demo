<?php

namespace Supra\Template\Parser;

/**
 * Common interface for template parsers
 */
interface TemplateParser
{
	/**
	 * Parses the template and returns string output
	 * @param string $templateName
	 * @param array $templateParameters
	 * @return string
	 */
	public function parseTemplate($templateName, array $templateParameters = array());
	
	/**
	 * Returns template filename, null if parser uses different loader
	 * @param string $templateName
	 * @return string
	 */
	public function getTemplateFilename($templateName);
}
