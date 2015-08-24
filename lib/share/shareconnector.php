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
namespace OCA\CalendarPlus\Share;

use \OCP\Share;
use \OCA\CalendarPlus\Share\Backend\Calendar as ShareCalendar;

class ShareConnector {
	const SHARECALENDAR = 'calpl';
	const SHARECALENDARPREFIX = 'calendar-';
	const SHAREEVENT = 'calplevent';
	const SHAREEVENTPREFIX = 'event-';
	const SHARETODO = 'calpltodo';
	const SHARETODOPREFIX = 'todo-';

	/**
	 * @NoAdminRequired
	 *
	 *
	 * @param integer $calendarid calendar id
	 * @param string $userid
	 *@return array || null
	 */
	public function getItemSharedWithByLinkCalendar($calendarid, $userid) {
		return Share::getItemSharedWithByLink(self::SHARECALENDAR, self::SHARECALENDARPREFIX . $calendarid, $userid);
	}

	public function getItemsSharedWithCalendar() {
		return Share::getItemsSharedWith(self::SHARECALENDAR, ShareCalendar::FORMAT_CALENDAR);
	}

	public function getConstShareCalendar() {
		return self::SHARECALENDAR;
	}

	public function getConstSharePrefixCalendar() {
		return self::SHARECALENDARPREFIX;
	}

	public function getConstShareEvent() {
		return self::SHAREEVENT;
	}

	public function getConstSharePrefixEvent() {
		return self::SHAREEVENTPREFIX;
	}

	public function getConstShareTodo() {
		return self::SHARETODO;
	}

	public function getConstSharePrefixTodo() {
		return self::SHARETODOPREFIX;
	}

	/**
	 *
	 * @param string $token
	 *@return array || null
	 */
	public function getShareByToken($token) {
		return Share::getShareByToken($token, false);
	}

	/**
	 *
	 * @param array $linkItem
	 *@return array || null
	 */
	public function resolveReShare($linkItem) {
		return Share::resolveReShare($linkItem);
	}

	/**
	 * @NoAdminRequired
	 *
	 *
	 * @param integer $calendarid calendar id
	 *@return array || null
	 */
	public function getItemSharedWithBySourceCalendar($calendarid) {
		return Share::getItemSharedWithBySource(self::SHARECALENDAR, self::SHARECALENDARPREFIX . $calendarid);
	}

	/**
	 * @NoAdminRequired
	 *
	 *
	 * @param integer $eventid
	 *@return array || null
	 */
	public function getItemSharedWithBySourceEvent($eventid) {
		return Share::getItemSharedWithBySource(self::SHAREEVENT, self::SHAREEVENTPREFIX . $eventid);
	}

	/**
	 * @NoAdminRequired
	 *
	 *
	 * @param integer $eventid
	 *@return array || null
	 */
	public function getItemSharedWithBySourceTodo($eventid) {
		return Share::getItemSharedWithBySource(self::SHARETODO, self::SHARETODOPREFIX . $eventid);
	}

	/**
	 * @NoAdminRequired
	 *
	 *
	 * @param integer $eventid
	 *@return array || null
	 */
	public function unshareAllEvent($eventid) {
		return Share::unshareAll(self::SHAREEVENT, self::SHAREEVENTPREFIX . $eventid);
	}

	/**
	 * @NoAdminRequired
	 *
	 *
	 * @param integer $eventid
	 *@return array || null
	 */
	public function unshareAllTodos($eventid) {
		return Share::unshareAll(self::SHARETODO, self::SHARETODOPREFIX . $eventid);
	}

	/**
	 * @NoAdminRequired
	 *
	 *
	 * @param integer $calendarid
	 *@return array || null
	 */
	public function unshareAllCalendar($calendarid) {
		return Share::unshareAll(self::SHARECALENDAR, self::SHARECALENDARPREFIX . $calendarid);
	}

	public function validateItemSource($itemSource, $itemType) {

		if (stristr($itemSource, $itemType)) {
			$iTempItemSource = explode($itemType, $itemSource);
			return (int)$iTempItemSource[1];
		} else {
			return $itemSource;
		}

	}

	public function getShareTypeLink() {
		return Share::SHARE_TYPE_LINK;
	}

	public function getShareAccess() {
		return \OCP\Constants::PERMISSION_SHARE;
	}

	public function getReadAccess() {
		return \OCP\Constants::PERMISSION_READ;
	}

	public function getCreateAccess() {
		return \OCP\Constants::PERMISSION_CREATE;
	}

	public function getUpdateAccess() {
		return \OCP\Constants::PERMISSION_UPDATE;
	}

	public function getDeleteAccess() {
		return \OCP\Constants::PERMISSION_DELETE;
	}

	public function getAllAccess() {
		return \OCP\Constants::PERMISSION_ALL;
	}

}
