<?php

namespace Supra\Ip;

use Supra\Log\Logger;

/**
 * SiteSupra IP Range Manipulations
 * @package Core
 * @author Aigars Gedroics <aigars.gedroics@videinfra.com>
 * @copyright Vide Infra Group
 * @since 5.3
 *
 * Usage:
 * <code>
 *	 	$range = new \Supra\Ip\Range();
 *		$range->fromString('10.0.1.0/24,127.0.0.*');
 *		$allow = $range->includes('10.0.1.24'); // true
 *		$allow = $range->includes('192.168.0.1'); // false
 * </code>
 */
class Range
{
	/**
	 * Whether to throw exception on range parse failure
	 * @var boolean
	 */
	public $strict = true;
	
	/**
	 * Array of ranges
	 * @var array
	 */
	protected $ranges = array();
	
	/**
	 * Constructor
	 * @param string $rangeString range
	 * @param boolean $strict
	 */
	public function __construct($rangeString = null, $strict = true)
	{
		$this->strict = $strict;
		if ( ! is_null($rangeString)) {
			$this->fromString($rangeString);
		}
	}

	/**
	 * Load range from the string
	 * @param string $rangeString range
	 * @return boolean success
	 */
	public function fromString($rangeString)
	{
		if ( ! is_string($rangeString)) {
			throw new Exception('The IP range parameter is not a string');
		}

		$ranges = preg_split('/[^\d\.\-\*\/]+/', $rangeString);
		foreach ($ranges as $i => $range) {

			try {

				$ip = null;
				$rangeStart = null;
				$rangeEnd = null;
				$subnet = null;

				// subnet
				if (strpos($range, '/') !== false) {
					$rangeParts = explode('/', $range);
					if (count($rangeParts) != 2) {
						throw new Exception(sprintf('IP range format not recognized: "%s"', $range));
					}
					list($range, $subnetString) = $rangeParts;

					if (preg_match('/^\d{1,2}$/', $subnetString)) {
						$subnet = (int)$subnetString;
						if ($subnet > 32) {
							throw new Exception(sprintf('IP range format not recognized, CIDR incorrect: "%s"', $range));
						}
						$subnet = str_repeat('1', $subnet) . str_repeat('0', 32 - $subnet);
						$subnet = bindec($subnet);
					} else {
						$subnet = ip2long($subnetString);
						if ($subnet === false) {
							throw new Exception("Subnet value $subnetString is not valid");
						}
					}

				}

				// range using *
				if (strpos($range, '*') !== false) {
					
					// validate format
					if ( ! preg_match('/^(\d{1,3}\.){0,3}\*$/', $range)) {
						throw new Exception(sprintf('IP range format not recognized, * range incorrect: "%s"', $range));
					}
					$range = rtrim($range, '*.');
					$rangeParts = explode('.', $range);

					$rangeStartString = implode('.', $rangeParts + array(0, 0, 0, 0));
					$rangeStart = ip2long($rangeStartString);
					if ($rangeStart === false) {
						throw new Exception("IP address $rangeStartString is not valid");
					}
					$rangeEndString = implode('.', $rangeParts + array(255, 255, 255, 255));
					$rangeEnd = ip2long(implode('.', $rangeParts + array(255, 255, 255, 255)));
					if ($rangeEnd === false) {
						throw new Exception("IP address $rangeEndString is not valid");
					}
				} elseif (strpos($range, '-') !== false) {
					$rangeParts = explode('-', $range);
					if (count($rangeParts) != 2) {
						throw new Exception(sprintf('IP range format not recognized, - range incorrect: "%s"', $range));
					}

					list($rangeStartString, $rangeEndString) = $rangeParts;
					$rangeStart = ip2long($rangeStartString);
					if ($rangeStart === false) {
						throw new Exception("IP address $rangeStartString is not valid");
					}
					$rangeEnd = ip2long($rangeEndString);
					if ($rangeEnd === false) {
						throw new Exception("IP address $rangeEndString is not valid");
					}
				} else {
					$rangeStart = $rangeEnd = null;
					$ip = ip2long($range);
					if ($ip === false) {
						throw new Exception("IP address $range is not valid");
					}
					// Subnet 255.255.255.255 - only this IP is valid when no subnet defined
					if (is_null($subnet)) {
						$subnet = -1;
					}
				}

				$ranges[$i] = array(
					'start' => $rangeStart,
					'end' => $rangeEnd,
					'ip' => $ip,
					'subnet' => $subnet,
				);
			} catch (Exception $e) {
				if ($this->strict) {
					throw $e;
				} else {
					Logger::swarn($e->getMessage());
					unset($ranges[$i]);
				}
			}
		}
		$this->ranges = $ranges;

		return true;
	}

	/**
	 * If IP is in the range specified
	 * @param integer|string $ip
	 * @return boolean result
	 */
	public function includes($ip)
	{
		if ( ! is_numeric($ip)) {
			$ip = ip2long($ip);
		}

		// Cycle through ranges
		foreach ($this->ranges as $range) {
			if ( ! is_null($range['start'])) {
				if ($ip < $range['start']) {
					continue;
				}
				if (($ip & $range['subnet']) != ($range['start'] & $range['subnet'])) {
					continue;
				}
			}
			if ( ! is_null($range['end'])) {
				if ($ip > $range['end']) {
					continue;
				}
				if (($ip & $range['subnet']) != ($range['end'] & $range['subnet'])) {
					continue;
				}
			}
			if ( ! is_null($range['ip'])) {
				if (($ip & $range['subnet']) != ($range['ip'] & $range['subnet'])) {
					continue;
				}
			}

			return true;
		}

		return false;
	}

}