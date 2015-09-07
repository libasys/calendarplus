<?php

/**
 * ownCloud - CalendarPlus
 *
 * @author Sebastian Doell
 * @copyright 2015 sebastian doell sebastian@libasys.de
 *
 * Copyright (c) 2011 Bart Visscher <bartv@thisnet.nl>
 * Copyright (c) 2012 Georg Ehrke <georg@owncloud.com>
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

namespace OCA\CalendarPlus;

use  \OCA\CalendarPlus\Calendar as CalendarCalendar;
use \OCA\CalendarPlus\Share\Backend\Event as ShareEvent;
use \OCA\CalendarPlus\AppInfo\Application;

App::$appname = 'calendarplus';


App::$l10n =  \OC::$server->getL10N('calendarplus');
App::$tz = App::getTimezone();

class App {
	const CALENDAR = 'calendar';
	const EVENT = 'event';
	const SHARECALENDAR = 'calpl';
	const SHAREEVENT = 'calplevent';
	const SHARECALENDARPREFIX = 'calendar-';
	const SHAREEVENTPREFIX = 'event-';
	const SHARETODO = 'calpltodo';
	const SHARETODOPREFIX = 'todo-';
	const CldObjectTable='*PREFIX*clndrplus_objects';
	const CldCalendarTable='*PREFIX*clndrplus_calendars';
	const CldRepeatTable='*PREFIX*clndrplus_repeat';
	/**
	 * @brief language object for calendar app
	 */
	public static $l10n;
	
	public static $appname;

	/**
	 * @brief categories of the user
	 */
	protected static $categories = null;

	/**
	 * @brief timezone of the user
	 */
	public static $tz;

	/**
	 * @brief returns informations about a calendar
	 * @param int $id - id of the calendar
	 * @param bool $security - check access rights or not
	 * @param bool $shared - check if the user got access via sharing
	 * @return mixed - bool / array
	 */
	public static function getCalendar($id, $security = true, $shared = false) {
		if (!is_numeric($id)) {
			return false;
		}
		
		$calendar = Calendar::find($id);
		
		// FIXME: Correct arguments to just check for permissions
		if ($security === true && $shared === false) {
			if (\OCP\User::getUser() === $calendar['userid']) {
				return $calendar;
			} else {
				return false;
			}
		}
		
		if ($security === true && $shared === true) {
			if (\OCP\Share::getItemSharedWithBySource(self::SHARECALENDAR, App::SHARECALENDARPREFIX.$id) || \OCP\Share::getItemSharedWithByLink(self::SHARECALENDAR, App::SHARECALENDARPREFIX.$id, $calendar['userid'])) {
				return $calendar;
			}
		}
		return $calendar;
	}


	public static function validateItemSource($itemSource,$itemType){
	
		if(stristr($itemSource, $itemType)){
			$iTempItemSource = explode($itemType,$itemSource);
			return (int) $iTempItemSource[1];
		}else{
			return $itemSource;
		}
	}
	
	
	/**
	 * @brief returns informations about a calendar
	 * @param int $id - id of the calendar
	 * @return associative array
	 */
	 /**FIXME not in use**/
	public static function getSharedCalendarInfo($id) {
		if (!is_numeric($id)) {
			return false;
		}
		$stmt = \OCP\DB::prepare('SELECT calendarcolor,userid,displayname FROM `'.App::CldCalendarTable.'` WHERE `userid` = ?');
		$result = $stmt -> execute(array($id));
		$row = $result -> fetchRow();
		return $row;
	}

	/**
	 * @brief returns informations about an event
	 * @param int $id - id of the event
	 * @param bool $security - check access rights or not
	 * @param bool $shared - check if the user got access via sharing
	 * @return mixed - bool / array
	 */
	public static function getEventObject($id, $security = true, $shared = false) {
		if ($shared === true || $security === true) {
			if (self::getPermissions($id, self::EVENT)) {
				return  Object::find($id);
			}
		} else {
			return  Object::find($id);
		}

		return false;
	}

	/**
	 * @brief returns the parsed calendar data
	 * @param int $id - id of the event
	 * @param bool $security - check access rights or not
	 * @return mixed - bool / object
	 */
	public static function getVCalendar($id, $security = true, $shared = false) {
		$event_object = self::getEventObject($id, $security, $shared);
		if ($event_object === false) {
			return false;
		}
		$vobject = VObject::parse($event_object['calendardata']);
		if (is_null($vobject)) {
			return false;
		}
		return $vobject;
	}

	/**
	 * @brief checks if an event was edited and dies if it was
	 * @param (object) $vevent - vevent object of the event
	 * @param (int) $lastmodified - time of last modification as unix timestamp
	 * @return (bool)
	 */
	public static function isNotModified($vevent, $lastmodified) {
		$last_modified = $vevent -> __get('LAST-MODIFIED');
		if ($last_modified && $lastmodified != $last_modified -> getDateTime() -> format('U')) {
			\OCP\JSON::error(array('modified' => true));
			exit ;
		}
		return true;
	}

	/**
	 * @brief returns the default categories of ownCloud
	 * @return (array) $categories
	 */
	public static function getDefaultCategories() {
		return array(
		(string)self::$l10n -> t('Birthday'), 
		(string)self::$l10n -> t('Business'), 
		(string)self::$l10n -> t('Call'), 
		(string)self::$l10n -> t('Clients'), 
		(string)self::$l10n -> t('Deliverer'), 
		(string)self::$l10n -> t('Holidays'), 
		(string)self::$l10n -> t('Ideas'), 
		(string)self::$l10n -> t('Journey'), 
		(string)self::$l10n -> t('Jubilee'), 
		(string)self::$l10n -> t('Meeting'), 
		(string)self::$l10n -> t('Other'), 
		(string)self::$l10n -> t('Personal'), 
		(string)self::$l10n -> t('Projects'), 
		(string)self::$l10n -> t('Questions'), 
		(string)self::$l10n -> t('Work'), );
	}


	/*FIXME NOT MORE IN USE*/
	public static function loadCategoriesCalendar() {
		$tags = array();
		$result = null;
		$sql = 'SELECT `id`, `category`  FROM `*PREFIX*vcategory` ' . 'WHERE `uid` = ? AND `type` = ? ORDER BY `category`';
		try {
			$stmt = \OCP\DB::prepare($sql);
			$result = $stmt -> execute(array(\OCP\User::getUser(), 'event'));

		} catch(\Exception $e) {
			\OCP\Util::writeLog('core', __METHOD__ . ', exception: ' . $e -> getMessage(), \OCP\Util::ERROR);
		}

		if (!is_null($result)) {
			while ($row = $result -> fetchRow()) {
				$tags[$row['category']] = $row['color'];
			}

			return $tags;
		} else
			return false;

	}

	/**
	 * @brief returns the vcategories object of the user
	 * @return (object) $vcategories
	 */
	public static function getVCategories() {
		if (is_null(self::$categories)) {
			$categories = \OC::$server -> getTagManager() -> load('event');
			if ($categories -> isEmpty('event')) {
				self::scanCategories();
			}
			self::$categories = \OC::$server -> getTagManager() -> load('event', self::getDefaultCategories());
		}
		return self::$categories;

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

    public static function loadTags(){
		$existCats=self::getCategoryOptions();
		
		$tag=array();
		$cats=array();
		foreach($existCats as $groupInfo){
			$backgroundColor=	self::genColorCodeFromText(trim($groupInfo['name']));
			$tag[]=array(
				'id'=>$groupInfo['id'],
				'name'=>$groupInfo['name'],
				'bgcolor' =>$backgroundColor,
				'color' => self::generateTextColor($backgroundColor),
			);
			$cats[] = $groupInfo['name'];
		}
					
		$tagsReturn['tagslist']=$tag;
		$tagsReturn['categories']=$cats;
		
						  
		return $tagsReturn;
	}
	/**
	 * @brief returns the categories of the vcategories object
	 * @return (array) $categories
	 */
	public static function getCategoryOptions() {

		$getNames = function($tag) {
				
			return $tag;
		};
		$categories = self::getVCategories() -> getTags();
		$categories = array_map($getNames, $categories);
		return $categories;
	}

	/**
	 * scan events for categories.
	 * @param $events VEVENTs to scan. null to check all events for the current user.
	 */
	public static function scanCategories($events = null) {
		if (is_null($events)) {
			$calendars = CalendarCalendar::allCalendars(\OCP\USER::getUser());
			if (count($calendars) > 0) {
				$events = array();
				foreach ($calendars as $calendar) {
					if ($calendar['userid'] === \OCP\User::getUser()) {
						$calendar_events = Object::all($calendar['id']);
						$events = $events + $calendar_events;
					}
				}
			}
		}
		if (is_array($events) && count($events) > 0) {
			$vcategories = \OC::$server -> getTagManager() -> load('event');
			$getName = function($tag) {
				return $tag['name'];
			};
			$tags = array_map($getName, $vcategories -> getTags());
			$vcategories -> delete($tags);
			
			foreach ($events as $event) {
				$vobject = VObject::parse($event['calendardata']);
				if (!is_null($vobject)) {
					$object = null;
					if (isset($calendar -> VEVENT)) {
						$object = $calendar -> VEVENT;
					} else if (isset($calendar -> VTODO)) {
						$object = $calendar -> VTODO;
					} else if (isset($calendar -> VJOURNAL)) {
						$object = $calendar -> VJOURNAL;
					}
					if ($object && isset($object -> CATEGORIES)) {
						$vcategories -> addMultiple($object -> CATEGORIES -> getParts(), true, $event['id']);
					}
				}
			}
		}
	}

	/**
	 * check VEvent for new categories.
	 * @see \OC_VCategories::loadFromVObject
	 */
	public static function loadCategoriesFromVCalendar($id, VObject $calendar) {
		$object = null;
		if (isset($calendar -> VEVENT)) {
			$object = $calendar -> VEVENT;
		} else if (isset($calendar -> VTODO)) {
			$object = $calendar -> VTODO;
		} else if (isset($calendar -> VJOURNAL)) {
			$object = $calendar -> VJOURNAL;
		}
		if ($object && isset($object -> CATEGORIES)) {

			self::getVCategories() -> addMultiple($object -> getAsArray('CATEGORIES'), true, $id);
		}
	}

	/**
	 * @brief returns the options for the access class of an event
	 * @return array - valid inputs for the access class of an event
	 */
	public static function getAccessClassOptions() {
		return Object::getAccessClassOptions(self::$l10n);
	}

	/**
	 * @brief returns the options for the repeat rule of an repeating event
	 * @return array - valid inputs for the repeat rule of an repeating event
	 */
	public static function getRepeatOptions() {
		return Object::getRepeatOptions(self::$l10n);
	}

	/**
	 * @brief returns the options for the repeat rule of an repeating event
	 * @return array - valid inputs for the repeat rule of an repeating event
	 */
	public static function getAdvancedRepeatOptions() {
		return Object::getAdvancedRepeatOptions(self::$l10n);
	}

	/**
	 * @brief returns the options for the end of an repeating event
	 * @return array - valid inputs for the end of an repeating events
	 */
	public static function getEndOptions() {
		return Object::getEndOptions(self::$l10n);
	}

	/**
	 * @brief returns the options for an monthly repeating event
	 * @return array - valid inputs for monthly repeating events
	 */
	public static function getMonthOptions() {
		return Object::getMonthOptions(self::$l10n);
	}

	/**
	 * @brief returns the options for an weekly repeating event
	 * @return array - valid inputs for weekly repeating events
	 */
	public static function getWeeklyOptions() {
		return Object::getWeeklyOptions(self::$l10n);
	}

	/**
	 * @brief returns the options for an weekly repeating event
	 * @return array - valid inputs for weekly repeating events
	 */
	public static function getWeeklyOptionsShort() {
		return Object::getWeeklyOptionsShort(self::$l10n);
	}

	/**
	 * @brief returns the options for an yearly repeating event
	 * @return array - valid inputs for yearly repeating events
	 */
	public static function getYearOptions() {
		return Object::getYearOptions(self::$l10n);
	}

	/**
	 * @brief returns the options for an yearly repeating event which occurs on specific days of the year
	 * @return array - valid inputs for yearly repeating events
	 */
	public static function getByYearDayOptions() {
		return Object::getByYearDayOptions();
	}

	/**
	 * @brief returns the options for an yearly repeating event which occurs on specific month of the year
	 * @return array - valid inputs for yearly repeating events
	 */
	public static function getByMonthOptions() {
		return Object::getByMonthOptions(self::$l10n);
	}

	/**
	 * @brief returns the options for an yearly repeating event which occurs on specific month of the year
	 * @return array - valid inputs for yearly repeating events
	 */
	public static function getByMonthShortOptions() {
		return Object::getByMonthShortOptions(self::$l10n);
	}

	/**
	 * @brief returns the options for an yearly repeating event which occurs on specific week numbers of the year
	 * @return array - valid inputs for yearly repeating events
	 */
	public static function getByWeekNoOptions() {
		return Object::getByWeekNoOptions();
	}

	/**
	 * @brief returns the options for an yearly or monthly repeating event which occurs on specific days of the month
	 * @return array - valid inputs for yearly or monthly repeating events
	 */
	public static function getByMonthDayOptions() {
		return Object::getByMonthDayOptions();
	}

	/**
	 * @brief returns the options for an monthly repeating event which occurs on specific weeks of the month
	 * @return array - valid inputs for monthly repeating events
	 */
	public static function getWeekofMonth() {
		return Object::getWeekofMonth(self::$l10n);
	}

	/**
	 * @brief returns the options for reminder choose
	 * @return array - valid inputs for reminder options
	 */
	public static function getReminderOptions() {
		return Object::getReminderOptions(self::$l10n);
	}

	/**
	 * @brief returns the options for reminder choose
	 * @return array - valid inputs for reminder options
	 */
	public static function getAdvancedReminderOptions() {
		return Object::getAdvancedReminderOptions(self::$l10n);
	}

	/**
	 * @brief returns the options for reminder timing choose
	 * @return array - valid inputs for reminder timing options
	 */
	public static function getReminderTimeOptions() {
		return Object::getReminderTimeOptions(self::$l10n);
	}

	/**
	 * @return (string) $timezone as set by user or the default timezone
	 */
	public static function getTimezone() {
		if (\OCP\User::isLoggedIn()) {
			return \OCP\Config::getUserValue(\OCP\User::getUser(),self::$appname, 'timezone', date_default_timezone_get());
		} else {

			if (\OC::$server -> getSession() -> exists('public_link_timezone')) {
				return \OC::$server -> getSession() -> get('public_link_timezone');
			} else {
				return date_default_timezone_get();
			}
		}
	}
	
	/**
	 * @return (array) Daylight and Standard Beginntime timezone
	 */
	public static function getTzDaylightStandard() {
			
		$aTzTimes=[
				'Europe' =>[
					'daylight' => '19810329T020000',
					'standard' => '19961027T030000',
					'daylightstart' => '3',
					'daylightend' => '10'
				],
				'America' =>[
					'daylight' => '19810308T020000',
					'standard' => '19961101T020000',
					'daylightstart' => '3',
					'daylightend' => '11'
				],
				'Australia' =>[
					'daylight' => '20150405T030000',
					'standard' => '20161002T020000',
					'daylightstart' => '4',
					'daylightend' => '10'
				],
		];
		
		return $aTzTimes;
	}
	/**
	 * @brief Get the permissions for a calendar / an event
	 * @param (int) $id - id of the calendar / event
	 * @param (string) $type - type of the id (calendar/event)
	 * @return (int) $permissions - CRUDS permissions
	 * @param (string) $accessclass - access class (rfc5545, section 3.8.1.3)
	 * @see \OCP\Share
	 */
	public static function getPermissions($id, $type, $accessclass = '') {
			
		$permissions_all = \OCP\PERMISSION_ALL;

		if ($type == self::CALENDAR) {
			$calendar = self::getCalendar($id, false, false);

			if ($calendar['userid'] === \OCP\USER::getUser()) {
				if (isset($calendar['issubscribe'])) {
					$permissions_all = \OCP\PERMISSION_READ;
				}
				return $permissions_all;
			} else {
				$sharedCalendar = \OCP\Share::getItemSharedWithBySource(self::SHARECALENDAR,App::SHARECALENDARPREFIX. $id);
				if ($sharedCalendar) {
					return $sharedCalendar['permissions'];
				}

			}
		} elseif ($type == self::EVENT) {
			
			$object = Object::find($id);
			$cal = Calendar::find($object['calendarid']);
			
	
			if ($cal['userid'] == \OCP\USER::getUser()) {
				if ($cal['issubscribe']) {
					$permissions_all = \OCP\PERMISSION_READ;
				}
				return $permissions_all;
			} else {
				if(\OCP\USER::isLoggedIn()){
					$sharedCalendar = \OCP\Share::getItemSharedWithBySource(self::SHARECALENDAR, self::SHARECALENDARPREFIX.$object['calendarid']);
					$sharedEvent = \OCP\Share::getItemSharedWithBySource(self::SHAREEVENT,self::SHAREEVENTPREFIX.$id);
					$calendar_permissions = 0;
					$event_permissions = 0;
					if ($sharedCalendar) {
						$calendar_permissions = $sharedCalendar['permissions'];
						
					}
					if ($sharedEvent) {
						$event_permissions = $sharedEvent['permissions'];
					}
				}
				
				if(!\OCP\USER::isLoggedIn()){
					//\OCP\Util::writeLog('calendar', __METHOD__ . ' id: ' . $id . ', NOT LOGGED IN: ', \OCP\Util::DEBUG);		
					$sharedByLinkCalendar = \OCP\Share::getItemSharedWithByLink(self::SHARECALENDAR, self::SHARECALENDARPREFIX.$object['calendarid'], $cal['userid']);
					if ($sharedByLinkCalendar) {
						$calendar_permissions = $sharedByLinkCalendar['permissions'];
						$event_permissions = 0;
					}
				}
				if ($accessclass === 'PRIVATE') {
					return 0;
				} elseif ($accessclass === 'CONFIDENTIAL') {
					return \OCP\PERMISSION_READ;
				} else {
					return max($calendar_permissions, $event_permissions);
				}
			}
		}
		return 0;
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

	/**
	 * @brief analyses the parameter for calendar parameter and returns the objects
	 * @param (string) $calendarid - calendarid
	 * @param (int) $start - unixtimestamp of start
	 * @param (int) $end - unixtimestamp of end
	 * @return (array) $events
	 */
	public static function getrequestedEvents($calendarid, $start, $end) {
		$events = array();
			
		if ($calendarid === 'shared_events') {

			$checkStart = $start -> format('U');

			$singleevents = \OCP\Share::getItemsSharedWith(self::SHAREEVENT,  ShareEvent::FORMAT_EVENT);
			foreach ($singleevents as $singleevent) {

				$startCheck_dt = new \DateTime($singleevent['startdate'], new \DateTimeZone('UTC'));
				$checkStartSE = $startCheck_dt -> format('U');

				if ($checkStartSE > $checkStart) {
					$singleevent['summary'] .= ' (' . (string) self::$l10n -> t('by') . ' ' . Object::getowner($singleevent['id']) . ')';
					$events[] = $singleevent;
				}

			}
		} else {
					
			if (is_numeric($calendarid)) {
				$calendar = self::getCalendar($calendarid);
				\OCP\Response::enableCaching(0);
				\OCP\Response::setETagHeader($calendar['ctag']);

				$events = Object::allInPeriod($calendarid, $start, $end);

			} else {
				
				\OCP\Util::emitHook('OCA\CalendarPlus', 'getEvents', array('calendar_id' => $calendarid, 'events' => &$events));
			}
		}
		return $events;
	}

	
	/**
	 * @brief use to create HTML emails and send them
	 * @param $eventid The event id
	 * @param $location The location
	 * @param $description The description
	 * @param $dtstart The start date
	 * @param $dtend The end date
	 *
	 * FIXME NOT MORE IN USE
	 */
	public static function sendEmails($eventid, $summary, $dtstart, $dtend, $emails) {

		$user = \OCP\User::getDisplayName();
		$useremail = \OCP\Util::getDefaultEmailAddress('sharing-noreply');

		$eventsharees = array();
		$eventShareesNames = array();
		//$emails = array();
		//$data = App::getEventObject($eventid, true);
		$data = Export::export($eventid, Export::EVENT);

		$tmpStartDate = strtotime($dtstart);
		$myFile = date('Ymd', $tmpStartDate) . '.ics';
		$fh = fopen(\OCP\User::getHome($user) . '/files/' . $myFile, "x+");
		fwrite($fh, $data);
		fclose($fh);
		$attach['path'] = \OCP\User::getHome($user) . '/files/' . $myFile;
		$attach['name'] = $myFile;

		//$useremail = Calendar::getUsersEmails($user);

		//$testEmail=explode(",",$emails);
		//if(count($testEmail)>1)
		foreach ($emails as $email) {
			if ($email === null) {
				continue;
			}

			$subject = 'Termineinladung/ Calendar Invitation';

			$message = '<b>' . $user . '</b> informiert Sie &uuml;ber das Ereignis<b> ' . \OCP\Util::sanitizeHTML($summary) . '</b> , geplant f&uuml;r <b>' . date('d.m.Y', $tmpStartDate) . '.</b> 
             Um das Ereignis zum Kalender hinzuzuf&uuml;gen, klicken Sie auf den Link.<br><br>';

			\OC_MAIL::send($email, "User", $subject, $message, $useremail, $user, $html = 1, $altbody = '', $ccaddress = '', $ccname = '', $bcc = '', $attach);
		}
		unlink(\OCP\User::getHome($user) . '/files/' . $myFile);
	}

}
