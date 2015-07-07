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
use \OCP\ICache;

class ImportController extends Controller {

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
	public function getImportDialogTpl() {
			
		$pPath = $this -> params('path');	
		$pFile = $this -> params('filename');
		$pIsDragged = $this->params('isDragged');
		
		$params=[
			'path' => $pPath,
			'filename' => $pFile,
			'isDragged' => $pIsDragged
		];
		
		$response = new TemplateResponse($this->appName, 'part.import',$params, '');  
        
        return $response;
		
	}
	
	/**
	 * @NoAdminRequired
	 */
	public function checkCalendarExists() {
		$calname = strip_tags($this -> params('calname'));
		$calendars = CalendarCalendar::allCalendars($this -> userId);
		
		foreach($calendars as $calendar) {
			if($calendar['displayname'] == $calname) {
				$params = [
				'status' => 'success',
				'message' => 'exists'
				];
				$response = new JSONResponse($params);
				return $response;	
			}
		}
		
		$params = [
		'status' => 'error',
		];
		$response = new JSONResponse($params);
		return $response;
		
		
	}
	
	/**
	 * @NoAdminRequired
	 */
	public function importEvents() {
		$pProgresskey = $this -> params('progresskey');
		$pGetprogress = $this -> params('getprogress');
		$pPath = $this -> params('path');
		$pFile = $this -> params('file');
		$pMethod = $this -> params('method');
		$pCalname = $this -> params('calname');
		$pCalcolor = $this -> params('calcolor');
		$pId = $this -> params('id');
		$pOverwrite = $this -> params('overwrite');
		$pIsDragged = $this->params('isDragged');
		
		\OC::$server->getSession()->close();
		
		
		if (isset($pProgresskey) && isset($pGetprogress)) {
				
				$params = [
					'status' => 'success',
					'percent' => \OC::$server->getCache()->get($pProgresskey),
				];
				$response = new JSONResponse($params);
				return $response;	
		}
		
		if($pIsDragged === 'true') {
			//OCP\JSON::error(array('error'=>'404'));
			$file = explode(',', $pFile);
			$file = end($file);
			$file = base64_decode($file);
		}else{
			$file = \OC\Files\Filesystem::file_get_contents($pPath . '/' . $pFile);
		}
		
		if(!$file) {
				$params = [
					'status' => 'error',
					'error' => '404',
				];
				$response = new JSONResponse($params);
				return $response;	
			
		}
		$file = \Sabre\VObject\StringUtil::convertToUTF8($file);
		
		$import = new Import($file);
		$import->setUserID($this->userId);
		$import->setTimeZone(CalendarApp::$tz);
		$import->enableProgressCache();
		$import->setProgresskey($pProgresskey);
		if(!$import->isValid()) {
			$params = [
					'status' => 'error',
					'error' => 'notvalid',
				];
				$response = new JSONResponse($params);
				return $response;	
		}
		
		$newcal = false;
		if($pMethod == 'new') {
			$calendars = CalendarCalendar::allCalendars($this -> userId);
			foreach($calendars as $calendar) {
				if($calendar['displayname'] == $pCalname) {
					$id = $calendar['id'];
					$newcal = false;
					break;
				}
				$newcal = true;
			}
			if($newcal) {
				$id = CalendarCalendar::addCalendar($this -> userId, strip_tags($pCalname),'VEVENT,VTODO,VJOURNAL',null,0,strip_tags($pCalcolor));
				CalendarCalendar::setCalendarActive($id, 1);
				
			}
		}else{
			$id=	$pId;
			$calendar = CalendarApp::getCalendar($id);
			if($calendar['userid'] != $this -> userId) {
				$params = [
					'status' => 'error',
					'error' => 'missingcalendarrights',
				];
				$response = new JSONResponse($params);
				return $response;	
			}
			
			$import->setOverwrite($pOverwrite);
		}
		
		$import->setCalendarID($id);
		try{
			$import->import();
		}catch (\Exception $e) {
			$params = [
					'status' => 'error',
					'message' => $this->l10n -> t('Import failed'),
				];
				$response = new JSONResponse($params);
				return $response;		
		}
		$count = $import->getCount();
		
		if($count == 0) {
			if($newcal) {
				CalendarCalendar::deleteCalendar($id);
			}
			$params = [
				'status' => 'error',
				'message' => $this->l10n -> t('The file contained either no events or all events are already saved in your calendar.'),
			];
			$response = new JSONResponse($params);
			return $response;
		}else{
			if($newcal) {
				$params = [
					'status' => 'success',
					'message' => $count . ' ' . $this->l10n -> t('events has been saved in the new calendar'). ' ' .  strip_tags($pCalname),
					'eventSource' => CalendarCalendar::getEventSourceInfo(CalendarCalendar::find($id))
				];
				
				$response = new JSONResponse($params);
				return $response;		
			}else{
				$params = [
					'status' => 'success',
					'message' => $count . ' ' . $this->l10n -> t('events has been saved in your calendar'),
					'eventSource' => '',
				];
				
				$response = new JSONResponse($params);
				return $response;		
			}
		}
		
		
	}

	/**
	 * @NoAdminRequired
	 */
	public function importEventsPerDrop() {
		$pCalid = $this -> params('calid');
		$pAddCal = $this -> params('addCal');
		$pAddCalCol = $this -> params('addCalCol');
		$data =  $this -> params('data');
		
		$data = explode(',', $data);
		$data = end($data);
		$data = base64_decode($data);
		
		$import = new Import($data);
		$import->setUserID($this -> userId);
		$import->setTimeZone(CalendarApp::$tz);
		$import->disableProgressCache();
		if(!$import->isValid()) {
			$params = [
					'status' => 'error',
					'error' => 'notvalid',
				];
				$response = new JSONResponse($params);
				return $response;	
		}
		if($pCalid == 'newCal' && $pAddCal != ''){
			$calendars = CalendarCalendar::allCalendars($this -> userId);
			foreach($calendars as $calendar) {
				if($calendar['displayname'] == $pAddCal) {
					$id = $calendar['id'];
					$newcal = false;
					break;
				}
				$newcal = true;
			}
			if($newcal) {
				$id = CalendarCalendar::addCalendar($this -> userId, strip_tags($pAddCal),'VEVENT,VTODO,VJOURNAL',null,0,strip_tags($pAddCalCol));
				CalendarCalendar::setCalendarActive($id, 1);
			}
		}else{
			$id = $pCalid;		
			$calendar = CalendarApp::getCalendar($id);
			if($calendar['userid'] != $this -> userId) {
				$params = [
					'status' => 'error',
					'error' => 'missingcalendarrights',
				];
				$response = new JSONResponse($params);
				return $response;	
			}
			
			
		}
		$import->setOverwrite(false);
		$import->setCalendarID($id);
		$import->import();
		$count = $import->getCount();
		if($count == 0) {
			CalendarCalendar::deleteCalendar($id);
			$params = [
				'status' => 'error',
				'message' => $this->l10n -> t('The file contained either no events or all events are already saved in your calendar.'),
			];
			$response = new JSONResponse($params);
			return $response;
		}else{
			$newcalendarname	=strip_tags($pAddCal);
			if($pAddCal!=''){
				$params = [
					'status' => 'success',
					'message' => $count . ' ' . $this->l10n -> t('events has been saved in the new calendar'). ' ' .  $newcalendarname,
					'eventSource' => CalendarCalendar::getEventSourceInfo(CalendarCalendar::find($id)),
				];
				
				$response = new JSONResponse($params);
				return $response;		
			}else{
				$params = [
					'status' => 'success',
					'message' => $count . ' ' . $this->l10n -> t('events has been saved in the calendar').' '.$calendar['displayname'],
					'eventSource' => '',
				];
				
				$response = new JSONResponse($params);
				return $response;			
			}
		}
		
	}
	
	
}