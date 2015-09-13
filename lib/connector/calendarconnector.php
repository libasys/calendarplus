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
namespace OCA\CalendarPlus\Connector;

use OCA\CalendarPlus\AppInfo\Application;
use OCA\CalendarPlus\Db\CalendarDAO;
use OCA\CalendarPlus\Db\EventDAO;
use OCA\CalendarPlus\Share\Backend\Calendar as ShareCalendar;
use OCA\CalendarPlus\ActivityData;
use OCA\CalendarPlus\Share\ShareConnector;
use OCA\CalendarPlus\Service\ObjectParser;

class CalendarConnector {

	private $userId;
	private $db;
	private $shareConnector;

	public function __construct() {
		$this -> db = \OC::$server -> getDb();
		$this -> shareConnector = new ShareConnector();
	}

	/**
	 * @brief Returns the list of calendars for a principal (DAV term of user)
	 * @param string $principaluri
	 * @return array
	 */
	public function allCalendarsWherePrincipalURIIs($principaluri) {
		$uid = $this -> extractUserID($principaluri);
		$this -> setUserId($uid);
		
		return $this -> all();

	}

	/**
	 * @brief finds an object by its DAV Data
	 * @param integer $cid Calendar id
	 * @param string $uri the uri ('filename')
	 * @return associative array || null
	 */
	public function findObjectWhereDAVDataIs($cid, $uri) {
		$this -> setUserId(\OCP\User::getUser());
		$eventDB = new EventDAO($this -> db, $this -> userId, null);

		return $eventDB -> findWhereDAVDataIs($cid, $uri);
	}

	/**
	 * add object from DAV
	 * @param integer $cid Calendar id
	 * @param string $uri the uri ('filename')
	 * @param boolean $bLogActivity 
	 * @return associative array || null
	 */
	public function addObject($calendarId, $objectUri, $calendarData, $bLogActivity = true) {

		$this -> setUserId(\OCP\User::getUser());

		$calendar = $this -> find($calendarId);
		if ($calendar['userid'] !== $this -> userId) {
			$sharedCalendar = $this -> shareConnector -> getItemSharedWithBySourceCalendar($calendarId);
			if (!$sharedCalendar || !($sharedCalendar['permissions'] & $this -> shareConnector -> getCreateAccess())) {
				throw new \Sabre\DAV\Exception\Forbidden('You do not have the permissions to add events to this calendar.');
			}
		}

		$objectParser = new ObjectParser($this -> userId);

		$object = $objectParser -> parse($calendarData);
		list($type, $startdate, $enddate, $summary, $repeating, $uid, $isAlarm, $relatedTo) = $objectParser -> extractData($object);
		
		//Thunderbird fix multiple categories
		$vevent = $object->VEVENT;
		if(isset($vevent->CATEGORIES) && count($vevent->CATEGORIES) > 1){
			$sCat ='';	
			foreach($vevent->CATEGORIES as $key => $val){
				if($sCat === ''){
					$sCat .= $val;
				}else{
					$sCat .= ','.$val;
				}	
			}
			unset($vevent->CATEGORIES);
			$vevent->CATEGORIES = $sCat;
			$calendarData = $object->serialize();			
		}
		
		$eventDB = new EventDAO($this -> db, $this -> userId, null);
		$object_id = $eventDB -> add($calendarId, $type, $startdate, $enddate, $repeating, $summary, $calendarData, $objectUri, time(), $isAlarm, $uid, $relatedTo, 0, $this -> userId);

		$calendarDB = new CalendarDAO($this -> db, $this -> userId);
		$calendarDB -> touch($calendarId);

		if ($repeating) {
			$app = new Application();
			$c = $app -> getContainer();
			$repeatController = $c -> query('RepeatController');
			$repeatController -> generateEventCache($object_id);
		}
		//\OCP\Util::emitHook('\OCA\CalendarPlus', 'addEvent', $object_id);
		
		if($bLogActivity === true){
			$linkTypeApp = 'calendarplus';
			if ($type == 'VTODO') {
				$linkTypeApp = 'tasksplus';
			}
	
			$link = \OC::$server -> getURLGenerator() -> linkToRoute($linkTypeApp . '.page.index') . '#' . urlencode($object_id);
	
			//FIXME lang
			$params = array('mode' => 'created', 'link' => $link, 'trans_type' => $type, 'summary' => $summary, 'cal_user' => $calendar['userid'], 'cal_displayname' => $calendar['displayname'], );
	
			ActivityData::logEventActivity($params, true);
		}

		return $object_id;
	}

	/**
	 * edits an object with the data provided by sabredav
	 * @param integer $id calendar id
	 * @param string $uri   the uri of the object
	 * @param string $data  object
	 * @param boolean $bLogActivity 
	 * @return boolean
	 */
	public function updateObject($cid, $uri, $data, $bLogActivity = true) {

		$this -> setUserId(\OCP\User::getUser());

		$oldobject = $this -> findObjectWhereDAVDataIs($cid, $uri);

		$calendar = $this -> find($cid);
		$objectParser = new ObjectParser($this -> userId);

		$oldvobject = $objectParser -> parse($oldobject['calendardata']);

		if ($calendar['userid'] !== $this -> userId) {
			$sharedCalendar = $this -> shareConnector -> getItemSharedWithBySourceCalendar($cid);
			$sharedAccessClassPermissions = $objectParser -> getAccessClassPermissions($oldvobject);
			if (!$sharedCalendar || !($sharedCalendar['permissions'] & $this -> shareConnector -> getUpdateAccess()) || !($sharedAccessClassPermissions & $this -> shareConnector -> getUpdateAccess())) {
				throw new \Sabre\DAV\Exception\Forbidden('You do not have the permissions to edit this event.');
			}
		}
		$object = $objectParser -> parse($data);
		list($type, $startdate, $enddate, $summary, $repeating, $uid, $isAlarm, $relatedTo) = $objectParser -> extractData($object);
		
		//Thunderbird fix
		$vevent = $object->VEVENT;
		if(isset($vevent->CATEGORIES) && count($vevent->CATEGORIES) > 1){
			$sCat ='';	
			foreach($vevent->CATEGORIES as $key => $val){
				if($sCat === ''){
					$sCat .= $val;
				}else{
					$sCat .= ','.$val;
				}	
			}
			unset($vevent->CATEGORIES);
			$vevent->CATEGORIES = $sCat;
			$data = $object->serialize();			
		}
		

		$eventDB = new EventDAO($this -> db, $this -> userId, null);
		$eventDB -> update($type, $startdate, $enddate, $repeating, $summary, $data, time(), $isAlarm, $uid, $relatedTo, $oldobject['id']);

		$calendarDB = new CalendarDAO($this -> db, $this -> userId);
		$calendarDB -> touch($oldobject['calendarid']);

		if ($repeating) {
			$app = new Application();
			$c = $app -> getContainer();
			$repeatController = $c -> query('RepeatController');
			$repeatController -> updateEvent($oldobject['id']);
		}
		
		if($bLogActivity === true){
			$linkTypeApp = 'calendarplus';
			if ($type == 'VTODO') {
				$linkTypeApp = 'tasksplus';
			}
	
			$link = \OC::$server -> getURLGenerator() -> linkToRoute($linkTypeApp . '.page.index') . '#' . urlencode($oldobject['calendarid']);
			$params = array('mode' => 'edited', 'link' => $link, 'trans_type' => $type, 'summary' => $summary, 'cal_user' => $calendar['userid'], 'cal_displayname' => $calendar['displayname'], );
	
			ActivityData::logEventActivity($params, true);
		}

		return true;
	}

	/**
	 * @brief deletes an  object with the data provided by \Sabredav
	 * @param integer $cid calendar id
	 * @param string $uri the uri of the object
	 * @param boolean $bLogActivity 
	 * @return boolean
	 */
	public function deleteObject($cid, $uri, $bLogActivity = true) {

		$this -> setUserId(\OCP\User::getUser());

		$calendar = $this -> find($cid);

		if ($calendar['userid'] !== $this -> userId) {
			$sharedCalendar = $this -> shareConnector -> getItemSharedWithBySourceCalendar($cid);
			if (!$sharedCalendar || !($sharedCalendar['permissions'] & $this -> shareConnector -> getDeleteAccess())) {
				throw new \Sabre\DAV\Exception\Forbidden('You do not have the permissions to delete this event.');
			}
		}

		$oldobject = $this -> findObjectWhereDAVDataIs($cid, $uri);

		$eventDB = new EventDAO($this -> db, $this -> userId, null);
		$eventDB -> deleteEventFromDAV($cid, $uri);

		$calendarDB = new CalendarDAO($this -> db, $this -> userId);
		$calendarDB -> touch($cid);

		if ($oldobject['repeating']) {
			$app = new Application();
			$c = $app -> getContainer();
			$repeatController = $c -> query('RepeatController');
			$repeatController -> cleanEvent($oldobject['id']);
		}

		//\OCP\Util::emitHook('\OCA\CalendarPlus', 'deleteEvent', $oldobject['id']);
		if($bLogActivity === true){
			$params = array('mode' => 'deleted', 'link' => '', 'trans_type' => $oldobject['objecttype'], 'summary' => $oldobject['summary'], 'cal_user' => $calendar['userid'], 'cal_displayname' => $calendar['displayname'], );
	
			ActivityData::logEventActivity($params, true);
		}

		return true;
	}

	/**
	 * @brief finds an object by its DAV Data
	 * @param integer $cid Calendar id
	 * @param string $uri the uri ('filename')
	 * @return associative array || null
	 */
	public function allObjects($calendarId) {
		$this -> setUserId(\OCP\User::getUser());
		$eventDB = new EventDAO($this -> db, $this -> userId, null);

		return $eventDB -> all($calendarId);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @brief Returns an object
	 * @param integer $id
	 * @return associative array || null
	 */
	public function findObject($id) {
		$this -> setUserId(\OCP\User::getUser());
		$eventDB = new EventDAO($this -> db, $this -> userId, null);

		return $eventDB -> find($id);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @brief Returns true or false if event is an shared event
	 * @param integer $id
	 *
	 * @return associative array || null
	 *
	 */

	public function checkIfObjectIsShared($id) {
		$this -> setUserId(\OCP\User::getUser());
		$eventDB = new EventDAO($this -> db, $this -> userId, null);

		return $eventDB -> checkSharedEvent($id);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @brief Returns an object
	 * @param integer $id
	 * @return associative array || null
	 */
	public function deleteObjects($calendarid) {

		$this -> setUserId(\OCP\User::getUser());
		$eventDB = new EventDAO($this -> db, $this -> userId, null);

		return $eventDB -> deleteAllObjectsFromCalendar($calendarid);
	}

	/**
	 * @brief returns the owner of an object
	 * @param integer $id event id
	 * @return string
	 */
	public function getowner($id) {
		$event = $this -> findObject($id);
		$calendar = $this -> find($event['calendarid']);
		if ($calendar === false || is_array($calendar) === false) {
			return null;
		}

		if (array_key_exists('userid', $calendar)) {
			return $calendar['userid'];
		} else {
			return null;
		}
	}

	/**
	 * @brief returns informations about a calendar
	 * @param int $id - id of the calendar
	 * @param bool $security - check access rights or not
	 * @param bool $shared - check if the user got access via sharing
	 * @return mixed - bool / array
	 */
	public function getCalendar($id, $security = true, $shared = false) {

		if (!is_numeric($id)) {
			return false;
		}
		$this -> setUserId(\OCP\User::getUser());

		$calendar = $this -> find($id);

		// FIXME: Correct arguments to just check for permissions
		if ($security === true && $shared === false) {
			if ($this -> userId === $calendar['userid']) {
				return $calendar;
			} else {
				return false;
			}
		}

		if ($security === true && $shared === true) {
			if ($this -> shareConnector -> getItemSharedWithBySourceCalendar($id) || $this -> shareConnector -> getItemSharedWithByLinkCalendar($id, $calendar['userid'])) {
				return $calendar;
			}
		}
		return $calendar;
	}

	/**
	 * @brief Gets the data of one calendar
	 * @param integer $id
	 * @return associative array
	 */
	public function find($id) {

		$this -> setUserId(\OCP\User::getUser());

		$calendarDB = new CalendarDAO($this -> db, $this -> userId);

		$calendarInfo = $calendarDB -> find($id);

		if ($calendarInfo !== null) {

			if ($calendarInfo['userid'] !== $this -> userId) {
				$userExists = \OC::$server -> getUserManager() -> userExists($this -> userId);

				if (!$userExists) {
					$sharedCalendar = $this -> shareConnector -> getItemSharedWithByLinkCalendar($id, $calendarInfo['userid']);
				} else {
					$sharedCalendar = $this -> shareConnector -> getItemSharedWithBySourceCalendar($id);
				}

				if ((!$sharedCalendar || !(isset($sharedCalendar['permissions']) && $sharedCalendar['permissions'] & $this -> shareConnector -> getReadAccess()))) {

					return $calendarInfo;
					// I have to return the row so e.g. Object::getowner() works.
				}

				$calendarInfo['permissions'] = $sharedCalendar['permissions'];

			} else {
				$calendarInfo['permissions'] = $this -> shareConnector -> getAllAccess();
			}

			return $calendarInfo;
		} else {
			return null;
		}
	}

	/**
	 * @brief Creates a new calendar from the data sabredav provides
	 * @param string $principaluri
	 * @param string $uri
	 * @param string $name
	 * @param string $components
	 * @param string $timezone
	 * @param integer $order
	 * @param string $color format: '#RRGGBB(AA)'
	 * @return insertid
	 */

	public function add($principaluri, $uri, $name, $components, $timezone, $order, $color, $transparent) {

		$userid = $this -> extractUserID($principaluri);
		$this -> setUserId($userid);
		$all = $this -> all();

		$uris = array();
		foreach ($all as $i) {
			$uris[] = $i['uri'];
		}

		$lastmodified = time();

		$uri = $this -> createURI($name, $uris);

		$calendarDB = new CalendarDAO($this -> db, $this -> userId);

		$insertid = $calendarDB -> add($name, $uri, $order, $color, $timezone, $components, 0, '', 0);

		if ($insertid !== null) {
			\OCP\Util::emitHook('\OCA\CalendarPlus', 'addCalendar', $insertid);
			return $insertid;
		} else {
			return null;
		}
	}

	/**
	 * @brief Edits a calendar
	 * @param integer $id
	 * @param string $name Default: null
	 * @param string $components Default: null
	 * @param string $timezone Default: null
	 * @param integer $order Default: null
	 * @param string $color Default: null, format: '#RRGGBB(AA)'
	 * @param boolean $bLogActivity 
	 * @return boolean
	 *
	 * Values not null will be set
	 */
	public function edit($id, $name = null, $components = null, $timezone = null, $order = null, $color = null, $transparent = null, $bLogActivity = true) {
		// Need these ones for checking uri
		$calendar = $this -> find($id);

		if ($calendar['userid'] !== $this -> userId) {
			$sharedCalendar = $this -> shareConnector -> getItemSharedWithBySourceCalendar($id);

			if (!$sharedCalendar || !($sharedCalendar['permissions'] & $this -> shareConnector -> getUpdateAccess())) {
				throw new \Sabre\DAV\Exception\Forbidden('You do not have the permissions to update this calendar.');
			}
		}

		// Keep old stuff
		if (is_null($name))
			$name = $calendar['displayname'];
		if (is_null($components))
			$components = $calendar['components'];
		if (is_null($timezone))
			$timezone = $calendar['timezone'];
		if (is_null($order))
			$order = $calendar['calendarorder'];
		if (is_null($color))
			$color = $calendar['calendarcolor'];
		if (is_null($transparent))
			$transparent = $calendar['transparent'];

		$calendarDB = new CalendarDAO($this -> db, $this -> userId);

		$bUpdateCalendar = $calendarDB -> update($name, $order, $color, $timezone, $components, $transparent, $id);

		if ($bUpdateCalendar === true) {
			\OCP\Util::emitHook('\OCA\CalendarPlus', 'editCalendar', $id);
			if($bLogActivity === true){
				$link = \OC::$server -> getURLGenerator() -> linkToRoute('calendarplus.page.index');
	
				$params = array('mode' => 'edited', 'link' => $link, 'trans_type' => '', 'summary' => $calendar['displayname'], 'cal_user' => $calendar['userid'], 'cal_displayname' => $calendar['displayname'], );
	
				ActivityData::logEventActivity($params, false, true);
			}

			return true;

		} else {
			return null;
		}
	}

	/**
	 * removes a calendar
	 * @param integer $id
	 * @param boolean $bLogActivity
	 * @return associative array
	 */
	public function delete($id, $bLogActivity = true) {
		$calendar = $this -> find($id);
		//\OCP\Util::writeLog('DAV', 'DEL ID-> '.$id, \OCP\Util::DEBUG);

		$group = \OC::$server -> getGroupManager() -> get('admin');
		$this -> setUserId(\OCP\User::getUser());

		if ($calendar['userid'] !== $this -> userId && !$group -> inGroup($this -> userId)) {
			$sharedCalendar = $this -> shareConnector -> getItemSharedWithBySourceCalendar($id);
			if (!$sharedCalendar || !($sharedCalendar['permissions'] & $this -> shareConnector -> getDeleteAccess())) {
				throw new \Sabre\DAV\Exception\Forbidden('You do not have the permissions to delete this calendar.');
			}
		}

		$calendarDB = new CalendarDAO($this -> db, $this -> userId);
		$bDeleteCalendar = $calendarDB -> delete($id);

		if ($bDeleteCalendar === true) {

			\OCP\Util::emitHook('\OCA\CalendarPlus', 'deleteCalendar', $id);

			$this -> deleteObjects($id);

			$this -> shareConnector -> unshareAllCalendar($id);

			$app = new Application();
			$c = $app -> getContainer();
			$repeatController = $c -> query('RepeatController');
			$repeatController -> cleanCalendar($id);

			$calendars = $this -> all(false, false);

			if ((\OCP\USER::isLoggedIn() && count($calendars) === 0)) {
				//self::addDefaultCalendars($user);
			}
			if($bLogActivity === true){
				$link = \OC::$server -> getURLGenerator() -> linkToRoute('calendarplus.page.index');
	
				$params = array('mode' => 'deleted', 'link' => $link, 'trans_type' => '', 'summary' => $calendar['displayname'], 'cal_user' => $this -> userId, 'cal_displayname' => $calendar['displayname'], );
	
				ActivityData::logEventActivity($params, false, true);
			}

			return $bDeleteCalendar;
		} else {
			return $bDeleteCalendar;
		}
	}

	/**
	 * @brief Returns the list of calendars for a specific user.
	 * @param string $uid User ID
	 * @param boolean $active Only return calendars with this $active state, default(=false) is don't care
	 * @param boolean $bSubscribe  return calendars with this $issubscribe state, default(=true) is don't care
	 * @return array
	 */
	public function all($active = false, $bSubscribe = true) {

		$calendarDB = new CalendarDAO($this -> db, $this -> userId);

		$calendars = $calendarDB -> all($active, $bSubscribe);

		$calendars = array_merge($calendars, $this -> shareConnector -> getItemsSharedWithCalendar());

		\OCP\Util::emitHook('OCA\CalendarPlus', 'getCalendars', array('calendar' => &$calendars));

		return $calendars;
	}

	private function setUserId($userid) {
		$this -> userId = $userid;
	}

	/**
	 * @brief Creates a URI for Calendar
	 * @param string $name name of the calendar
	 * @param array  $existing existing calendar URIs
	 * @return string uri
	 */
	private function createURI($name, $existing) {
		$strip = array(' ', '/', '?', '&');
		//these may break sync clients
		$name = str_replace($strip, '', $name);
		$name = strtolower($name);

		$newname = $name;
		$i = 1;
		while (in_array($newname, $existing)) {
			$newname = $name . $i;
			$i = $i + 1;
		}
		return $newname;
	}

	/**
	 * @brief gets the userid from a principal path
	 * @return string
	 */
	public function extractUserID($principaluri) {
		list($prefix, $userid) = \Sabre\DAV\URLUtil::splitPath($principaluri);
		return $userid;
	}

}
