<?php
/**
 * ownCloud - CalendarPlus
 *
 * @author Sebastian Doell
 * @copyright 2015 sebastian doell sebastian@libasys.de
 *
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


namespace OCA\CalendarPlus;

class Activity implements \OCP\Activity\IExtension {
		
	const USED_APP = 'calendarplus';
	const FILTER_CALENDAR = 'calendar';
	const FILTER_SHARECALENDAR = 'sharescal';
	const FILTER_UNSHARECALENDAR = 'unsharescal';
	
	const TYPE_SHARED_EVENT_CREATED = 'shared_event_created';
	const TYPE_SHARED_EVENT_EDITED = 'shared_event_edited';
	const TYPE_SHARED_EVENT_DELETED = 'shared_event_deleted';
	const TYPE_SHARED_CALENDAR = 'shared_calendar';
	const TYPE_UNSHARED_CALENDAR = 'unshared_calendar';
	const TYPE_EVENT_CREATED = 'event_created';
	const TYPE_EVENT_EDITED = 'event_edited';
	const TYPE_EVENT_DELETED = 'event_deleted';
	const TYPE_CALENDAR_CREATED = 'calendar_created';
	const TYPE_CALENDAR_EDITED = 'calendar_edited';
	const TYPE_CALENDAR_DELETED = 'calendar_deleted';
	

	/**
	 * The extension can return an array of additional notification types.
	 * If no additional types are to be added false is to be returned
	 *
	 * @param string $languageCode
	 * @return array|false
	 */
	public function getNotificationTypes($languageCode) {
		$l = \OC::$server->getL10N(self::USED_APP, $languageCode);
		return array(
		self::TYPE_SHARED_EVENT_CREATED =>$l->t('A event or todo has been <strong>created</strong> on a shared Calendar'),
		self::TYPE_SHARED_EVENT_EDITED =>$l->t('A event or todo has been <strong>edited</strong> on a shared Calendar'),
		self::TYPE_SHARED_EVENT_DELETED =>$l->t('A event or todo has been <strong>deleted</strong> on a shared Calendar'),
		self::TYPE_SHARED_CALENDAR =>$l->t('A event, todo or calendar has been <strong>shared</strong>'),
		self::TYPE_UNSHARED_CALENDAR=>$l->t('A event, todo or calendar has been <strong>unshared</strong>'),
		self::TYPE_EVENT_CREATED=>$l->t('A event or todo has been <strong>created</strong>'),
		self::TYPE_EVENT_EDITED=>$l->t('A event or todo has been <strong>edited</strong>'),
		self::TYPE_EVENT_DELETED=>$l->t('A event or todo has been <strong>deleted</strong>'),
		self::TYPE_CALENDAR_CREATED=>$l->t('A calendar has been <strong>created</strong>'),
		self::TYPE_CALENDAR_EDITED=>$l->t('A calendar has been <strong>edited</strong>'),
		self::TYPE_CALENDAR_DELETED=>$l->t('A calendar has been <strong>deleted</strong>'),
	);
		
	}

	/**
	 * The extension can filter the types based on the filter if required.
	 * In case no filter is to be applied false is to be returned unchanged.
	 *
	 * @param array $types
	 * @param string $filter
	 * @return array|false
	 */
	public function filterNotificationTypes($types, $filter) {
		return false;
	}

	/**
	 * For a given method additional types to be displayed in the settings can be returned.
	 * In case no additional types are to be added false is to be returned.
	 *
	 * @param string $method
	 * @return array|false
	 */
	public function getDefaultTypes($method) {
		if ($method === 'stream') {
			$settings = array();
			$settings[] = self::TYPE_SHARED_EVENT_CREATED;
			$settings[] = self::TYPE_SHARED_EVENT_EDITED;
			$settings[] = self::TYPE_SHARED_EVENT_DELETED;
			$settings[] = self::TYPE_SHARED_CALENDAR;
			$settings[] = self::TYPE_UNSHARED_CALENDAR;
			$settings[] = self::TYPE_CALENDAR_DELETED;
			return $settings;
		}
		return false;
	}

	/**
	 * The extension can translate a given message to the requested languages.
	 * If no translation is available false is to be returned.
	 *
	 * @param string $app
	 * @param string $text
	 * @param array $params
	 * @param boolean $stripPath
	 * @param boolean $highlightParams
	 * @param string $languageCode
	 * @return string|false
	 */
	public function translate($app, $text, $params, $stripPath, $highlightParams, $languageCode) {

		$l = \OC::$server->getL10N(self::USED_APP, $languageCode);

		if ($app === self::USED_APP) {
			switch ($text) {
				case 'created_self':
					return (string) $l->t('You created a %1$s on calendar %2$s',$params);
				break;
				case 'edited_self':
					return (string) $l->t('You edited a %1$s on calendar %2$s',$params);
				break;
				case 'deleted_self':
					return (string) $l->t('You deleted a %1$s on calendar %2$s',$params);
				case 'created_calendar_self':
					return (string) $l->t('You created calendar %1$s',$params);
				break;
				case 'edited_calendar_self':
					return (string) $l->t('You edited calendar %1$s',$params);
				break;
				case 'deleted_calendar_self':
					return (string) $l->t('You deleted calendar %1$s',$params);
				break;
				case 'shared_link_self_calendar':
					return (string) $l->t('You shared %1$s via Link',$params);
				break;
				case 'unshared_link_self_calendar':
					return (string) $l->t('You unshared %1$s via Link',$params);
				break;
				case 'shared_user_self_calendar':
					return (string) $l->t('You shared %1$s with %2$s',$params);
				break;
				case 'unshared_user_self_calendar':
					return (string) $l->t('You unshared %1$s with %2$s',$params);
				break;
				case 'shared_group_self_calendar':
					return (string) $l->t('You shared %1$s with group %2$s',$params);
				break;
				case 'unshared_group_self_calendar':
					return (string) $l->t('You unshared %1$s with group %2$s',$params);
				break;
				case 'shared_with_by_calendar':
					return (string) $l->t('%2$s shared %1$s with you',$params);
				break;
				case 'unshared_with_by_calendar':
					return (string) $l->t('%2$s unshared %1$s with you',$params);
				break;
				case 'created_by_other':
					return (string) $l->t('A new %1$s from %2$s in shared calendar %3$s created',$params);
				break;
				case 'edited_by_other':
					return (string) $l->t('A %1$s from %2$s in shared calendar %3$s edited',$params);
				break;
				case 'deleted_by_other':
					return (string) $l->t('A %1$s from %2$s in shared calendar %3$s deleted',$params);
				break;
			}
		}

		return false;
	}

	/**
	 * The extension can define the type of parameters for translation
	 *
	 * Currently known types are:
	 * * file		=> will strip away the path of the file and add a tooltip with it
	 * * username	=> will add the avatar of the user
	 *
	 * @param string $app
	 * @param string $text
	 * @return array|false
	 */
	public function getSpecialParameterList($app, $text) {
		if ($app === self::USED_APP) {
			switch ($text) {
				case 'shared_with_by_calendar':
				case 'unshared_with_by_calendar':
				case 'shared_user_self_calendar':
				case 'unshared_user_self_calendar':
					return [
						//0 => 'calendar'
						1 => 'username',
					];
				case 'created_by_other':
				case 'edited_by_other':
				case 'deleted_by_other':
					return [
						//0 => 'event',
						1 => 'username',
						//2 => 'calendar'
					];
				case 'shared_group_self_calendar':
				case 'unshared_group_self_calendar':
					return [
						//0 => 'calendar'
						1 => 'group',
					];
				case 'created_self':
				case 'edited_self':
				case 'deleted_self':
					return [
						//0 => 'event',
						//1 => 'calendar',
					];
				case 'shared_link_self_calendar':
				case 'unshared_link_self_calendar':
				case 'created_calendar_self':
				case 'edited_calendar_self':
				case 'deleted_calendar_self':
					return [
						//0 => 'calendar',
					];
			}
		}

		return false;
	}

	/**
	 * A string naming the css class for the icon to be used can be returned.
	 * If no icon is known for the given type false is to be returned.
	 *
	 * @param string $type
	 * @return string|false
	 */
	public function getTypeIcon($type) {
	
		switch($type){
			case self::TYPE_SHARED_CALENDAR:
				return 'icon-shared';
			break;
			case self::TYPE_UNSHARED_CALENDAR:
				return 'icon-share';
			break;
			case self::TYPE_SHARED_EVENT_CREATED:
			case self::TYPE_EVENT_CREATED:
			case self::TYPE_CALENDAR_CREATED:
				return 'icon-info';
			break;
			case self::TYPE_EVENT_EDITED:
			case self::TYPE_SHARED_EVENT_EDITED:
			case self::TYPE_CALENDAR_EDITED:
				return 'icon-rename';
			break;
			case self::TYPE_SHARED_EVENT_DELETED:
			case self::TYPE_CALENDAR_DELETED:
			case self::TYPE_EVENT_DELETED:
				return 'icon-delete';
			break;
		}

		return false;
	}

	/**
	 * The extension can define the parameter grouping by returning the index as integer.
	 * In case no grouping is required false is to be returned.
	 *
	 * @param array $activity
	 * @return integer|false
	 */
	public function getGroupParameter($activity) {
		if ($activity['app'] === self::USED_APP) {
			switch ($activity['subject']) {
				case 'created_calendar_self':
				case 'edited_calendar_self':
				case 'deleted_calendar_self':
					// You created calendar calA and calB
					return 0;

				case 'shared_user_self_calendar':
				case 'shared_group_self_calendar':
				case 'unshared_user_self_calendar':
				case 'unshared_group_self_calendar':
					// You shared calA with userA and userB
					return 1;

				case 'shared_with_by_calendar':
				case 'unshared_with_by_calendar':
					// UserA shared calA and calB with you
					return 0;
			}
		}

		return false;
	}

	/**
	 * The extension can define additional navigation entries. The array returned has to contain two keys 'top'
	 * and 'apps' which hold arrays with the relevant entries.
	 * If no further entries are to be added false is no be returned.
	 *
	 * @return array|false
	 */
	public function getNavigation() {

		$l = \OC::$server->getL10N(self::USED_APP);

		return [
			'apps' => [
				self::FILTER_CALENDAR => [
					'id' => self::FILTER_CALENDAR,
					'name' => $l->t('Calendar+'),
					'url' =>\OC::$server->getRouter()->generate('activity.Activities.showList',array('filter' => self::FILTER_CALENDAR)),
				],
				self::FILTER_SHARECALENDAR => [
					'id' => self::FILTER_SHARECALENDAR,
					'name' => $l->t('Sharees Calendar+'),
					'url' => \OC::$server->getRouter()->generate('activity.Activities.showList',array('filter' => self::FILTER_SHARECALENDAR)),
				],
				self::FILTER_UNSHARECALENDAR => [
					'id' => self::FILTER_UNSHARECALENDAR,
					'name' => $l->t('Suspendend Sharees Calendar+'),
					'url' => \OC::$server->getRouter()->generate('activity.Activities.showList',array('filter' => self::FILTER_UNSHARECALENDAR)),
				],
			],
			'top' => [],
		];
	}

	/**
	 * The extension can check if a customer filter (given by a query string like filter=abc) is valid or not.
	 *
	 * @param string $filterValue
	 * @return boolean
	 */
	public function isFilterValid($filterValue) {
		return $filterValue === self::FILTER_CALENDAR || $filterValue === self::FILTER_SHARECALENDAR || $filterValue === self::FILTER_UNSHARECALENDAR;
	}

	/**
	 * For a given filter the extension can specify the sql query conditions including parameters for that query.
	 * In case the extension does not know the filter false is to be returned.
	 * The query condition and the parameters are to be returned as array with two elements.
	 * E.g. return array('`app` = ? and `message` like ?', array('mail', 'ownCloud%'));
	 *
	 * @param string $filter
	 * @return array|false
	 */
	public function getQueryForFilter($filter) {
		if ($filter === self::FILTER_CALENDAR) {
			return ['`app` = ?', [self::USED_APP]];
		}
		if ($filter === self::FILTER_SHARECALENDAR) {
			return ['`app` = ? AND `type` = ?',[self::USED_APP, self::TYPE_SHARED_CALENDAR]];
		}
		if ($filter === self::FILTER_UNSHARECALENDAR) {
			return ['`app` = ? AND `type` = ?', [self::USED_APP, self::TYPE_UNSHARED_CALENDAR]];
		}

		return false;
	}

}
