<?php

namespace Supra\Log\Formatter;

/**
 * Log4j log formatter - formats the message in format similar to log4j
 */
class Log4jFormatter extends SimpleFormatter
{
	
	const FORMAT = '<log4j:event logger="%logger%" level="%level%" thread="%thread%" timestamp="%microtime%">
<log4j:message><![CDATA[%message%]]></log4j:message>
<log4j:locationInfo class="%class%" file="%file%" line="%line%" method="%method%" />
</log4j:event>
';
	
	/**
	 * Configuration
	 * @var array
	 */
	protected static $defaultParameters = array(
		'format' => self::FORMAT,
	);
	
	/**
	 * Format function - escape character combination "]]>"
	 * @param array $event
	 */
	function format(array &$event)
	{
		$event['message'] = strtr($event['message'], array(']]>' => ']]' . ']]>' . '<![CDATA[' . '>'));
		parent::format($event);
	}
}