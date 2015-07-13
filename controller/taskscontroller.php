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

use \OCA\TasksPlus\App as TasksApp;
use \OCA\TasksPlus\Timeline;

use \OCP\AppFramework\Controller;
use \OCP\AppFramework\Http\JSONResponse;
use \OCP\AppFramework\Http\TemplateResponse;
use \OCP\IRequest;
use \OCP\Share;
use \OCP\IConfig;

class TasksController extends Controller {

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
	public function rebuildTaskViewRight() {
			
		if(\OC::$server->getAppManager()->isEnabledForUser('tasksplus')){
			$calendars = CalendarCalendar::allCalendars($this -> userId, true);
		
			 if( count($calendars) > 0 ) {
			 		$allowedCals=[];
			
					foreach($calendars as $calInfo){
						$isAktiv=(int)$calInfo['active'];
						if($this->configInfo->getUserValue($this -> userId, $this->appName, 'calendar_'.$calInfo['id']) !== ''){
							$isAktiv= (int) $this->configInfo->getUserValue($this -> userId, $this->appName, 'calendar_'.$calInfo['id']);
						}
						if($isAktiv === 1){
							$allowedCals[]=$calInfo;
						}	
					}
				
					$cDataTimeLine=new Timeline();
					$cDataTimeLine->setCalendars($allowedCals);
					$taskOutPutbyTime=$cDataTimeLine->generateAddonCalendarTodo();
					
					$params=[
					'taskOutPutbyTime' => $taskOutPutbyTime,
					];
				
				$response = new TemplateResponse('tasksplus', 'calendars.tasks.list',$params, '');  
		        
		        return $response;
			 }
		}
		
	}
	/**
	 * @NoAdminRequired
	 */
	public function setCompletedTask() {
			
		$id = $this -> params('id');
		$checked = $this -> params('checked');
		
		$vcalendar = CalendarApp::getVCalendar( $id );
		$vtodo = $vcalendar->VTODO;
		
		TasksApp::setComplete($vtodo, $checked ? 100 : 0, null);
		Object::edit($id, $vcalendar->serialize());
		$user_timezone = CalendarApp::getTimezone();
		
		$aTask= TasksApp::getEventObject($id, true, true);	
		$aCalendar = CalendarCalendar::find($aTask['calendarid']);	
		
		$task_info[] =  TasksApp::arrayForJSON($id, $vtodo, $user_timezone,$aCalendar,$aTask);
		
		$subTaskIds='';
			if($aTask['relatedto'] === ''){
				$subTaskIds = TasksApp::getSubTasks($aTask['eventuid']);
				if($subTaskIds !== ''){
				  $tempIds = explode(',',$subTaskIds);	
				  foreach($tempIds as $subIds){
				  	$vcalendar = TasksApp::getVCalendar( $subIds, true, true );
					$vtodo = $vcalendar->VTODO;
					TasksApp::setComplete($vtodo, $checked ? 100 : 0, null);
					TasksApp::edit($subIds, $vcalendar->serialize());
					$task_info[] = TasksApp::arrayForJSON($subIds, $vtodo, $user_timezone,$aCalendar,$aTask);
				  }
				}
			}
		$params = [
		'status' => 'success',
		'data' =>$task_info
		];
		
		$response = new JSONResponse($params);
		
		return $response;
		
	}
	
}