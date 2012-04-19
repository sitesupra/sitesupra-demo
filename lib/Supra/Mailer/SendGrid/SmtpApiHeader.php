<?php

namespace Supra\Mailer\SendGrid;

class SmtpApiHeader
{

	var $data;

	/**
	 * Provide either a single recipient or a list of recipients. 
	 * Using this function, it is possible to send one e-mail from your system 
	 * that will be delivered to many addresses.
	 * @param string|array $tos 
	 */
	function addTo($tos)
	{
		if ( ! isset($this->data['to'])) {
			$this->data['to'] = array();
		}
		$this->data['to'] = array_merge($this->data['to'], (array) $tos);
	}

	/**
	 * Specify substitution variables for multi recipient e-mails. 
	 * This would allow you to, for example, substitute the string with a recipient’s name. 
	 * ‘val’ can be either a scalar or an array. 
	 * It is the user’s responsibility to ensure that there are an equal number of 
	 * substitution values as there are recipients
	 * @param string $var
	 * @param string|array $val 
	 */
	function addSubVal($var, $val)
	{
		if ( ! isset($this->data['sub'])) {
			$this->data['sub'] = array();
		}

		if ( ! isset($this->data['sub'][$var])) {
			$this->data['sub'][$var] = array();
		}
		$this->data['sub'][$var] = array_merge($this->data['sub'][$var], (array) $val);
	}

	/**
	 * Specify unique arg values.
	 * @param string $val
	 * @return void 
	 */
	function setUniqueArgs($val)
	{
		if ( ! is_array($val))
			return;
		// checking for associative array
		$diff = array_diff_assoc($val, array_values($val));
		if (((empty($diff)) ? false : true)) {
			$this->data['unique_args'] = $val;
		}
	}
	
	/**
	 * Sets a category for an e-mail to be logged as. 
	 * You may use any category name you like. 
	 * You are allowed to set up to 10 categories under this field as a standard array.
	 * @param string $cat 
	 */
	function setCategory($cat)
	{
		$this->data['category'] = $cat;
	}

	/**
	 * Adds/changes a setting for a filter. 
	 * Settings specified in the header will override configured settings. 
	 * Note: ‘filter’ is the app.
	 * @param string $filter
	 * @param string $setting
	 * @param string $value 
	 */
	function addFilterSetting($filter, $setting, $value)
	{
		if ( ! isset($this->data['filters'])) {
			$this->data['filters'] = array();
		}

		if ( ! isset($this->data['filters'][$filter])) {
			$this->data['filters'][$filter] = array();
		}

		if ( ! isset($this->data['filters'][$filter]['settings'])) {
			$this->data['filters'][$filter]['settings'] = array();
		}
		$this->data['filters'][$filter]['settings'][$setting] = $value;
	}

	/**
	 * Returns JSON version of data.
	 * @return string
	 */
	function asJSON()
	{
		$json = json_encode($this->data);
		// Add spaces so that the field can be folded
		$json = preg_replace('/(["\]}])([,:])(["\[{])/', '$1$2 $3', $json);
		return $json;
	}
	
	/**
	 * Returns the full header which can be inserted into an e-mail.
	 * @return string 
	 */
	function as_string()
	{
		$json = $this->asJSON();
		$str = "X-SMTPAPI: " . wordwrap($json, 76, "\n ");
		return $str;
	}

}
