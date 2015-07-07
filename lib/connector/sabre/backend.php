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
namespace OCA\CalendarPlus\Connector\Sabre;

use OCA\CalendarPlus\Calendar as CalendarCalendar;
use OCA\CalendarPlus\App as CalendarApp;
use OCA\CalendarPlus\Object;
use OCA\CalendarPlus\VObject;

class Backend extends \Sabre\CalDAV\Backend\AbstractBackend  {
	/**
	 * List of CalDAV properties, and how they map to database fieldnames
	 *
	 * Add your own properties by simply adding on to this array
	 *
	 * @var array
	 */
	public $propertyMap = array(
		'{DAV:}displayname'                          => 'displayname',
        '{urn:ietf:params:xml:ns:caldav}calendar-description' => 'description',
        '{urn:ietf:params:xml:ns:caldav}calendar-timezone'    => 'timezone',
        '{http://apple.com/ns/ical/}calendar-order'  => 'calendarorder',
        '{http://apple.com/ns/ical/}calendar-color'  => 'calendarcolor',
	);

	/**
	 * Returns a list of calendars for a principal.
	 *
	 * Every project is an array with the following keys:
	 *  * id, a unique id that will be used by other functions to modify the
	 *	calendar. This can be the same as the uri or a database key.
	 *  * uri, which the basename of the uri with which the calendar is
	 *	accessed.
	 *  * principalUri. The owner of the calendar. Almost always the same as
	 *	principalUri passed to this method.
	 *
	 * Furthermore it can contain webdav properties in clark notation. A very
	 * common one is '{DAV:}displayname'.
	 *
	 * @param string $principalUri
	 * @return array
	 */
	public function getCalendarsForUser($principalUri) {
			
		$raw = CalendarCalendar::allCalendarsWherePrincipalURIIs($principalUri);

		$calendars = array();
		
		foreach( $raw as $row ) {
			$components = explode(',',$row['components']);

			if($row['userid'] != \OCP\USER::getUser()) {
				$row['uri'] = $row['uri'] . '_shared_by_' . $row['userid'];
			}
			
				$calendar = array(
					'id' => $row['id'],
					'uri' => $row['uri'],
					'principaluri' => 'principals/'.\OCP\USER::getUser(),
					'{' . \Sabre\CalDAV\Plugin::NS_CALENDARSERVER . '}getctag' => $row['ctag']?$row['ctag']:'0',
					'{' . \Sabre\CalDAV\Plugin::NS_CALDAV . '}supported-calendar-component-set' => new \Sabre\CalDAV\Property\SupportedCalendarComponentSet($components),
	 			
				);
			
			 
			foreach($this->propertyMap as $xmlName=>$dbName) {
				$calendar[$xmlName] = isset($row[$dbName]) ? $row[$dbName] : '';
			}

			$calendars[] = $calendar;
		}
           return $calendars;
		
		
		
	}

	/**
	 * Creates a new calendar for a principal.
	 *
	 * If the creation was a success, an id must be returned that can be used to reference
	 * this calendar in other methods, such as updateCalendar
	 *
	 * @param string $principalUri
	 * @param string $calendarUri
	 * @param array $properties
	 * @return mixed
	 */
	 public function createCalendar($principalUri,$calendarUri, array $properties) {
        $values = array();

        // Default value
        $sccs = '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set';
        if (!isset($properties[$sccs])) {
            $values[':components'] = 'VEVENT,VTODO';
        } else {
            if (!($properties[$sccs] instanceof \Sabre\CalDAV\Property\SupportedCalendarComponentSet)) {
                throw new \Sabre\DAV\Exception('The ' . $sccs . ' property must be of type: Sabre_CalDAV_Property_SupportedCalendarComponentSet');
            }
            $values[':components'] = implode(',',$properties[$sccs]->getValue());
        }
		
		$transp = '{' . \Sabre\CalDAV\Plugin::NS_CALDAV . '}schedule-calendar-transp';
        if (isset($properties[$transp])) {
            $values[':transparent'] = $properties[$transp]->getValue()==='transparent';
        }
		
        foreach($this->propertyMap as $xmlName=>$dbName) {
            if (isset($properties[$xmlName])) {
                $values[$dbName] = $properties[$xmlName];
            }
        }

        if(!isset($values['displayname'])) $values['displayname'] = 'unnamed';
        $values['components'] = 'VEVENT,VTODO';
        if(!isset($values['timezone'])) $values['timezone'] = null;
		if(!isset($values['transparent'])) $values['transparent'] = 0;
		
        if(!isset($values['calendarorder'])) $values['calendarorder'] = 0;
        if(!isset($values['calendarcolor'])) $values['calendarcolor'] = null;
        if(!is_null($values['calendarcolor']) && strlen($values['calendarcolor']) == 9) {
            $values['calendarcolor'] = substr($values['calendarcolor'], 0, 7);
        }

        return CalendarCalendar::addCalendarFromDAVData($principalUri,$calendarUri,$values['displayname'],$values['components'],$values['timezone'],$values['calendarorder'],$values['calendarcolor'],$values['transparent']);
    }
	 
	 

	/**
	 * Updates a calendars properties
	 *
	 * The properties array uses the propertyName in clark-notation as key,
	 * and the array value for the property value. In the case a property
	 * should be deleted, the property value will be null.
	 *
	 * This method must be atomic. If one property cannot be changed, the
	 * entire operation must fail.
	 *
	 * If the operation was successful, true can be returned.
	 * If the operation failed, false can be returned.
	 *
	 * Deletion of a non-existant property is always succesful.
	 *
	 * Lastly, it is optional to return detailed information about any
	 * failures. In this case an array should be returned with the following
	 * structure:
	 *
	 * array(
	 *   403 => array(
	 *	  '{DAV:}displayname' => null,
	 *   ),
	 *   424 => array(
	 *	  '{DAV:}owner' => null,
	 *   )
	 * )
	 *
	 * In this example it was forbidden to update {DAV:}displayname.
	 * (403 Forbidden), which in turn also caused {DAV:}owner to fail
	 * (424 Failed Dependency) because the request needs to be atomic.
	 *
	 * @param string $calendarId
	 * @param array $properties
	 * @return bool|array
	 */
	public function updateCalendar($calendarId, \Sabre\DAV\PropPatch $propPatch) {
			
		
		$supportedProperties = array_keys($this->propertyMap);
        $supportedProperties[] = '{' . \Sabre\CalDAV\Plugin::NS_CALDAV . '}schedule-calendar-transp';

        $propPatch->handle($supportedProperties, function($mutations) use ($calendarId) {
        $newValues =array();	
		$bChange=false;
		 foreach($mutations as $propertyName => $propertyValue) {

                switch($propertyName) {
                    case '{' . \Sabre\CalDAV\Plugin::NS_CALDAV . '}schedule-calendar-transp' :
                        $fieldName = 'transparent';
                        $newValues[$fieldName] = $propertyValue->getValue()==='transparent';
                        break;
                    default :
                        $fieldName = $this->propertyMap[$propertyName];
                        $newValues[$fieldName] = $propertyValue;
						
                        break;
                }
			$bChange =true;
            }	
		//\OCP\Util::writeLog('calendar',' change Found: '.$bChange, \OCP\Util::DEBUG);	
		// Success
		if(!isset($newValues['displayname'])) $newValues['displayname'] = null;
		if(!isset($newValues['timezone'])) $newValues['timezone'] = null;
		if(!isset($newValues['calendarorder'])) $newValues['calendarorder'] = null;
		if(!isset($newValues['calendarcolor'])) $newValues['calendarcolor'] = null;
		if(!is_null($newValues['calendarcolor']) && strlen($newValues['calendarcolor']) == 9) {
			$newValues['calendarcolor'] = substr($newValues['calendarcolor'], 0, 7);
		}

		CalendarCalendar::editCalendar($calendarId,$newValues['displayname'],null,$newValues['timezone'],$newValues['calendarorder'],$newValues['calendarcolor']);
	   return true;
	});
	//	return \OCA\Calendar\Calendar::editCalendarFromDAVData($principalUri,$calendarUri,$values['displayname'],$values['components'],$values['timezone'],$values['calendarorder'],$values['calendarcolor']);
		
		//return true;

	}

	/**
	 * Delete a calendar and all it's objects
	 *
	 * @param string $calendarId
	 * @return void
	 */
	public function deleteCalendar($calendarId) {
	    if(preg_match( '=iCal/[1-4]?.*Mac OS X/10.[1-6](.[0-9])?=', $_SERVER['HTTP_USER_AGENT'] )) {
	    	//throw new \Sabre\DAV\Exception\Forbidden("Action is not possible with OSX 10.6.x", 403);
		}
		//\OCP\Util::writeLog('calendar', 'DEL ID-> '.$calendarId, \OCP\Util::DEBUG);
		CalendarCalendar::deleteCalendar($calendarId);
		return true;
	}

	/**
	 * Returns all calendar objects within a calendar object.
	 *
	 * Every item contains an array with the following keys:
	 *   * id - unique identifier which will be used for subsequent updates
	 *   * calendardata - The iCalendar-compatible calnedar data
	 *   * uri - a unique key which will be used to construct the uri. This can be any arbitrary string.
	 *   * lastmodified - a timestamp of the last modification time
	 *   * etag - An arbitrary string, surrounded by double-quotes. (e.g.:
	 *   '  "abcdef"')
	 *   * calendarid - The calendarid as it was passed to this function.
	 *
	 * Note that the etag is optional, but it's highly encouraged to return for
	 * speed reasons.
	 *
	 * The calendardata is also optional. If it's not returned
	 * 'getCalendarObject' will be called later, which *is* expected to return
	 * calendardata.
	 *
	 * @param string $calendarId
	 * @return array
	 */
	public function getCalendarObjects($calendarId) {
		$data = array();
		$calendar = CalendarCalendar::find($calendarId);
		$isShared = ($calendar['userid'] !== \OCP\USER::getUser());
		
		foreach(Object::all($calendarId) as $row) {
			if (!$isShared) {
					
				$data[] = $this->OCAddETag($row);
			}else{
				
				if (substr_count($row['calendardata'], 'CLASS') === 0) {
						$data[] = $this->OCAddETag($row);
					} else {
							$object = VObject::parse($row['calendardata']);
							if(!$object) {
								return false;
							}
						$isPrivate = false;
						$toCheck = array('VEVENT', 'VJOURNAL', 'VTODO');
						foreach ($toCheck as $type) {
							foreach ($object->select($type) as $vobject) {
								if (isset($vobject->{'CLASS'}) && $vobject->{'CLASS'}->getValue() === 'PRIVATE') {
									$isPrivate = true;
								}
							}
						}
						if ($isPrivate === false) {
							$data[] = $this->OCAddETag($row);
						}
					}
			}
		}
		return $data;
	}

	/**
	 * Returns information from a single calendar object, based on it's object
	 * uri.
	 *
	 * The returned array must have the same keys as getCalendarObjects. The
	 * 'calendardata' object is required here though, while it's not required
	 * for getCalendarObjects.
	 *
	 * @param string $calendarId
	 * @param string $objectUri
	 * @return array
	 */
	public function getCalendarObject($calendarId,$objectUri) {
		$data = Object::findWhereDAVDataIs($calendarId,$objectUri);
		
		if(is_array($data)) {
			$data = $this->OCAddETag($data);	
			$object = VObject::parse($data['calendardata']);
			if(!$object) {
				return false;
			}
			$object = Object::cleanByAccessClass($data['id'], $object);
			$data['calendardata'] = $object->serialize();
			//$data = $this->OCAddETag($data);
			return $data;
		}else return false;
		
	}

	/**
	 * Creates a new calendar object.
	 *
	 * @param string $calendarId
	 * @param string $objectUri
	 * @param string $calendarData
	 * @return void
	 */
	public function createCalendarObject($calendarId,$objectUri,$calendarData) {
		$calendar = CalendarCalendar::find($calendarId);
		$bAccess=true;
		if($calendar['userid'] !== \OCP\User::getUser()) {
			$sharedCalendar = \OCP\Share::getItemSharedWithBySource(CalendarApp::SHARECALENDAR, CalendarApp::SHARECALENDARPREFIX. $calendarId);
			if (!$sharedCalendar || !($sharedCalendar['permissions'] & \OCP\PERMISSION_UPDATE)) {	
				$bAccess=false;
				$calendarData=null;
				\OCP\Util::writeLog('calendarplus', 'CALDAV -> CREATE Permission denied! Calendar '.$calendar['displayname'], \OCP\Util::DEBUG);
			}
		}
		if($bAccess === true){	
			Object::addFromDAVData($calendarId,$objectUri,$calendarData);
		}
	}

	/**
	 * Updates an existing calendarobject, based on it's uri.
	 *
	 * @param string $calendarId
	 * @param string $objectUri
	 * @param string $calendarData
	 * @return void
	 */
	public function updateCalendarObject($calendarId,$objectUri,$calendarData) {
		$calendar = CalendarCalendar::find($calendarId);
		$bAccess=true;
		if($calendar['userid'] != \OCP\User::getUser()) {
			$sharedCalendar = \OCP\Share::getItemSharedWithBySource(CalendarApp::SHARECALENDAR, CalendarApp::SHARECALENDARPREFIX.$calendarId);
			if (!$sharedCalendar || !($sharedCalendar['permissions'] & \OCP\PERMISSION_UPDATE)) {	
				$bAccess=false;
				\OCP\Util::writeLog('calendarplus', 'CALDAV -> UPDATE Permission denied! Calendar '.$calendar['displayname'], \OCP\Util::DEBUG);
			}
		}
		if($bAccess === true){
			Object::editFromDAVData($calendarId,$objectUri,$calendarData);
		}
	}

	/**
	 * Deletes an existing calendar object.
	 *
	 * @param string $calendarId
	 * @param string $objectUri
	 * @return void
	 */
	public function deleteCalendarObject($calendarId,$objectUri) {
		$calendar = CalendarCalendar::find($calendarId);
		$bAccess=true;
		if($calendar['userid'] !== \OCP\User::getUser()) {
			$sharedCalendar = \OCP\Share::getItemSharedWithBySource(CalendarApp::SHARECALENDAR,CalendarApp::SHARECALENDARPREFIX.$calendarId);
			if (!$sharedCalendar || !($sharedCalendar['permissions'] & \OCP\PERMISSION_UPDATE)) {	
				$bAccess=false;
				\OCP\Util::writeLog('calendarplus', 'CALDAV -> DELETE Permission denied! Calendar '.$calendar['displayname'], \OCP\Util::DEBUG);
			}
		}
		if($bAccess === true){	
			Object::deleteFromDAVData($calendarId,$objectUri);
		}
	}

	/**
	 * @brief Creates a etag
	 * @param array $row Database result
	 * @returns associative array
	 *
	 * Adds a key "etag" to the row
	 */
	private function OCAddETag($row) {
		$row['etag'] = '"'.md5($row['calendarid'].$row['uri'].$row['calendardata'].$row['lastmodified']).'"';
		return $row;
	}
}
