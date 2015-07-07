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
use \OCA\CalendarPlus\Export;

use \OCP\AppFramework\Controller;
use \OCP\AppFramework\Http\JSONResponse;
use \OCP\AppFramework\Http\DataDownloadResponse;
use \OCP\IRequest;
use \OCP\Share;
use \OCP\IConfig;

class ExportController extends Controller {

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
	*@PublicPage
	 * @NoCSRFRequired
	 * 
	 */
	public function exportEvents(){
		$token = $this -> params('t');	
		$calid = null;
		$eventid = null;
		
		if (isset($token)) {
				
			$linkItem = \OCP\Share::getShareByToken($token, false);
			if (is_array($linkItem) && isset($linkItem['uid_owner'])) {
				$rootLinkItem = \OCP\Share::resolveReShare($linkItem);
				
				if (isset($rootLinkItem['uid_owner'])) {
					\OCP\JSON::checkUserExists($rootLinkItem['uid_owner']);
					if($linkItem['item_type'] === CalendarApp::SHARECALENDAR){
						$sPrefix =CalendarApp::SHARECALENDARPREFIX;
					}
					if($linkItem['item_type'] === CalendarApp::SHAREEVENT){
						$sPrefix = CalendarApp::SHAREEVENTPREFIX;
					}
					if($linkItem['item_type'] === CalendarApp::SHARETODO){
						$sPrefix = CalendarApp::SHARETODOPREFIX;
					}
						
					$itemSource =CalendarApp::validateItemSource($linkItem['item_source'],$sPrefix);
					if($linkItem['item_type'] === CalendarApp::SHARECALENDAR){
						$calid=$itemSource;
					}
					if($linkItem['item_type'] === CalendarApp::SHAREEVENT || $linkItem['item_type'] === CalendarApp::SHARETODO){
						$eventid=$itemSource;
					}
				}
			}
			
		}
		else{
			if (\OCP\User::isLoggedIn()) {
				
				$calid = $this -> params('calid');
				$eventid = $this -> params('eventid');
				
				
			}
		}
		
		if(!is_null($calid)) {
			$calendar = CalendarApp::getCalendar($calid, true);
			if(!$calendar) {
				$params = [
				'status' => 'error',
				];
				$response = new JSONResponse($params);
				return $response;
			}
			
			$name = str_replace(' ', '_', $calendar['displayname']) . '.ics';
			$calendarEvents = Export::export($calid, Export::CALENDAR);
			
			$response = new DataDownloadResponse($calendarEvents, $name, 'text/calendar');
			
			return $response;	
				
		}
		if(!is_null($eventid)) {
			$data = CalendarApp::getEventObject($eventid, false);
			if(!$data) {
				$params = [
				'status' => 'error',
				];
				$response = new JSONResponse($params);
				return $response;
			}
			
			$name = str_replace(' ', '_', $data['summary']) . '.ics';
			$singleEvent = Export::export($eventid, Export::EVENT);
			
			$response = new DataDownloadResponse($singleEvent, $name, 'text/calendar');
			
			return $response;	
			
		}
		
	}
	
	

}