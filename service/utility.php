<?php

/**
 * ownCloud - Calendar+
 *
 * @author Sebastian Doell
 * @copyright 2015 sebastian doell sebastian@libasys.de
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
 
namespace OCA\CalendarPlus\Service;


Utility::$l10n =  \OC::$server->getL10N('calendarplus');
Utility::$appName = 'calendarplus';

class Utility  {
	
	public static $appName;
	
	public static $l10n;
	
	
	/**
	 * @return (string) $timezone as set by user or the default timezone
	 */
	public static function getTimezone() {
		//FIXME
			
		if (\OCP\User::isLoggedIn()) {
			return \OCP\Config::getUserValue(\OCP\User::getUser(),self::$appName, 'timezone', date_default_timezone_get());
		} else {

			if (\OC::$server -> getSession() -> exists('public_link_timezone')) {
				return \OC::$server -> getSession() -> get('public_link_timezone');
			} else {
				return date_default_timezone_get();
			}
		}
	}
	
	/*
	 * @brief Get the permissions for an access class
	 * @param (string) $accessclass - access class (rfc5545, section 3.8.1.3)
	 * @return (int) $permissions - CRUDS permissions
	 * @see \OCP\Share
	 */
	public static function getAccessClassPermissions($accessclass = '') {

		switch($accessclass) {
			case 'CONFIDENTIAL' :
				return \OCP\PERMISSION_READ;
			case 'PUBLIC' :
			case '' :
				return (\OCP\PERMISSION_READ | \OCP\PERMISSION_UPDATE | \OCP\PERMISSION_DELETE);
			default :
				return 0;
		}
	}
	
	
	  /*
	 * @brief generates the text color for the calendar
	 * @param string $calendarcolor rgb calendar color code in hex format (with or without the leading #)
	 * (this function doesn't pay attention on the alpha value of rgba color codes)
	 * @return boolean
	 */
	public static function generateTextColor($calendarcolor) {
		if(substr_count($calendarcolor, '#') == 1) {
			$calendarcolor = substr($calendarcolor,1);
		}
		$red = hexdec(substr($calendarcolor,0,2));
		$green = hexdec(substr($calendarcolor,2,2));
		$blue = hexdec(substr($calendarcolor,4,2));
		//recommendation by W3C
		$computation = ((($red * 299) + ($green * 587) + ($blue * 114)) / 1000);
		return ($computation > 130)?'#000000':'#FAFAFA';
	}
	
	
	 /**
     * genColorCodeFromText method
     *
     * Outputs a color (#000000) based Text input
     *
     * (https://gist.github.com/mrkmg/1607621/raw/241f0a93e9d25c3dd963eba6d606089acfa63521/genColorCodeFromText.php)
     *
     * @param String $text of text
     * @param Integer $min_brightness: between 0 and 100
     * @param Integer $spec: between 2-10, determines how unique each color will be
     * @return string $output
	  * 
	  */
	  
	 public static function genColorCodeFromText($text, $min_brightness = 100, $spec = 10){
        // Check inputs
        if(!is_int($min_brightness)) throw new Exception("$min_brightness is not an integer");
        if(!is_int($spec)) throw new Exception("$spec is not an integer");
        if($spec < 2 or $spec > 10) throw new Exception("$spec is out of range");
        if($min_brightness < 0 or $min_brightness > 255) throw new Exception("$min_brightness is out of range");

        $hash = md5($text);  //Gen hash of text
        $colors = array();
        for($i=0; $i<3; $i++) {
            //convert hash into 3 decimal values between 0 and 255
            $colors[$i] = max(array(round(((hexdec(substr($hash, $spec * $i, $spec))) / hexdec(str_pad('', $spec, 'F'))) * 255), $min_brightness));
        }

        if($min_brightness > 0) {
            while(array_sum($colors) / 3 < $min_brightness) {
                for($i=0; $i<3; $i++) {
                    //increase each color by 10
                    $colors[$i] += 10;
                }
            }
        }

        $output = '';
        for($i=0; $i<3; $i++) {
            //convert each color to hex and append to output
            $output .= str_pad(dechex($colors[$i]), 2, 0, STR_PAD_LEFT);
        }

        return '#'.$output;
    }
	
	/**
	 * @brief DateTime to UTC string
	 * @param DateTime $datetime The date to convert
	 * @returns date as YYYY-MM-DD hh:mm
	 *
	 * This function creates a date string that can be used by MDB2.
	 * Furthermore it converts the time to UTC.
	 */
	public static function getUTCforMDB($datetime) {
		return date('Y-m-d H:i', $datetime->format('U'));
	}
	
	/**
	 * @brief returns the options for reminder timing choose
	 * @return array - valid inputs for reminder timing options
	 */
	public static function getReminderTimeOptions($l10n) {
		return array(
			'minutesbefore' => (string)$l10n->t('Minutes before'),
			'hoursbefore'  => (string)$l10n->t('Hours before'),
			'daysbefore'  => (string)$l10n->t('Days before'),
			'minutesafter' => (string)$l10n->t('Minutes after'),
			'hoursafter'  => (string)$l10n->t('Hours after'),
			'daysafter'  => (string)$l10n->t('Days after'),
			'weeksafter'  => (string)$l10n->t('Weeks after'),
			'weeksbefore'  =>  (string)$l10n->t('Weeks before'),
			'ondate'  => (string)$l10n->t('on'),
		);
	}
	
	 /**
	 * @brief returns the options for reminder choose
	 * @return array - valid inputs for reminder options
	 */
	public static function getAdvancedReminderOptions($l10n) {
		/*'messageaudio'  => (string)$l10n->t('messageaudio'),*/	
		return array(
			'DISPLAY' => (string)$l10n->t('Message'),
			'EMAIL'  => (string)$l10n->t('Email'),
		);
	}
	
	/**
	 * @brief returns the options for reminder choose
	 * @return array - valid inputs for reminder options
	 */
	public static function getReminderOptions($l10n) {
		/*'messageaudio'  => (string)$l10n->t('messageaudio'),*/	
		return array(
			'none' => (string)$l10n->t('None'),
			'-PT5M' => '5 '.(string)$l10n->t('Minutes before'),
			'-PT10M' => '10 '.(string)$l10n->t('Minutes before'),
			'-PT15M' => '15 '.(string)$l10n->t('Minutes before'),
			'-PT30M' => '30 '.(string)$l10n->t('Minutes before'),
			'-PT1H' => '1 '.(string)$l10n->t('Hours before'),
			'-PT2H' => '2 '.(string)$l10n->t('Hours before'),
			'-PT1D' => '1 '.(string)$l10n->t('Days before'),
			'-PT2D' => '2 '.(string)$l10n->t('Days before'),
			'-PT1W' => '1 '.(string)$l10n->t('Weeks before'),
			'OWNDEF'        => (string)$l10n->t('Customize ...')
		);
	}
	
	/**
	 * @brief returns the options for an yearly or monthly repeating event which occurs on specific days of the month
	 * @return array - valid inputs for yearly or monthly repeating events
	 */
	public static function getByMonthDayOptions() {
		$return = array();
		foreach(range(1,31) as $num) {
			$return[(string) $num] = (string) $num;
		}
		return $return;
	}
	
	/**
	 * @brief returns the options for an yearly repeating event which occurs on specific week numbers of the year
	 * @return array - valid inputs for yearly repeating events
	 */
	public static function getByWeekNoOptions() {
		return range(1, 52);
	}
	
	/**
	 * @brief returns the options for an yearly repeating event which occurs on specific month of the year
	 * @return array - valid inputs for yearly repeating events
	 */
	public static function getByMonthOptions($l10n) {
		return array(
			'1'  => (string)$l10n->t('January'),
			'2'  => (string)$l10n->t('February'),
			'3'  => (string)$l10n->t('March'),
			'4'  => (string)$l10n->t('April'),
			'5'  => (string)$l10n->t('May'),
			'6'  => (string)$l10n->t('June'),
			'7'  => (string)$l10n->t('July'),
			'8'  => (string)$l10n->t('August'),
			'9'  => (string)$l10n->t('September'),
			'10' => (string)$l10n->t('October'),
			'11' => (string)$l10n->t('November'),
			'12' => (string)$l10n->t('December')
		);
	}
	
	/**
	 * @brief returns the options for an yearly repeating event which occurs on specific days of the year
	 * @return array - valid inputs for yearly repeating events
	 */
	public static function getByYearDayOptions() {
		$return = array();
		foreach(range(1,366) as $num) {
			$return[(string) $num] = (string) $num;
		}
		return $return;
	}
	
	/**
	 * @brief returns the options for an monthly repeating event which occurs on specific weeks of the month
	 * @return array - valid inputs for monthly repeating events
	 */
	public static function getWeekofMonth($l10n) {
		return array(
			'+1' => (string)$l10n->t('first'),
			'+2' => (string)$l10n->t('second'),
			'+3' => (string)$l10n->t('third'),
			'+4' => (string)$l10n->t('fourth'),
			'-1' => (string)$l10n->t('last')
		);
	}
	
	/**
	 * @brief returns the options for an yearly repeating event which occurs on specific month of the year
	 * @return array - valid inputs for yearly repeating events
	 */
	public static function getByMonthShortOptions($l10n) {
		return array(
			'1'  => (string)$l10n->t('Jan.'),
			'2'  => (string)$l10n->t('Feb.'),
			'3'  => (string)$l10n->t('Mar.'),
			'4'  => (string)$l10n->t('Apr.'),
			'5'  => (string)$l10n->t('May.'),
			'6'  => (string)$l10n->t('Jun.'),
			'7'  => (string)$l10n->t('Jul.'),
			'8'  => (string)$l10n->t('Aug.'),
			'9'  => (string)$l10n->t('Sep.'),
			'10' => (string)$l10n->t('Oct.'),
			'11' => (string)$l10n->t('Nov.'),
			'12' => (string)$l10n->t('Dec.')
		);
	}
	
	/**
	 * @brief returns the options for an weekly repeating event
	 * @return array - valid inputs for weekly repeating events
	 */
	public static function getWeeklyOptionsShort($l10n) {
		return array(
			'MO' => (string)$l10n->t('Mon.'),
			'TU' => (string)$l10n->t('Tue.'),
			'WE' => (string)$l10n->t('Wed.'),
			'TH' => (string)$l10n->t('Thu.'),
			'FR' => (string)$l10n->t('Fri.'),
			'SA' => (string)$l10n->t('Sat.'),
			'SU' => (string)$l10n->t('Sun.')
		);
	}
	
	
	/**
	 * @brief returns the options for the repeat rule of an repeating event
	 * @return array - valid inputs for the repeat rule of an repeating event
	 */
	public static function getAdvancedRepeatOptions($l10n) {
		return array(
			'DAILY'         => (string)$l10n->t('DAILY'),
			'WEEKLY'        => (string)$l10n->t('WEEKLY'),
			'MONTHLY'       => (string)$l10n->t('MONTHLY'),
			'YEARLY'       => (string)$l10n->t('YEARLY')
			
		);
	}
	
	/**
	 * @param string $sWeekDay
	 * @return string $checkArray
	 */
	public static function getWeeklyOptionsCheck($sWeekDay) {
		 $checkArray=array(
			'Mon' => 'MO',
			'Tue' => 'TU',
			'Wed' => 'WE',
			'Thu' =>'TH',
			'Fri' => 'FR',
			'Sat' =>'SA',
			'Sun' => 'SU'
		);
		return $checkArray[$sWeekDay];
	}
	
	/**
	 * @brief returns the options for an weekly repeating event
	 * @return array - valid inputs for weekly repeating events
	 */
	public static function getWeeklyOptions($l10n) {
		return array(
			'MO' => (string)$l10n->t('Monday'),
			'TU' => (string)$l10n->t('Tuesday'),
			'WE' => (string)$l10n->t('Wednesday'),
			'TH' => (string)$l10n->t('Thursday'),
			'FR' => (string)$l10n->t('Friday'),
			'SA' => (string)$l10n->t('Saturday'),
			'SU' => (string)$l10n->t('Sunday')
		);
	}
	
	/**
	 * @brief returns the options for an monthly repeating event
	 * @return array - valid inputs for monthly repeating events
	 */
	public static function getMonthOptions($l10n) {
		return array(
			'monthday' => (string)$l10n->t('by monthday'),
			'weekday'  => (string)$l10n->t('by weekday')
		);
	}
	
	/**
	 * @brief returns the options for an yearly repeating event
	 * @return array - valid inputs for yearly repeating events
	 */
	public static function getYearOptions($l10n) {
			/*'byweekno'  => (string)$l10n->t('by weeknumber(s)'),*/
		return array(
			'bydaymonth'  => (string)$l10n->t('by day and month')
		);
	}
	
	/**
	 * @brief returns the options for the end of an repeating event
	 * @return array - valid inputs for the end of an repeating events
	 */
	public static function getEndOptions($l10n) {
		return array(
			'never' => (string)$l10n->t('never'),
			'count' => (string)$l10n->t('by occurrences'),
			'date'  => (string)$l10n->t('by date')
		);
	}
	
	/**
	 * @brief returns the options for the access class of an event
	 * @return array - valid inputs for the access class of an event
	 */
	public static function getAccessClassOptions($l10n) {
		return array(
			'PUBLIC'       => (string)$l10n->t('Show full event'),
			'PRIVATE'      => (string)$l10n->t('Hide event'),
			'CONFIDENTIAL' => (string)$l10n->t('Show only busy')
		);
	}
	
	/**
	 * @brief returns the options for the repeat rule of an repeating event
	 * @return array - valid inputs for the repeat rule of an repeating event
	 */
	public static function getRepeatOptions($l10n) {
		return array(
			'doesnotrepeat' => (string)$l10n->t('Does not repeat'),
			'DAILY'         => (string)$l10n->t('DAILY'),
			'WEEKLY'        => (string)$l10n->t('WEEKLY'),
			'MONTHLY'       => (string)$l10n->t('MONTHLY'),
			'YEARLY'       => (string)$l10n->t('YEARLY'),
			'OWNDEF'        => (string)$l10n->t('Customize ...')
		);
	}
}