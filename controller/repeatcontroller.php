<?php
/**
 * ownCloud - CalendarPlus
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
 
namespace OCA\CalendarPlus\Controller;


use \OCP\AppFramework\Controller;
use OCA\CalendarPlus\Object;
use OCA\CalendarPlus\VObject;
use OCA\CalendarPlus\Db\RepeatDAO;
use \OCP\IRequest;

class RepeatController extends Controller {

	private $userId;
	private $repeatDB;

	public function __construct($appName, IRequest $request, $userId, RepeatDAO $RepeatDAO) {
		parent::__construct($appName, $request);
		$this ->userId = $userId;
		$this ->repeatDB = $RepeatDAO;
	}
	
	/**
	 * @NoAdminRequired
	 * 
	 * @brief returns the cache of an event
	 * @param integer $id
	 * @return array || null
	 */
	public function getEvent($id){
		return $this ->repeatDB->getEvent($id);
	}
	
	
	/**
	 * @NoAdminRequired
	 *
	 * @brief returns the cache of an event in a specific peroid
	 * @param integer $id - id of the event
	 * @param (DateTime) $from - start for period in UTC
	 * @param (DateTime) $until - end for period in UTC
	 * @param bool $bAlarm
	 * @return array || null
	 */
	public function getEventInperiod($id, $from, $until, $bAlarm = false){
			
		return $this ->repeatDB->getEventInperiod($id, $from, $until, $bAlarm);
	}
	
	/**
	 * @NoAdminRequired
	 * 
	 * @brief returns the cache of an calendar
	 * @param integer $id
	 * @return array || null
	 */
	public function getCalendar($id){
		return $this->repeatDB->getCalendar($id);
	}
	
	 /**
	 *@NoAdminRequired
	  * 
	  *  @brief returns the cache of all repeating events of a calendar in a specific period
	 * @param integer $id - id of the event
	 * @param string $from - start for period in UTC
	 * @param string $until - end for period in UTC
	 * @return array || null
	 */
	public function getCalendarInperiod($id, $from, $until) {
		return $this ->repeatDB->getCalendar($id, $from, $until);
	}
	
	/**
	 * @NoAdminRequired
	 * 
	 * @brief generates the cache the first time
	 * @param integer $id 
	 * @return bool true 
	 */
	public function generateEventCache($id) {
			
		$event = Object::find($id);
		
		if((int) $event['repeating'] === 0) {
			return false;
		}
		
		$calendarId = Object::getCalendarid($id);
		
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
			$this ->repeatDB->insertEvent($id,$calendarId,$startenddate['start'],$startenddate['end']);
			
		}
		return true;
	}
	
	/**
	 * @NoAdminRequired
	 * 
	 * @brief generates the cache the first time for all repeating event of an calendar
	 * @param integer $id
	 * @return bool
	 */
	public function generateCalendar($id) {
		$allobjects = Object::all($id);
		
		foreach($allobjects as $event) {
			$this->generateEventCache($event['id']);
		}
		return true;
	}
	
	/**
	 * @NoAdminRequired
	 * 
	 * @brief updates an event that is already cached
	 * @param integer $id 
	 * @return true
	 */
	public function updateEvent($id) {
		$this -> cleanEvent($id);
		$this->generateEventCache($id);
		return true;
	}
	
	/**
	 * @NoAdminRequired
	 * 
	 * @brief updates all repating events of a calendar that are already cached
	 * @param integer $id
	 * @return bool
	 */
	public function updateCalendar($id) {
		$this ->cleanCalendar($id);
		$this->generateCalendar($id);
		return true;
	}
	
	/**
	 *@NoAdminRequired
	 * 
	 *  @brief checks if an event is already cached
	 * @param integer $id 
	 * @return bool
	 */
	public  function isCachedEvent($id) {
		if($this->getEvent($id) !== null) {
			return true;
		}else{
			return false;
		}
	}
	
	/**
	 * @NoAdminRequired
	 * 
	 * @brief checks if an event is already cached in a specific period
	 * @param integer $id 
	 * @param (DateTime) $start - start for period in UTC
	 * @param (DateTime) $end - end for period in UTC
	 * @return bool
	 */
	public function isCachedEventInperiod($id, $start, $end) {
		if($this->getEventInperiod($id, $start, $end) !== null) {
			return true;
		}else{
			return false;
		}

	}
	
	/**
	 * @NoAdminRequired
	 * 
	 * @brief checks if a whole calendar is already cached
	 * @param integer $id 
	 * @return bool
	 */
	public function isCalendarCached($id) {
			
		$aCachedevents = $this->getCalendar($id);
		$cachedevents = 0;
		if($aCachedevents !== null){
			$cachedevents = count($aCachedevents);
		}
		
		$repeatingevents = 0;
		$allevents = Object::all($id);
		foreach($allevents as $event) {
			if((int)$event['repeating'] === 1) {
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
	 * @NoAdminRequired
	 * 
	 * @brief removes the cache of an event
	 * @param integer $id 
	 * @return true || null
	 */
	public function cleanEvent($id) {
		return $this->repeatDB->cleanEvent($id);
	}
	
	
	/**
	 * @NoAdminRequired
	 * 
	 * @brief removes the cache of all events of a calendar
	 * @param integer $id 
	 * @return true || null
	 */
	public function cleanCalendar($id) {
		return $this->repeatDB->cleanCalendar($id);
	}
}