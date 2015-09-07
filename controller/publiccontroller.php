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

use \OCP\AppFramework\Controller;
use \OCP\AppFramework\Http\TemplateResponse;
use \OCP\AppFramework\Http\JSONResponse;
use \OCP\IRequest;
use \OCP\IL10N;
use \OCP\IURLGenerator;
use \OCP\ISession;
use \OCP\Security\IHasher;
use \OCP\AppFramework\Http\RedirectResponse;
use \OCP\AppFramework\Utility\IControllerMethodReflector;

use \OCA\CalendarPlus\App as CalendarApp;
use \OCA\CalendarPlus\Calendar as CalendarCalendar;
use \OCA\CalendarPlus\VObject;
use \OCA\CalendarPlus\Object;

use OCA\CalendarPlus\Share\ShareConnector;

/**
 * Controller class for main page.
 */
class PublicController extends Controller {
	
	
	private $l10n;
	/** @var \OC\URLGenerator */
	protected $urlGenerator;
	
	/**
	 * @type ISession
	 * */
	private $session;
	
	/**
	 * @type IControllerMethodReflector
	 */
	protected $reflector;

	private $token;
	
	private $eventController;
	
	private $shareConnector;

	public function __construct($appName, IRequest $request,  IL10N $l10n, ISession $session, IControllerMethodReflector $reflector, IURLGenerator $urlGenerator, $eventController) {
		parent::__construct($appName, $request);
		$this->l10n = $l10n;
		$this->urlGenerator = $urlGenerator;
		$this->session = $session;
		$this->reflector=$reflector;
		$this->eventController = $eventController;
		$this->shareConnector = new ShareConnector();
	}
	
	public function getLanguageCode() {
        return $this->l10n->getLanguageCode();
    }


    public function beforeController($controller, $methodName) {
		if ($this->reflector->hasAnnotation('Guest')) {
			return;
		}
		$isPublicPage = $this->reflector->hasAnnotation('PublicPage');
		if ($isPublicPage) {
			$this->validateAndSetTokenBasedEnv();
		} else {
			//$this->environment->setStandardEnv();
		}
	}
	
	
	private function validateAndSetTokenBasedEnv() {
			$this->token = $this->request->getParam('t');
	}
	
	/**
	*@PublicPage
	 * @NoCSRFRequired
	 * @UseSession
	 */
	 
	public function index($token) {
			
		
		
		
		if ($token) {
			
			
				
			$linkItem = $this->shareConnector->getShareByToken($token) ;
			if (is_array($linkItem) && isset($linkItem['uid_owner'])) {
				if($linkItem['item_type'] === $this->shareConnector->getConstShareCalendar()){
					$sPrefix = $this->shareConnector->getConstSharePrefixCalendar();
				}
				if($linkItem['item_type'] === $this->shareConnector->getConstShareEvent()){
					$sPrefix = $this->shareConnector->getConstSharePrefixEvent();
				}
				$itemSource = $this->shareConnector->validateItemSource($linkItem['item_source'], $sPrefix);
				
				$itemType= $linkItem['item_type'];
				$shareOwner = $linkItem['uid_owner'];
				$calendarName= $linkItem['item_target'];
				$rootLinkItem = $this->shareConnector->resolveReShare($linkItem);
				
				// stupid copy and paste job
					if (isset($linkItem['share_with'])) {
						// Authenticate share_with
						
						$password=$this->params('password');
						
						if (isset($password)) {
							
							if ($linkItem['share_type'] ==  $this->shareConnector->getShareTypeLink()) {
								// Check Password
								$newHash = '';
								if(\OC::$server->getHasher()->verify($password, $linkItem['share_with'], $newHash)) {
									$this->session->set('public_link_authenticated', $linkItem['id']);
									if(!empty($newHash)) {

									}
								} else {
									\OCP\Util::addStyle('files_sharing', 'authenticate');
									$params=array(
									'wrongpw'=>true
									);
									return new TemplateResponse('files_sharing', 'authenticate', $params, 'guest');
									
								}
							} else {
								\OCP\Util::writeLog('share', 'Unknown share type '.$linkItem['share_type'].' for share id '.$linkItem['id'], \OCP\Util::ERROR);
									return false;
							}
			
						} else {
							// Check if item id is set in session
							if ( ! $this->session->exists('public_link_authenticated') || $this->session->get('public_link_authenticated') !== $linkItem['id']) {
								// Prompt for password
								\OCP\Util::addStyle('files_sharing', 'authenticate');
								
									$params=array();
									return new TemplateResponse('files_sharing', 'authenticate', $params, 'guest');
								
							}
						}
					}
				
				if($itemType ===  $this->shareConnector->getConstShareCalendar()){
						
					\OCP\Util::addStyle($this->appName, '3rdparty/fullcalendar');	
					\OCP\Util::addStyle($this->appName, "3rdparty/chosen");
					\OCP\Util::addStyle($this->appName, '3rdparty/fontello/css/animation');
					\OCP\Util::addStyle($this->appName, '3rdparty/fontello/css/fontello');
					\OCP\Util::addStyle($this->appName, '3rdparty/jquery.webui-popover');
					\OCP\Util::addStyle($this->appName, 'style');
					\OCP\Util::addStyle($this->appName, 'share');
					
					\OCP\Util::addScript($this->appName, '3rdparty/jstz-1.0.4.min');		
					\OCP\Util::addScript($this->appName, '3rdparty/fullcalendar');
					\OCP\Util::addScript($this->appName,'jquery.scrollTo.min');
					//\OCP\Util::addScript($this->appName,'timepicker');
					\OCP\Util::addScript($this->appName, "3rdparty/jquery.webui-popover");
					\OCP\Util::addScript($this->appName, "3rdparty/chosen.jquery.min");
					//\OCP\Util::addScript($this->appName,'jquery.nicescroll.min');
					\OCP\Util::addScript($this->appName, 'share');
					\OCP\Util::addScript($this->appName, 'share.config');
					
					$timezone = \OC::$server->getSession()->get('public_link_timezone');
					
					
					//$webcalUrl = \OC::$server->getRequest()->getRequestUri();
					//\OCP\Util::writeLog($this->appName,'PUBLIC FOUND'.$timezone, \OCP\Util::DEBUG);
					$params = [
					'timezone' => $timezone,
					'appname' => $this->appName,
					'uidOwner' => $shareOwner,
					'timezones' => \DateTimeZone::listIdentifiers(),
					'calendarName' => $calendarName,
					'displayName' => \OCP\User::getDisplayName($shareOwner),
					'sharingToken' => $token,
					];	
				
				$response = new TemplateResponse($this->appName, 'public',$params,'base');
				return $response;
			}
			if($itemType ===  $this->shareConnector->getConstShareEvent()){
					\OCP\Util::addStyle($this->appName, '3rdparty/fontello/css/fontello');	
					\OCP\Util::addStyle($this->appName, 'style');
					\OCP\Util::addStyle($this->appName, 'share');
					\OCP\Util::addScript($this->appName, 'share');
					
					return $this->getPublicEvent($itemSource, $shareOwner, $token);
			}
				
			
		
			}//end isset
			
		}//end token
		
		$params=[];
		$response = new TemplateResponse('core', '404',$params,'guest');
		return $response;
		
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 */
	public function getEventsPublic() {
		$token = $this -> params('t');
		$pStart = $this -> params('start');
		$pEnd = $this -> params('end');
		$calendar_id = null;
		\OC::$server->getSession()->close();
		
		
		if (isset($token)) {
			
			$linkItem =  $this->shareConnector->getShareByToken($token);
			if (is_array($linkItem) && isset($linkItem['uid_owner'])) {
				$rootLinkItem =  $this->shareConnector->resolveReShare($linkItem);
				
				if (isset($rootLinkItem['uid_owner'])) {
					\OCP\JSON::checkUserExists($rootLinkItem['uid_owner']);	
					$calendar_id = $this->shareConnector->validateItemSource($linkItem['item_source'], $this->shareConnector->getConstSharePrefixCalendar());
					
				}
			}
			
		}
		$start = new \DateTime('@' . $pStart);
		$end = new \DateTime('@' . $pEnd);
		$aCalendar = CalendarApp::getCalendar($calendar_id, false, false);
        $isBirthday = false;
		if($aCalendar['uri'] === 'bdaycpltocal_'.$aCalendar['userid']){
			$isBirthday = true;
		}
		$events = CalendarApp::getrequestedEvents($calendar_id, $start, $end);
		
		$output = array();
		
		foreach($events as $event) {
		     	$event['bday'] = 0;	
		     	if($isBirthday === true){
		     		$event['bday'] =1;
		     	}
				$eventArray=	$this->eventController->generateEventOutput($event, $start, $end);
				if(is_array($eventArray)) $output = array_merge($output, $eventArray);
			
		}
		
		$response = new JSONResponse();
		$response -> setData($output);
		return $response;
		
	}


	 /**
     * @PublicPage
	 * @NoCSRFRequired
	  * @UseSession
     */
    public function getDateTimeFormat(){
    	$pDateFormat= $this -> params('dateformat');
		$pTimeFormat= $this -> params('timeformat');
		
		$this->session->set('public_dateformat', $pDateFormat);
		$this->session->set('public_timeformat', $pTimeFormat);
		
    }

	 /**
     * @PublicPage
	 * @NoCSRFRequired
	  * @UseSession
     */
    public function getGuessTimeZone() {
    	$pTimezone= $this -> params('timezone');
		
		try {
			$tz = new \DateTimeZone($pTimezone);
		} catch(\Exception $ex) {
			$params = [
				'status' => 'error',
			];
		
			$response = new JSONResponse($params);
			return $response;
		}
		
		if($this->session->get('public_link_timezone') ==''){
		
			$this->session->set('public_link_timezone', $pTimezone);
			
			$params = [
				'status' => 'success',
				'message' => $this -> l10n -> t('New Timezone:'). ' ' . $pTimezone
			];
		
			$response = new JSONResponse($params);
			return $response;
		}else{
			$params = [
				'status' => 'success',
			];
		
			$response = new JSONResponse($params);
			return $response;
		}
		

    }

	/**
     * @PublicPage
	 * @NoCSRFRequired
     */
    public function getGuestSettingsCalendar() {
   
   		$token= $this -> params('t');
		if (isset($token)) {
			$linkItem = $this->shareConnector->getShareByToken($token);
			if (is_array($linkItem) && isset($linkItem['uid_owner'])) {
				// seems to be a valid share
				if($linkItem['item_type'] ===  $this->shareConnector->getConstShareCalendar()){
					$sPrefix =  $this->shareConnector->getConstSharePrefixCalendar();
				}
				if($linkItem['item_type'] ===  $this->shareConnector->getConstShareEvent()){
					$sPrefix =  $this->shareConnector->getConstSharePrefixEvent();
				}
				
				$itemSource =  $this->shareConnector->validateItemSource($linkItem['item_source'],$sPrefix);
				$shareOwner = $linkItem['uid_owner'];
				$rootLinkItem = $this->shareConnector->resolveReShare($linkItem);
				
				if (isset($rootLinkItem['uid_owner'])) {
					\OCP\JSON::checkUserExists($rootLinkItem['uid_owner']);
					
					$calendar = CalendarCalendar::find($itemSource);
					   if(!array_key_exists('active', $calendar)){
						$calendar['active'] = 1;
					}
					if($calendar['active'] == 1) {
						$eventSources[] = CalendarCalendar::getEventSourceInfo($calendar, true);
						
						$eventSources[0]['url']= \OC::$server->getURLGenerator()->linkToRoute($this->appName.'.public.getEventsPublic').'?t='.$token;
						
						$calendarInfo[$calendar['id']]=array(
						'bgcolor'=>$calendar['calendarcolor'],
						'color'=> CalendarCalendar::generateTextColor($calendar['calendarcolor'])
						);
						
						$myRefreshChecker[$calendar['id']]=$calendar['ctag'];
						
					}
				}
				
				
			}
			
			$defaultView ='month';
			if($this->session->get('public_currentView')!=''){
					$defaultView = (string)$this->session->get('public_currentView');
				}
			
			if($this->session->get('public_dateformat')!=''){
		   	  	$sDateFormat = $this->session->get('public_dateformat');
		   	 }else{
		   	  	$sDateFormat ='d.m.Y';
		   	  }
			 
			 if($this->session->get('public_timeformat')!=''){
		   	  	$sTimeFormatRead = $this->session->get('public_timeformat');
		   	 }else{
		   	  	$sTimeFormatRead ='H:i';
		   	 }
		  
			$params = [
			'status' => 'success',
			'defaultView' => $defaultView,
			'agendatime' => 'HH:mm { - HH:mm}',
			'defaulttime' => 'HH:mm',
			'dateformat' => $sDateFormat,
			'timeformat' => $sTimeFormatRead,
			'firstDay' => '1',
			'calendarId' => $calendar['id'],
			'eventSources' => $eventSources,
			'calendarcolors'=> $calendarInfo,
			'myRefreshChecker'=> $myRefreshChecker,
		];
		
		$response = new JSONResponse($params);
		
		return $response;
			
			
		}
		
		
    }

	 /**
     * @PublicPage
	 * @NoCSRFRequired
	  * @UseSession
     */
	public function changeViewCalendarPublic() {
		$view = $this -> params('v');
		
		switch($view) {
			case 'agendaDay':	
			case 'agendaWeek':
			case 'month':
			case 'agendaWorkWeek':
			case 'agendaThreeDays':
			case 'fourWeeks':
			case 'year':							
			case 'list':
				$this->session->set('public_currentView', $view);
				break;
			default:
				$this->session->set('public_currentView', 'month');
				break;
		}
		
		
		$response = new JSONResponse();
		
		return $response;
		
		
	}
	
	
	
	private function getPublicEvent($itemSource, $shareOwner, $token){
				
			$itemSource = $this->shareConnector->validateItemSource($itemSource, $this->shareConnector->getConstSharePrefixEvent());
				
			$data = CalendarApp::getEventObject($itemSource, false, false);
			$object = VObject::parse($data['calendardata']);
			$vevent = $object -> VEVENT;
			
			$object = Object::cleanByAccessClass($itemSource, $object);
			$accessclass = $vevent -> getAsString('CLASS');
			if($accessclass=='PRIVATE'){
				header('HTTP/1.0 404 Not Found');
				$response = new TemplateResponse('core', '404','','guest');
				return $response;
			}
			$permissions = CalendarApp::getPermissions($itemSource, CalendarApp::EVENT, $accessclass);
			$dtstart = $vevent -> DTSTART;
			$dtend = Object::getDTEndFromVEvent($vevent);

           $dtstartType=$vevent->DTSTART->getValueType();

		
			 if($dtstartType=='DATE'){
			 	$startdate = $dtstart -> getDateTime() -> format('d-m-Y');
				$starttime = '';
				$enddate = $dtend -> getDateTime()-> modify('-1 day') -> format('d-m-Y');
				
				$endtime = '';
				$choosenDate=$choosenDate + (3600 * 24);
				$allday = true;
			 }
			 if($dtstartType=='DATE-TIME'){
			 	$startdate = $dtstart -> getDateTime() -> format('d-m-Y');
			    $starttime = $dtstart -> getDateTime() -> format('H:i');
				$enddate = $dtend -> getDateTime() -> format('d-m-Y');
			    $endtime = $dtend -> getDateTime() -> format('H:i');
				
			    $allday = false;
			 }
			 
				$summary = strtr($vevent -> getAsString('SUMMARY'), array('\,' => ',', '\;' => ';'));
				$location = strtr($vevent -> getAsString('LOCATION'), array('\,' => ',', '\;' => ';'));
				$categories = $vevent -> getAsArray('CATEGORIES');
				$description = strtr($vevent -> getAsString('DESCRIPTION'), array('\,' => ',', '\;' => ';'));
				$link = strtr($vevent -> getAsString('URL'), array('\,' => ',', '\;' => ';'));
				
				$last_modified = $vevent -> __get('LAST-MODIFIED');
				if ($last_modified) {
					$lastmodified = $last_modified -> getDateTime() -> format('U');
				} else {
					$lastmodified = 0;
				}
				
				$repeatInfo = array();
				$repeat['repeat'] = '';
				if ($data['repeating'] == 1) {
					
					$rrule = explode(';', $vevent -> getAsString('RRULE'));
					$rrulearr = array();
				
					$repeat['repeat_rules'] = '';
					foreach ($rrule as $rule) {
						list($attr, $val) = explode('=', $rule);
						if ($attr != 'COUNT' && $attr !== 'UNTIL') {
							if ($repeat['repeat_rules'] === ''){
								$repeat['repeat_rules'] = $attr . '=' . $val;
							}else{
								$repeat['repeat_rules'] .= ';' . $attr . '=' . $val;
								}
						}
						if ($attr === 'COUNT' || $attr !== 'UNTIL') {
							$rrulearr[$attr] = $val;
						}
					}
				
					if (array_key_exists('COUNT', $rrulearr)) {
						$repeat['end'] = 'count';
						$repeat['count'] = $rrulearr['COUNT'];
					} elseif (array_key_exists('UNTIL', $rrulearr)) {
						$repeat['end'] = 'date';
						$endbydate_day = substr($rrulearr['UNTIL'], 6, 2);
						$endbydate_month = substr($rrulearr['UNTIL'], 4, 2);
						$endbydate_year = substr($rrulearr['UNTIL'], 0, 4);
						$repeat['date'] = $endbydate_day . '-' . $endbydate_month . '-' . $endbydate_year;
					} else {
						$repeat['end'] = 'never';
					}
				
					$repeat_end_options =CalendarApp::getEndOptions();
					if ($repeat['end'] === 'count') {
						$repeatInfo['end'] = $this->l10n -> t('after') . ' ' . $repeat['count'] . ' ' . $this->l10n -> t('Events');
					}
					if ($repeat['end'] === 'date') {
						$repeatInfo['end'] = $repeat['date'];
					}
					if ($repeat['end'] === 'never') {
						$repeatInfo['end'] = $repeat_end_options[$repeat['end']];
					}
				
				} else {
					$repeat['repeat'] = 'doesnotrepeat';
				}
				
				$calendar_options[0]['id'] = $data['calendarid'];
				
				$access_class_options =CalendarApp::getAccessClassOptions();
				
				$aOExdate = '';
				if ($vevent -> EXDATE) {
				
					$timezone = CalendarApp::getTimezone();
				
					foreach ($vevent->EXDATE as $param) {
						$param = new \DateTime($param);
						$aOExdate[$param -> format('U')] = $param -> format('d-m-Y');
					}
				
				}
				$timezone=\OC::$server->getSession()->get('public_link_timezone');
				
				
				$sCat = '';
				if(is_array($categories) && count($categories)>0){
					$sCat=$categories;
				}
			
				$params = [
					'eventid' => $itemSource,
					'appname' => $this->appName,
					'permissions' => $permissions,
					'lastmodified' => $lastmodified,
					'exDate' => $aOExdate,
					'sharingToken' => $token,
					'token' => $token,
					'calendar_options' => $calendar_options,
					'access_class_options' => $access_class_options,
					'title' => $summary,
					'accessclass' => $accessclass,
					'location' => $location,
					'calendar' => $data['calendarid'],
					'timezone' => $timezone,
					'uidOwner' => $shareOwner,
					'displayName' => \OCP\User::getDisplayName($shareOwner),
					'allday' => $allday,
					'startdate' => $startdate,
					'starttime' => $starttime,
					'enddate' => $enddate,
					'endtime' => $endtime,
					'description' => $description,
					'link' => $link,
					'repeat_rules' => isset($repeat['repeat_rules']) ? $repeat['repeat_rules'] : '',
					'repeat' => $repeat['repeat'],
					'repeatInfo' => $repeat['repeat'] !='doesnotrepeat' ? $repeatInfo : '',
					'categories' => $sCat,
				];	
			
			$response = new TemplateResponse($this->appName, 'publicevent',$params,'base');
	
			return $response;
			
			
	}



}