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
use \OCA\CalendarPlus\Import;

use \OCP\AppFramework\Controller;
use \OCP\AppFramework\Http\JSONResponse;
use \OCP\AppFramework\Http\TemplateResponse;
use \OCP\IRequest;
use \OCP\Share;
use \OCP\IConfig;

class CalendarController extends Controller {

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
	public function getNewFormCalendar() {
	
		$calendar = [
			'id' => 'new',
			'displayname' => '',
			'calendarcolor' => '',
			'externuri' => '',
		];
		
		$params=[
			'new' => true,
			'calendar' => $calendar,
			'calendarcolor_options' => CalendarCalendar::getCalendarColorOptions(),
		];
		
		$response = new TemplateResponse($this->appName, 'part.editcalendar',$params, '');  
        
        return $response;
		
	}
	
	
	
	/**
	 * @NoAdminRequired
	 */
	public function newCalendar() {
		$calendarName = (string) $this -> params('name');	
		$externUriFile = (string) $this -> params('externuri');
		$pColor = (string) $this -> params('color');
		
		if(trim($calendarName) === '') {
			$params = [
			'status' => 'error',
			];
			$response = new JSONResponse($params);
			return $response;
		}
		
		$calendars =CalendarCalendar::allCalendars($this->userId);
		foreach($calendars as $cal) {
			if($cal['displayname'] === $calendarName) {
				$params = [
				'status' => 'error',
				'message' => 'namenotavailable'
				];
				$response = new JSONResponse($params);
				return $response;
			}
		}
		
		$bError=false;
		
		$count=false;
		
		if(trim($externUriFile) !== '') {
			$aResult=$this->addEventsFromSubscribedCalendar($externUriFile, $calendarName, $pColor);
			if($aResult['isError'] === true){
				$bError=true;
			}
			if($aResult['countEvents'] > 0){
				$count = $aResult['countEvents'];
			}
			$calendarid = $aResult['calendarid'];
		}else{
		   $calendarid = CalendarCalendar::addCalendar($this->userId, $calendarName, 'VEVENT,VTODO,VJOURNAL', null, 0, $pColor);
		   CalendarCalendar::setCalendarActive($calendarid, 1);
		}
		
		if(!$bError){
			$calendar = CalendarCalendar::find($calendarid);
			$isShareApiActive=\OC::$server->getAppConfig()->getValue('core', 'shareapi_enabled', 'yes');
			
			$paramsList = [
				'calendar' => $calendar,
				'appname' => $this->appName,
				'shared' => false,
				'isShareApi' => $isShareApiActive,
			];
			$calendarRow = new TemplateResponse($this->appName, 'part.choosecalendar.rowfields', $paramsList, '');
			
			$params = [
				'status' => 'success',
				'eventSource' => CalendarCalendar::getEventSourceInfo($calendar),
				'calid' => $calendar['id'],
				'countEvents'=>$count,
				'page' => $calendarRow->render(),
			];
			$response = new JSONResponse($params);
			return $response;		
				
		}else{
		  $params = [
			'status' => 'error',
			'message' => $this -> l10n->t('Import failed')
			];
			$response = new JSONResponse($params);
			return $response;	
		}
		
	}

	/**
	 * @NoAdminRequired
	 */
	public function getEditFormCalendar() {
		$calId = (int) $this -> params('calendarid');
		
		$calendar = CalendarApp::getCalendar($calId, true, true);
		
		$params=[
			'new' => false,
			'calendar' => $calendar,
			'calendarcolor_options' => CalendarCalendar::getCalendarColorOptions(),
		];
		
		$response = new TemplateResponse($this->appName, 'part.editcalendar',$params, '');  
        
        return $response;
		
	}
	
	/**
	 * @NoAdminRequired
	 */
	public function editCalendar() {
		
		$calendarid = (int) $this -> params('id');
		$pName = (string) $this -> params('name');
		$pActive = (int) $this -> params('active');
		$pColor = (string) $this -> params('color');	
			
			
		if(trim($pName) === '') {
				
			$params = [
				'status' => 'error',
				'message' => 'empty'
			];
			
			$response = new JSONResponse($params);
			return $response;	
			
		}
		
		$calendars = CalendarCalendar::allCalendars($this -> userId);
		foreach($calendars as $cal) {
			if($cal['userid'] !== $this -> userId){
				continue;
			}
			
			if($cal['displayname'] === $pName && (int)$cal['id'] !== $calendarid) {
				$params = [
					'status' => 'error',
					'message' => 'namenotavailable'
				];
				
				$response = new JSONResponse($params);
				return $response;		
			}
		}
			
		try {
			CalendarCalendar::editCalendar($calendarid, strip_tags($pName), null, null, null, $pColor, null);
			CalendarCalendar::setCalendarActive($calendarid, $pActive);
		} catch(Exception $e) {
				$params = [
					'status' => 'error',
					'message' => $e->getMessage()
				];
				
				$response = new JSONResponse($params);
				return $response;				
		}
		
		$calendar = CalendarCalendar::find($calendarid);
		$isShareApiActive=\OC::$server->getAppConfig()->getValue('core', 'shareapi_enabled', 'yes');
		
		$shared = false;
		if ($calendar['userid'] !== $this -> userId) {
			$sharedCalendar = \OCP\Share::getItemSharedWithBySource(CalendarApp::SHARECALENDAR, CalendarApp::SHARECALENDARPREFIX. $calendarid);
			if ($sharedCalendar && ($sharedCalendar['permissions'] & \OCP\PERMISSION_UPDATE)) {
				$shared = true;
			}
		}
		
		$paramsList =[
			'calendar' => $calendar,
			'shared' => $shared,
			'appname' => $this->appName,
			'isShareApi' => $isShareApiActive,
		];
		$calendarRow = new TemplateResponse($this->appName, 'part.choosecalendar.rowfields', $paramsList, '');
		
		$params = [
				'status' => 'success',
				'eventSource' => CalendarCalendar::getEventSourceInfo($calendar),
				'calid' => $calendarid,
				'countEvents'=> false,
				'page' => $calendarRow->render(),
			];
			
			$response = new JSONResponse($params);
			return $response;	
		
	}
	
	/**
	 * @NoAdminRequired
	 */
	public function deleteCalendar() {
			
		$calId = (int) $this -> params('calendarid');
		$del = CalendarCalendar::deleteCalendar($calId);
		if($del === true) {
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
	 * @NoAdminRequired
	 */
	public function setMyActiveCalendar() {
			
		$calendarid = (int) $this -> params('calendarid');
		$this -> configInfo -> setUserValue($this -> userId, $this->appName, 'choosencalendar', $calendarid);
		
		$params = [
		'status' => 'success',
		'choosencalendar' => $calendarid
		];	
		
		$response = new JSONResponse($params);
		return $response;
	}
	
	/**
	 * @NoAdminRequired
	 */
	public function setActiveCalendar() {
			
		$calendarid = $this -> params('calendarid');
		$pActive = intval($this -> params('active'));
		
		$calendar=false;
		if($calendarid !== 'birthday_'.$this -> userId) {
			$calendar = CalendarApp::getCalendar((int)$calendarid, true,true);
		}
		
		if(!$calendar && $calendarid !== 'birthday_'.$this -> userId) {
			$params = [
			'status' => 'error',
			'message' => 'permission denied'
			];	
			$response = new JSONResponse($params);
			return $response;
		}
		
		CalendarCalendar::setCalendarActive($calendarid,(int) $pActive);
		
		$isAktiv = $pActive;
		
		if($this -> configInfo -> getUserValue($this -> userId,$this->appName, 'calendar_'.$calendarid) !== ''){
			$isAktiv = $this -> configInfo -> getUserValue($this -> userId,$this->appName, 'calendar_'.$calendarid);
		}
		
		$eventSource='';
		if( $calendarid !== 'birthday_'.$this -> userId){
			$eventSource = CalendarCalendar::getEventSourceInfo($calendar);
		}else{
			\OCP\Util::emitHook('OC_Calendar', 'getSources', array('all'=>false,'sources' => &$eventSource));
		}
		
		$params = [
			'status' => 'success',
			'active' => $isAktiv,
			'eventSource' =>$eventSource ,
		];	
		
		$response = new JSONResponse($params);
		return $response;
		
	}
	
	/**
	 * @NoAdminRequired
	 */
	public function refreshSubscribedCalendar() {
		$calendarid = (int) $this -> params('calendarid');	
		
		$calendar =CalendarApp::getCalendar($calendarid, false,false);
		if(!$calendar) {
			$params = [
			'status' => 'error',
			'message' => 'permission denied'
			];
			$response = new JSONResponse($params);
			return $response;	
		}
		
		$getProtocol=explode('://',$calendar['externuri']);
		$protocol=$getProtocol[0];
			
		$opts = array($protocol =>
			  array(
			    'method'  => 'POST',
			    'header'  => "Content-Type: text/calendar\r\n",
			    'timeout' => 60
			  )
			);
	
		$last_modified=$this -> stream_last_modified(trim($calendar['externuri']));
		if (!is_null($last_modified)){
		    $context  = stream_context_create($opts);
			$file=file_get_contents($calendar['externuri'],false,$context);
			$file = \Sabre\VObject\StringUtil::convertToUTF8($file);
			
			$import = new Import($file);
			$import->setUserID($this -> userId);
			$import->setTimeZone(CalendarApp::$tz);
			$import->setOverwrite(true);
			$import->setCalendarID($calendarid);
			try{
				$import->import();
			}catch (Exception $e) {
				$params = [
				'status' => 'error',
				'message' => $this -> l10n -> t('Import failed')
				];
				$response = new JSONResponse($params);
				return $response;		
			
			}
		}
		$params = [
		'status' => 'success',
		'refresh' => $calendarid,
		];
		$response = new JSONResponse($params);
		return $response;	
		
		
	}
	
	private function addEventsFromSubscribedCalendar($externUriFile, $calName, $calColor){
			$externUriFile=trim($externUriFile);
			$newUrl='';
			$bExistUri=false;
			$getProtocol=explode('://',$externUriFile);
			
			if(strtolower($getProtocol[0]) === 'webcal') {
				$newUrl='https://'.	$getProtocol[1];
				$last_modified = $this -> stream_last_modified($newUrl);
				if (is_null($last_modified)){
					$newUrl = 'http://'.$getProtocol[1];
				    $last_modified= $this ->stream_last_modified($newUrl);
					if (is_null($last_modified)){$bExistUri=false;}
					else{$bExistUri=true;}
				}else{
					$bExistUri=true;
				}
			}else{
				$protocol=$getProtocol[0];
				$newUrl=$externUriFile;
				$last_modified = $this -> stream_last_modified($newUrl);
				if (!is_null($last_modified)){
					$bExistUri=true;
				}
				
			}	
			
			$opts = array($protocol =>
			  array(
			    'method'  => 'POST',
			    'header'  => "Content-Type: text/calendar\r\n",
			    'timeout' => 60
			  )
			);
		 	    $bError=false;	
				if ($bExistUri === true){
				    $context  = stream_context_create($opts);
					
				    try{
					  $file=file_get_contents($newUrl,false,$context);
				    }catch (Exception $e) {
					  $params = [
						'status' => 'error',
						'message' => $this -> l10n->t('Import failed')
						];
						$response = new JSONResponse($params);
						return $response;	
				   }
					//\OCP\Util::writeLog('calendar', 'FILE IMPORT-> '.$file, \OCP\Util::DEBUG);
					$file = \Sabre\VObject\StringUtil::convertToUTF8($file);
					$import = new Import($file);
					
					$import->setUserID($this -> userId);
					$import->setTimeZone(CalendarApp::$tz);
					$calendarid = CalendarCalendar::addCalendar($this -> userId,$calName,'VEVENT,VTODO,VJOURNAL',null,0,strip_tags($calColor),1,$newUrl,$last_modified);
					CalendarCalendar::setCalendarActive($calendarid, 1);
					$import->setCalendarID($calendarid);
				
					try{
					   $import->import();
				    }catch (Exception $e) {
						$params = [
						'status' => 'error',
						'message' => $this -> l10n->t('Import failed')
						];
						$response = new JSONResponse($params);
						return $response;	
				   }
				   $count = $import->getCount();
			   }else{
			   	 $bError=true;	
			   	
			   }
			   
			   return ['isError' => $bError, 'countEvents' => $count, 'calendarid' => $calendarid];
	}

	/**
	 * @NoAdminRequired
	 */
	public function rebuildLeftNavigation() {
		$leftNavAktiv =  $this->configInfo->getUserValue($this->userId,$this->appName, 'calendarnav');
		
		//make it as template
		if($leftNavAktiv === 'true'){
				$calendars = CalendarCalendar::allCalendars($this->userId, false);
				//$mySharees=Object::getCalendarSharees();
				$activeCal=$this -> configInfo -> getUserValue($this->userId,$this->appName, 'choosencalendar');
				$outputAbo='';
				$output='<div id="leftcontentInner">
							<div class="view navigation-left button-group" style="float:none;">
							<button class="button viewaction" data-action="agendaDay" data-view="true" data-weekends="true">'.$this->l10n->t('Day').'</button>
							<button class="button viewaction" data-action="agendaThreeDays" data-view="true" data-weekends="true">'.$this->l10n->t('3-Days').'</button>	
							<button class="button viewaction" data-action="agendaWorkWeek" data-view="true" data-weekends="false">'.$this->l10n->t('W-Week').'</button>			
							<button class="button viewaction" data-action="agendaWeek" data-view="true" data-weekends="true">'.$this->l10n->t('Week').'</button>
						  <button class="button viewaction" data-action="month" data-view="true" data-weekends="true">'.$this->l10n->t('Month').'</button>
						   <button class="button viewaction" data-action="list" data-view="true" data-weekends="true"><i class="ioc ioc-list" title="'.$this->l10n->t('List').'"></i></button>
						   	<button class="button viewaction" data-action="year" data-view="true" data-weekends="true">'.$this->l10n->t('Year').'</button>
						   
						   <br />
						   <button class="button" data-action="prev" data-view="false" data-weekends="false"><i class="ioc ioc-previous"></i></button>		
						  <button class="button"  data-action="next" data-view="false" data-weekends="false"><i class="ioc ioc-next"></i></button>	
				
			  </div>	
				<div id="datepickerNav"></div>
					<h3><i class="ioc ioc-calendar"></i>&nbsp;'.$this->l10n->t('Calendar').'</h3>
								<ul id="calendarList">';
				   
				   $bShareApi = \OC::$server->getAppConfig()->getValue('core', 'shareapi_enabled', 'yes');
				
				   foreach($calendars as $calInfo){
				         	
						 $rightsOutput='';
						 $share='';
						 $checkBox='';
						 
						  $isActiveUserCal='';
						  $addCheckClass='';
						  $sharedescr='';
						 if($activeCal === $calInfo['id']){
						 	$isActiveUserCal='isActiveCal';
							 $addCheckClass='isActiveUserCal';
						 }
							/*
						  if((is_array($mySharees) && array_key_exists($calInfo['id'], $mySharees))) {
						 	$sharedescr=$mySharees[$calInfo['id']];	
						 	$share='<i class="ioc ioc-share toolTip" title="<b>'. $this->l10n->t('Shared with').'</b><br>'.$sharedescr.'"></i> '; 	
						 }*/
						 	$shareLink='';
						  if($calInfo['permissions'] & \OCP\PERMISSION_SHARE && $bShareApi === 'yes') { 
							  $shareLink='<a href="#" class="share icon-share" 
							  	data-item-type="'.CalendarApp::SHARECALENDAR.'" 
							    data-item="'.CalendarApp::SHARECALENDARPREFIX.$calInfo['id'].'" 
							    data-link="true"
							    data-title="'.$calInfo['displayname'].'"
								data-possible-permissions="'.$calInfo['permissions'].'"
								title="'.(string) $this->l10n->t('Share Calendar').'"
								style="float:right;"
								>
								</a>';
						  }
						   $displayName='<span class="descr">'.$calInfo['displayname'].'</span>'.$shareLink;
						   $checked=$calInfo['active'] ? ' checked="checked"' : '';
						 
						  $notice='';
						  $shareInfo ='';
				         if($calInfo['userid'] !== $this->userId){
				  	      	if($shareLink === ''){	
					  	      	if(\OCP\Share::getItemSharedWithByLink(CalendarApp::SHARECALENDAR, CalendarApp::SHARECALENDARPREFIX.$calInfo['id'], $calInfo['userid'])){
					         		$notice='<b>Notice</b><br>This calendar is also shared by Link for public!<br>';
					         	}
								
								$rightsOutput = CalendarCalendar::permissionReader($calInfo['permissions']);
								$shareInfo = '<i style="float:right;" class="toolTip ioc ioc-info" title="'.$notice.(string) $this->l10n->t('by') . ' ' .$calInfo['userid'].'<br />('.$rightsOutput.')"></i>';
							}
							
							$calShare = $calInfo['active'];
							if($this -> configInfo ->getUserValue($this->userId, 'calendar', 'calendar_'.$calInfo['id']) !== ''){
								$calShare= $this -> configInfo ->getUserValue($this->userId, 'calendar', 'calendar_'.$calInfo['id']);
							}
							$checked=$calShare ? ' checked="checked"' : '';
							
				  	        	
				  	        $displayName = '<span class="descr">'.$calInfo['displayname'].'</span>'.$shareLink.$shareInfo;
				           // $checkBox='';
						 }
						 
				 	    $checkBox = '<input class="activeCalendarNav regular-checkbox" data-id="'.$calInfo['id'].'" style="float:left;" id="edit_active_'.$calInfo['id'].'" type="checkbox" '.$checked.' /><label style="float:left;margin-right:5px;" for="edit_active_'.$calInfo['id'].'"></label>';
				 		 
				 		 
						 
						 if((bool)$calInfo['issubscribe'] === false){
					   	 		$output.='<li data-id="'.$calInfo['id'].'" class="calListen '.$isActiveUserCal.'">'.$checkBox.'<div class="colCal iCalendar '.$addCheckClass.'" style="cursor:pointer;background:'.$calInfo['calendarcolor'].'">&nbsp;</div> '.$displayName.'</li>';
						 }else{
						    if($calInfo['userid'] === $this->userId){
						   		$refreshImage='<i title="refresh"  class="refreshSubscription ioc ioc-refresh" style="cursor:pointer;float:right;position:absolute;right:18px;">&nbsp;</i>';
							}
				 			$outputAbo.='<li data-id="'.$calInfo['id'].'" class="calListen '.$isActiveUserCal.'">'.$checkBox.'<div class="colCal" style="cursor:pointer;background:'.$calInfo['calendarcolor'].'">&nbsp;</div> '.$displayName.$refreshImage.'</li>';
							
						 }
					}
				   if($outputAbo !== ''){
				   	  $outputAbo='<br style="clear:both;"><br /><h3><i class="ioc ioc-rss-alt"></i>&nbsp;'.$this->l10n->t('Subscription').'</h3><ul>'.$outputAbo.'</ul>';
				   }
				   $output.='</ul>'.$outputAbo.'<br />
				   <br style="clear:both;"><br />
				   <h3 data-id="lCategory" style=" cursor:pointer; line-height:24px;" ><label id="showCategory"><i style="font-size:22px;" class="ioc ioc-angle-down ioc-rotate-270"></i>&nbsp;<i class="ioc ioc-tags"></i>&nbsp;'.$this->l10n->t('Tags').'</label> 
				   	 	
				   
				   </h3>
					 <ul id="categoryCalendarList">
					 </ul>
					  </div>
					     ';
						 
					return $output;
			}else{
				return '';
			}	
	}
	
	/**
	 * @NoAdminRequired
	 * 
	 */
	public function changeViewCalendar() {
		$view = (string) $this -> params('v');
		
		switch($view) {
			case 'agendaDay':	
			case 'agendaWeek':
			case 'month':
			case 'agendaWorkWeek':
			case 'agendaThreeDays':
			case 'fourWeeks':
			case 'year':						
			case 'list':
				$this->configInfo->setUserValue($this -> userId, $this->appName, 'currentview', $view);
				break;
			default:
				$this->configInfo->setUserValue($this -> userId,$this->appName, 'currentview', 'month');
				break;
		}
		
		
		$response = new JSONResponse();
		
		return $response;
		
		
	}

	/**
	 * @NoAdminRequired
	 */
	public function touchCalendar() {
		
		$id = (int) $this -> params('eventid');
		$data = CalendarApp::getEventObject($id, false, false);
		$vcalendar =  VObject::parse($data['calendardata']);
		$vevent=$vcalendar->VEVENT;
		$vevent->setDateTime('LAST-MODIFIED', 'now');
		$vevent->setDateTime('DTSTAMP', 'now');
		Object::edit($id, $vcalendar->serialize());
		
		$params = [
		'status' => 'success',
		];
		
		$response = new JSONResponse($params);
		
		return $response;
		
	}
	
	private function stream_last_modified($url){
 
	      if (!($fp = @fopen($url, 'r'))){
	         return NULL;
		  }
	      $meta = stream_get_meta_data($fp);
	      for ($j = 0; isset($meta['wrapper_data'][$j]); $j++){
	      
	         if (strstr(strtolower($meta['wrapper_data'][$j]), 'last-modified')){
	            $modtime = substr($meta['wrapper_data'][$j], 15);
	            break;
	         }
	      }
	      fclose($fp);
	   
	   
	   return isset($modtime) ? strtotime($modtime) : time();
	}
}