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
 * This class overrides Sabre_CalDAV_Calendar::getACL() to return read/write
 * permissions based on user and shared state and it overrides
 * Sabre_CalDAV_Calendar::getChild() and Sabre_CalDAV_Calendar::getChildren()
 * to instantiate OC_Connector_Sabre_CalDAV_CalendarObjects.
*/

namespace OCA\CalendarPlus\Connector\Sabre;
use OCA\CalendarPlus\App as CalendarApp;
use OCA\CalendarPlus\Calendar as CalendarCalendar;

class Calendar extends \Sabre\CalDAV\Calendar {

	/**
	* Returns a list of ACE's for this node.
	*
	* Each ACE has the following properties:
	*   * 'privilege', a string such as {DAV:}read or {DAV:}write. These are
	*     currently the only supported privileges
	*   * 'principal', a url to the principal who owns the node
	*   * 'protected' (optional), indicating that this ACE is not allowed to
	*      be updated.
	*
	* @return array
	*/
	public function getACL() {

		$readprincipal = $this->getOwner();
		$writeprincipal = $this->getOwner();
		$uid =CalendarCalendar::extractUserID($this->getOwner());
		
		$calendar = CalendarApp::getCalendar($this->calendarInfo['id'], false, false);
		
        if($uid === \OCP\USER::getUser() && (bool)$calendar['issubscribe'] === true) {
         		$readprincipal = 'principals/' . \OCP\USER::getUser();
				$writeprincipal ='';
         }
		
		if($uid !== \OCP\USER::getUser()) {
			$sharedCalendar = \OCP\Share::getItemSharedWithBySource(CalendarApp::SHARECALENDAR, CalendarApp::SHARECALENDARPREFIX.$this->calendarInfo['id']);
			if ($sharedCalendar && ($sharedCalendar['permissions'] & \OCP\PERMISSION_READ)) {
				$readprincipal = 'principals/' . \OCP\USER::getUser();
				$writeprincipal = '';
				
			}
			if ($sharedCalendar && ($sharedCalendar['permissions'] & \OCP\PERMISSION_UPDATE)) {
				$readprincipal = 'principals/' . \OCP\USER::getUser();	
				$writeprincipal = 'principals/' . \OCP\USER::getUser();
			}
		}

		$acl = array(
			array(
				'privilege' => '{DAV:}read',
				'principal' => $readprincipal,
				'protected' => true,
			),
			array(
				'privilege' => '{DAV:}write',
				'principal' => $writeprincipal,
				'protected' => true,
			),
			array(
				'privilege' => '{DAV:}read',
				'principal' => $readprincipal . '/calendar-proxy-write',
				'protected' => true,
			),
			array(
				'privilege' => '{DAV:}write',
				'principal' => $writeprincipal . '/calendar-proxy-write',
				'protected' => true,
			),
			array(
				'privilege' => '{DAV:}read',
				'principal' => $readprincipal . '/calendar-proxy-read',
				'protected' => true,
			),
			array(
				'privilege' => '{' . \Sabre\CalDAV\Plugin::NS_CALDAV . '}read-free-busy',
				'principal' => '{DAV:}authenticated',
				'protected' => true,
			),

		);
		
		if (empty($this->calendarInfo['{http://sabredav.org/ns}read-only'])) {
            $acl[] = [
                'privilege' => '{DAV:}write',
                'principal' => $writeprincipal,
                'protected' => true,
            ];
            $acl[] = [
                'privilege' => '{DAV:}write',
                'principal' => $writeprincipal . '/calendar-proxy-write',
                'protected' => true,
            ];
        }
				
		return $acl;
	}

	/**
	* Returns a calendar object
	*
	* The contained calendar objects are for example Events or Todo's.
	*
	* @param string $name
	* @return Sabre_DAV_ICalendarObject
	*/
	public function getChild($name) {

		$obj = $this->caldavBackend->getCalendarObject($this->calendarInfo['id'],$name);
		if (!$obj) throw new \Sabre\DAV\Exception\NotFound('Calendar object not found');
		return new CalendarObject($this->caldavBackend,$this->calendarInfo,$obj);

	}

	/**
	* Returns the full list of calendar objects
	*
	* @return array
	*/
	public function getChildren() {

		$objs = $this->caldavBackend->getCalendarObjects($this->calendarInfo['id']);
		$children = array();
		foreach($objs as $obj) {
			$children[] = new CalendarObject($this->caldavBackend,$this->calendarInfo,$obj);
		}
		return $children;

	}

}