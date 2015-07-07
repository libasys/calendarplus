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
 * This class overrides Sabre_CalDAV_CalendarObject::getACL()
 * to return read/write permissions based on user and shared state.
*/

namespace OCA\CalendarPlus\Connector\Sabre;

use OCA\CalendarPlus\Calendar as CalendarCalendar;
use OCA\CalendarPlus\Object;
use OCA\CalendarPlus\VObject;
use OCA\CalendarPlus\App as CalendarApp;

class CalendarObject extends \Sabre\CalDAV\CalendarObject {

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
		$uid = CalendarCalendar::extractUserID($this->getOwner());
		
		if($uid != \OCP\USER::getUser()) {
			$object = VObject::parse($this->objectData['calendardata']);
			$sharedCalendar = \OCP\Share::getItemSharedWithBySource(CalendarApp::SHARECALENDAR, CalendarApp::SHARECALENDARPREFIX.$this->calendarInfo['id']);
			$sharedAccessClassPermissions = Object::getAccessClassPermissions($object);
			if ($sharedCalendar && ($sharedCalendar['permissions'] & \OCP\PERMISSION_READ) && ($sharedAccessClassPermissions & \OCP\PERMISSION_READ)) {
				$readprincipal = 'principals/' . \OCP\USER::getUser();
			}
			if ($sharedCalendar && ($sharedCalendar['permissions'] & \OCP\PERMISSION_UPDATE) && ($sharedAccessClassPermissions & \OCP\PERMISSION_UPDATE)) {
				$writeprincipal = 'principals/' . \OCP\USER::getUser();
			}else{
				$writeprincipal = '';
			}
		}

		return array(
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
		);

	}

}