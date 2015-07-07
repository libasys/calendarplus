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
use \OCA\CalendarPlus\Alarm;

use \OCP\AppFramework\Controller;
use \OCP\AppFramework\Http\JSONResponse;
use \OCP\AppFramework\Http\TemplateResponse;
use \OCP\IRequest;
use \OCP\Share;
use \OCP\IConfig;

class EventController extends Controller {

	private $userId;
	private $l10n;
	private $configInfo;

	public function __construct($appName, IRequest $request, $userId, $l10n, IConfig $settings) {
		parent::__construct($appName, $request);
		$this -> userId = $userId;
		$this->l10n = $l10n;
		$this->configInfo = $settings;
	}
	
	/**
	 * @NoAdminRequired
	 */
	public function getEvents() {
		
		$pStart = $this -> params('start');
		$pEnd = $this -> params('end');
		$calendar_id = null;
		\OC::$server->getSession()->close();
		
		$getId = $this -> params('calendar_id');
			
			
			if (strval(intval($getId)) === strval($getId)) { // integer for sure.
				$id = intval($getId);
			
				$calendarrow = CalendarApp::getCalendar($id, true, false); // Let's at least security check otherwise we might as well use OCA\Calendar\Calendar::find())
				if($calendarrow !== false) {
					$calendar_id = $id;
					
				}else{
					if(\OCP\Share::getItemSharedWithBySource(CalendarApp::SHARECALENDAR, 'calendar-'.$id) === false){
						exit;
					}
				}
			}
			$calendar_id = (is_null($calendar_id)?strip_tags($getId):$calendar_id);
		
		$start = new \DateTime('@' . $pStart);
		$end = new \DateTime('@' . $pEnd);
		
		$events = CalendarApp::getrequestedEvents($calendar_id, $start, $end);
		
		$output = array();
		
		foreach($events as $event) {
		     
				$eventArray=	CalendarApp::generateEventOutput($event, $start, $end);
				if(is_array($eventArray)) $output = array_merge($output, $eventArray);
			
		}
		
		$response = new JSONResponse();
		$response -> setData($output);
		return $response;
		
	}

	/**
	 * @NoAdminRequired
	 */
	public function getEventsDayView() {
		\OC::$server->getSession()->close();	
		if (\OCP\User::isLoggedIn()) {
			$monthToCalc=intval($this -> params('month'))+1;
			$year=intval($this -> params('year'));
			 $ersterTag =  mktime(0, 0, 0,$monthToCalc, 1, $year);
		 
			if (date("L", $ersterTag)) {
					$iSchaltMonat = 29;
			} else{
				$iSchaltMonat = 28;
		    }
			$MonthEndDayArray = array (31,$iSchaltMonat,31,30,31,30,31,31,	30,31,30,31);
			$letzterTag = mktime(23, 59, 59,$monthToCalc, $MonthEndDayArray[$monthToCalc -1],$year);
			
			 $start = new \DateTime('@' . $ersterTag);
		     $end = new \DateTime('@' . $letzterTag);
			 
			 
			 $calendars =CalendarCalendar::allCalendars($this -> userId, true);
			 $calendars[]=['id' => 'shared_events', 'active' => 1];
			 
			 $events='';
			 
			foreach($calendars as $calInfo){
				$isAktiv=(int)$calInfo['active'];
				if($this -> configInfo -> getUserValue($this -> userId, $this->appName, 'calendar_'.$calInfo['id']) !== ''){
					$isAktiv= (int) $this -> configInfo -> getUserValue($this -> userId, $this->appName, 'calendar_'.$calInfo['id']);
				}
				if($calInfo['id'] !== 'birthday_'.$this -> userId && $isAktiv === 1){
					\OCP\Util::writeLog($this->appName,'DAYVIEW => '.$calInfo['id'], \OCP\Util::DEBUG);	
					$events[] = CalendarApp::getrequestedEvents($calInfo['id'], $start, $end);
				}
				
			}
			$output = array();
			$aSort = array();
			$eventArray=array();
			
			if(is_array($events)){
					
				foreach($events as $event){
							
						foreach($event as $eventInfo){
								
							if((int)$eventInfo['repeating'] === 0) {
											
								$start_dt = new \DateTime($eventInfo['startdate'], new \DateTimeZone('UTC'));
							    $startDate=$start_dt->format('Y/m/d');
								$start_tmst = new \DateTime($startDate, new \DateTimeZone('UTC'));
								$startTimeStamp=$start_tmst->format('U');
								
								$end_dt = new \DateTime($eventInfo['enddate'], new \DateTimeZone('UTC'));
								$endTimeStamp=$end_dt->format('U');
								
								$aSort[$startTimeStamp]=$startDate;
								
								if($endTimeStamp > ($startTimeStamp + (24*60*60))){
										
									$datetime1 = new \DateTime($eventInfo['startdate']);
									$datetime2 = new \DateTime($eventInfo['enddate']);
									$interval = $datetime1->diff($datetime2);
									$count=(int) $interval->format('%a');
									for($i=1; $i<$count; $i++){
										$start_dtNew = new \DateTime($eventInfo['startdate'], new \DateTimeZone('UTC'));
										$start_dtNew->modify('+'.$i.' day');
									    $startDateNew=$start_dtNew->format('Y/m/d');
										$start_tmstNew = new \DateTime($startDateNew, new \DateTimeZone('UTC'));
								        $startTimeStampNew=$start_tmstNew->format('U');
										$aSort[$startTimeStampNew]=$startDateNew;
										//OCP\Util::writeLog('calendar','STARTDATE'.$startDateNew.' -> '.$eventInfo['summary'], OCP\Util::DEBUG);
										
										$eventArray[$startDateNew][]=CalendarApp::generateEventOutput($eventInfo, $start, $end);
									}
								}
								 
								 $eventArray[$startDate][]=CalendarApp::generateEventOutput($eventInfo, $start, $end);
							}
							
							if((int)$eventInfo['repeating'] === 1) {
							  	
							  $cachedinperiod = Repeat::get_inperiod($eventInfo['id'], $start, $end);
								$counter=0;
								foreach($cachedinperiod as $cacheinfo){
									 $start_dt_cache = new \DateTime($cacheinfo['startdate'], new \DateTimeZone('UTC'));
									 $startCacheDate=$start_dt_cache->format('Y/m/d');
									 $start_Cachetmst = new \DateTime($startCacheDate, new \DateTimeZone('UTC'));
								     $startCacheTimeStamp=$start_Cachetmst->format('U');
									 $aSort[$startCacheTimeStamp]=$startCacheDate;
									
									$eventArray[$startCacheDate][]=CalendarApp::generateEventOutput($eventInfo, $start, $end);
									 
									 $counter++;
								}
							    
							}
							
							 if(is_array($eventArray)) $output= array_merge($output, $eventArray);
							 
						 }
						
						}
						ksort($aSort);
					
					$params=[
						'data' => $output,
						'sortdate' => $aSort
					];
					$response = new JSONResponse();
					$response -> setData($params);
					return $response;
			}
			
		}
	}


	/**
	 * @NoAdminRequired
	 */
	public function addCategorieToEvent() {
				
		$id = $this -> params('id');
		$pStart = $this -> params('viewstart');
		$pEnd = $this -> params('viewend');
		
		$aCheckPermissions =$this->checkPermissions($id, \OCP\PERMISSION_UPDATE);
		if($aCheckPermissions['status'] === 'success'){
			$category = $this -> params('category');
			$vcalendar = CalendarApp::getVCalendar($id, false, false);
			$vevent = $vcalendar->VEVENT;
			
			if($vevent->CATEGORIES){
				$aCategory=$vevent->getAsArray('CATEGORIES');
				$sCatNew='';
				$aCatNew=array();
				foreach($aCategory as $sCat){
					$aCatNew[$sCat]=1;	
					if($sCatNew === '') {
						$sCatNew = $sCat;
					}else{
						$sCatNew.= ','.$sCat;
					}
				}
				if(!array_key_exists($category, $aCatNew)){
					$sCatNew.= ','.$category;
				}
				$vevent->setString('CATEGORIES', $sCatNew);
			}else{
				$vevent->setString('CATEGORIES', $category);
			}
			
			$vevent->setDateTime('LAST-MODIFIED', 'now');
			$vevent->setDateTime('DTSTAMP', 'now');
			Object::edit($id, $vcalendar->serialize());
			Repeat::update($id);
			$lastmodified = $vevent->__get('LAST-MODIFIED')->getDateTime();
			
			$editedEvent = CalendarApp::getEventObject($id, false, false);
			
			$start = new \DateTime($pStart);
			$end = new \DateTime($pEnd);
			
		    $events = CalendarApp::generateEventOutput($editedEvent, $start, $end);
			
			$params = [
				'status' => 'success',
				'lastmodified' => (int)$lastmodified->format('U'),
				'data' =>[
					'id' => $id,
					'events' => $events 
				]
				];
			
			
		}else{
			$params = [
				'status' =>$aCheckPermissions['status'],
				'msg' => $aCheckPermissions['msg']
			];
		}
			$response = new JSONResponse();
			$response -> setData($params);
			return $response;
			
	}

	/**
	 * @NoAdminRequired
	 */
	public function addSharedEvent() {
		$eventid =$this -> params('eventid');
		$calid =$this -> params('calid');
	
		Object::addSharedEvent($eventid,$calid);
		$params = [
				'status' =>'success',
				'msg' => 'Added Event success'
			];
		$response = new JSONResponse($params);
		return $response;
	}
	
	/**
	 * @NoAdminRequired
	 */
	public function deleteExdateEvent() {
		
		$id = $this -> params('id');
		$choosenDate = $this -> params('choosendate');
		$pStart = $this -> params('viewstart');
		$pEnd = $this -> params('viewend');
		
		$data = CalendarApp::getEventObject($id, false, false);
		$vcalendar = VObject::parse($data['calendardata']);
		$vevent = $vcalendar->VEVENT;
		
		
		$vevent->setDateTime('LAST-MODIFIED', 'now');
		$vevent->setDateTime('DTSTAMP', 'now');
		$timezone = CalendarApp::getTimezone();
		$paramsExt=array();
		foreach($vevent->EXDATE as $key => $param){
			$paramToCheck = new \DateTime($param);
			$checkEx=$paramToCheck -> format('U');
			
			if($checkEx !== $choosenDate){
				$paramsExt[]=$param;
			}
		} 
		 $vevent->setString('EXDATE','');
		
		foreach($paramsExt as $param){
			    $vevent->addProperty('EXDATE',(string)$param);
		}
		
		$output='success';
	
		Object::edit($id, $vcalendar->serialize());
		Repeat::update($id);
		
		$editedEvent = CalendarApp::getEventObject($id, false, false);
			
		$start = new \DateTime($pStart);
		$end = new \DateTime($pEnd);
		
	    $events = CalendarApp::generateEventOutput($editedEvent, $start, $end);
		
		$params = ['status' => 'success',
			'data' =>[
				'id' => $id,
				'events' => $events 
			]
			];
		
		$response = new JSONResponse();
		$response -> setData($params);
		return $response;
		
	}
	
	/**
	 * @NoAdminRequired
	 */
	public function moveEvent() {
			
		$id = $this -> params('id');
		$aCheckPermissions =$this->checkPermissions($id, \OCP\PERMISSION_UPDATE);
		if($aCheckPermissions['status'] === 'success'){
			$vcalendar = CalendarApp::getVCalendar($id, false, false);
			$vevent = $vcalendar->VEVENT;
			
			$allday = $this -> params('allDay');
			$delta = new \DateInterval('P0D');
			$delta->d = $this -> params('dayDelta');
			$delta->i = $this -> params('minuteDelta');
			$lastModified = $this -> params('lastmodified');
			CalendarApp::isNotModified($vevent, $lastModified);
			
			$dtstart = $vevent->DTSTART;
			$dtend = Object::getDTEndFromVEvent($vevent);
			$start_type = (string) $dtstart->getValueType();
			
			$end_type = $dtend->getValueType();
			if ($allday && $start_type !== 'DATE') {
				$start_type = $end_type ='DATE';
				$dtend->setDateTime($dtend->getDateTime()->modify('+1 day'));
			}
			
			if (!$allday && $start_type === 'DATE') {
				$start_type = $end_type = 'DATE-TIME';
			}
			
			if($vevent->EXDATE){
				$aExt=$vevent->EXDATE;
				$vevent->setString('EXDATE','');
				 $timezone = CalendarApp::getTimezone();
				 
				foreach($aExt as $param){
					$dateTime = new \DateTime($param->getValue());
					$datetime_element = new \Sabre\VObject\Property\ICalendar\DateTime(new \Sabre\VObject\Component\VCalendar(),'EXDATE');
					$datetime_element->setDateTime($dateTime->add($delta));
					$vevent->addProperty('EXDATE;TZID='.$timezone,(string) $datetime_element);
				}
			}
	
			$dtstart->setDateTime($dtstart->getDateTime()->add($delta));
			$dtend->setDateTime($dtend->getDateTime()->add($delta));
			unset($vevent->DURATION);
			
			$vevent->setDateTime('LAST-MODIFIED', 'now');
			$vevent->setDateTime('DTSTAMP', 'now');
					
			Object::edit($id, $vcalendar->serialize());
			Repeat::update($id);
			
			$lastmodified = $vevent->__get('LAST-MODIFIED')->getDateTime();
			$params = [
				'status' => $aCheckPermissions['status'],
				'lastmodified' => (int)$lastmodified->format('U')
			];
		}else{
			$params = [
				'status' =>$aCheckPermissions['status'],
				'msg' => $aCheckPermissions['msg']
			];
		}
		$response = new JSONResponse();
		$response -> setData($params);
		return $response;
	}
	
	private function checkPermissions($eventId,  $cPERMISSIONS){
		
		$calid = Object::getCalendarid($eventId);
		$calendar = CalendarCalendar::find($calid);
		
		if ($calendar['userid'] === $this->userId) {
			$return=[
				 'status' => 'success',
				 'msg' => 'All good'
				];	
				return $return;
		}
		
		if ($calendar['userid'] !== $this->userId) {
			$shareMode=Object::checkShareMode($calid);
			if($shareMode){
				$sharedCalendar = \OCP\Share::getItemSharedWithBySource(CalendarApp::SHARECALENDAR,CalendarApp::SHARECALENDARPREFIX. $calid);
			}else{
				$sharedCalendar = \OCP\Share::getItemSharedWithBySource(CalendarApp::SHAREEVENT,CalendarApp::SHAREEVENTPREFIX. $id); 
			}
			
			if (!$sharedCalendar || !($sharedCalendar['permissions'] & $cPERMISSIONS)){
				$return=[
				 'status' => 'error',
				 'msg' => (string)$this->l10n->t('You do not have the permissions to edit this event.')
				];	
				return $return;
			}else{
				$return=[
				 'status' => 'success',
				 'msg' => 'All good'
				];	
				return $return;
			}
			
		}
		
		
	}
	
	/**
	 * @NoAdminRequired
	 */
	public function resizeEvent() {
		$id = $this -> params('id');
		
		$aCheckPermissions =$this->checkPermissions($id, \OCP\PERMISSION_UPDATE);
		if($aCheckPermissions['status'] === 'success'){
		
			$vcalendar = CalendarApp::getVCalendar($id, false, false);
			$vevent = $vcalendar->VEVENT;
			
			$accessclass = $vevent->getAsString('CLASS');
			$permissions = CalendarApp::getPermissions($id, CalendarApp::EVENT, $accessclass);
			if(!$permissions & \OCP\PERMISSION_UPDATE) {
				exit;
			}
			
			$delta = new \DateInterval('P0D');
			$delta->d = $this -> params('dayDelta');
			$delta->i = $this -> params('minuteDelta');
			$lastModified = $this -> params('lastmodified');
			CalendarApp::isNotModified($vevent, $lastModified);
			
			$dtend = Object::getDTEndFromVEvent($vevent);
			$dtend->setDateTime($dtend->getDateTime()->add($delta));
			unset($vevent->DURATION);
			
			$vevent->setDateTime('LAST-MODIFIED', 'now');
			$vevent->setDateTime('DTSTAMP', 'now');
			
			Object::edit($id, $vcalendar->serialize());
			
			$lastmodified = $vevent->__get('LAST-MODIFIED')->getDateTime();
			$params = [
				'status' =>$aCheckPermissions['status'],
				'lastmodified' => (int)$lastmodified->format('U')
			];
		}else{
			$params = [
				'status' =>$aCheckPermissions['status'],
				'msg' => $aCheckPermissions['msg']
			];
		}
		$response = new JSONResponse();
		$response -> setData($params);
		return $response;
	}
     /**
     * @NoAdminRequired
     */
    public function getNewFormEvent() {
    	
	    $start = $this -> params('start');
		$end = $this -> params('end');
		$allday =$this -> params('allday');
		
		if (!$end) {
			$duration = $this-> configInfo ->getUserValue($this -> userId,$this->appName, 'duration', '60');
			$end = $start + ($duration * 60);
		}
		$start = new \DateTime('@'.$start);
		$end = new \DateTime('@'.$end);
		
		$timezone = CalendarApp::getTimezone();
		$start->setTimezone(new \DateTimeZone($timezone));
		$end->setTimezone(new \DateTimeZone($timezone));
		
		$calendars = CalendarCalendar::allCalendars($this -> userId);
		$calendar_options = array();
		
		foreach($calendars as $calendar) {
			if($calendar['userid'] !== $this -> userId) {
				$sharedCalendar = \OCP\Share::getItemSharedWithBySource(CalendarApp::SHARECALENDAR,'calendar-'. $calendar['id']);
				if ($sharedCalendar && ($sharedCalendar['permissions'] & \OCP\PERMISSION_CREATE)) {
					array_push($calendar_options, $calendar);
				}
			} else {
				array_push($calendar_options, $calendar);
			}
		}
		
		$access_class_options = CalendarApp::getAccessClassOptions();
		$repeat_options = CalendarApp::getRepeatOptions();
		$repeat_end_options = CalendarApp::getEndOptions();
		$repeat_month_options = CalendarApp::getMonthOptions();
		$repeat_year_options = CalendarApp::getYearOptions();
		$repeat_weekly_options = CalendarApp::getWeeklyOptions();
		
		//NEW
		$repeat_advancedoptions = CalendarApp::getAdvancedRepeatOptions();
		$repeat_weeklyshort_options = CalendarApp::getWeeklyOptionsShort();
		$repeat_monthshort_options = CalendarApp::getByMonthShortOptions();
		
		$repeat_weekofmonth_options = CalendarApp::getWeekofMonth();
		$repeat_byyearday_options = CalendarApp::getByYearDayOptions();
		$repeat_bymonth_options = CalendarApp::getByMonthOptions();
		$repeat_byweekno_options = CalendarApp::getByWeekNoOptions();
		$repeat_bymonthday_options = CalendarApp::getByMonthDayOptions();
		
		//NEW Reminder
		$reminder_options = CalendarApp::getReminderOptions();
		$reminder_advanced_options = CalendarApp::getAdvancedReminderOptions();
		$reminder_time_options = CalendarApp::getReminderTimeOptions();
		
		$tWeekDay=Object::getWeeklyOptionsCheck($start->format('D'));
        $transWeekDay[$tWeekDay]=$tWeekDay;
		
		$tDayOfMonth[$start->format('j')]=$start->format('j');
        $tMonth[$start->format('n')]=$start->format('n');
        
        $first_of_month = $start->format('Y-m-1');
        $day_of_first = date('N',$start->format('U'));
        $day_of_month = $start->format('j');
        $weekNum = floor(($day_of_first + $day_of_month - 1) / 7) + 1;
        if($weekNum >=1 && $weekNum <= 4) $weekNum='+'.$weekNum;
        else $weekNum='-1';
         
           $paramsRepeat = [
            'logicCheckWeekDay' => $tWeekDay,
            'rRadio0' => 'checked="checked"',
            'rClass0' => '',
            'rRadio1' => '',
            'rClass1' => 'class="ui-isDisabled"',
            'checkedMonth' => '',
            'bdayClass' => 'class="ui-isDisabled"',
            'repeat_rules' => '',
            'advancedrepeat' => 'DAILY',
            'repeat_weekdaysSingle' => $transWeekDay,
            'repeat_weekdays' => $transWeekDay,
            'repeat_bymonthday' => $tDayOfMonth,
            'repeat_bymonth' => $tMonth,
            'repeat_weekofmonth' => $weekNum,
            'repeat_interval' => 1,
            'repeat_end' => 'never',
            'repeat_count' => '10',
            'repeat_date' => '',
            'repeat' => 'doesnotrepeat'
        ];
		
		$activeCal=$this -> configInfo -> getUserValue($this -> userId, $this->appName, 'choosencalendar');
		
		$myChoosenCalendar='';
		
		if(intval($activeCal) > 0 && $activeCal !== ''){
			$myChoosenCalendar = $activeCal;
		}else{
			$myChoosenCalendar = $calendar_options[0]['id'];
		}

		$params = [
        'eventid' => 'new',
        'appname' => $this->appName,
         'calendar_options' => $calendar_options,
        'access_class_options' => $access_class_options,
        'repeat_options' => $repeat_options,
        'repeat_month_options' => $repeat_month_options,
        'repeat_weekly_options' => $repeat_weekly_options,
        'repeat_end_options' => $repeat_end_options,
        'repeat_year_options' => $repeat_year_options,
        'repeat_byyearday_options' => $repeat_byyearday_options,
        'repeat_bymonth_options' => $repeat_bymonth_options,
        'repeat_byweekno_options' => $repeat_byweekno_options,
        'repeat_bymonthday_options' => $repeat_bymonthday_options,
        'repeat_weekofmonth_options' => $repeat_weekofmonth_options,
        'repeat_advancedoptions' => $repeat_advancedoptions,
        'repeat_weeklyshort_options' => $repeat_weeklyshort_options,
        'repeat_monthshort_options' => $repeat_monthshort_options,
        'reminder_options' => $reminder_options,
        'reminder_advanced_options' => $reminder_advanced_options,
        'reminder_time_options' => $reminder_time_options,
        'reminder_advanced' => 'DISPLAY',
        'reminder_rules' => '',
        'reminder' => 'none',
        'remindertimeselect' => '',
        'remindertimeinput' => '',
        'reminderemailinput' => '',
        'reminderdate' => '',
        'remindertime' => '',
        'accessclass' => 'PUBLIC',
        'access' => 'owner',
        'categories' => '',
        'calendar' => $myChoosenCalendar,
        'allday' => $allday,
        'startdate' =>$start->format('d-m-Y'),
        'starttime' => $start->format('H:i'),
        'enddate' => $end->format('d-m-Y'),
        'endtime' => $end->format('H:i'),
       ];
	   
		$params = array_merge($params, $paramsRepeat);
        $response = new TemplateResponse($this->appName, 'part.newevent',$params, '');  
        
        return $response;
		
    }
	/**
     * @NoAdminRequired
     */
    public function newEvent() {
    	
		$postRequestAll = $this -> getParams();
		$pStart = $this -> params('viewstart');
		$pEnd = $this -> params('viewend');
		
		$calId = $this -> params('calendar');
		
		
		$errarr = Object::validateRequest($postRequestAll);
		if($errarr) {
			$errarr['status'] = 'error';	
			$response = new JSONResponse($errarr);
			return $response;
			
		}else{
			$vcalendar = Object::createVCalendarFromRequest($postRequestAll);
			$id = Object::add($calId, $vcalendar->serialize());
			
			$editedEvent = CalendarApp::getEventObject($id, false, false);
			
			$start = new \DateTime($pStart);
			$end = new \DateTime($pEnd);
			
		    $events = CalendarApp::generateEventOutput($editedEvent, $start, $end);
			
			$params = ['status' => 'success',
			'data' =>[
				'id' => $id,
				'events' => $events 
			]
			];
			$response = new JSONResponse($params);
			return $response;
			}
		
		}
    /**
     * @NoAdminRequired
     */
    public function getEditFormEvent() {
       $id = $this -> params('id');
       $choosenDate = $this -> params('choosendate');
       
       $data = CalendarApp::getEventObject($id, false, false);
       
       $editInfo = $this -> getVobjectData($id, $choosenDate, $data);
       
       if($editInfo['permissions'] !== \OCP\PERMISSION_ALL){
            $aCalendar=CalendarCalendar::find($data['calendarid']);
             $calendar_options[0]['id']=$data['calendarid'];
             $calendar_options[0]['permissions']=$editInfo['permissions'];
             $calendar_options[0]['displayname']=$aCalendar['displayname'];
             $calendar_options[0]['calendarcolor']=$aCalendar['calendarcolor'];
             
        }else{
            $calendar_options = CalendarCalendar::allCalendars($this -> userId);
        }
        
        
        $category_options = CalendarApp::getCategoryOptions();
        $access_class_options = CalendarApp::getAccessClassOptions();
        $repeat_options = CalendarApp::getRepeatOptions();
        $repeat_end_options = CalendarApp::getEndOptions();
        $repeat_month_options = CalendarApp::getMonthOptions();
        $repeat_year_options = CalendarApp::getYearOptions();
        $repeat_weekly_options = CalendarApp::getWeeklyOptions();
        $repeat_weekofmonth_options = CalendarApp::getWeekofMonth();
        $repeat_byyearday_options = CalendarApp::getByYearDayOptions();
        $repeat_bymonth_options = CalendarApp::getByMonthOptions();
        $repeat_byweekno_options = CalendarApp::getByWeekNoOptions();
        $repeat_bymonthday_options = CalendarApp::getByMonthDayOptions();
        
        //NEW
        $repeat_weeklyshort_options = CalendarApp::getWeeklyOptionsShort();
        $repeat_advancedoptions = CalendarApp::getAdvancedRepeatOptions();
        $repeat_monthshort_options = CalendarApp::getByMonthShortOptions();
        
        //NEW Reminder
       $reminder_advanced_options = CalendarApp::getAdvancedReminderOptions();
       $reminder_time_options = CalendarApp::getReminderTimeOptions();
       
      
      
       
        $start = new \DateTime($editInfo['dtstart'], new \DateTimeZone('UTC'));
        
        $tWeekDay=Object::getWeeklyOptionsCheck($start->format('D'));
        $transWeekDay[$tWeekDay]=$tWeekDay;
        
        
       if ((string)$editInfo['rrule']['repeat'] !== 'doesnotrepeat') {
               
           $paramsRepeat = [
            'logicCheckWeekDay' => $tWeekDay,
            'rRadio0' => isset($editInfo['rrule']['rRadio0']) ? $editInfo['rrule']['rRadio0'] : 'checked="checked"',
            'rClass0' => isset($editInfo['rrule']['rClass0']) ? $editInfo['rrule']['rClass0'] : '',
            'rRadio1' => isset($editInfo['rrule']['rRadio1']) ? $editInfo['rrule']['rRadio1'] : '',
            'rClass1' => isset($editInfo['rrule']['rClass1']) ? $editInfo['rrule']['rClass1'] : 'class="ui-isDisabled"',
            'checkedMonth' => isset($editInfo['rrule']['checkedMonth']) ? $editInfo['rrule']['checkedMonth'] : '',
            'bdayClass' => isset($editInfo['rrule']['bdayClass']) ? $editInfo['rrule']['bdayClass'] : 'class="ui-isDisabled"',
            'repeat_rules' => isset($editInfo['rrule']['repeat_rules']) ? $editInfo['rrule']['repeat_rules'] : '',
            'advancedrepeat' =>isset($editInfo['rrule']['rAdvanced']) ? $editInfo['rrule']['rAdvanced'] : 'DAILY',
            'repeat_weekdaysSingle' => $transWeekDay,
            'repeat_weekdays' => isset($editInfo['rrule']['weekdays']) ? $editInfo['rrule']['weekdays'] : array(),
            'repeat_bymonthday' => isset($editInfo['rrule']['bymonthday']) ? $editInfo['rrule']['bymonthday'] : array(),
            'repeat_bymonth' =>  isset($editInfo['rrule']['bymonth']) ? $editInfo['rrule']['bymonth'] : array(),
            'repeat_weekofmonth' => isset($editInfo['rrule']['weekofmonth']) ? $editInfo['rrule']['weekofmonth'] : '1',
            'repeat_interval' => isset($editInfo['rrule']['interval']) ? $editInfo['rrule']['interval'] : '1',
            'repeat_end' => isset($editInfo['rrule']['end']) ? $editInfo['rrule']['end'] : 'never',
            'repeat_count' => isset($editInfo['rrule']['count']) ? $editInfo['rrule']['count'] : '10',
            'repeat_date' => isset($editInfo['rrule']['date']) ? $editInfo['rrule']['date'] : '',
           
        ];
        
       }else{
             
                $tDayOfMonth[$start->format('j')]=$start->format('j');
                $tMonth[$start->format('n')]=$start->format('n');
                
                $first_of_month = $start->format('Y-m-1');
                $day_of_first = date('N',$start->format('U'));
                $day_of_month = $start->format('j');
                $weekNum = floor(($day_of_first + $day_of_month - 1) / 7) + 1;
                if($weekNum >=1 && $weekNum <= 4) $weekNum='+'.$weekNum;
                else $weekNum='-1';
         
           $paramsRepeat = [
            'logicCheckWeekDay' => $tWeekDay,
            'rRadio0' => 'checked="checked"',
            'rClass0' => '',
            'rRadio1' => '',
            'rClass1' => 'class="ui-isDisabled"',
            'checkedMonth' => '',
            'bdayClass' => 'class="ui-isDisabled"',
            'repeat_rules' => '',
            'advancedrepeat' => 'DAILY',
            'repeat_weekdaysSingle' => $transWeekDay,
            'repeat_weekdays' => $transWeekDay,
            'repeat_bymonthday' => $tDayOfMonth,
            'repeat_bymonth' => $tMonth,
            'repeat_weekofmonth' => $weekNum,
            'repeat_interval' => 1,
            'repeat_end' => 'never',
            'repeat_count' => '10',
            'repeat_date' => '',
           
        ];
       }
      
    
       
      $params = [
        'eventid' => $id,
        'appname' => $this->appName,
        'permissions' => $editInfo['permissions'],
        'lastmodified' => $editInfo['lastmodified'],
        'calendar_options' => $calendar_options,
        'access_class_options' => $access_class_options,
        'repeat_options' => $repeat_options,
        'repeat_month_options' => $repeat_month_options,
        'repeat_weekly_options' => $repeat_weekly_options,
        'repeat_end_options' => $repeat_end_options,
        'repeat_year_options' => $repeat_year_options,
        'repeat_byyearday_options' => $repeat_byyearday_options,
        'repeat_bymonth_options' => $repeat_bymonth_options,
        'repeat_byweekno_options' => $repeat_byweekno_options,
        'repeat_bymonthday_options' => $repeat_bymonthday_options,
        'repeat_weekofmonth_options' => $repeat_weekofmonth_options,
        'repeat_advancedoptions' => $repeat_advancedoptions,
        'repeat_weeklyshort_options' => $repeat_weeklyshort_options,
        'repeat_monthshort_options' => $repeat_monthshort_options,
        'reminder_options' => $editInfo['reminder_options'],
        'reminder_advanced_options' => $reminder_advanced_options,
        'reminder_time_options' => $reminder_time_options,
        'reminder_advanced' => 'DISPLAY',
        'reminder_rules' => (array_key_exists('triggerRequest',$editInfo['alarm'])) ? $editInfo['alarm']['triggerRequest']:'',
        'reminder' => $editInfo['alarm']['action'],
        'remindertimeselect' => (array_key_exists('reminder_time_select',$editInfo['alarm'])) ? $editInfo['alarm']['reminder_time_select']:'',
        'remindertimeinput' => (array_key_exists('reminder_time_input',$editInfo['alarm'])) ? $editInfo['alarm']['reminder_time_input']:'',
        'reminderemailinput' => (array_key_exists('email',$editInfo['alarm'])) ? $editInfo['alarm']['email']:'',
        'reminderdate' => (array_key_exists('reminderdate',$editInfo['alarm'])) ? $editInfo['alarm']['reminderdate']:'',
        'remindertime' => (array_key_exists('remindertime',$editInfo['alarm'])) ? $editInfo['alarm']['remindertime']:'',
        'title' => $editInfo['summary'],
        'accessclass' => $editInfo['accessclass'],
        'location' => $editInfo['location'],
        'categories' => $editInfo['categories'],
        'calendar' => $data['calendarid'],
        'allday' => $editInfo['allday'],
        'startdate' => $editInfo['startdate'],
        'starttime' => $editInfo['starttime'],
        'enddate' => $editInfo['enddate'],
        'endtime' => $editInfo['endtime'],
        'description' => $editInfo['description'],
        'link' => $editInfo['link'],
        'addSingleDeleteButton' => $editInfo['addSingleDeleteButton'],
        'choosendate' => $choosenDate,
        'isShareApi' => \OC::$server->getAppConfig()->getValue('core', 'shareapi_enabled', 'yes'),
        'repeat' => $editInfo['rrule']['repeat'],
        'mailNotificationEnabled' => \OC::$server->getAppConfig() -> getValue('core', 'shareapi_allow_mail_notification', 'yes'),
        'allowShareWithLink' => \OC::$server->getAppConfig() -> getValue('core', 'shareapi_allow_links', 'yes'),
        'mailPublicNotificationEnabled' => \OC::$server->getAppConfig()->getValue('core', 'shareapi_allow_public_notification', 'no'),
        'sharetypeevent' => CalendarApp::SHAREEVENT,
        'sharetypeeventprefix' => CalendarApp::SHAREEVENTPREFIX
      ];
        
       
        $params = array_merge($params, $paramsRepeat);
      
       if ($editInfo['permissions'] & \OCP\PERMISSION_UPDATE) {
           $response = new TemplateResponse($this->appName, 'part.editevent',$params, '');  
        } elseif ($editInfo['permissions'] & \OCP\PERMISSION_READ) {
             //$response = new TemplateResponse('calendar', 'part.showevent',$params, '');  
        }
        
        return $response;
    }
    
     /**
     * @NoAdminRequired
     */
    public function editEvent() {
    	$id = (int) $this -> params('id');
		$pStart = $this -> params('viewstart');
		$pEnd = $this -> params('viewend');
		
		$postRequestAll = $this -> getParams();
		$calId = (int) $this -> params('calendar');
		$lastmodified = $this -> params('lastmodified');
		
		if(!array_key_exists('calendar', $postRequestAll)) {
			$calId = Object::getCalendarid($id);
		}
		
		$errarr = Object::validateRequest($postRequestAll);
		if($errarr) {
			$errarr['status'] = 'error';	
			$response = new JSONResponse($errarr);
			return $response;
			
		}else{
			$data = CalendarApp::getEventObject($id, false, false);
			$vcalendar = VObject::parse($data['calendardata']);
		
			CalendarApp::isNotModified($vcalendar->VEVENT, $lastmodified);
			Object::updateVCalendarFromRequest($postRequestAll, $vcalendar);
			Object::edit($id, $vcalendar->serialize());
			if ($data['calendarid'] !== $calId) {
				Object::moveToCalendar($id, $calId);
			}
			$editedEvent = CalendarApp::getEventObject($id, false, false);
			
			$start = new \DateTime($pStart);
			$end = new \DateTime($pEnd);
			
		    $events = CalendarApp::generateEventOutput($editedEvent, $start, $end);
			
			$params = ['status' => 'success',
			'data' =>[
				'id' => $id,
				'events' => $events 
			]
			];
			$response = new JSONResponse($params);
			return $response;
			
		}
			
			
    }

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 */
	public function getQuickInfoEvent(){
		 $id = $this -> params('id');
		 $data = CalendarApp::getEventObject($id, false, false);
		$start = new \DateTime($data['startdate']);
	    $showdate = $start -> format('Y-m-d H:i:s');
		 
		 $params = ['status' => 'success',
			'data' =>[
				'id' => $id,
				'startdate' => $showdate 
			]
			];
		
		$response = new JSONResponse();
		$response -> setData($params);
		return $response;
	}
	
	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 */
	public function getShowEvent() {
	   $id = $this -> params('id');
       $choosenDate = $this -> params('choosendate');
       
	   $data = CalendarApp::getEventObject($id, false, false);
       
       if (!$data) {
            exit ;
        }
        $object = VObject::parse($data['calendardata']);
        $vevent = $object -> VEVENT;
        
        $object = Object::cleanByAccessClass($id, $object);
        $accessclass = $vevent -> getAsString('CLASS');
        $permissions = CalendarApp::getPermissions($id, CalendarApp::EVENT, $accessclass);
        $dtstart = $vevent -> DTSTART;
        
        if (isset($choosenDate) && $choosenDate !== ''){
            $choosenDate = $choosenDate;
        } else {
            $choosenDate = $dtstart -> getDateTime() -> format('Ymd');
       }
		
        $showdate = $dtstart -> getDateTime() -> format('Y-m-d H:i:s');
		
        $organzier='';
        if($vevent->ORGANIZER){
            $organizerVal=$vevent -> getAsString('ORGANIZER');
            $temp=explode('mailto:',$organizerVal);
            $organzier=$temp[1];
        }
        $attendees='';
        if($vevent->ATTENDEE){
             $attendees=array();
             foreach($vevent->ATTENDEE as $key => $param){
                $temp1=explode('mailto:',$param);
                 $attendees[$key]=$temp1[1];
             }
        }
        
	
        $dtend = Object::getDTEndFromVEvent($vevent);
        
        $dtstartType = (string) $vevent->DTSTART->getValueType();
        
          $datetimedescr='';      
         if($dtstartType === 'DATE'){
            $startdate = $dtstart -> getDateTime() -> format('d-m-Y');
            $starttime = '';
            $enddate = $dtend -> getDateTime()-> modify('-1 day') -> format('d-m-Y');
            
            $endtime = '';
            $choosenDate=$choosenDate + (3600 * 24);
            $allday = true;
			
			if($startdate === $enddate){
				$datetimedescr = (string)$this -> l10n -> t('On').' '.$dtstart -> getDateTime() -> format('d.m.Y');
			}else{
				$datetimedescr= (string)$this->l10n -> t('From').' '.$dtstart -> getDateTime() -> format('d.m.Y').' '.(string)$this->l10n ->t('To').' '.$dtend -> getDateTime()-> modify('-1 day') -> format('d.m.Y');
			}
			
         }
         
         if($dtstartType === 'DATE-TIME'){
            $tz= CalendarApp::getTimezone();
            
            $start_dt = new \DateTime($data['startdate'], new \DateTimeZone('UTC'));
            $end_dt = new \DateTime($data['enddate'], new \DateTimeZone('UTC'));    
            $start_dt -> setTimezone(new \DateTimeZone($tz));
            $end_dt -> setTimezone(new \DateTimeZone($tz));
            $startdate = $start_dt -> format('d-m-Y');
            $starttime = $start_dt -> format('H:i');
            $enddate = $end_dt -> format('d-m-Y');
            $endtime = $end_dt -> format('H:i');
            if($startdate === $enddate){
				$datetimedescr = (string)$this -> l10n -> t('On').' '.$start_dt -> format('d.m.Y'). ' '.(string)$this -> l10n -> t('From').' '.$starttime.' '.(string)$this -> l10n -> t('To').' '.$endtime;
			}else{
				$datetimedescr = (string)$this -> l10n -> t('From').' '.$start_dt -> format('d.m.Y').' '.$starttime.' '.(string)$this -> l10n -> t('To').' '.$end_dt -> format('d.m.Y').' '.$endtime;
				
			}
            $allday = false;
         }
       
        $summary = strtr($vevent -> getAsString('SUMMARY'), array('\,' => ',', '\;' => ';'));
        $location = strtr($vevent -> getAsString('LOCATION'), array('\,' => ',', '\;' => ';'));
        $categories = $vevent -> getAsArray('CATEGORIES');
        $description = strtr($vevent -> getAsString('DESCRIPTION'), array('\,' => ',', '\;' => ';'));
        $link = strtr($vevent -> getAsString('URL'), array('\,' => ',', '\;' => ';'));
        
        $categoriesOut='';
        if(is_array($categories)){
            foreach($categories as $category){
                $bgColor=CalendarApp::genColorCodeFromText(trim($category),80);
                $categoriesOut[]=array('name'=>$category,'bgcolor'=>$bgColor, 'color' => CalendarApp::generateTextColor($bgColor));
            }
        }
        
        $last_modified = $vevent -> __get('LAST-MODIFIED');
        if ($last_modified) {
            $lastmodified = $last_modified -> getDateTime() -> format('U');
        } else {
            $lastmodified = 0;
        }
    
        $addSingleDeleteButton = false;
        $repeatInfo = array();
        $repeat['repeat'] = '';
		
        if ((int)$data['repeating'] === 1) {
            $addSingleDeleteButton = true;
            $rrule = explode(';', $vevent -> getAsString('RRULE'));
            $rrulearr = array();
        
            $repeat['repeat_rules'] = '';
            foreach ($rrule as $rule) {
                list($attr, $val) = explode('=', $rule);
                if ((string)$attr !== 'COUNT' && (string)$attr !== 'UNTIL') {
                    if ($repeat['repeat_rules'] === ''){
                        $repeat['repeat_rules'] = $attr . '=' . $val;
                        } else{
                        $repeat['repeat_rules'] .= ';' . $attr . '=' . $val;
						}
                }
                if ((string)$attr === 'COUNT' || (string)$attr !== 'UNTIL') {
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
        
            $repeat_end_options = CalendarApp::getEndOptions();
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
        
        if ($permissions !== \OCP\PERMISSION_ALL) {
            $calendar_options[0]['id'] = $data['calendarid'];
            
        } else {
            $calendar_options =CalendarCalendar::allCalendars($this -> userId);
        }
        
        $checkCatCache = '';
        if (\OCP\User::isLoggedIn()) {
            $category_options = CalendarApp::loadTags();
            
            foreach ($category_options['tagslist'] as $catInfo) {
                   $checkCatCache[$catInfo['name']] = $catInfo['bgcolor'];
             }
        }
    
        $access_class_options = CalendarApp::getAccessClassOptions();

        $aOExdate = '';
        if ($vevent -> EXDATE) {
        
            $timezone = CalendarApp::getTimezone();
        
            foreach ($vevent->EXDATE as $param) {
                $param = new \DateTime($param);
                $aOExdate[$param -> format('U')] = $param -> format('d-m-Y');
            }
        
        }
        
        //NEW Reminder
        $reminder_options = CalendarApp::getReminderOptions();
        $reminder_time_options = CalendarApp::getReminderTimeOptions();
        //reminder
        
        $sAlarm='';
        $count='';
        if ($vevent -> VALARM) {
            
            $valarm='';
            $sAlarm='';
             $counter=0;
            
            foreach ($vevent ->getComponents() as $param) {
                if($param->name === 'VALARM'){
                    $attr = $param->children();
                    foreach($attr as $attrInfo){
                        $valarm[$counter][$attrInfo->name]=$attrInfo->getValue();
                    }
                    $counter++;
                }
            }
            foreach($valarm as $vInfo){
                if($vInfo['ACTION'] === 'DISPLAY' && strstr($vInfo['TRIGGER'],'P')){
                    if(substr_count($vInfo['TRIGGER'],'PT') === 1 && !stristr($vInfo['TRIGGER'],'TRIGGER')){
                    	 $sAlarm[]='TRIGGER:'.$vInfo['TRIGGER'];
                    }
					if(substr_count($vInfo['TRIGGER'],'PT') === 1 && stristr($vInfo['TRIGGER'],'TRIGGER')){
                    	 $sAlarm[]=$vInfo['TRIGGER'];
                    }
				    if(substr_count($vInfo['TRIGGER'],'-P') === 1 && substr_count($vInfo['TRIGGER'],'PT') === 0){
                    	 $temp=explode('-P',(string)$vInfo['TRIGGER']);
						 $sAlarm[]='TRIGGER:-PT'.$temp[1];
                    }
					if(substr_count($vInfo['TRIGGER'],'+P') === 1 && substr_count($vInfo['TRIGGER'],'PT') === 0){
                    	$temp=explode('+P',$vInfo['TRIGGER']);
						 $sAlarm[]='TRIGGER:+PT'.$temp[1];
                    }
				   
                }
                if($vInfo['ACTION'] === 'DISPLAY' && !strstr($vInfo['TRIGGER'],'P')){
                    if(!strstr($vInfo['TRIGGER'],'DATE-TIME'))  {
                        $sAlarm[]='TRIGGER;VALUE=DATE-TIME:'.$vInfo['TRIGGER'];
                    }else{
                        $sAlarm[]=$vInfo['TRIGGER'];
                    }
                    
                }
            }

        }
        
        if ($permissions & \OCP\PERMISSION_READ) {
            $bShareOnlyEvent = 0;
            if (Object::checkShareEventMode($id)) {
                $bShareOnlyEvent = 1;
            }
            
            $pCategoriesCol = '';
            $pCategories ='';
            if(is_array($categoriesOut) && count($categoriesOut)>0){
                $pCategoriesCol = $checkCatCache;
                $pCategories =$categoriesOut;
            }
           
            $pRepeatInfo='';
            if ($repeat['repeat'] !== 'doesnotrepeat') {
                $pRepeatInfo = $repeatInfo;
            }
            
            $params= [
                'bShareOnlyEvent' => $bShareOnlyEvent,
                'eventid' => $id,
                'appname' => $this->appName,
                'permissions' => $permissions,
                'lastmodified' => $lastmodified,
                'exDate' => $aOExdate,
                'calendar_options' => $calendar_options,
                'access_class_options' => $access_class_options,
                'sReminderTrigger' => $sAlarm,
                'cValarm' => $count,
                'isShareApi' => \OC::$server->getAppConfig() -> getValue('core', 'shareapi_enabled', 'yes'),
                'mailNotificationEnabled' => \OC::$server->getAppConfig() -> getValue('core', 'shareapi_allow_mail_notification', 'yes'),
                'allowShareWithLink' => \OC::$server->getAppConfig() -> getValue('core', 'shareapi_allow_links', 'yes'),
                'mailPublicNotificationEnabled' => \OC::$server->getAppConfig()->getValue('core', 'shareapi_allow_public_notification', 'no'), 
                'title' => $summary,
                'accessclass' => $accessclass,
                'location' => $location,
                'categoriesCol' => $pCategoriesCol,
                'categories' => $pCategories,
                'aCalendar' => CalendarApp::getCalendar($data['calendarid'], false, false),
                'allday' => $allday,
                'startdate' => $startdate,
                'starttime' => $starttime,
                'enddate' => $enddate,
                'endtime' => $endtime,
                'datetimedescr' => $datetimedescr,
                'description' => $description,
                'link' => $link,
                'organzier' => $organzier,
                'attendees' => $attendees,
                'addSingleDeleteButton' => $addSingleDeleteButton,
                'choosendate' => $choosenDate,
                 'showdate' => $showdate,
                'repeat' => $repeat['repeat'],
                'repeat_rules' => isset($repeat['repeat_rules']) ? $repeat['repeat_rules'] : '',
                'repeatInfo' => $pRepeatInfo,
                'sharetypeevent' => CalendarApp::SHAREEVENT,
                'sharetypeeventprefix' => CalendarApp::SHAREEVENTPREFIX
            ];

            
            $response = new TemplateResponse($this->appName, 'part.showevent', $params,'');
            return $response;
        } 
    
		
		
	}
	
	/**
	 * @NoAdminRequired
	 */
	public function deleteEvent() {
		$id = $this -> params('id');
		Object::delete($id);
		$response = new JSONResponse();
		return $response;
	}
	
	/**
	 * @NoAdminRequired
	 */
	public function deleteSingleRepeatingEvent() {
		$id = $this -> params('id');
		$pStart = $this -> params('viewstart');
		$pEnd = $this -> params('viewend');
		$choosenDate = $this -> params('choosendate');
		$choosenDate=date('Ymd',$choosenDate);
		$choosenDate1=$choosenDate;
		
		$data = CalendarApp::getEventObject($id, false, false);
		$vcalendar = VObject::parse($data['calendardata']);
		$vevent = $vcalendar->VEVENT;
		
		$vevent->setDateTime('LAST-MODIFIED', 'now');
		$vevent->setDateTime('DTSTAMP', 'now');
		$dtstart = $vevent->DTSTART;
		$start_type = (string) $dtstart->getValueType();
		
		if ($start_type === 'DATE') {
			    $dateTime = new \DateTime($choosenDate);
			   if ($dateTime instanceof \DateTime) {
					$vevent->addProperty('EXDATE',$dateTime);
			   }
			    
		}else{
		       $sTimezone = CalendarApp::getTimezone();
	 	       $dStartTime=$vevent->DTSTART;
			   $sTime = $dStartTime -> getDateTime() -> format('H:i');
		       $dateTime = new \DateTime($choosenDate.' '.$sTime ,new \DateTimeZone($sTimezone));
			  
			    if ($dateTime instanceof \DateTime) {
					$vevent->addProperty('EXDATE',$dateTime);
				}
		}
		
		
		Repeat::update($id);
		Object::edit($id, $vcalendar->serialize());
		
		$editedEvent = CalendarApp::getEventObject($id, false, false);
			
		$start = new \DateTime($pStart);
		$end = new \DateTime($pEnd);
		
	    $events = CalendarApp::generateEventOutput($editedEvent, $start, $end);
		
		$params = ['status' => 'success',
			'data' =>[
				'id' => $id,
				'events' => $events 
			]
			];
		
		$response = new JSONResponse();
		$response -> setData($params);
		return $response;
	}
	
	/**
	 * @NoAdminRequired
	 */
	public function addSubscriberEvent() {
		
		$eventId = $this -> params('eventId');
		$pEmails = $this -> params('emails');
		$ExistAttendees = $this -> params('attendees');

		$emails=array_map('trim',explode(",",$pEmails));
		
		if(is_array($ExistAttendees)){
			$testEmailArray=array();	
			foreach($ExistAttendees as $val){
				$testEmailArray[$val]=1;
			}
		}
		
		$data = CalendarApp::getEventObject($eventId, false, false);
		$vcalendar = VObject::parse($data['calendardata']);
		$vevent = $vcalendar->VEVENT;
		$vevent->setDateTime('LAST-MODIFIED', 'now');
		$vevent->setDateTime('DTSTAMP', 'now');
		if(!$vevent->ORGANIZER){
			$vevent->add('ORGANIZER','mailto:'.$this->configInfo -> getUserValue($this -> userId, 'settings', 'email'));
		}
		$sendEmails=array();
		foreach($emails as $email){
			if(!$testEmailArray[$email]) {
				$vevent->add('ATTENDEE','mailto:'.$email);
				$sendEmails[]=$email;
			}
		}
		
		Object::edit($eventId, $vcalendar->serialize());
		
		$summary = $data['summary'];
		$dtstart = $data['startdate'];
		$dtend = $data['enddate'];
		
		if(count($sendEmails)>0){
		   CalendarApp::sendEmails($eventId, $summary, $dtstart, $dtend,$sendEmails);
			$msg='sent';
		}else{
			$msg='notsent';
		}	
		
		$params=[
			'message' => $msg,
		];	
		
		$response = new JSONResponse();
		$response -> setData($params);
		return $response;
	}
	
	/**
     * @PublicPage
	 * @NoCSRFRequired
	  * @UseSession
     */
	public function getReminderEvents() {
				
			$EvSource = $this -> params('EvSource');
				
			$ALARMDATA = new Alarm();
			$resultRefresh='';
			if(isset($EvSource) && $EvSource !== '') {
				$ALARMDATA->setEventSources($EvSource);
				$resultRefresh = $ALARMDATA->checkAutoRefresh();
				if($resultRefresh === false){
					 $resultRefresh='onlyTimeLine';
				}
			}else{
				$resultRefresh='onlyTimeLine';
			}
			$result='';
			if (\OCP\User::isLoggedIn()) {
				$ALARMDATA->checkAlarm();
				$result=$ALARMDATA->getAlarms();
			}
		
			if(count($result)>0 || $resultRefresh !== ''){
					
				$params=[
					'data' => $result,
					'refresh' => $resultRefresh
				];	
				
				$response = new JSONResponse();
				$response -> setData($params);
				return $response;	
				
			}
	}
    /**
	 * @NoAdminRequired
	 */
	public function sendEmailEventIcs() {
		$eventId = $this -> params('eventId');
		$pEmails = $this -> params('emails');
		$emails=array_map('trim',explode(",",$pEmails));
		
		$event = CalendarApp::getEventObject($eventId);
		if($event === false || $event === null) {
			$errarr['status'] = 'error';	
			$response = new JSONResponse($errarr);
			return $response;
		}
		
		$summary = $event['summary'];
		$dtstart = $event['startdate'];
		$dtend = $event['enddate'];
		
		CalendarApp::sendEmails($eventId, $summary, $dtstart, $dtend,$emails);
		
		$errarr['status'] = 'success';	
		$response = new JSONResponse($errarr);
		return $response;
	}
	
	
    private function parseRrules($rrule){
        $rrulearr = array();
    
        $repeat['repeat_rules']=''; 
            foreach ($rrule as $rule) {
                list($attr, $val) = explode('=', $rule);
                if((string)$attr !== 'COUNT'){
                    if($repeat['repeat_rules'] === ''){
                    	 $repeat['repeat_rules']=$attr.'='.$val; 
                    }else{
                    	 $repeat['repeat_rules'].=';'.$attr.'='.$val;
					}
                }
            }
            foreach ($rrule as $rule) {
                list($attr, $val) = explode('=', $rule);
                $rrulearr[$attr] = $val;
            }
            if (!isset($rrulearr['INTERVAL']) || $rrulearr['INTERVAL'] === '') {
                $rrulearr['INTERVAL'] = 1;
            }
            if (array_key_exists('BYDAY', $rrulearr)) {
                if (substr_count($rrulearr['BYDAY'], ',') === 0) {
                    if (strlen($rrulearr['BYDAY']) === 2) {
                        $repeat['weekdays'][$rrulearr['BYDAY']] = $rrulearr['BYDAY'];
                    } elseif (strlen($rrulearr['BYDAY']) === 3) {
                        $repeat['weekofmonth'] = substr($rrulearr['BYDAY'], 0, 1);
                        $repeat['weekdays'][substr($rrulearr['BYDAY'], 1, 2)] = substr($rrulearr['BYDAY'], 1, 2);
                    } elseif (strlen($rrulearr['BYDAY']) === 4) {
                        $repeat['weekofmonth'] = substr($rrulearr['BYDAY'], 0, 2);
                        $repeat['weekdays'][substr($rrulearr['BYDAY'], 2, 2)] = substr($rrulearr['BYDAY'], 2, 2);
                    }
                } else {
                    $byday_days = explode(',', $rrulearr['BYDAY']);
                    foreach ($byday_days as $byday_day) {
                        if (strlen($byday_day) === 2) {
                            $repeat['weekdays'][$byday_day] = $byday_day;
                        } elseif (strlen($byday_day) === 3) {
                            $repeat['weekofmonth'] = substr($byday_day, 0, 1);
                            $repeat['weekdays'][substr($byday_day, 1, 2)] = substr($byday_day, 1, 2);
                        } elseif (strlen($byday_day) === 4) {
                            $repeat['weekofmonth'] = substr($byday_day, 0, 2);
                            $repeat['weekdays'][substr($byday_day, 2, 2)] = substr($byday_day, 2, 2);
                        }
                    }
                }
            }
            if (array_key_exists('BYMONTHDAY', $rrulearr)) {
                if (substr_count($rrulearr['BYMONTHDAY'], ',') === 0) {
                    $repeat['bymonthday'][$rrulearr['BYMONTHDAY']] = $rrulearr['BYMONTHDAY'];
                } else {
                    $bymonthdays = explode(',', $rrulearr['BYMONTHDAY']);
                    foreach ($bymonthdays as $bymonthday) {
                        $repeat['bymonthday'][$bymonthday] = $bymonthday;
                    }
                }
            }
          
            if (array_key_exists('BYMONTH', $rrulearr)) {
                 //Fix
                if (substr_count($rrulearr['BYMONTH'], ',') === 0) {
                    $repeat['bymonth'][(string)$rrulearr['BYMONTH']] = (string)$rrulearr['BYMONTH'];
                } else {
                    $bymonth = explode(',', $rrulearr['BYMONTH']);
                    foreach ($bymonth as $month) {
                        $repeat['bymonth'][$month] = $month;
                    }
                }
                //$repeat['bymonthday'][] =$dtstart -> getDateTime() -> format('d');
            }
            switch($rrulearr['FREQ']) {
                case 'DAILY' :
                       if((int)$repeat['interval'] === 1){
                          $repeat['repeat'] = 'DAILY';
                       }else{
                           $repeat['repeat'] = 'OWNDEF';
                       }
                    break;
                case 'WEEKLY' :
                    if (array_key_exists('BYDAY', $rrulearr) === false) {
                        $rrulearr['BYDAY'] = '';
                        $repeat['repeat'] = 'WEEKLY';
                    } else {
                        $repeat['repeat'] = 'OWNDEF';
                        $repeat['rAdvanced'] = 'WEEKLY';
                    }
                    break;
                case 'MONTHLY' :
                    $repeat['repeat'] = 'MONTHLY';
                    if (array_key_exists('BYDAY', $rrulearr)) {
                        $repeat['rRadio0']='';
                        $repeat['rClass0']='class="ui-isDisabled"';
                        $repeat['rRadio1']='checked="checked"';
                        $repeat['rClass1']='';
                        $repeat['rAdvanced'] = 'MONTHLY';
                        $repeat['repeat'] = 'OWNDEF';
                    } elseif (array_key_exists('BYMONTHDAY', $rrulearr)) {
                        $repeat['rRadio0']='checked="checked"';
                        $repeat['rClass0']='';
                        $repeat['rRadio1']='';
                        $repeat['rClass1']='class="ui-isDisabled"';
                        $repeat['rAdvanced'] = 'MONTHLY';
                        $repeat['repeat'] = 'OWNDEF';
                    }else {
                        $repeat['rRadio0']='checked="checked"';
                        $repeat['rClass0']='';
                        $repeat['rRadio1']='';
                        $repeat['rClass1']='class="ui-isDisabled"';
                        
                    }
                    break;
                case 'YEARLY' :
                    //Fix
                    $repeat['repeat'] = 'YEARLY';
                    if (array_key_exists('BYMONTH', $rrulearr) && array_key_exists('BYDAY', $rrulearr)) {
                        $repeat['checkedMonth']='checked';
                        $repeat['bdayClass']='';
                        $repeat['repeat'] = 'OWNDEF';
                        $repeat['rAdvanced'] = 'YEARLY';
                    }elseif (array_key_exists('BYMONTH', $rrulearr) && array_key_exists('BYDAY', $rrulearr) === false) {
                        $repeat['checkedMonth']='';
                        $repeat['repeat'] = 'OWNDEF';
                        $repeat['rAdvanced'] = 'YEARLY';
                        $repeat['bdayClass']='class="ui-isDisabled"';
                    }else {
                        $repeat['year'] = '';
                    }
            }
            $repeat['interval'] = $rrulearr['INTERVAL'];
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
            if (array_key_exists('weekdays', $repeat)) {
                $repeat_weekdays_ = array();
                $days = CalendarApp::getWeeklyOptions();
                foreach ($repeat['weekdays'] as $weekday) {
                    $repeat_weekdays[$weekday] = $weekday;
                }
                $repeat['weekdays'] = $repeat_weekdays;
            }
        
        return $repeat;
    }
    
    private function parseValarm($vevent, $reminder_options){
       
        $aAlarm='';
        $valarm='';
			
        if($vevent -> VALARM){
        	$counter=0;
            $tAlarm='';
            foreach ($vevent ->getComponents() as $param) {
                if($param->name === 'VALARM'){
                    $attr = $param->children();
                    foreach($attr as $attrInfo){
                        $tAlarm[$counter][$attrInfo->name]=(string) $attrInfo->getValue();
                    }
                    $counter++;
                }
            }
			
			
			if(count($tAlarm)>1){
            	$valarm=$tAlarm[1];
			}else{
				$valarm=$tAlarm[0];
			}
			
            $aAlarm['action']= $valarm['ACTION'];
            $aAlarm['triggerRequest'] = $valarm['TRIGGER'];
            $tempTrigger=(string)$aAlarm['triggerRequest'];
			
           	 if(substr_count($tempTrigger,'PT') === 1 && stristr($tempTrigger,'TRIGGER')){
           	 	$temp=explode('TRIGGER:',$tempTrigger);	
           	 	$aAlarm['trigger']=$temp[1];
				$aAlarm['triggerRequest']=$aAlarm['trigger'];
           	 }
 			
 			if(substr_count($tempTrigger,'PT') === 1 && !stristr($tempTrigger,'TRIGGER')){
           	 	$aAlarm['trigger']=$tempTrigger;
				$aAlarm['triggerRequest']=$aAlarm['trigger'];
           	 }
			
           	 if(substr_count($tempTrigger,'-P') === 1 && substr_count($tempTrigger,'PT') === 0){
           	 	$temp=explode('-P',$tempTrigger);
				$aAlarm['trigger']='-PT'.$temp[1];
				$aAlarm['triggerRequest']=$aAlarm['trigger'];
				
           	 }
			 if(substr_count($tempTrigger,'+P') === 1 && substr_count($tempTrigger,'PT') === 0){
           	 	$temp=explode('+P',$tempTrigger);
				$aAlarm['trigger']='+PT'.$temp[1];
				$aAlarm['triggerRequest']=$aAlarm['trigger'];
           	 }
			 
           	 if(!strstr($tempTrigger,'P')){
               //\OCP\Util::writeLog('calendar', 'ALARM TRIGGER TIME-> '.$tempTrigger, \OCP\Util::DEBUG);
			    $aAlarm['trigger']=$tempTrigger;
                $aAlarm['triggerRequest']=$tempTrigger;
            }
            
            $aAlarm['email']='';
            if(array_key_exists('ATTENDEE',$valarm)){
                $aAlarm['email']=$valarm['ATTENDEE'];
                if(stristr($aAlarm['email'],'mailto:')) $aAlarm['email']=substr($aAlarm['email'],7,strlen($aAlarm['email']));
            }
       
           if(array_key_exists($aAlarm['trigger'],$reminder_options)){
               $aAlarm['action']=$aAlarm['trigger'];
               $aAlarm['reminderdate'] ='';
               $aAlarm['remindertime'] = '';
               
           }else{
              $aAlarm['action']='OWNDEF';
          		
                if(stristr($aAlarm['trigger'],'PT')){
                  
				        $tempDescr='';
                        $aAlarm['reminderdate'] ='';
                        $aAlarm['remindertime'] = '';
                        if(stristr($aAlarm['trigger'],'-PT')){
                            $tempDescr='before';
                        }
                        if(stristr($aAlarm['trigger'],'+PT')){
                            $tempDescr='after';
                        }
                        
                        //GetTime
                        $TimeCheck=substr($aAlarm['triggerRequest'],3,strlen($aAlarm['triggerRequest']));
                        
                        $aAlarm['reminder_time_input']=substr($TimeCheck,0,(strlen($TimeCheck)-1));
                        
                        //returns M,H,D
                        $alarmTimeDescr=substr($aAlarm['trigger'],-1,1);
                        if($alarmTimeDescr === 'H'){
                            $aAlarm['reminder_time_select']='hours'.$tempDescr;
                            
                        }
                        if($alarmTimeDescr === 'M'){
                            $aAlarm['reminder_time_select']='minutes'.$tempDescr;
                        }
                        if($alarmTimeDescr === 'D'){
                            $aAlarm['reminder_time_select']='days'.$tempDescr;
                        }
						 if($alarmTimeDescr === 'W'){
                            $aAlarm['reminder_time_select']='weeks'.$tempDescr;
                        }
                }else{
                   
                    
					$tDttriggertime=explode('TRIGGER;VALUE=DATE-TIME:',$aAlarm['triggerRequest']);
					$tDttriggertime = $tDttriggertime[1];
					
                    if(strlen($tDttriggertime) === 8){
                         $dttriggertime= new \DateTime($tDttriggertime);		
                        $aAlarm['reminderdate'] = $dttriggertime  -> format('d-m-Y');
                        $aAlarm['remindertime'] ='';
                    }
                    if(strlen($tDttriggertime)>8){
                        $dttriggertime= new \DateTime($tDttriggertime);	
                        $aAlarm['reminderdate'] = $dttriggertime -> format('d-m-Y');
                        $aAlarm['remindertime'] = $dttriggertime -> format('H:i');
                    }
                    $aAlarm['reminder_time_input']='';
                    $aAlarm['reminder_time_select']='ondate';
                }
               
            }
            
        }else{
            $aAlarm['action']='none';
        }
        
        return $aAlarm;
    }


    private function getVobjectData($id, $choosenDate, $data){
       
       $result=[];
       
        if (!$data) {
            return false;
        }
        
        $object =VObject::parse($data['calendardata']);
        $vevent = $object -> VEVENT;
        $object = Object::cleanByAccessClass($id, $object);
        
        $result['accessclass'] = $vevent -> getAsString('CLASS');
        $result['permissions'] = CalendarApp::getPermissions($id, CalendarApp::EVENT, $result['accessclass']);
        
        $dtstart = $vevent -> DTSTART;
        $result['dtstart'] = $dtstart;
        
        $dtend = Object::getDTEndFromVEvent($vevent);
        $dateStartType= (string)$vevent->DTSTART->getValueType();
                
         if($dateStartType === 'DATE'){
            $result['startdate'] = $dtstart -> getDateTime() -> format('d-m-Y');
            $result['starttime'] = '';
            $result['enddate'] = $dtend -> getDateTime()-> modify('-1 day') -> format('d-m-Y');
             $result['endtime'] = '';
            $result['choosenDate'] = $choosenDate + (3600 * 24);
            $result['allday'] = true;
         }
         
         if($dateStartType === 'DATE-TIME'){
            $tz= CalendarApp::getTimezone();
            $start_dt = new \DateTime($data['startdate'], new \DateTimeZone('UTC'));
            $end_dt = new \DateTime($data['enddate'], new \DateTimeZone('UTC'));    
            $start_dt -> setTimezone(new \DateTimeZone($tz));
            $end_dt -> setTimezone(new \DateTimeZone($tz));
            $result['startdate'] = $start_dt -> format('d-m-Y');
            $result['starttime'] = $start_dt -> format('H:i');
            $result['enddate'] = $end_dt -> format('d-m-Y');
            $result['endtime']  = $end_dt -> format('H:i');    
            $result['allday'] = false;
            $result['choosenDate'] = $choosenDate;
         }

        $result['summary'] = strtr($vevent -> getAsString('SUMMARY'), array('\,' => ',', '\;' => ';'));
        $result['location'] = strtr($vevent -> getAsString('LOCATION'), array('\,' => ',', '\;' => ';'));
        $result['categories'] = $vevent -> getAsString('CATEGORIES');
        $result['description'] = strtr($vevent -> getAsString('DESCRIPTION'), array('\,' => ',', '\;' => ';'));
        $result['link'] = strtr($vevent -> getAsString('URL'), array('\,' => ',', '\;' => ';'));
        
        $last_modified = $vevent -> __get('LAST-MODIFIED');
        if ($last_modified) {
             $result['lastmodified'] = $last_modified -> getDateTime() -> format('U');
        } else {
             $result['lastmodified'] = 0;
        }
        
         $result['addSingleDeleteButton']=false;
        if ((int)$data['repeating'] === 1) {
            $result['addSingleDeleteButton']=true;
            $rrule = explode(';', $vevent -> getAsString('RRULE'));
            $result['rrule']= $this -> parseRrules($rrule);
        } else {
            $result['rrule']['repeat'] = 'doesnotrepeat';
        }
        //NEW Reminder
        
        $result['reminder_options'] = CalendarApp::getReminderOptions();
        $result['alarm'] = $this -> parseValarm($vevent, $result['reminder_options']);
        
        
        return $result;
    }
	
}
