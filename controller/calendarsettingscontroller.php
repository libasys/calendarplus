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


use \OCA\CalendarPlus\App as CalendarApp;
use \OCA\CalendarPlus\Calendar as CalendarCalendar;
use \OCA\CalendarPlus\VObject;
use \OCA\CalendarPlus\Object;


use \OCP\AppFramework\Controller;
use \OCP\AppFramework\Http\JSONResponse;
use \OCP\AppFramework\Http\TemplateResponse;
use \OCP\IRequest;
use \OCP\Share;
use \OCP\IConfig;
use \OCP\ISession;

class CalendarSettingsController extends Controller {

	private $userId;
	private $l10n;
	private $configInfo;
	private $repeatController;
	/**
	 * @type ISession
	 * */
	private $session;
	
	public function __construct($appName, IRequest $request, $userId, $l10n, IConfig $settings ,ISession $session, $repeatController) {
		parent::__construct($appName, $request);
		$this -> userId = $userId;
		$this->l10n = $l10n;
		$this->configInfo = $settings;
		$this->session = $session;
		$this->repeatController = $repeatController;
	}
	
	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function index() {
			
		$calendars = CalendarCalendar::allCalendars($this -> userId);	
		$allcached = true;
		foreach($calendars as $calendar) {
			if(!$this->repeatController->isCalendarCached($calendar['id'])){	
				$allcached = false;
			}
		}
		
		
		if ($this -> configInfo -> getUserValue($this -> userId, $this->appName, 'userconfig')) {	
			$userConfig = json_decode($this -> configInfo -> getUserValue($this -> userId, $this->appName, 'userconfig'));
		}else{
			//Guest Config Public Page	
			$userConfig='{"agendaDay":"true","agendaThreeDays":"false","agendaWorkWeek":"false","agendaWeek":"true","month":"true","year":"false","list":"false"}';
			$userConfig = json_decode($userConfig);
		}
		
		$params =[
			'appname' => $this->appName,
			'timezone' => $this -> configInfo -> getUserValue($this -> userId,$this->appName,'timezone',''),
			'timezones' => \DateTimeZone::listIdentifiers(),
			'calendars' => $calendars,
			'mySharedCalendars' => Object::getCalendarSharees(),
			'isShareApiActive' => \OC::$server->getAppConfig()->getValue('core', 'shareapi_enabled', 'yes'),
			'timeformat' => $this -> configInfo -> getUserValue($this -> userId,$this->appName,'timeformat','24'),
			'dateformat' => $this -> configInfo -> getUserValue($this -> userId,$this->appName,'dateformat','d-m-Y'),
			'timezonedetection' => $this -> configInfo -> getUserValue($this -> userId,$this->appName,'timezonedetection'),
			'firstday' => $this -> configInfo -> getUserValue($this -> userId,$this->appName,'firstday', 'mo'),
			'allCalendarCached' => $allcached,
			'userConfig' => $userConfig
		];	
			
		$response = new TemplateResponse($this->appName, 'settings', $params, '');
		
		return $response;
	}

	/**
     * @NoAdminRequired
     */
    public function getUserSettingsCalendar() {
   
   		
		$firstDayConfig = $this ->configInfo -> getUserValue($this -> userId,$this->appName, 'firstday', 'mo');
		$firstDay = $this -> prepareFirstDay($firstDayConfig);
   
		$agendaTime ='hh:mm tt { - hh:mm tt}';
		$defaultTime ='hh:mm tt';
		$timeFormat = $this ->configInfo -> getUserValue($this -> userId,$this->appName, 'timeformat', '24');
		if($timeFormat === '24'){
			$agendaTime ='HH:mm { - HH:mm}';
			$defaultTime ='HH:mm { - HH:mm}';
		}
		
		$dateFormat = $this ->configInfo -> getUserValue($this -> userId,$this->appName, 'dateformat', 'd-m-Y');
		
		
		$checkCat=CalendarApp::loadTags();
		$checkCatTagsList='';
		$checkCatCategory='';
		
		foreach($checkCat['categories'] as $category){
				$checkCatCategory[]=$category;
		}
		
		foreach($checkCat['tagslist'] as $tag){
				$checkCatTagsList[$tag['name']]=array('id'=>$tag['id'],'name'=>$tag['name'],'color'=>$tag['color'],'bgcolor'=>$tag['bgcolor']);
		}
		$eventSources = [];
		$calendars = CalendarCalendar::allCalendars($this -> userId);
		$calendarInfo=[];
		$myCalendars=[];
		$myRefreshChecker=[];
		
		foreach($calendars as $calendar) {
				
			$isAktiv= (int) $calendar['active'];
			
			if($this ->configInfo -> getUserValue($this -> userId, $this->appName, 'calendar_'.$calendar['id']) !== ''){
			    $isAktiv = (int) $this ->configInfo -> getUserValue($this -> userId, $this->appName, 'calendar_'.$calendar['id']);
		    }	
			if(!array_key_exists('active', $calendar)){
				$isAktiv = 1;
			}
			if($this->userId !== $calendar['userid']){
				$calendar['uri'] = $calendar['uri'] . '_shared_by_' . $calendar['userid'];
			}
			$addClass = '';
			if(isset($calendar['className'])){
				$addClass = $calendar['className'];
			}
			
			$bgColor = '';
			$textColor = '';
			if(isset($calendar['calendarcolor'])){
				$bgColor = $calendar['calendarcolor'];
				/*DEFAULT*/
				$textColorDefault = CalendarCalendar::generateTextColor($bgColor);
				$textColor = $bgColor;
			}
			
			if(isset($calendar['textColor'])){
				/*DEFAULT*/	
				
				$textColorDefault = $calendar['textColor'];
				$textColor = $bgColor;
			}
			
			
			$calendarInfo[$calendar['id']]=[
					'bgcolor' => $bgColor,
					'color' => $textColorDefault,
					'colorDefault' => $textColorDefault,
					'name' => $calendar['displayname'],
					'externuri' => $calendar['externuri'],
					'uri' => $calendar['uri'],
					'className' => $addClass
				];
				
			if((int)$isAktiv === 1) {
				$eventSources[] = CalendarCalendar::getEventSourceInfo($calendar);
				
				$myCalendars[$calendar['id']]=[
					'id'=> $calendar['id'],
					'name'=>$calendar['displayname'],
					'uri' => $calendar['uri'],
					'issubscribe' => (int) $calendar['issubscribe'],
					'permissions' => (int) $calendar['permissions'],
				];
				
				$myRefreshChecker[$calendar['id']]=$calendar['ctag'];
			}
		}
			
		
		$events_baseURL = \OC::$server->getURLGenerator()->linkToRoute($this->appName.'.event.getEvents');
		$eventSources[] = array(
				'url' => $events_baseURL.'?calendar_id=shared_events',
				'className' => 'shared-events',
				'editable' => 'false');
				
		if ($this -> configInfo -> getUserValue($this -> userId,$this->appName, 'userconfig')) {	
			$userConfig = json_decode($this -> configInfo -> getUserValue($this -> userId, $this->appName, 'userconfig'));
		}else{
			//Guest Config Public Page	
			$userConfig='{"agendaDay":"true","agendaThreeDays":"false","agendaWorkWeek":"false","agendaWeek":"true","month":"true","year":"false","list":"false"}';
			$userConfig = json_decode($userConfig);
		}
		
		$leftNavAktiv = $this->configInfo->getUserValue($this->userId, $this->appName, 'calendarnav');
		$rightNavAktiv = $this->configInfo->getUserValue($this->userId, $this->appName, 'tasknav');
		$taskAppActive = \OC::$server->getAppManager()->isEnabledForUser('tasksplus');
		
    	$params = [
			'status' => 'success',
			'defaultView' => $this ->configInfo -> getUserValue($this -> userId,$this->appName, 'currentview', 'month'),
			'agendatime' => $agendaTime,
			'defaulttime' => $defaultTime,
			'dateformat' => $dateFormat,
			'timeformat' => $timeFormat,
			'firstDay' => $firstDay,
			'firstDayString' => $firstDayConfig,
			'categories' => $checkCatCategory,
			'tags' => $checkCatTagsList,
			'eventSources' => $eventSources,
			'calendarcolors'=> $calendarInfo,
			'mycalendars'=> $myCalendars,
			'myRefreshChecker'=> $myRefreshChecker,
			'choosenCalendar' => $this -> configInfo -> getUserValue($this->userId, $this->appName, 'choosencalendar'),
			'userConfig' => $userConfig,
			'sharetypeevent' => CalendarApp::SHAREEVENT,
			'sharetypecalendar' => CalendarApp::SHARECALENDAR,
			'leftnavAktiv' => $leftNavAktiv,
			'rightnavAktiv' =>$rightNavAktiv,
			'taskAppActive' =>$taskAppActive,
		];
		
		$response = new JSONResponse($params);
		
		return $response;
    }
	
	/**
	 * @NoAdminRequired
	 * 
	 */
	public function saveUserViewSettings() {
		$checked = $this -> params('checked');
		$pName = $this -> params('name');	
		
		
		$userConfig = '';
		if(!$this -> configInfo  -> getUserValue($this -> userId,$this->appName, 'userconfig')){
			$userConfig='{"agendaDay":"true","agendaThreeDays":"false","agendaWorkWeek":"false","agendaWeek":"true","month":"true","year":"false","list":"false"}';
			$userConfig = json_decode($userConfig);
		}else{
			$userConfig = json_decode($this -> configInfo  -> getUserValue($this -> userId, $this->appName, 'userconfig'));
		}

		$userConfig ->$pName = $checked;
		
		$this -> configInfo -> setUserValue($this -> userId, $this->appName, 'userconfig',json_encode($userConfig));
		$data = [
			'status' => 'success',
			'data' => ['name' => $pName,'checked' => $checked],
			'msg' => 'Saving success!'
		];	
		$response = new JSONResponse();
		$response -> setData($data);
		return $response;
	}
	
	/**
     * @NoAdminRequired
     */
    public function getGuessTimeZoneUser() {
    	$pTimezone= (string)$this -> params('timezone');
		
		try {
			$tz = new \DateTimeZone($pTimezone);
		} catch(\Exception $ex) {
			$params = [
				'status' => 'error',
			];
		
			$response = new JSONResponse($params);
			return $response;
		}
		
			if($pTimezone === $this -> configInfo -> getUserValue($this->userId, $this->appName, 'timezone')) {
			$params = [
				'status' => 'success',
			];
		
			$response = new JSONResponse($params);
			return $response;
			}
			
			$this -> configInfo -> setUserValue($this->userId,$this->appName, 'timezone', $pTimezone);
			$params = [
				'status' => 'success',
				'message' => $this -> l10n -> t('New Timezone:'). ' ' . $pTimezone
			];
		
			$response = new JSONResponse($params);
			return $response;
		

    }
	
	


	/**
     * @NoAdminRequired
     */
    public function setTimeZone() {
    	
		$timezone = $this -> params('timezone');
		$this -> configInfo -> setUserValue($this -> userId,$this->appName,'timezone',$timezone);
		//\OC::$session->set('public_link_timezone', $timezone);
		$params = [
		'status' => 'success',
		'data' =>[
			'message' => (string)$this -> l10n -> t('Timezone changed')
		],
		];
		
		$response = new JSONResponse($params);
		
		return $response;
		
	}

	/**
     * @NoAdminRequired
     */
    public function setTimeFormat() {
    	
		$timeformat = (string) $this -> params('timeformat');
		$this -> configInfo -> setUserValue($this -> userId,$this->appName,'timeformat',$timeformat);
		
		$agendaTime ='hh:mm tt { - hh:mm tt}';
		$defaultTime ='hh:mm tt';
		$timeFormat = 'ampm';
		if($this ->configInfo ->getUserValue($this -> userId, $this->appName, 'timeformat', '24') === '24'){
			$agendaTime ='HH:mm { - HH:mm}';
			$defaultTime ='HH:mm';
			$timeFormat = '24';
		}
		
		$params = [
		'status' => 'success',
		'data' =>[
			'message' => (string)$this -> l10n -> t('Timeformat changed'),
			'agendaTime' => $agendaTime,
			'defaultTime' => $defaultTime,
			'timeformat' => $timeFormat,
		],
		];
		
		$response = new JSONResponse($params);
		
		return $response;
		
	}
	
	/**
     * @NoAdminRequired
     */
    public function setDateFormat() {
    	
		$dateformat = (string) $this -> params('dateformat');
		$this -> configInfo -> setUserValue($this -> userId,$this->appName,'dateformat',$dateformat);
		
		
		$params = [
		'status' => 'success',
		'data' =>[
			'message' => (string)$this -> l10n -> t('Dateformat changed'),
			'dateformat' => $dateformat,
		],
		];
		
		$response = new JSONResponse($params);
		
		return $response;
		
	}
	/**
     * @NoAdminRequired
     */
    public function setFirstDay() {
    	
		$firstday = $this -> params('firstday');
		$this -> configInfo -> setUserValue($this -> userId,$this->appName,'firstday', $firstday);
		$firstDay = $this -> prepareFirstDay($firstday);
		
		$params = [
		'status' => 'success',
		'firstday' => $firstDay,
		'data' =>[
			'message' => (string)$this -> l10n -> t('Firstday changed')
		],
		];
		
		$response = new JSONResponse($params);
		
		return $response;
		
	}
	
	private function prepareFirstDay($firstDayTmp){
			
		switch($firstDayTmp) {
			case 'su':
				return 0;
				break;
			case 'mo':
				return 1;
				break;
			case 'tu':
				return 2;
				break;
			case 'we':
				return 3;
				break;
			case 'th':
				return 4;
				break;
			case 'fr':
				return 5;
				break;			
			case 'sa':
				return 6;
				break;
			default:
				return 1;
			break;
		}

	}
	/**
     * @NoAdminRequired
     */
    public function setTaskNavActive() {
    		
    	$isHidden = 'false';
		$pChecked = $this -> params('checked');
		if($pChecked === 'true') {
			$this -> configInfo -> setUserValue($this -> userId, $this->appName, 'tasknav', 'true');
			$isHidden = 'false';
		}else{
			$this -> configInfo -> setUserValue($this -> userId, $this->appName, 'tasknav', 'false');
			$isHidden='true';
		}
	
		$params = [
		'status' => 'success',
		'isHidden' =>$isHidden
		];
		
		$response = new JSONResponse($params);
		
		return $response;
		
	}
	
	/**
     * @NoAdminRequired
     */
    public function setCalendarNavActive() {
    		
    	$isHidden='false';
		$pChecked = $this -> params('checked');
		if($pChecked==='true') {
			$this -> configInfo -> setUserValue($this -> userId, $this->appName, 'calendarnav', 'true');
			$isHidden='false';
		}else{
			$this -> configInfo -> setUserValue($this -> userId, $this->appName, 'calendarnav', 'false');
			$isHidden='true';
		}
	
		$params = [
		'status' => 'success',
		'isHidden' =>$isHidden
		];
		
		$response = new JSONResponse($params);
		
		return $response;
		
	}
	
	 /**
     * @NoAdminRequired
     */
    public function timeZoneDectection() {
    	
		$timezonedetection = (string) $this -> params('timezonedetection');
		
		if($timezonedetection === 'on'){
			$this -> configInfo -> setUserValue($this -> userId,$this->appName,'timezonedetection','true');
		}else{
			$this -> configInfo -> setUserValue($this -> userId,$this->appName,'timezonedetection','false');
		}
		
	
		$params = [
		'status' => 'success',
		'data' =>[
			'message' => (string)$this -> l10n -> t('Success')
		],
		];
		
		$response = new JSONResponse($params);
		
		return $response;
		
	}
	
	/**
     * @NoAdminRequired
     */
    public function reScanCal() {
    	
		$calendars = CalendarCalendar::allCalendars($this -> userId);
		foreach($calendars as $calendar) {
			$this->repeatController->cleanCalendar($calendar['id']);
			$this->repeatController->generateCalendar($calendar['id']);	
		}
	
		$params = [
		'status' => 'success',
		'data' =>[
			'message' => (string)$this -> l10n -> t('Success')
		],
		];
		
		$response = new JSONResponse($params);
		
		return $response;
		
	}
	
}