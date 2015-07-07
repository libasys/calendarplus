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
 
/**
 * This class manages the caching of repeating events
 * Events will be cached for the current year ± 5 years
 */
 
 namespace OCA\CalendarPlus;
 
class Repeat{
	/**
	 * @brief returns the cache of an event
	 * @param (int) $id - id of the event
	 * @return (array)
	 */
	public static function get($id) {
		$stmt = \OCP\DB::prepare('SELECT * FROM `'.App::CldRepeatTable.'` WHERE `eventid` = ?');
		$result = $stmt->execute(array($id));
		$return = array();
		while($row = $result->fetchRow()) {
			$return[] = $row;
		}
		return $return;
	}
	/**
	 * @brief returns the cache of an event in a specific peroid
	 * @param (int) $id - id of the event
	 * @param (DateTime) $from - start for period in UTC
	 * @param (DateTime) $until - end for period in UTC
	 * @return (array)
	 */
	public static function get_inperiod($id, $from, $until) {
		$stmt = \OCP\DB::prepare( 'SELECT * FROM `'.App::CldRepeatTable.'` WHERE `eventid` = ?'
		.' AND ((`startdate` >= ? AND `startdate` <= ?)'
		.' OR (`enddate` >= ? AND `enddate` <= ?))');
		$result = $stmt->execute(array($id,
					Object::getUTCforMDB($from), Object::getUTCforMDB($until),
					Object::getUTCforMDB($from), Object::getUTCforMDB($until)));
		$return = array();
		while($row = $result->fetchRow()) {
			$return[] = $row;
		}
		return $return;
	}
	
	/**
	 * @brief returns the cache of an event in a specific peroid
	 * @param (int) $id - id of the event
	 * @param (DateTime) $from - start for period in UTC
	 * @param (DateTime) $until - end for period in UTC
	 * @return (array)
	 */
	public static function get_inperiod_Alarm($id, $from, $until) {
		$stmt = \OCP\DB::prepare( 'SELECT * FROM `'.App::CldRepeatTable.'` WHERE `eventid` = ?'
		.' AND ((`startdate` >= ? AND `startdate` <= ?)'
		.' OR (`enddate` >= ? AND `enddate` <= ?))');
		$result = $stmt->execute(array($id,
					$from, $until,
					$from, $until));
		$return = array();
		while($row = $result->fetchRow()) {
			$return[] = $row;
		}
		return $return;
	}
	
	/**
	 * @brief returns the cache of all repeating events of a calendar
	 * @param (int) $id - id of the calendar
	 * @return (array)
	 */
	public static function getCalendar($id) {
		$stmt = \OCP\DB::prepare('SELECT * FROM `'.App::CldRepeatTable.'` WHERE `calid` = ?');
		$result = $stmt->execute(array($id));
		$return = array();
		while($row = $result->fetchRow()) {
			$return[] = $row;
		}
		return $return;
	}
	/**
	 * @brief returns the cache of all repeating events of a calendar in a specific period
	 * @param (int) $id - id of the event
	 * @param (string) $from - start for period in UTC
	 * @param (string) $until - end for period in UTC
	 * @return (array)
	 */
	public static function getCalendar_inperiod($id, $from, $until) {
		$stmt = \OCP\DB::prepare( 'SELECT * FROM `'.App::CldRepeatTable.'` WHERE `calid` = ?'
		.' AND ((`startdate` >= ? AND `startdate` <= ?)'
		.' OR (`enddate` >= ? AND `enddate` <= ?))');
		$result = $stmt->execute(array($id,
					$from, $until,
					$from, $until));
		$return = array();
		while($row = $result->fetchRow()) {
			$return[] = $row;
		}
		return $return;
	}
	/**
	 * @brief generates the cache the first time
	 * @param (int) id - id of the event
	 * @return (bool)
	 */
	public static function generate($id) {
		$event = Object::find($id);
		if($event['repeating'] == 0) {
			return false;
		}
		$object = VObject::parse($event['calendardata']);
		$start = new \DateTime('01-01-' . date('Y') . ' 00:00:00', new \DateTimeZone('UTC'));
		$start->modify('-2 years');
		$end = new \DateTime('31-12-' . date('Y') . ' 23:59:59', new \DateTimeZone('UTC'));
		$end->modify('+2 years');
		$object->expand($start, $end);
		foreach($object->getComponents() as $vevent) {
			if(!($vevent instanceof \Sabre\VObject\Component)) {
				continue;
			}
			
			
			$startenddate = Object::generateStartEndDate($vevent->DTSTART, Object::getDTEndFromVEvent($vevent), ($vevent->DTSTART->getValueType() == 'DATE')?true:false, 'UTC');
			$stmt = \OCP\DB::prepare('INSERT INTO `'.App::CldRepeatTable.'` (`eventid`,`calid`,`startdate`,`enddate`) VALUES(?,?,?,?)');
			$stmt->execute(array($id,Object::getCalendarid($id),$startenddate['start'],$startenddate['end']));
		}
		return true;
	}
	/**
	 * @brief generates the cache the first time for all repeating event of an calendar
	 * @param (int) id - id of the calendar
	 * @return (bool)
	 */
	public static function generateCalendar($id) {
		$allobjects = Object::all($id);
		foreach($allobjects as $event) {
			self::generate($event['id']);
		}
		return true;
	}
	/**
	 * @brief updates an event that is already cached
	 * @param (int) id - id of the event
	 * @return (bool)
	 */
	public static function update($id) {
		self::clean($id);
		self::generate($id);
		return true;
	}
	/**
	 * @brief updates all repating events of a calendar that are already cached
	 * @param (int) id - id of the calendar
	 * @return (bool)
	 */
	public static function updateCalendar($id) {
		self::cleanCalendar($id);
		self::generateCalendar($id);
		return true;
	}
	/**
	 * @brief checks if an event is already cached
	 * @param (int) id - id of the event
	 * @return (bool)
	 */
	public static function is_cached($id) {
		if(count(self::get($id)) != 0) {
			return true;
		}else{
			return false;
		}
	}
	/**
	 * @brief checks if an event is already cached in a specific period
	 * @param (int) id - id of the event
	 * @param (DateTime) $from - start for period in UTC
	 * @param (DateTime) $until - end for period in UTC
	 * @return (bool)
	 */
	public static function is_cached_inperiod($id, $start, $end) {
		if(count(self::get_inperiod($id, $start, $end)) != 0) {
			return true;
		}else{
			return false;
		}

	}
	/**
	 * @brief checks if a whole calendar is already cached
	 * @param (int) id - id of the calendar
	 * @return (bool)
	 */
	public static function is_calendar_cached($id) {
		$cachedevents = count(self::getCalendar($id));
		$repeatingevents = 0;
		$allevents = Object::all($id);
		foreach($allevents as $event) {
			if($event['repeating'] === 1) {
				$repeatingevents++;
			}
		}
		if($cachedevents < $repeatingevents) {
			return false;
		}else{
			return true;
		}
	}
	/**
	 * @brief removes the cache of an event
	 * @param (int) id - id of the event
	 * @return (bool)
	 */
	public static function clean($id) {
		$stmt = \OCP\DB::prepare('DELETE FROM `'.App::CldRepeatTable.'` WHERE `eventid` = ?');
		$stmt->execute(array($id));
	}
	/**
	 * @brief removes the cache of all events of a calendar
	 * @param (int) id - id of the calendar
	 * @return (bool)
	 */
	public static function cleanCalendar($id) {
		$stmt = \OCP\DB::prepare('DELETE FROM `'.App::CldRepeatTable.'` WHERE `calid` = ?');
		$stmt->execute(array($id));
	}
}