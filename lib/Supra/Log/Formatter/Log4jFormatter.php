<?php

namespace Supra\Log\Formatter;

use Supra\Log\LogEvent;

/**
 * Log4j log formatter - formats the log event in format similar to log4j
 */
class Log4jFormatter extends SimpleFormatter
{
	
	const FORMAT = '<log4j:event logger="%logger%" level="%level%" thread="%thread%" timestamp="%microtime%">
<log4j:message><![CDATA[%subject%]]></log4j:message>
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
	 * @param LogEvent $event
	 */
	function format(LogEvent $event)
	{
		// Escape subject at first
		$subject = $event->getSubject();
		$subject = strtr($subject, array(']]>' => ']]' . ']]>' . '<![CDATA[' . '>'));
		$event->setSubject($subject);
		
		parent::format($event);
	}
}