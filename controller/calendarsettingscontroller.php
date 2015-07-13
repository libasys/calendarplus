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
use \OCA\CalendarPlus\Repeat;

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
	/**
	 * @type ISession
	 * */
	private $session;
	
	public function __construct($appName, IRequest $request, $userId, $l10n, IConfig $settings ,ISession $session) {
		parent::__construct($appName, $request);
		$this -> userId = $userId;
		$this->l10n = $l10n;
		$this->configInfo = $settings;
		$this->session = $session;
	}
	
	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function index() {
			
		$calendars = CalendarCalendar::allCalendars($this -> userId);	
		$allcached = true;
		foreach($calendars as $calendar) {
			if(!Repeat::is_calendar_cached($calendar['id'])) {
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
   
   		
		$firstDayConfig =$this ->configInfo -> getUserValue($this -> userId,$this->appName, 'firstday', 'mo');
		$firstDay = $this -> prepareFirstDay($firstDayConfig);
   
		$agendaTime ='hh:mm tt { - hh:mm tt}';
		$defaultTime ='hh:mm tt';
		if($this ->configInfo ->getUserValue($this -> userId, $this->appName, 'timeformat', '24') === '24'){
			$agendaTime ='HH:mm { - HH:mm}';
			$defaultTime ='HH:mm';
		}

		$checkCat=CalendarApp::loadTags();
		$checkCatTagsList='';
		$checkCatCategory='';
		
		foreach($checkCat['categories'] as $category){
				$checkCatCategory[]=$category;
		}
		
		foreach($checkCat['tagslist'] as $tag){
				$checkCatTagsList[$tag['name']]=array('name'=>$tag['name'],'color'=>$tag['color'],'bgcolor'=>$tag['bgcolor']);
		}
		$eventSources = [];
		$calendars = CalendarCalendar::allCalendars($this -> userId);
		$calendarInfo=[];
		$myCalendars=[];
		$myRefreshChecker=[];
		
		foreach($calendars as $calendar) {
			$isAktiv= $calendar['active'];
			
			if($this ->configInfo -> getUserValue($this -> userId, $this->appName, 'calendar_'.$calendar['id']) !== ''){
			    $isAktiv = (int)$this ->configInfo -> getUserValue($this -> userId, $this->appName, 'calendar_'.$calendar['id']);
		    }	
			if(!array_key_exists('active', $calendar)){
				$isAktiv = 1;
			}
			if((int)$isAktiv === 1) {
				$eventSources[] = CalendarCalendar::getEventSourceInfo($calendar);
				$calendarInfo[$calendar['id']]=[
					'bgcolor'=>$calendar['calendarcolor'],
					'color'=>CalendarCalendar::generateTextColor($calendar['calendarcolor'])
				];
				
				$myCalendars[$calendar['id']]=[
					'id'=>$calendar['id'],
					'name'=>$calendar['displayname'],
					'issubscribe' => (int) $calendar['issubscribe'],
					'permissions' => (int) $calendar['permissions'],
				];
				
				$myRefreshChecker[$calendar['id']]=$calendar['ctag'];
			}
		}
			
		
		$events_baseURL = \OC::$server->getURLGenerator()->linkToRoute($this->appName.'.event.getEvents');
		$eventSources[] = array('url' => $events_baseURL.'?calendar_id=shared_events',
				'backgroundColor' => '#1D2D44',
				'borderColor' => '#888',
				'textColor' => 'white',
				'editable' => 'false');
				
		if ($this -> configInfo -> getUserValue($this -> userId,$this->appName, 'userconfig')) {	
			$userConfig = json_decode($this -> configInfo -> getUserValue($this -> userId, $this->appName, 'userconfig'));
		}else{
			//Guest Config Public Page	
			$userConfig='{"agendaDay":"true","agendaThreeDays":"false","agendaWorkWeek":"false","agendaWeek":"true","month":"true","year":"false","list":"false"}';
			$userConfig = json_decode($userConfig);
		}
		
    	$params = [
			'status' => 'success',
			'defaultView' => $this ->configInfo -> getUserValue($this -> userId,$this->appName, 'currentview', 'month'),
			'agendatime' => $agendaTime,
			'defaulttime' => $defaultTime,
			'firstDay' => $firstDay,
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
		if($this ->configInfo ->getUserValue($this -> userId, $this->appName, 'timeformat', '24') === '24'){
			$agendaTime ='HH:mm { - HH:mm}';
			$defaultTime ='HH:mm';
		}
		
		$params = [
		'status' => 'success',
		'data' =>[
			'message' => (string)$this -> l10n -> t('Timeformat changed'),
			'agendaTime' => $agendaTime,
			'defaultTime' => $defaultTime,
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
			Repeat::cleancalendar($calendar['id']);
			Repeat::generatecalendar($calendar['id']);
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