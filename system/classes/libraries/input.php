<?php
/**
 * Input library.
 *
 * @package		System
 * @subpackage	Libraries
 * @author		EightPHP Development Team
 * @copyright	(c) 2009-2010 EightPHP
 * @license		http://license.eightphp.com
 */
class Input_Core {

	// Enable or disable automatic XSS cleaning
	protected $use_xss_clean = NO;

	// Are magic quotes enabled?
	protected $magic_quotes_gpc = NO;

	// IP address of current user
	public $ip_address;

	// Input singleton
	protected static $instance;

	/**
	 * Retrieve a singleton instance of Input. This will always be the first
	 * created instance of this class.
	 *
	 * @return  object
	 */
	public static function instance() {
		if(self::$instance === nil) {
			// Create a new instance
			return new Input;
		}

		return self::$instance;
	}

	/**
	 * Sanitizes global GET, POST and COOKIE data. Also takes care of
	 * magic_quotes and register_globals, if they have been enabled.
	 *
	 * @return  void
	 */
	public function __construct() {
		// Use XSS clean?
		$this->use_xss_clean = (bool) Eight::config('core.global_xss_filtering');

		if(self::$instance === nil) {
			// Convert all global variables to UTF-8.
			$_GET    = Input::clean($_GET);
			$_POST   = Input::clean($_POST);
			$_COOKIE = Input::clean($_COOKIE);
			$_SERVER = Input::clean($_SERVER);

			if(PHP_SAPI == 'cli') {
				// Convert command line arguments
				$_SERVER['argv'] = Input::clean($_SERVER['argv']);
			}
			
			// magic_quotes_runtime is enabled
			if(get_magic_quotes_runtime()) {
				set_magic_quotes_runtime(0);
				Eight::log('debug', 'Disable magic_quotes_runtime! It is evil and deprecated: http://php.net/magic_quotes');
			}

			// magic_quotes_gpc is enabled
			if(get_magic_quotes_gpc()) {
				$this->magic_quotes_gpc = YES;
				Eight::log('debug', 'Disable magic_quotes_gpc! It is evil and deprecated: http://php.net/magic_quotes');
			}

			// register_globals is enabled
			if(ini_get('register_globals')) {
				if(isset($_REQUEST['GLOBALS'])) {
					// Prevent GLOBALS override attacks
					exit('Global variable overload attack.');
				}

				// Destroy the REQUEST global
				$_REQUEST = array();

				// These globals are standard and should not be removed
				$preserve = array('GLOBALS', '_REQUEST', '_GET', '_POST', '_FILES', '_COOKIE', '_SERVER', '_ENV', '_SESSION');

				// This loop has the same effect as disabling register_globals
				foreach($GLOBALS as $key => $val) {
					if(!in_array($key, $preserve)) {
						global $$key;
						$$key = nil;

						// Unset the global variable
						unset($GLOBALS[$key], $$key);
					}
				}

				// Warn the developer about register globals
				Eight::log('debug', 'Disable register_globals! It is evil and deprecated: http://php.net/register_globals');
			}

			if(is_array($_GET)) {
				foreach($_GET as $key => $val) {
					// Sanitize $_GET
					$_GET[$this->clean_input_keys($key)] = $this->clean_input_data($val);
				}
			} else {
				$_GET = array();
			}

			if(is_array($_POST)) {
				foreach($_POST as $key => $val) {
					// Sanitize $_POST
					$_POST[$this->clean_input_keys($key)] = $this->clean_input_data($val);
				}
			} else {
				$_POST = array();
			}

			if(is_array($_COOKIE)) {
				foreach($_COOKIE as $key => $val) {
					// Sanitize $_COOKIE
					$_COOKIE[$this->clean_input_keys($key)] = $this->clean_input_data($val);
				}
			} else {
				$_COOKIE = array();
			}

			// Create a singleton
			self::$instance = $this;

			Eight::log('debug', 'Global GET, POST and COOKIE data sanitized');
		}
		
		// Assign global vars to request helper vars
		request::$get = $_GET;
		request::$post = $_POST;
		request::$input = array_merge(URI::instance()->segments(2,YES), $_REQUEST);
	}

	/**
	 * Fetch an item from the $_GET array.
	 *
	 * @param   string   key to find
	 * @param   mixed    default value
	 * @param   boolean  XSS clean the value
	 * @return  mixed
	 */
	public function get($key = array(), $default = nil, $xss_clean = NO) {
		return $this->search_array($_GET, $key, $default, $xss_clean);
	}

	/**
	 * Fetch an item from the $_POST array.
	 *
	 * @param   string   key to find
	 * @param   mixed    default value
	 * @param   boolean  XSS clean the value
	 * @return  mixed
	 */
	public function post($key = array(), $default = nil, $xss_clean = NO) {
		return $this->search_array($_POST, $key, $default, $xss_clean);
	}

	/**
	 * Fetch an item from the $_COOKIE array.
	 *
	 * @param   string   key to find
	 * @param   mixed    default value
	 * @param   boolean  XSS clean the value
	 * @return  mixed
	 */
	public function cookie($key = array(), $default = nil, $xss_clean = NO) {
		return $this->search_array($_COOKIE, $key, $default, $xss_clean);
	}

	/**
	 * Fetch an item from the $_SERVER array.
	 *
	 * @param   string   key to find
	 * @param   mixed    default value
	 * @param   boolean  XSS clean the value
	 * @return  mixed
	 */
	public function server($key = array(), $default = nil, $xss_clean = NO) {
		return $this->search_array($_SERVER, $key, $default, $xss_clean);
	}

	/**
	 * Fetch an item from a global array.
	 *
	 * @param   array    array to search
	 * @param   string   key to find
	 * @param   mixed    default value
	 * @param   boolean  XSS clean the value
	 * @return  mixed
	 */
	protected function search_array($array, $key, $default = nil, $xss_clean = NO) {
		if($key === array())
			return $array;
			
		if(preg_match_all("#\[([^\]]+)\]#", $key, $matches)) {
			$key = trim(substr($key, 0, strpos($key, "[")));
			$keys = $matches[1];
			
			if(!isset($array[$key]))
				return $default;

			$array = $array[$key];
			$final_key = array_pop($keys);
			
			if(count($keys) > 0) foreach($keys as $key) {
				if(!isset($array[$key]))
					return $default;
				else
					$array = $array[$key];
			}
			
			$key = $final_key;
		}

		if(!isset($array[$key]))
			return $default;

		// Get the value
		$value = $array[$key];

		if($this->use_xss_clean === NO and $xss_clean === YES) {
			// XSS clean the value
			$value = $this->xss_clean($value);
		}

		return $value;
	}

	/**
	 * Fetch the IP Address.
	 *
	 * @return string
	 */
	public function ip_address() {
		if($this->ip_address !== nil)
			return $this->ip_address;

		if($ip = $this->server('HTTP_CLIENT_IP')) {
			 $this->ip_address = $ip;
		} elseif($ip = $this->server('HTTP_X_REAL_IP')) {
			$this->ip_address = $ip;
		} elseif($ip = $this->server('HTTP_X_FORWARDED_FOR')) {
			$this->ip_address = $ip;
		} elseif($ip = $this->server('REMOTE_ADDR')) {
			 $this->ip_address = $ip;
		}

		if($comma = strrpos($this->ip_address, ',') !== NO) {
			$this->ip_address = substr($this->ip_address, $comma + 1);
		}
		
		if(!valid::ip($this->ip_address)) {
			// Use an empty IP
			$this->ip_address = '0.0.0.0';
		}

		return $this->ip_address;
	}

	/**
	 * Clean cross site scripting exploits from string.
	 * HTMLPurifier may be used if installed, otherwise defaults to built in method.
	 * Note - This function should only be used to deal with data upon submission.
	 * It's not something that should be used for general runtime processing
	 * since it requires a fair amount of processing overhead.
	 *
	 * @param   string  data to clean
	 * @param   string  xss_clean method to use ('htmlpurifier' or defaults to built-in method)
	 * @return  string
	 */
	public function xss_clean($data, $tool = nil) {
		if($tool === nil) {
			// Use the default tool
			$tool = Eight::config('core.global_xss_filtering');
		}

		if(is_array($data)) {
			foreach($data as $key => $val) {
				$data[$key] = $this->xss_clean($val, $tool);
			}

			return $data;
		}

		// Do not clean empty strings
		if(trim($data) === '')
			return $data;

		if($tool === YES) {
			// NOTE: This is necessary because switch is NOT type-sensative!
			$tool = 'default';
		}

		switch ($tool) {
			case 'htmlpurifier':
				/**
				 * @todo License should go here, http://htmlpurifier.org/
				 */
				if(!class_exists('HTMLPurifier_Config', NO)) {
					// Load HTMLPurifier
					require Eight::find_file('vendor', 'htmlpurifier/HTMLPurifier.auto', YES);
					require 'HTMLPurifier.func.php';
				}

				// Set configuration
				$config = HTMLPurifier_Config::createDefault();
				$config->set('HTML', 'TidyLevel', 'none'); // Only XSS cleaning now

				// Run HTMLPurifier
				$data = HTMLPurifier($data, $config);
			break;
			default:
				// http://svn.bitflux.ch/repos/public/popoon/trunk/classes/externalinput.php
				// +----------------------------------------------------------------------+
				// | Copyright (c) 2001-2006 Bitflux GmbH                                 |
				// +----------------------------------------------------------------------+
				// | Licensed under the Apache License, Version 2.0 (the "License");      |
				// | you may not use this file except in compliance with the License.     |
				// | You may obtain a copy of the License at                              |
				// | http://www.apache.org/licenses/LICENSE-2.0                           |
				// | Unless required by applicable law or agreed to in writing, software  |
				// | distributed under the License is distributed on an "AS IS" BASIS,    |
				// | WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or      |
				// | implied. See the License for the specific language governing         |
				// | permissions and limitations under the License.                       |
				// +----------------------------------------------------------------------+
				// | Author: Christian Stocker <chregu@bitflux.ch>                        |
				// +----------------------------------------------------------------------+
				//
				// Eight Modifications:
				// * Changed double quotes to single quotes, changed indenting and spacing
				// * Removed magic_quotes stuff
				// * Increased regex readability:
				//   * Used delimeters that aren't found in the pattern
				//   * Removed all unneeded escapes
				//   * Deleted U modifiers and swapped greediness where needed
				// * Increased regex speed:
				//   * Made capturing parentheses non-capturing where possible
				//   * Removed parentheses where possible
				//   * Split up alternation alternatives
				//   * Made some quantifiers possessive

				// Fix &entity\n;
				$data = str_replace(array('&amp;','&lt;','&gt;'), array('&amp;amp;','&amp;lt;','&amp;gt;'), $data);
				$data = preg_replace('/(&#*\w+)[\x00-\x20]+;/u', '$1;', $data);
				$data = preg_replace('/(&#x*[0-9A-F]+);*/iu', '$1;', $data);
				$data = html_entity_decode($data, ENT_COMPAT, 'UTF-8');

				// Remove any attribute starting with "on" or xmlns
				$data = preg_replace('#(<[^>]+?[\x00-\x20"\'])(?:on|xmlns)[^>]*+>#iu', '$1>', $data);

				// Remove javascript: and vbscript: protocols
				$data = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'"]*)[\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2nojavascript...', $data);
				$data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2novbscript...', $data);
				$data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*-moz-binding[\x00-\x20]*:#u', '$1=$2nomozbinding...', $data);

				// Only works in IE: <span style="width: expression(alert('Ping!'));"></span>
				$data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?expression[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
				$data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?behaviour[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
				$data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:*[^>]*+>#iu', '$1>', $data);

				// Remove namespaced elements (we do not need them)
				$data = preg_replace('#</*\w+:\w[^>]*+>#i', '', $data);

				do
				{
					// Remove really unwanted tags
					$old_data = $data;
					$data = preg_replace('#</*(?:applet|b(?:ase|gsound|link)|embed|frame(?:set)?|i(?:frame|layer)|l(?:ayer|ink)|meta|object|s(?:cript|tyle)|title|xml)[^>]*+>#i', '', $data);
				}
				while($old_data !== $data);
			break;
		}

		return $data;
	}

	/**
	 * This is a helper method. It enforces W3C specifications for allowed
	 * key name strings, to prevent malicious exploitation.
	 *
	 * @param   string  string to clean
	 * @return  string
	 */
	public function clean_input_keys($str) {
		$chars = PCRE_UNICODE_PROPERTIES ? '\pL' : 'a-zA-Z';

		if(!preg_match('#^['.$chars.'0-9:_.\-\(\)]++$#uD', $str)) {
			exit('Disallowed key characters in global data.');
		}

		return $str;
	}

	/**
	 * This is a helper method. It escapes data and forces all newline
	 * characters to "\n".
	 *
	 * @param   unknown_type  string to clean
	 * @return  string
	 */
	public function clean_input_data($str) {
		if(is_array($str)) {
			$new_array = array();
			foreach($str as $key => $val) {
				// Recursion!
				$new_array[$this->clean_input_keys($key)] = $this->clean_input_data($val);
			}
			return $new_array;
		}

		if($this->magic_quotes_gpc === YES) {
			// Remove annoying magic quotes
			$str = stripslashes($str);
		}

		if($this->use_xss_clean === YES) {
			$str = $this->xss_clean($str);
		}

		if(strpos($str, "\r") !== NO) {
			// Standardize newlines
			$str = str_replace(array("\r\n", "\r"), "\n", $str);
		}

		return $str;
	}

	/**
	 * Recursively cleans arrays, objects, and strings. Removes ASCII control
	 * codes and converts to UTF-8 while silently discarding incompatible
	 * UTF-8 characters.
	 *
	 * @param   string  string to clean
	 * @return  string
	 */
	public static function clean($str) {
		if (is_array($str) OR is_object($str)) {
			foreach ($str as $key => $val) {
				// Recursion!
				$str[Input::clean($key)] = Input::clean($val);
			}
		} elseif (is_string($str) AND $str !== '') {
			// Remove control characters
			$str = str::strip_ascii_ctrl($str);

			if (!str::is_ascii($str)) {
				// Disable notices
				$ER = error_reporting(~E_NOTICE);
				
				// iconv is expensive, so it is only used when needed
				$str = iconv(Eight::CHARSET, Eight::CHARSET.'//IGNORE', $str);

				// Turn notices back on
				error_reporting($ER);
			}
		}

		return $str;
	}

} // End Input Class