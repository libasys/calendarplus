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
use \OCP\IRequest;
use \OCP\IL10N;
use \OCP\IConfig;
use \OCA\CalendarPlus\Calendar as CalendarCalendar;

/**
 * Controller class for main page.
 */
class PageController extends Controller {
	
	private $userId;
	private $l10n;
	private $configInfo;
	private $calendarController;
	
	

	public function __construct($appName, IRequest $request,  $userId, IL10N $l10n, IConfig $settings, $calendarController) {
		parent::__construct($appName, $request);
		$this -> userId = $userId;
		$this->l10n = $l10n;
		$this->configInfo = $settings;
		$this->calendarController = $calendarController;
		
	}
	
	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function index() {
			
		if(\OC::$server->getAppManager()->isEnabledForUser('contactsplus')) {
			
			$appinfo =\OCP\App::getAppVersion('contactsplus');
			if (version_compare($appinfo, '1.0.6', '>=')) {
				$calId = $this->calendarController->checkBirthdayCalendarByUri('birthday_'.$this->userId);
			}
			
		}
			
		$calendars = CalendarCalendar::allCalendars($this -> userId, false, false, false);
		
		if( count($calendars) == 0) {
			 CalendarCalendar::addDefaultCalendars($this -> userId);
			$calendars = CalendarCalendar::allCalendars($this -> userId, true);
		}	
			
		if($this->configInfo->getUserValue($this->userId, $this->appName, 'currentview', 'month') == "onedayview"){
			$this->configInfo->setUserValue($this->userId,$this->appName, 'currentview', "agendaDay");
		}	
		
		if($this->configInfo->getUserValue($this->userId,$this->appName, 'currentview', 'month') == "oneweekview"){
			$this->configInfo->setUserValue($this->userId,$this->appName, 'currentview', "agendaWeek");
		}
		
		if($this->configInfo->getUserValue($this->userId, $this->appName, 'currentview', 'month') == "onemonthview"){
			$this->configInfo->setUserValue($this->userId,$this->appName, 'currentview', "month");
		}
		
		if($this->configInfo->getUserValue($this->userId,$this->appName, 'currentview', 'month') == "listview"){
			$this->configInfo->setUserValue($this->userId, $this->appName, 'currentview', "list");
		}
		
		if($this->configInfo->getUserValue($this->userId,$this->appName, 'currentview', 'month') == "fourweeksview"){
			$this->configInfo->setUserValue($this->userId,$this->appName, 'currentview', "fourweeks");
		}
		
		
		\OCP\Util::addStyle($this->appName, '3rdparty/colorPicker');
		\OCP\Util::addscript($this->appName, '3rdparty/jquery.colorPicker');
		\OCP\Util::addScript($this->appName, '3rdparty/fullcalendar');
		\OCP\Util::addStyle($this->appName, '3rdparty/fullcalendar');
		\OCP\Util::addStyle($this->appName,'3rdparty/jquery.timepicker');
		\OCP\Util::addStyle($this->appName, '3rdparty/fontello/css/animation');
		\OCP\Util::addStyle($this->appName, '3rdparty/fontello/css/fontello');
		\OCP\Util::addScript($this->appName,'jquery.scrollTo.min');
		//\OCP\Util::addScript($this->appName,'timepicker');
		\OCP\Util::addScript($this->appName,'3rdparty/datepair');
		\OCP\Util::addScript($this->appName,'3rdparty/jquery.datepair');
		\OCP\Util::addScript($this->appName,'3rdparty/jquery.timepicker');
		\OCP\Util::addScript($this->appName, "3rdparty/jquery.webui-popover");
		
		\OCP\Util::addScript($this->appName, "3rdparty/chosen.jquery.min");
		
		\OCP\Util::addStyle($this->appName, "3rdparty/chosen");
		\OCP\Util::addScript($this->appName, '3rdparty/tag-it');
		\OCP\Util::addStyle($this->appName, '3rdparty/jquery.tagit');
		\OCP\Util::addStyle($this->appName, '3rdparty/jquery.webui-popover');
		
		if($this->configInfo->getUserValue($this->userId, $this->appName, 'timezone') == null || $this->configInfo->getUserValue($this->userId, $this->appName, 'timezonedetection') == 'true'){
			\OCP\Util::addScript($this->appName, '3rdparty/jstz-1.0.4.min');	
			\OCP\Util::addScript($this->appName, 'geo');
		}
		
		\OCP\Util::addScript($this->appName, '3rdparty/printThis');
		\OCP\Util::addScript($this->appName, 'app');
		\OCP\Util::addScript($this->appName,'loaderimport');
		\OCP\Util::addStyle($this->appName, 'style');
		\OCP\Util::addStyle($this->appName, "mobile");
		\OCP\Util::addScript($this->appName,'jquery.multi-autocomplete');
		\OCP\Util::addScript('core','tags');
		\OCP\Util::addScript($this->appName,'on-event');
		
		$leftNavAktiv = $this->configInfo->getUserValue($this->userId, $this->appName, 'calendarnav');
		$rightNavAktiv = $this->configInfo->getUserValue($this->userId, $this->appName, 'tasknav');
		
		$pCalendar = $calendars;	
		
		$pHiddenCal = 'class="isHiddenCal"';
		$pButtonCalAktive = '';
		
		if($leftNavAktiv === 'true') {
			$pHiddenCal = '';
			$pButtonCalAktive = 'button-info';
		}
		
		
		$pButtonTaskAktive='';
		$pTaskOutput = '';
		$pRightnavAktiv = $rightNavAktiv;
		$pIsHidden =  'class="isHiddenTask"';
		
		
						
		if($rightNavAktiv === 'true' && \OC::$server->getAppManager()->isEnabledForUser('tasksplus')) {
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
						
			$cDataTimeLine=new \OCA\TasksPlus\Timeline();
			$cDataTimeLine->setCalendars($allowedCals);
			$taskOutPutbyTime=$cDataTimeLine->generateAddonCalendarTodo();
			
			$paramsList =[
				'taskOutPutbyTime' => $taskOutPutbyTime
			];
			$list = new TemplateResponse('tasksplus', 'calendars.tasks.list', $paramsList, '');
			$pButtonTaskAktive='button-info';
			$pTaskOutput =$list -> render();
			$pIsHidden =  '';
		}
		
		$params = [
			'calendars' => $pCalendar,
			'leftnavAktiv' => $leftNavAktiv,
			'isHiddenCal' => $pHiddenCal,
			'buttonCalAktive' => $pButtonCalAktive,
			'isHidden' => $pIsHidden,
			'buttonTaskAktive' => $pButtonTaskAktive,
			'taskOutput' => $pTaskOutput,
			'rightnavAktiv' =>$pRightnavAktiv,
			'mailNotificationEnabled' => \OC::$server->getAppConfig()->getValue('core', 'shareapi_allow_mail_notification', 'yes'),
			'allowShareWithLink' => \OC::$server->getAppConfig()->getValue('core', 'shareapi_allow_links', 'yes'),
			'mailPublicNotificationEnabled' => \OC::$server->getAppConfig()->getValue('core', 'shareapi_allow_public_notification', 'no'),
			
		];
		
		$csp = new \OCP\AppFramework\Http\ContentSecurityPolicy();
		$csp->addAllowedImageDomain('*');
		
		
		$response = new TemplateResponse($this->appName, 'calendar', $params);
		$response->setContentSecurityPolicy($csp);
		

		return $response;
	}
}