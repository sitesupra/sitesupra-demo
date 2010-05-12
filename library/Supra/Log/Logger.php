<?php

namespace Supra\Log;

use \DateTime;
use \DateTimeZone;

/**
 * Main logger class
 * @method void static dump(mixed $argument)
 * @method void static debug(mixed $argument)
 * @method void static info(mixed $argument)
 * @method void static warn(mixed $argument)
 * @method void static error(mixed $argument)
 * @method void static fatal(mixed $argument)
 * @method void static pdump(mixed $argument)
 * @method void static pdebug(mixed $argument)
 * @method void static pinfo(mixed $argument)
 * @method void static pwarn(mixed $argument)
 * @method void static perror(mixed $argument)
 * @method void static pfatal(mixed $argument)
 * @method void static sdump(mixed $argument)
 * @method void static sdebug(mixed $argument)
 * @method void static sinfo(mixed $argument)
 * @method void static swarn(mixed $argument)
 * @method void static serror(mixed $argument)
 * @method void static sfatal(mixed $argument)
 */
class Logger
{
	/**
	 * Logger singleton instance
	 * @var Logger
	 */
	protected static $instance;

	/**
	 * Logger instances
	 * @var Writer\WriterInterface[]
	 */
	private $loggers = array();

	/**
	 * Default logger name
	 * @var string
	 */
	private $defaultLogger = self::LOGGER_APPLICATION;

	/**
	 * Bootstrap logger
	 * @var Writer\WriterInterface
	 */
	protected static $bootstrapLogger;

	/**
	 * The default timezone
	 * @var DateTimeZone
	 */
	protected static $defaultTimezone;

	/**
	 * Log level constants
	 */
	const DEBUG = 'DEBUG';
	const INFO = 'INFO';
	const WARN = 'WARN';
	const ERROR = 'ERROR';
	const FATAL = 'FATAL';

	/**
	 * Log level priorities
	 * @var array
	 */
	public static $levels = array(
		self::DEBUG	=> 10,
		self::INFO	=> 20,
		self::WARN	=> 30,
		self::ERROR	=> 40,
		self::FATAL	=> 50,
	);

	/**
	 * Applicarion logger default name
	 */
	const LOGGER_APPLICATION = 'Application';

	/**
	 * Bootstrap logger name
	 */
	const LOGGER_BOOTSTRAP = 'Bootstrap';

	/**
	 * PHP logger name
	 */
	const LOGGER_PHP = 'PHP';

	/**
	 * SiteSupra logger name
	 */
	const LOGGER_SUPRA = 'SiteSupra';

	/**
	 * Default bootstrap logger
	 * @var string
	 */
	private static $bootstrapLoggerClass = 'Supra\\Log\\Writer\\System';

	/**
	 * Short names of loggers
	 * @var array
	 */
	private static $loggerAliases = array(
		'daily_file' => array(
			'Writer' => array(
				'id' => 'Supra\\Log\\Writer\\DailyFile',
			),
		),
		'file' => array(
			'Writer' => array(
				'id' => 'Supra\\Log\\Writer\\File',
			),
		),
		'socket' => array(
			'Writer' => array(
				'id' => 'Supra\\Log\\Writer\\Log4j',
			),
		),
		'system' => array(
			'Writer' => array(
				'id' => 'Supra\\Log\\Writer\\System',
			),
		),
		'firephp' => array(
			'Writer' => array(
				'id' => 'Supra\\Log\\Writer\\FirePhp',
			),
		),
		'null' => array(
			'Writer' => array(
				'id' => 'Supra\\Log\\Writer\\Null',
			),
		),
	);

	/**
	 * Detects if the logger is system or application logger
	 * @param string $name
	 * @return boolean
	 */
	private static function isSystemLogger($name)
	{
		return in_array($name,
			array(
				self::LOGGER_BOOTSTRAP,
				self::LOGGER_PHP,
				self::LOGGER_SUPRA
			)
		);
	}

	/**
	 * Return static instance
	 * @return Logger
	 */
	public static function getInstance()
	{
		if (is_null(self::$instance)) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	/**
	 * Sets default timezone used for date output to the log
	 * @param string $timezone
	 */
	public static function setDefaultTimezone($timezone)
	{
		self::$defaultTimezone = new DateTimeZone($timezone);
	}

	/**
	 * Sets default timezone used for date output to the log
	 * @param string $timezone
	 */
	public static function getDateInDefaultTimezone($format, $time = null)
	{
		$dateTime = new DateTime();
		$dateTime->setTimeZone(self::$defaultTimezone);

		if ( ! is_null($time)) {
			$dateTime->setTimestamp($time);
		}

		$timeString = $dateTime->format($format);

		return $timeString;
	}

	/**
	 * Add writer
	 * @param string $name
	 * @param Writer\WriterInterface $writer
	 */
	public function addWriter($name, Writer\WriterInterface $writer)
	{
		if ( ! array_key_exists($name, $this->loggers)) {
			$this->loggers[$name] = array();
		}
		$this->loggers[$name][] = $writer;
	}

	/**
	 * Set the bootstrap writer
	 * @param Writer\WriterInterface $writer
	 */
	public static function setBootstrapWriter(Writer\WriterInterface $writer)
	{
		self::$bootstrapLogger = $writer;
	}

	/**
	 * Get log writer instance by it's name
	 *
	 * @param string $name
	 * @return Writer\WriterInterface
	 */
	protected static function getLoggers($name = null)
	{
		try {
			
			$instance = self::getInstance();

			// use default if no name provided
			if (is_null($name)) {
				$name = $instance->defaultLogger;
			}

			// search in cached loggers
			if ( ! array_key_exists($name, $instance->loggers)) {
				if (is_null(self::$bootstrapLogger)) {
					// set false to disable recursive instance creation
					self::$bootstrapLogger = false;

					// instance creation
					self::$bootstrapLogger = new static::$bootstrapLoggerClass;
					self::$bootstrapLogger->setName(static::LOGGER_BOOTSTRAP);
				}
				return array(self::$bootstrapLogger);
			}
			return $instance->loggers[$name];
		} catch (Exception $e) {
			return array();
		}

	}

	/**
	 * Log writer factory
	 *
	 * @param array $configuration
	 * @return Writer\WriterInterface
	 */
	static function logWriterFactory(array $configuration)
	{
		if (empty($configuration['Writer']['id'])) {
			throw new Exception('Log writer id is empty');
		}

		// merge writer parameters in the first level of array
		$configuration = $configuration['Writer'] + $configuration;

		$instance = SupraPluginManager::factory($configuration['Writer']['id'], 'log.writer', $configuration);

		return $instance;
	}

	/**
	 * Format object for readable representation
	 */
	public static function formatObject(&$obj)
	{
		if (is_object($obj) || is_array($obj)){
			return print_r($obj, true);
		}
		return (string)$obj;
	}

	/**
	 * General log method
	 *
	 * @param string $method
	 * @param array $args
	 * @param string $loggerName
	 * @param backtrace offset $offset
	 */
	private static function _log($method, $args, $loggerName = null, $offset = 0)
	{
		$offset = (int)$offset;
		$loggers = self::getLoggers($loggerName);
		if (empty($loggers)) {
			// instance Bootstrap logger if it's not bootstrap already
			if ($loggerName != self::LOGGER_BOOTSTRAP) {
				self::_log($method, $args, self::LOGGER_BOOTSTRAP, $offset + 1);
			} else {
				// the base error_log when nothing is loaded
				$params = self::getBacktraceInfo($offset + 1);
				$str = $method . ' - ';
				if (!empty($params['file'])) {
					$str .= $params['file'] . '(' . $params['line'] . '): ';
				}
				foreach ($args as &$arg) {
					$str .= self::formatObject($arg);
				}
				error_log($str);
			}
			return;
		}

		foreach ($loggers as $logger) {
			$logger->$method($args, 2 + $offset);
		}
	}

	/**
	 * Get file/line/class/method information from the backtrace using the set offset
	 *
	 * @param int $offset
	 * @return array
	 */
	public static function getBacktraceInfo($offset)
	{

		$offset++;

		if (function_exists('debug_backtrace')) {

			$backtrace = debug_backtrace(false);

			/*
			 * full backtrace class::method call summary
			$backtraceSummary = array();
			foreach ($backtrace as $k => $v) {
				$v = $v + array(
					'class' => 'main',
					'function' => 'main',
					'args' => array(),
				);
				if ($k > $offset + 1) {
					foreach ($v['args'] as &$arg) {
						if (is_object($arg)) {
							$arg = get_class($arg);
						}
						if (is_array($arg)) {
							foreach ($arg as &$_arg) {
								if (is_object($_arg)) {
									$_arg = get_class($_arg);
								}
								if (is_array($_arg)) {
									$_arg = 'Array[' . count($_arg) . ']';
								}
							}
							$arg = 'ARRAY[' . implode(',', $arg) . ']';
						}
					}
					$backtraceSummary[] = $v['class'] . '::' . $v['function'] . '(' . implode(', ', $v['args']) . ')';
				}
			}
			$backtraceSummary = implode("\n", $backtraceSummary);
			*/

			// there are situations when no file/line is set for the backtrace item, we're using next in such case
			while (isset($backtrace[$offset]) && !isset($backtrace[$offset]['file'])) {
				$offset++;
			}

			// fetch the class::method information which called the log
			if (isset($backtrace[$offset + 1])) {
				$raiseContainer = $backtrace[$offset + 1];
			} else {
				$raiseContainer = array();
			}
			$raiseContainer = $raiseContainer + array(
				'class' => 'main',
				'function' => null,
				'type' => null
			);

			// fetch the file/line where the log raise was called
			if (isset($backtrace[$offset])) {
				$backtrace = $backtrace[$offset];
			} else {
				$backtrace = array();
			}
			$backtrace = $backtrace + array(
				'file' => null,
				'line' => 0,
			);

			$params['file'] = $backtrace['file'];
			$params['line'] = $backtrace['line'];

			$params['class'] = $raiseContainer['class'];
			$params['method'] = $raiseContainer['function'];
			$params['type'] = $raiseContainer['type'];
			//$params['backtrace'] = $backtraceSummary;

		} else {
			$params['file'] = '';
			$params['line'] = 0;
			$params = array(
				'class' => null,
				'method' => null,
				'type' => null,
				//'backtrace' => null,
			);
		}

		return $params;
	}

	/**
	 * Magic static method for calling dump(), debug(), etc methods
	 * @param string $name
	 * @param array $arguments
	 * @return null
	 */
	public static function  __callStatic($name,  $arguments)
	{
		$name = strtoupper($name);

		// switch between simple methods and methods with "p" or "s" prefix
		foreach (array(0, 1) as $loggerPrefixLength) {
			$loggerPrefix = substr($name, 0, $loggerPrefixLength);
			$logLevel = substr($name, $loggerPrefixLength);
			
			// special logics for dump method
			if ($logLevel == 'DUMP') {
				$logLevel = self::DEBUG;
				ob_start();
				foreach ($arguments as &$arg) {
					var_dump($arg);
				}
				$arguments = array(ob_get_clean());
			}

			// find appropriate
			if (isset(self::$levels[$logLevel])) {
				switch ($loggerPrefix) {
					case 'P':
						$logger = self::LOGGER_PHP;
					break;
					case 'S':
						$logger = self::LOGGER_SUPRA;
					break;
					case '':
						self::_log($logLevel, $arguments);
						return;
					break;
					default:
						$method = __CLASS__ . '::' . $name . '()';
						throw new \Exception("Method $method does not exist");
				}
				self::_log($logLevel, $arguments, $logger);
				return;
			}
		}
		$method = __CLASS__ . '::' . $name . '()';
		throw new \Exception("Method $method does not exist");
	}

}