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

use OCA\CalendarPlus\App as CalendarApp;
use OCA\CalendarPlus\Calendar as CalendarCalendar;
use OCA\CalendarPlus\VObject;
use OCA\CalendarPlus\Object;
use OCA\CalendarPlus\Import;
use OCA\CalendarPlus\Share\Backend\Calendar as ShareCalendar;
use OCA\CalendarPlus\Share\ShareConnector;
use \OCA\CalendarPlus\ActivityData;

use \OCP\AppFramework\Controller;
use \OCP\AppFramework\Http\JSONResponse;
use \OCP\AppFramework\Http\TemplateResponse;
use \OCP\IRequest;
use \OCP\IConfig;

class CalendarController extends Controller {

	private $userId;
	private $l10n;
	private $configInfo;
	private $calendarDB;
	private $shareConnector;

	public function __construct($appName, IRequest $request, $userId, $l10n, IConfig $settings, $calendarDB) {
		parent::__construct($appName, $request);
		$this -> userId = $userId;
		$this -> l10n = $l10n;
		$this -> configInfo = $settings;
		$this -> calendarDB = $calendarDB;
		$this -> shareConnector = new ShareConnector();
	}

	/**
	 * @brief Gets the data of one calendar
	 * @param integer $id
	 * @return associative array
	 */
	public function find($id) {

		$calendarInfo = $this -> calendarDB -> find($id);

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
	 * @brief Returns the list of calendars for a specific user.
	 * @param string $uid User ID
	 * @param boolean $active Only return calendars with this $active state, default(=false) is don't care
	 * @param boolean $bSubscribe  return calendars with this $issubscribe state, default(=true) is don't care
	 * @return array
	 */
	public function allCalendars($active = false, $bSubscribe = true) {

		$calendars = $this -> calendarDB -> all($active, $bSubscribe);

		$calendars = array_merge($calendars, $this -> shareConnector -> getItemsSharedWithCalendar());

		\OCP\Util::emitHook('OCA\CalendarPlus', 'getCalendars', array('calendar' => &$calendars));

		return $calendars;
	}

	/**
	 * @NoAdminRequired
	 */
	public function getNewFormCalendar() {

		$calendar = ['id' => 'new', 'displayname' => '', 'calendarcolor' => '#ff0000', 'externuri' => '', ];

		$params = ['new' => true, 'calendar' => $calendar];

		$response = new TemplateResponse($this -> appName, 'part.editcalendar', $params, '');

		return $response;

	}

	/**
	 * @NoAdminRequired
	 *
	 * Creates a new calendar
	 * @param string $id
	 * @param string $name
	 *@param integer $active
	 *	@param string $color
	 * @param string $externuri
	 * @return insertid
	 */

	public function newCalendar($id, $name, $active, $color, $externuri) {

		//$calendarName = (string) $this -> params('name');
		//$externUriFile = (string) $this -> params('externuri');
		//$pColor = (string) $this -> params('color');

		if (trim($name) === '') {
			$params = ['status' => 'error', ];
			$response = new JSONResponse($params);
			return $response;
		}

		$calendars = $this -> allCalendars();

		foreach ($calendars as $cal) {
			if ($cal['displayname'] === $name) {
				$params = ['status' => 'error', 'message' => (string)$this -> l10n -> t('Name is not available!')];
				$response = new JSONResponse($params);
				return $response;
			}
		}

		$bError = false;

		$count = false;

		if (trim($externuri) !== '') {
			$aResult = $this -> addEventsFromSubscribedCalendar($externuri, $name, $color);
			if ($aResult['isError'] === true) {
				$bError = true;
			}
			if ($aResult['countEvents'] > 0) {
				$count = $aResult['countEvents'];
			}
			$calendarid = $aResult['calendarid'];
		} else {

			$calendarid = $this -> add($this -> userId, $name, 'VEVENT,VTODO,VJOURNAL', null, 0, $color);

			CalendarCalendar::setCalendarActive($calendarid, 1);
		}

		if (!$bError) {
			$calendar = $this -> find($calendarid);
			//FIXME
			$isShareApiActive = \OC::$server -> getAppConfig() -> getValue('core', 'shareapi_enabled', 'yes');

			$params = [
			'status' => 'success', 
			'eventSource' => CalendarCalendar::getEventSourceInfo($calendar), 
			'calid' => $calendar['id'], 
			'countEvents' => $count
			];

			$response = new JSONResponse($params);
			return $response;

		} else {
			$params = ['status' => 'error', 'message' => (string)$this -> l10n -> t('Import failed')];
			$response = new JSONResponse($params);
			return $response;
		}

	}

	/**
	 *  Creates a new calendar
	 * @param string $userid
	 * @param string $name
	 * @param string $components Default: "VEVENT,VTODO,VJOURNAL"
	 * @param string $timezone Default: null
	 * @param integer $order Default: 1
	 * @param string $color
	 * @return insertid || null
	 */
	public function add($userid, $name, $components = 'VEVENT,VTODO,VJOURNAL', $timezone = null, $order = 0, $color = "#C2F9FC", $issubscribe = 0, $externuri = '', $lastmodified = 0) {

		$all = $this -> allCalendars();
		$uris = array();
		foreach ($all as $i) {
			$uris[] = $i['uri'];
		}
		if ($lastmodified === 0) {
			$lastmodified = time();
		}

		$uri = $this -> createURI($name, $uris);

		$insertid = $this -> calendarDB -> add($name, $uri, $order, $color, $timezone, $components, $issubscribe, $externuri, $lastmodified);

		if ($insertid !== null) {
			\OCP\Util::emitHook('\OCA\CalendarPlus', 'addCalendar', $insertid);

			$link = \OC::$server -> getURLGenerator() -> linkToRoute($this -> appName . '.page.index');

			$params = array('mode' => 'created', 'link' => $link, 'trans_type' => '', 'summary' => $name, 'cal_user' => $userid, 'cal_displayname' => $name, );

			ActivityData::logEventActivity($params, false, true);

			return $insertid;
		} else {
			return null;
		}
	}

	/**
	 * @NoAdminRequired
	 */
	public function getEditFormCalendar() {
		$calId = (int)$this -> params('calendarid');

		$calendar = CalendarApp::getCalendar($calId, true, true);

		$params = ['new' => false, 'calendar' => $calendar, 'calendarcolor_options' => CalendarCalendar::getCalendarColorOptions(), ];

		$response = new TemplateResponse($this -> appName, 'part.editcalendar', $params, '');

		return $response;

	}

	/**
	 * @NoAdminRequired
	 */
	public function editCalendar() {

		$calendarid = (int)$this -> params('id');
		$pName = (string)$this -> params('name');
		$pActive = (int)$this -> params('active');
		$pColor = (string)$this -> params('color');

		if (trim($pName) === '') {

			$params = ['status' => 'error', 'message' => 'empty'];

			$response = new JSONResponse($params);
			return $response;

		}

		$calendars = CalendarCalendar::allCalendars($this -> userId);
		foreach ($calendars as $cal) {
			if ($cal['userid'] !== $this -> userId) {
				continue;
			}

			if ($cal['displayname'] === $pName && (int)$cal['id'] !== $calendarid) {
				$params = ['status' => 'error', 'message' => 'namenotavailable'];

				$response = new JSONResponse($params);
				return $response;
			}
		}

		try {
			CalendarCalendar::editCalendar($calendarid, strip_tags($pName), null, null, null, $pColor, null);
			CalendarCalendar::setCalendarActive($calendarid, $pActive);
		} catch(Exception $e) {
			$params = ['status' => 'error', 'message' => $e -> getMessage()];

			$response = new JSONResponse($params);
			return $response;
		}

		$calendar = CalendarCalendar::find($calendarid);
		$isShareApiActive = \OC::$server -> getAppConfig() -> getValue('core', 'shareapi_enabled', 'yes');

		$shared = false;
		if ($calendar['userid'] !== $this -> userId) {
			$sharedCalendar = $this -> shareConnector -> getItemSharedWithBySourceCalendar($calendarid);
			if ($sharedCalendar && ($sharedCalendar['permissions'] & $this -> shareConnector -> getUpdateAccess())) {
				$shared = true;
			}
		}

		$paramsList = ['calendar' => $calendar, 'shared' => $shared, 'appname' => $this -> appName, 'isShareApi' => $isShareApiActive, ];
		$calendarRow = new TemplateResponse($this -> appName, 'part.choosecalendar.rowfields', $paramsList, '');

		$params = [
		'status' => 'success', 
		'eventSource' => CalendarCalendar::getEventSourceInfo($calendar), 
		'calid' => $calendarid, 'countEvents' => false, 
		'page' => $calendarRow -> render(), 
		];

		$response = new JSONResponse($params);
		return $response;

	}

	/**
	 * @NoAdminRequired
	 */
	public function deleteCalendar() {

		$calId = (int)$this -> params('calendarid');
		$del = CalendarCalendar::deleteCalendar($calId);
		if ($del === true) {
			$calendars = CalendarCalendar::allCalendars($this->userId, false, false, false);
			$bNewId = null;
			//\OCP\Util::writeLog($this->appName, 'DEL COUNT-> '.count($calendars).$calendars[0]['id'], \OCP\Util::DEBUG);
			if((\OCP\USER::isLoggedIn() && count($calendars) === 0) || (count($calendars) === 1 && $calendars[0]['id'] === 'birthday_'.$this->userId)) {
				$bNewId = CalendarCalendar::addDefaultCalendars($this->userId);
			}	
				
			$params = ['status' => 'success','newid' => $bNewId ];

		} else {
			$params = ['status' => 'error', ];
		}

		$response = new JSONResponse($params);
		return $response;
	}

	/**
	 * @NoAdminRequired
	 */
	public function setMyActiveCalendar() {

		$calendarid = (int)$this -> params('calendarid');
		$this -> configInfo -> setUserValue($this -> userId, $this -> appName, 'choosencalendar', $calendarid);

		$params = ['status' => 'success', 'choosencalendar' => $calendarid];

		$response = new JSONResponse($params);
		return $response;
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param string $calendarid
	 * @param integer $active
	 */
	public function setActiveCalendar($calendarid, $active) {

		//$calendarid = $this -> params('calendarid');
		//$pActive = intval($this -> params('active'));

		$calendar = false;
		if ($calendarid !== 'birthday_' . $this -> userId) {
			$calendar = CalendarApp::getCalendar((int)$calendarid, true, true);
		}

		if (!$calendar && $calendarid !== 'birthday_' . $this -> userId) {
			$params = ['status' => 'error', 'message' => 'permission denied'];
			$response = new JSONResponse($params);
			return $response;
		}

		CalendarCalendar::setCalendarActive($calendarid, (int)$active);

		$isAktiv = $active;

		if ($this->configInfo->getUserValue($this->userId, $this->appName, 'calendar_' . $calendarid) !== '') {
			$isAktiv = $this->configInfo->getUserValue($this -> userId, $this->appName, 'calendar_' . $calendarid);
		}

		$eventSource = '';
		if ($calendarid !== 'birthday_' . $this->userId) {
			$eventSource = CalendarCalendar::getEventSourceInfo($calendar);
		} else {
				
			\OCP\Util::emitHook('OCA\CalendarPlus', 'getSources', array('all' => false, 'sources' => &$eventSource));
		}

		$params = ['status' => 'success', 'active' => $isAktiv, 'eventSource' => $eventSource, ];

		$response = new JSONResponse($params);
		return $response;

	}

	/**
	 * @NoAdminRequired
	 */
	public function refreshSubscribedCalendar() {
		$calendarid = (int)$this -> params('calendarid');

		$calendar = CalendarApp::getCalendar($calendarid, false, false);
		if (!$calendar) {
			$params = ['status' => 'error', 'message' => 'permission denied'];
			$response = new JSONResponse($params);
			return $response;
		}

		$getProtocol = explode('://', $calendar['externuri']);
		$protocol = $getProtocol[0];

		$opts = array($protocol => array('method' => 'POST', 'header' => "Content-Type: text/calendar\r\n", 'timeout' => 60));

		$last_modified = $this -> stream_last_modified(trim($calendar['externuri']));
		if (!is_null($last_modified)) {
			$context = stream_context_create($opts);
			$file = file_get_contents($calendar['externuri'], false, $context);
			$file = \Sabre\VObject\StringUtil::convertToUTF8($file);

			$import = new Import($file);
			$import -> setUserID($this -> userId);
			$import -> setTimeZone(CalendarApp::$tz);
			$import -> setOverwrite(true);
			$import -> setCalendarID($calendarid);
			try {
				$import -> import();
			} catch (Exception $e) {
				$params = ['status' => 'error', 'message' => $this -> l10n -> t('Import failed')];
				$response = new JSONResponse($params);
				return $response;

			}
		}
		$params = ['status' => 'success', 'refresh' => $calendarid, ];
		$response = new JSONResponse($params);
		return $response;

	}

	private function addEventsFromSubscribedCalendar($externUriFile, $calName, $calColor) {
		$externUriFile = trim($externUriFile);
		$newUrl = '';
		$bExistUri = false;
		$getProtocol = explode('://', $externUriFile);

		if (strtolower($getProtocol[0]) === 'webcal') {
			$newUrl = 'https://' . $getProtocol[1];
			$last_modified = $this -> stream_last_modified($newUrl);
			if (is_null($last_modified)) {
				$newUrl = 'http://' . $getProtocol[1];
				$last_modified = $this -> stream_last_modified($newUrl);
				if (is_null($last_modified)) {$bExistUri = false;
				} else {$bExistUri = true;
				}
			} else {
				$bExistUri = true;
			}
		} else {
			$protocol = $getProtocol[0];
			$newUrl = $externUriFile;
			$last_modified = $this -> stream_last_modified($newUrl);
			if (!is_null($last_modified)) {
				$bExistUri = true;
			}

		}

		$opts = array($protocol => array('method' => 'POST', 'header' => "Content-Type: text/calendar\r\n", 'timeout' => 60));
		$bError = false;
		if ($bExistUri === true) {
			$context = stream_context_create($opts);

			try {
				$file = file_get_contents($newUrl, false, $context);
			} catch (Exception $e) {
				$params = ['status' => 'error', 'message' => $this -> l10n -> t('Import failed')];
				$response = new JSONResponse($params);
				return $response;
			}
			//\OCP\Util::writeLog('calendar', 'FILE IMPORT-> '.$file, \OCP\Util::DEBUG);
			$file = \Sabre\VObject\StringUtil::convertToUTF8($file);
			$import = new Import($file);

			$import -> setUserID($this -> userId);
			$import -> setTimeZone(CalendarApp::$tz);
			$calendarid = CalendarCalendar::addCalendar($this -> userId, $calName, 'VEVENT,VTODO,VJOURNAL', null, 0, strip_tags($calColor), 1, $newUrl, $last_modified);
			CalendarCalendar::setCalendarActive($calendarid, 1);
			$import -> setCalendarID($calendarid);

			try {
				$import -> import();
			} catch (Exception $e) {
				$params = ['status' => 'error', 'message' => $this -> l10n -> t('Import failed')];
				$response = new JSONResponse($params);
				return $response;
			}
			$count = $import -> getCount();
		} else {
			$bError = true;

		}

		return ['isError' => $bError, 'countEvents' => $count, 'calendarid' => $calendarid];
	}

	/**
	 * @NoAdminRequired
	 */
	public function rebuildLeftNavigation() {
		$leftNavAktiv = $this -> configInfo -> getUserValue($this -> userId, $this -> appName, 'calendarnav');

		//make it as template
		//if ($leftNavAktiv === 'true') {
			$calendars = CalendarCalendar::allCalendars($this -> userId, false);
			$bShareApi = \OC::$server -> getAppConfig() -> getValue('core', 'shareapi_enabled', 'yes');
			$activeCal = (int)$this -> configInfo -> getUserValue($this -> userId, $this -> appName, 'choosencalendar');
			$bActiveCalFound = false;
			$aCalendars = array();
			foreach ($calendars as $calInfo) {
				if($activeCal === $calInfo['id']){
					$bActiveCalFound = true;
				}
				$calInfo['bShare'] = false;
				if ($calInfo['permissions'] & $this -> shareConnector -> getShareAccess() && $bShareApi === 'yes') {
					$calInfo['bShare'] = true;
				}
				$calInfo['shareInfo'] = '';
				if ($calInfo['bShare'] === false) {
					$calInfo['shareInfoLink'] = false;
					if ($this -> shareConnector -> getItemSharedWithByLinkCalendar($calInfo['id'], $calInfo['userid'])) {
						$calInfo['shareInfoLink'] = true;
					}

					$calInfo['shareInfo'] = CalendarCalendar::permissionReader($calInfo['permissions']);

				}

				$calInfo['download'] = \OC::$server -> getURLGenerator() -> linkToRoute($this -> appName . '.export.exportEvents') . '?calid=' . $calInfo['id'];

				$calInfo['isActive'] = (int)$calInfo['active'];
				$calInfo['bRefresh'] = true;
				$calInfo['bAction'] = true;

				if ($calInfo['userid'] !== $this -> userId) {
					$calInfo['isActive'] = (int)$calInfo['active'];
					if ($this -> configInfo -> getUserValue($this -> userId, $this -> appName, 'calendar_' . $calInfo['id']) !== '') {
						$calInfo['isActive'] = (int)$this -> configInfo -> getUserValue($this -> userId, $this -> appName, 'calendar_' . $calInfo['id']);
					}
					$calInfo['bRefresh'] = false;
					$calInfo['bAction'] = false;
				}

				if ((bool)$calInfo['issubscribe'] === false) {
					$aCalendars['cal'][] = $calInfo;

				} else {
					$calInfo['birthday'] = false;
					if ($calInfo['id'] === 'birthday_' . $this -> userId) {
						$calInfo['birthday'] = true;
					}

					$aCalendars['abo'][] = $calInfo;
				}
			}

			if ($this -> configInfo -> getUserValue($this -> userId, $this -> appName, 'userconfig')) {
				$userConfig = json_decode($this -> configInfo -> getUserValue($this -> userId, $this -> appName, 'userconfig'));
			} else {
				//Guest Config Public Page
				$userConfig = '{"agendaDay":"true","agendaThreeDays":"false","agendaWorkWeek":"false","agendaWeek":"true","month":"true","year":"false","list":"false"}';
				$userConfig = json_decode($userConfig);
			}
			
			if($bActiveCalFound === false){
				$activeCal = $aCalendars['cal'][0]['id'];
				$this -> configInfo -> setUserValue($this -> userId, $this -> appName, 'choosencalendar',$activeCal);
			}
			$params = [
			'calendars' => $aCalendars, 
			'activeCal' => $activeCal, 
			'shareType' => $this -> shareConnector -> getConstShareCalendar(), 
			'shareTypePrefix' => $this -> shareConnector -> getConstSharePrefixCalendar(), 
			'timezone' => $this -> configInfo -> getUserValue($this -> userId, $this -> appName, 'timezone', ''), 
			'timezones' => \DateTimeZone::listIdentifiers(), 
			'timeformat' => $this -> configInfo -> getUserValue($this -> userId, $this -> appName, 'timeformat', '24'),
			'dateformat' => $this -> configInfo -> getUserValue($this -> userId, $this -> appName, 'dateformat', 'd-m-Y'), 
			'timezonedetection' => $this -> configInfo -> getUserValue($this -> userId, $this -> appName, 'timezonedetection'), 
			'firstday' => $this -> configInfo -> getUserValue($this -> userId, $this -> appName, 'firstday', 'mo'), 
			'userConfig' => $userConfig, 'appname' => $this -> appName];

			$response = new TemplateResponse($this -> appName, 'navigationleft', $params, '');

			return $response;

		
	}

	/**
	 * @NoAdminRequired
	 *
	 */
	public function changeViewCalendar() {
		$view = (string)$this -> params('v');

		switch($view) {
			case 'agendaDay' :
			case 'agendaWeek' :
			case 'month' :
			case 'agendaWorkWeek' :
			case 'agendaThreeDays' :
			case 'fourWeeks' :
			case 'year' :
			case 'list' :
				$this -> configInfo -> setUserValue($this -> userId, $this -> appName, 'currentview', $view);
				break;
			default :
				$this -> configInfo -> setUserValue($this -> userId, $this -> appName, 'currentview', 'month');
				break;
		}

		$response = new JSONResponse();

		return $response;

	}

	/**
	 * @NoAdminRequired
	 */
	public function touchCalendar() {

		$id = (int)$this -> params('eventid');
		$data = CalendarApp::getEventObject($id, false, false);
		$vcalendar = VObject::parse($data['calendardata']);
		$vevent = $vcalendar -> VEVENT;
		$vevent -> setDateTime('LAST-MODIFIED', 'now');
		$vevent -> setDateTime('DTSTAMP', 'now');
		Object::edit($id, $vcalendar -> serialize());

		$params = ['status' => 'success', ];

		$response = new JSONResponse($params);

		return $response;

	}

	/**
	 * @brief Creates a URI for Calendar
	 * @param string $name name of the calendar
	 * @param array  $existing existing calendar URIs
	 * @return string uri
	 */
	public function createURI($name, $existing) {
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

	private function stream_last_modified($url) {

		if (!($fp = @fopen($url, 'r'))) {
			return NULL;
		}
		$meta = stream_get_meta_data($fp);
		for ($j = 0; isset($meta['wrapper_data'][$j]); $j++) {

			if (strstr(strtolower($meta['wrapper_data'][$j]), 'last-modified')) {
				$modtime = substr($meta['wrapper_data'][$j], 15);
				break;
			}
		}
		fclose($fp);

		return isset($modtime) ? strtotime($modtime) : time();
	}

}
