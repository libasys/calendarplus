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
use \OCP\AppFramework\Http\DataDownloadResponse;
use \OCP\IRequest;
use \OCP\IConfig;
use \OC\Files\View;

class CalendarController extends Controller {

	private $userId;
	private $l10n;
	private $configInfo;
	private $calendarDB;
	private $shareConnector;
	private $contactsManager;

	public function __construct($appName, IRequest $request, $userId, $l10n, IConfig $settings, $calendarDB, $contactsManager) {
		parent::__construct($appName, $request);
		$this -> userId = $userId;
		$this -> l10n = $l10n;
		$this -> configInfo = $settings;
		$this -> calendarDB = $calendarDB;
		$this -> shareConnector = new ShareConnector();
		$this->contactsManager = $contactsManager;
	}

	/**
	 * @brief Gets the data of one calendar
	 * @param string $uri
	 * @return associative array
	 */
	public function checkBirthdayCalendarByUri($uri) {
		$calendarInfo = $this->calendarDB->findByUri($uri);
		
		if($calendarInfo !== null){
			return $calendarInfo['id'];
		}else{
			$newCalId = $this->add($this->userId,$uri,'VEVENT,VTODO,VJOURNAL',null,0,'#C2F9FC',1,'',0);
			CalendarCalendar::editCalendar($newCalId, (string) $this->l10n->t('Birthdays'));
			
			return $newCalId;
		}
		
	}
	/**
		 * Extracts all matching contacts with email address and name
		 *
		 * @param string $term
		 * @return array
		 */
		public function addBirthdays($userid,$calId) {
			if (!$this->contactsManager->isEnabled()) {
				return array();
			}
			
			$addrBooks = $this->contactsManager->getAddressBooks();
			
			$aContacts = array();
			foreach($addrBooks as $key => $addrBook){
				if(is_integer($key))	{
					$bContacts= \OCA\ContactsPlus\VCard::all($key);
					
					$aContacts = array_merge($aContacts,$bContacts);
				}
			}
			$sDateFormat = $this ->configInfo -> getUserValue($this -> userId,$this->appName, 'dateformat', 'd-m-Y');
			if($sDateFormat === 'd-m-Y'){
				$sDateFormat ='d.m.Y';
			}
			
			$aResult = array();
			foreach($aContacts as $contact){
				$vcard =  \Sabre\VObject\Reader::read($contact['carddata']);
				if (isset($vcard->BDAY)) {
					
					$Birthday = new \DateTime((string)$vcard->BDAY);	
					$checkForm = $Birthday->format('d-m-Y');
					$temp=explode('-',$checkForm);	
					$getAge = $this->getAge($temp[2],$temp[1],$temp[0]);
					$title = $contact['fullname'];
					
					$birthdayOutput = $Birthday->format($sDateFormat);
					
					$aktYear=$Birthday->format('d-m');
					$aktYear=$aktYear.date('-Y');
					$start = new \DateTime($aktYear);
					$end = new \DateTime($aktYear.' +1 day');
					
					$vcalendar = new VObject('VCALENDAR');
					$vcalendar->add('PRODID', 'ownCloud Calendar');
					$vcalendar->add('VERSION', '2.0');
			
					$vevent = new VObject('VEVENT');
					$vcalendar->add($vevent);
					$vevent->setDateTime('CREATED', 'now');
					$vevent->add('DTSTART');
					$vevent->DTSTART->setDateTime(
						$start
					);
					$vevent->add('DTEND');
					$vevent->DTEND->setDateTime(
								$end
							);
					$vevent->DTSTART['VALUE'] = 'date';
					$vevent->DTEND['VALUE'] = 'date';		
					$vevent->{'RRULE'} = 'FREQ=YEARLY;INTERVAL=1';
					$vevent->{'TRANSP'} = 'TRANSPARENT';
	                $vevent->{'SUMMARY'} = (string)$title. ' ('.$getAge.')';
					$description = (string)$this->l10n->t("Happy Birthday! Born on: ").$birthdayOutput;
					$vevent->setString('DESCRIPTION', $description);
					
	         		$vevent->{'UID'} = substr(md5(rand().time()), 0, 10);
					
					$insertid = Object::add($calId, $vcalendar->serialize());
					if($this->isDuplicate($insertid)) {
						Object::delete($insertid);
					}
				}
			}
			
		}
		
		
		private function isDuplicate($insertid) {
			
			$newobject = Object::find($insertid);
			$endDate = $newobject['enddate'];
			if(!$newobject['enddate']) {
				$endDate = null;
			}
			
			
			$stmt = \OCP\DB::prepare('SELECT * FROM `'.CalendarApp::CldObjectTable.'` `CO`
									 LEFT JOIN `'.CalendarApp::CldCalendarTable.'` ON `CO`.`calendarid`=`'.CalendarApp::CldCalendarTable.'`.`id`
									 WHERE `CO`.`objecttype`=? AND `CO`.`startdate`=? AND `CO`.`enddate`=? AND `CO`.`repeating`=? AND `CO`.`summary`=?  AND `'.CalendarApp::CldCalendarTable.'`.`userid` = ? AND `CO`.`calendarid`=?');
			$result = $stmt->execute(array($newobject['objecttype'],$newobject['startdate'],$endDate,$newobject['repeating'],$newobject['summary'], $this->userId, $newobject['calendarid']));
			$rowCount = $result->rowCount();
			
			
			if($rowCount > 1) {
				return true;
			}
			return false;
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
	 * 
	 * @return insertid
	 */

	public function newCalendar($id, $name, $active, $color) {

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

		$calendarid = $this -> add($this -> userId, $name, 'VEVENT,VTODO,VJOURNAL', null, 0, $color);
		CalendarCalendar::setCalendarActive($calendarid, 1);
		$calendar = $this -> find($calendarid);
		//FIXME
		$isShareApiActive = \OC::$server -> getAppConfig() -> getValue('core', 'shareapi_enabled', 'yes');

		$params = [
			'status' => 'success', 
			'eventSource' => CalendarCalendar::getEventSourceInfo($calendar), 
			'calid' => $calendar['id']
		];

		$response = new JSONResponse($params);
		return $response;

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

		$params = [
			'status' => 'success', 
			'eventSource' => CalendarCalendar::getEventSourceInfo($calendar), 
			'calid' => $calendarid 
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
			if((\OCP\USER::isLoggedIn() && count($calendars) === 0)) {
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

		
		$calendar = CalendarApp::getCalendar((int)$calendarid, true, true);
		CalendarCalendar::setCalendarActive($calendarid, (int)$active);

		$isAktiv = $active;

		if ($this->configInfo->getUserValue($this->userId, $this->appName, 'calendar_' . $calendarid) !== '') {
			$isAktiv = $this->configInfo->getUserValue($this -> userId, $this->appName, 'calendar_' . $calendarid);
		}
	
		$eventSource = CalendarCalendar::getEventSourceInfo($calendar);

		$params = ['status' => 'success', 'active' => $isAktiv, 'eventSource' => $eventSource, ];

		$response = new JSONResponse($params);
		return $response;

	}

	/**
	 * @NoAdminRequired
	 *@param integer $calendarid
	 */
	public function refreshSubscribedCalendar($calendarid) {
	
		$calendar = CalendarApp::getCalendar($calendarid, false, false);
		if (!$calendar) {
			$params = ['status' => 'error', 'message' => 'permission denied'];
			$response = new JSONResponse($params);
			return $response;
		}
		if($calendar['uri'] !== 'bdaycpltocal_'.$calendar['userid']){
			
			$getProtocol = explode('://', $calendar['externuri']);
			$protocol = $getProtocol[0];
	
			$opts = array($protocol => array('method' => 'POST', 'header' => "Content-Type: text/calendar\r\n", 'timeout' => 60));
	
			$aMeta = $this -> stream_last_modified(trim($calendar['externuri']));
			
			if ($aMeta['fileaccess'] === true) {
				$context = stream_context_create($opts);
				$file = file_get_contents($calendar['externuri'], false, $context);
				$file = \Sabre\VObject\StringUtil::convertToUTF8($file);
	
				$import = new Import($file);
				$import -> setUserID($this -> userId);
				$import -> setTimeZone(CalendarApp::$tz);
				//$import -> setOverwrite(false);
				$import->setCheckModifiedDate(true);
				$import->setImportFromUri(true);
				$import -> setCalendarID($calendarid);
				try {
					$import -> import();
					
					$importCount = $import->getCountImport();
					
					$params = ['status' => 'success', 'refresh' => $calendarid,'count' => $importCount ];
					$response = new JSONResponse($params);
					return $response;
					
				} catch (Exception $e) {
					$params = ['status' => 'error', 'message' => (string)$this -> l10n -> t('Import failed')];
					$response = new JSONResponse($params);
					return $response;
	
				}
			}else{
				$params = ['status' => 'error', 'message' =>(string)$this->l10n-> t('Import failed') ];
				$response = new JSONResponse($params);
				return $response;
			}
		}else{
			$this->addBirthdays($this->userId,(int)$calendarid);
			$params = ['status' => 'success', 'refresh' => $calendarid, ];
			$response = new JSONResponse($params);
			return $response;
		}
		
		

	}


	
	/**
	 * @NoAdminRequired
	 * @param string $importurl
	 */
	public function checkImportUrl($importurl){
		$externUriFile = trim(urldecode($importurl));
		
		$newUrl = '';
		$bExistUri = false;
		$getProtocol = explode('://', $externUriFile);
		if (strtolower($getProtocol[0]) === 'webcal') {
			$newUrl = 'https://' . $getProtocol[1];
			$aMetaHttps = $this -> stream_last_modified($newUrl);
			if ($aMetaHttps['fileaccess'] !== true) {
				$newUrl = 'http://' . $getProtocol[1];
				$aMetaHttp = $this -> stream_last_modified($newUrl);
				
				if ($aMetaHttp['fileaccess'] !== true) {
					$bExistUri = false;
				} else {
					$bExistUri = true;
				}
			} else {
				$bExistUri = true;
			}
		} else {
			$protocol = $getProtocol[0];
			if (preg_match('%index.php/apps/calendarplus/s/(/.*)?%', $externUriFile)) {
				$temp= explode('/s/',$externUriFile);
				$externUriFile =$temp[0].'/exporteventscalendar?t='.$temp[1];
			}
		
			$newUrl = $externUriFile;
			$aMeta = $this -> stream_last_modified($newUrl);
			
			if ($aMeta['fileaccess'] === true) {
				$bExistUri = true;
			}

		}
		$opts = array($protocol => array('method' => 'GET', 'header' => "Content-Type: text/calendar\r\n", 'timeout' => 60));
		$bError = false;
		
		
		if ($bExistUri === true) {
			$context = stream_context_create($opts);
			
				
			try {
				$file = file_get_contents($newUrl, false, $context);
				//\OCP\Util::writeLog('calendarplus','FILE: '.$newUrl, \OCP\Util::DEBUG);
				
				$import = new \OCA\CalendarPlus\Import($file);
				$import->setUserID($this->userId);
				$guessedcalendarname = \OCP\Util::sanitizeHTML($import->guessCalendarName());
				$testColor = $import->guessCalendarColor();
				$guessedcalendarcolor = ($testColor !== null?$testColor:'006DCC');
				$params = [
				'status' => 'success', 
				'file' => $file,
				'externUriFile' => $externUriFile,
				'guessedcalendarname' => $guessedcalendarname,
				'guessedcalendarcolor' => $guessedcalendarcolor
				];
				
				$response = new JSONResponse($params);
				return $response;
				
			} catch (Exception $e) {
				$params = ['status' => 'error',
				 'message' => (string)$this -> l10n -> t('Subscribed url is not valid')];
				$response = new JSONResponse($params);
				return $response;
			}
		}else{
			$params = ['status' => 'error',
			 'message' => (string)$this -> l10n -> t('Subscribed url is not valid')];
			$response = new JSONResponse($params);
			return $response;
		}
		
		
	}
	
	
	/**
	 * @NoAdminRequired
	 */
	public function rebuildLeftNavigation() {
		$leftNavAktiv = $this->configInfo->getUserValue($this -> userId, $this -> appName, 'calendarnav');

		//make it as template
		//if ($leftNavAktiv === 'true') {
			$calendars = CalendarCalendar::allCalendars($this -> userId, false);
			$bShareApi = \OC::$server -> getAppConfig() -> getValue('core', 'shareapi_enabled', 'yes');
			$activeCal = (int)$this ->configInfo->getUserValue($this -> userId, $this -> appName, 'choosencalendar');
			$bActiveCalFound = false;
			$aCalendars = array();
			foreach ($calendars as $calInfo) {
				if($activeCal === (int)$calInfo['id']){
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
					if ($calInfo['id'] === 'bdaycpltocal_' . $this -> userId) {
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
     * @NoAdminRequired
	   * 
	   * @param $grpid tag id
	   * @param $newname new name for tag
     */
	public function updateTag($grpid,$newname){
			
		if(\OC::$server->getTagManager()-> load('event')->rename($grpid,$newname)){
			$params = [
			'status' => 'success',
			];
		}else{
			$params = [
				'status' => 'error',
			];
		}
		
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
		
		foreach($existing as $existCalUri){
			if($existCalUri === $name){
				$name = $name.'1';
			}
		}
		
		return $name;
	}
	
	private function getAge ($y, $m, $d) {
    	return date('Y') - $y - (date('n') < (ltrim($m,'0') + (date('j') < ltrim($d,'0'))));
   }
	
	private function stream_last_modified($url) {
		
		if (!($fp = @fopen($url, 'r'))) {
			return NULL;
		}
		$meta = stream_get_meta_data($fp);
		$bAccess = false;
		$modtime = '';
		for ($j = 0; isset($meta['wrapper_data'][$j]); $j++) {
			if (strstr(strtolower($meta['wrapper_data'][$j]), 'content-type')) {
				$checkContentType = substr($meta['wrapper_data'][$j], 13);	
				list($contentType,$charset) = explode(';',$checkContentType);
				if(trim(strtolower($contentType)) === 'text/calendar'){
					$bAccess = true;
					
				}	
			}

			if (strstr(strtolower($meta['wrapper_data'][$j]), 'last-modified')) {
				$modtime = substr($meta['wrapper_data'][$j], 15);
				
			}
		}
		fclose($fp);
			
			$returnArray=[
				'lastmodified' => isset($modtime) ? strtotime($modtime) : time(),
				'fileaccess' => $bAccess,
			];
			
			return $returnArray;
		
	}

}
