<?php
/**
 * Date helper class
 *
 * @package		System
 * @subpackage	Helpers
 * @author		EightPHP Development Team
 * @copyright	(c) 2009-2010 EightPHP
 * @license		http://license.eightphp.com
 */

class date_Core {

	/**
	 * Converts a UNIX timestamp to DOS format.
	 *
	 * @param   integer  UNIX timestamp
	 * @return  integer
	 */
	public static function unix2dos($timestamp = NO) {
		$timestamp = ($timestamp === NO) ? getdate() : getdate($timestamp);

		if($timestamp['year'] < 1980) {
			return (1 << 21 | 1 << 16);
		}

		$timestamp['year'] -= 1980;

		// What voodoo is this? I have no idea... Geert can explain it though,
		// and that's good enough for me.
		return ($timestamp['year']    << 25 | $timestamp['mon']     << 21 |
		        $timestamp['mday']    << 16 | $timestamp['hours']   << 11 |
		        $timestamp['minutes'] << 5  | $timestamp['seconds'] >> 1);
	}

	/**
	 * Converts a DOS timestamp to UNIX format.
	 *
	 * @param   integer  DOS timestamp
	 * @return  integer
	 */
	public static function dos2unix($timestamp = NO) {
		$sec  = 2 * ($timestamp & 0x1f);
		$min  = ($timestamp >>  5) & 0x3f;
		$hrs  = ($timestamp >> 11) & 0x1f;
		$day  = ($timestamp >> 16) & 0x1f;
		$mon  = ($timestamp >> 21) & 0x0f;
		$year = ($timestamp >> 25) & 0x7f;

		return mktime($hrs, $min, $sec, $mon, $day, $year + 1980);
	}

	/**
	 * Returns the offset (in seconds) between two time zones.
	 * @see     http://php.net/timezones
	 *
	 * @param   string          timezone that to find the offset of
	 * @param   string|boolean  timezone used as the baseline
	 * @return  integer
	 */
	public static function offset($remote, $local = YES, $alt_format=false) {
		static $offsets;

		// Default values
		$remote = (string) $remote;
		$local  = ($local === YES) ? date_default_timezone_get() : (string) $local;

		// Cache key name
		$cache = $remote.$local;

		if(empty($offsets[$cache])) {
			// Create timezone objects
			$remote = new DateTimeZone($remote);
			$local  = new DateTimeZone($local);

			// Create date objects from timezones
			$time_there = new DateTime('now', $remote);
			$time_here  = new DateTime('now', $local);

			// Find the offset
			$offset = $remote->getOffset($time_there) - $local->getOffset($time_here);
			
			// Return offset in +3:00 format
			if($alt_format) {
				$offset = $offset / 60;
				$hours = floor($offset/60);
				$min = str_pad(abs($offset % 60), 2, 0);
				if($hours > 0) {
					$hours = '+'.$hours;
				}
				$offsets[$cache] = $hours.':'.$min;
			} else {
				$offsets[$cache] = $offset;
			}
		}

		return $offsets[$cache];
	}

	/**
	 * Number of seconds in a minute, incrementing by a step.
	 *
	 * @param   integer  amount to increment each step by, 1 to 30
	 * @param   integer  start value
	 * @param   integer  end value
	 * @return  array    A mirrored (foo => foo) array from 1-60.
	 */
	public static function seconds($step = 1, $start = 0, $end = 60) {
		// Always integer
		$step = (int) $step;

		$seconds = array();

		for($i = $start; $i < $end; $i += $step) {
			$seconds[$i] = ($i < 10) ? '0'.$i : $i;
		}

		return $seconds;
	}

	/**
	 * Number of minutes in an hour, incrementing by a step.
	 *
	 * @param   integer  amount to increment each step by, 1 to 30
	 * @return  array    A mirrored (foo => foo) array from 1-60.
	 */
	public static function minutes($step = 5) {
		// Because there are the same number of minutes as seconds in this set,
		// we choose to re-use seconds(), rather than creating an entirely new
		// function. Shhhh, it's cheating! ;) There are several more of these
		// in the following methods.
		return date::seconds($step);
	}

	/**
	 * Number of hours in a day.
	 *
	 * @param   integer  amount to increment each step by
	 * @param   boolean  use 24-hour time
	 * @param   integer  the hour to start at
	 * @return  array    A mirrored (foo => foo) array from start-12 or start-23.
	 */
	public static function hours($step = 1, $long = NO, $start = nil) {
		// Default values
		$step = (int) $step;
		$long = (bool) $long;
		$hours = array();

		// Set the default start if none was specified.
		if($start === nil) {
			$start = ($long === NO) ? 1 : 0;
		}

		$hours = array();

		// 24-hour time has 24 hours, instead of 12
		$size = ($long === YES) ? 23 : 12;

		for($i = $start; $i <= $size; $i += $step) {
			$hours[$i] = $i;
		}

		return $hours;
	}

	/**
	 * Returns AM or PM, based on a given hour.
	 *
	 * @param   integer  number of the hour
	 * @return  string
	 */
	public static function ampm($hour) {
		// Always integer
		$hour = (int) $hour;

		return ($hour > 11) ? 'PM' : 'AM';
	}

	/**
	 * Adjusts a non-24-hour number into a 24-hour number.
	 *
	 * @param   integer  hour to adjust
	 * @param   string   AM or PM
	 * @return  string
	 */
	public static function adjust($hour, $ampm) {
		$hour = (int) $hour;
		$ampm = strtolower($ampm);

		switch ($ampm) {
			case 'am':
				if($hour == 12)
					$hour = 0;
			break;
			case 'pm':
				if($hour < 12)
					$hour += 12;
			break;
		}

		return sprintf('%02s', $hour);
	}

	/**
	 * Number of days in month.
	 *
	 * @param   integer  number of month
	 * @param   integer  number of year to check month, defaults to the current year
	 * @return  array    A mirrored (foo => foo) array of the days.
	 */
	public static function days($month, $year = NO) {
		static $months;

		// Always integers
		$month = (int) $month;
		$year  = (int) $year;

		// Use the current year by default
		$year  = ($year == NO) ? date('Y') : $year;

		// We use caching for months, because time functions are used
		if(empty($months[$year][$month])) {
			$months[$year][$month] = array();

			// Use date to find the number of days in the given month
			$total = date('t', mktime(1, 0, 0, $month, 1, $year)) + 1;

			for($i = 1; $i < $total; $i++) {
				$months[$year][$month][$i] = $i;
			}
		}

		return $months[$year][$month];
	}

	/**
	 * Number of months in a year
	 *
	 * @return  array  A mirrored (foo => foo) array from 1-12.
	 */
	public static function months() {
		return date::hours();
	}

	/**
	 * Returns an array of years between a starting and ending year.
	 * Uses the current year +/- 5 as the max/min.
	 *
	 * @param   integer  starting year
	 * @param   integer  ending year
	 * @return  array
	 */
	public static function years($start = NO, $end = NO) {
		// Default values
		$start = ($start === NO) ? date('Y') - 5 : (int) $start;
		$end   = ($end   === NO) ? date('Y') + 5 : (int) $end;

		$years = array();

		// Add one, so that "less than" works
		$end += 1;

		for($i = $start; $i < $end; $i++) {
			$years[$i] = $i;
		}

		return $years;
	}

	/**
	 * Returns time difference between two timestamps, in human readable format.
	 *
	 * @param   integer       timestamp
	 * @param   integer       timestamp, defaults to the current time
	 * @param   string        formatting string
	 * @return  string|array
	 */
	public static function timespan($time1, $time2 = nil, $output = 'years,months,weeks,days,hours,minutes,seconds') {
		// Array with the output formats
		$output = preg_split('/[^a-z]+/', strtolower((string) $output));

		// Invalid output
		if(empty($output))
			return NO;

		// Make the output values into keys
		extract(array_flip($output), EXTR_SKIP);

		// Default values
		$time1  = max(0, (int) $time1);
		$time2  = empty($time2) ? time() : max(0, (int) $time2);

		// Calculate timespan (seconds)
		$timespan = abs($time1 - $time2);

		// All values found using Google Calculator.
		// Years and months do not match the formula exactly, due to leap years.

		// Years ago, 60 * 60 * 24 * 365
		isset($years) and $timespan -= 31556926 * ($years = (int) floor($timespan / 31556926));

		// Months ago, 60 * 60 * 24 * 30
		isset($months) and $timespan -= 2629744 * ($months = (int) floor($timespan / 2629743.83));

		// Weeks ago, 60 * 60 * 24 * 7
		isset($weeks) and $timespan -= 604800 * ($weeks = (int) floor($timespan / 604800));

		// Days ago, 60 * 60 * 24
		isset($days) and $timespan -= 86400 * ($days = (int) floor($timespan / 86400));

		// Hours ago, 60 * 60
		isset($hours) and $timespan -= 3600 * ($hours = (int) floor($timespan / 3600));

		// Minutes ago, 60
		isset($minutes) and $timespan -= 60 * ($minutes = (int) floor($timespan / 60));

		// Seconds ago, 1
		isset($seconds) and $seconds = $timespan;

		// Remove the variables that cannot be accessed
		unset($timespan, $time1, $time2);

		// Deny access to these variables
		$deny = array_flip(array('deny', 'key', 'difference', 'output'));

		// Return the difference
		$difference = array();
		foreach($output as $key) {
			if(isset($$key) and!isset($deny[$key])) {
				// Add requested key to the output
				$difference[$key] = $$key;
			}
		}

		// Invalid output formats string
		if(empty($difference))
			return NO;

		// If only one output format was asked, don't put it in an array
		if(count($difference) === 1)
			return current($difference);

		// Return array
		return $difference;
	}

	/**
	 * Returns time difference between two timestamps, in the format:
	 * N year, N months, N weeks, N days, N hours, N minutes, and N seconds ago
	 *
	 * @param   integer       timestamp
	 * @param   integer       timestamp, defaults to the current time
	 * @param   string        formatting string
	 * @return  string
	 */
	public static function timespan_string($time1, $time2 = nil, $output = 'years,months,weeks,days,hours,minutes,seconds') {
		if($difference = date::timespan($time1, $time2, $output) and is_array($difference)) {
			// Determine the key of the last item in the array
			$last = end($difference);
			$last = key($difference);

			$span = array();
			foreach($difference as $name => $amount) {
				if($name !== $last and $amount === 0) {
					// Skip empty amounts
					continue;
				}

				// Add the amount to the span
				$span[] = ($name === $last ? ' and ' : ', ').$amount.' '.($amount === 1 ? inflector::singular($name) : $name);
			}

			// Replace difference by making the span into a string
			$difference = trim(implode('', $span), ',');
		} elseif(is_int($difference)) {
			// Single-value return
			$difference = $difference.' '.($difference === 1 ? inflector::singular($output) : $output);
		}

		return $difference;
	}
	
	/**
	 * Converts a UNIX timestamp to MySQL format.
	 *
	 * @param   integer  UNIX timestamp
	 * @param   string  MySQL Format Type
	 * @return  string	MySQL timestamp
	 */
	public static function unix2mysql($time=0, $type='datetime') {
		$time == 0 && $time = time();
		if($type == 'date') {
			return date('Y-m-d', $time);
		} else if($type == 'time') {
			return date('H:i:s', $time);
		} else {
			return date('Y-m-d H:i:s', $time);
		}
	}

	/**
	 * Converts a UNIX timestamp to ATOM ISO 8601 format.
	 *
	 * @param   integer  UNIX timestamp
	 * @return  string	ATOM timestamp
	 */
	public static function unix2atom($time=0) {
		$time == 0 && $time = time();
		return gmdate(DATE_ATOM, $time);
	}
	/**
	 * Converts a UNIX timestamp to a relative string format.
	 *
	 * @param   integer  UNIX timestamp
	 * @return  string	Relative timestamp
	 */
	public static function unix2relative($format, $time) {
		if(date("Ymd", $time) == date("Ymd", time())) {
			return 'Today, '.date("h:i a", $time);
		} elseif(date("Ymd", $time) == date("Ymd", strtotime("-1 day"))) {
			return 'Yesterday, '.date("h:i a", $time);
		} else {
			return date($format, $time);
		}
	}
	
	public static function mysql2exactrelative($date, $show_weeks=TRUE, $format=NULL) {
		return date::unix2exactrelative(date::mysql2unix($date), $show_weeks, $format);
	}
	
	public static function unix2exactrelative($date, $show_weeks=TRUE, $format="\o\n F j, Y") {
		$diff = time() - $date;
		if ($diff<60)
			return $diff . " " . str::plural('second',$diff) . " ago";
		$diff = round($diff/60);
		if ($diff<60)
			return $diff . " " . str::plural('minute',$diff) . " ago";
		$diff = round($diff/60);
		if ($diff<24)
			return $diff . " " . str::plural('hour',$diff) . " ago";
		$diff = round($diff/24);
		if ($diff<7)
			return $diff . " " . str::plural('day',$diff) . " ago";
		$diff = round($diff/7);
		if ($diff<4 && $show_weeks)
			return $diff . " " . str::plural('week',$diff) . " ago";
		return date($format, $date);
	}

	/**
	 * Converts a UNIX timestamp to an apple relative string format.
	 *
	 * @param   integer  UNIX timestamp
	 * @return  string	Relative timestamp
	 */
	public static function unix2applerelative($time) {
		if(date("Ymd", $time) == date("Ymd", time())) {
			return date("g:i a", $time);
		} elseif(date("Ymd", $time) == date("Ymd", strtotime("-1 day"))) {
			return 'Yesterday';
		} else if(time()-$time <= 604800) {
			return date("l", $time);
		} else {
			return date("M j", $time);
		}
	}
	
	/**
	 * Converts a MySQL timestamp to UNIX format.
	 *
	 * @param   integer  UNIX timestamp
	 * @return  string	MySQL timestamp
	 */
	public static function mysql2unix($time="0000-00-00 00:00:00") {
		return strtotime($time); // Tests show that it's much faster than mktime()
		/*
		$time = str_replace('-', '', $time);
		$time = str_replace(':', '', $time);
		$time = str_replace(' ', '', $time);
		
		return  mktime(
						substr($time, 8, 2),
						substr($time, 10, 2),
						substr($time, 12, 2),
						substr($time, 4, 2),
						substr($time, 6, 2),
						substr($time, 0, 4)
						);
		*/
	}
	
	/**
	 * Converts an ATOM ISO 8601 timestamp to UNIX format.
	 *
	 * @param   integer  ATOM timestamp
	 * @return  string	UNIX timestamp
	 */
	public static function atom2unix($time="0000-00-00T00:00:00+00:00") {
		return strtotime($time);
	}	
	
	
	/**
	 * Takes a string and determines whether it's a valid MySQL Timestamp.
	 *
	 * @param   string  MySQL timestamp
	 * @return  bool
	 */
	public static function is_mysql_date($time="0000-00-00 00:00:00") {
		if(preg_match("/[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}/", $time)) {
			return $time == '0000-00-00 00:00:00' ? false : true;
		} else {
			return false;
		}
	}
	
	/**
	 * Converts a MySQL timestamp to UTC format.
	 *
	 * @param   string  MySQL timestamp
	 * @return  string	UTC timestamp
	 */
	public static function mysql2utc($time="0000-00-00 00:00:00") {
		return date::unix2utc(date::mysql2unix($time));
	}
	
	/**
	 * Converts a UNIX timestamp to UTC format.
	 *
	 * @param   integer  UNIX timestamp
	 * @return  string	MySQL timestamp
	 */
	public static function unix2utc($time=0) {
		$time == 0 && $time = time();
		return gmdate('Y-m-d\TH:i:s\Z', $time);
	}
	
	/**
	 * Get a list of timezones suitable for a select list
	 */
	public static function timezones() {
		$zones = array();
		$timezones = Eight::config('locale.timezones');
		foreach($timezones as $zone) {
			$dateTime = new DateTime('now');
		    $dateTime->setTimeZone(new DateTimeZone($zone));
		    $offset = $dateTime->getOffset() / 60;
			$hours = floor($offset/60);
			$min = str_pad(abs($offset % 60), 2, 0);
			if($hours > 0) {
				$hours = '+'.$hours;
			}
			$zones[$zone] = "(GMT".$hours.":".$min.") ".str_replace('_', ' ', $zone);
		}
		return $zones;
	}

} // End date