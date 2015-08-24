<?php
/**
 * ownCloud - CalendarPlus
 *
 * @author Sebastian Doell
 * @copyright 2015 sebastian doell sebastian@libasys.de
 *
 * Copyright (c) 2011 Bart Visscher <bartv@thisnet.nl>
 * Copyright (c) 2012 Georg Ehrke <georg@owncloud.com>
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

namespace OCA\CalendarPlus\Search;

use  \OCA\CalendarPlus\Calendar as CalendarCalendar;
use \OCA\CalendarPlus\App as CalendarApp;
use  \OCA\CalendarPlus\Object;
use  \OCA\CalendarPlus\VObject;

/**
 * Provide search results from the 'calendar' app
 */
class Provider extends \OCP\Search\Provider {

	/**
	 * 
	 * @param string $query
	 * @return \OCP\Search\Result
	 */
	function search($query) {
			
		$today= date('Y-m-d',time());
		$allowedCommands=array('#ra'=>1,'#dt'=>1);	
			
		$calendars = CalendarCalendar::allCalendars(\OCP\USER::getUser(), true);
		$activeCalendars = '';
		$config = \OC::$server->getConfig();	
		
			foreach($calendars as $calendar) {
				$isAktiv = $calendar['active'];
				
				if($config -> getUserValue(\OCP\USER::getUser(), CalendarApp::$appname, 'calendar_'.$calendar['id'])!=''){
				    $isAktiv= (int)$config -> getUserValue(\OCP\USER::getUser(),CalendarApp::$appname, 'calendar_'.$calendar['id']);
			    }	
				if(!array_key_exists('active', $calendar)){
					$isAktiv = 1;
				}
				if($isAktiv == 1 && (int) $calendar['issubscribe'] === 0) {
					$activeCalendars[] = $calendar;
				}
			}
		
		
		
		if(count($activeCalendars) === 0 || !\OCP\App::isEnabled(CalendarApp::$appname)) {
			//return false;
		}
		$results = array();
		$searchquery = array();
		if(substr_count($query, ' ') > 0) {
			$searchquery = explode(' ', $query);
		}else{
			$searchquery[] = $query;
		}
	
		
		$user_timezone = CalendarApp::getTimezone();
		$l =  \OC::$server->getL10N(CalendarApp::$appname);
		
		$isDate=false;
		if(strlen($query) >= 5 && self::validateDate($query)){
			$isDate=true;
			//\OCP\Util::writeLog('calendar','VALID DATE FOUND', \OCP\Util::DEBUG);
		}
		
		foreach($activeCalendars as $calendar) {
			$objects = Object::all($calendar['id']);
			foreach($objects as $object) {
				if($object['objecttype'] !== 'VEVENT') {
					continue;
				}
				
				
				$searchAdvanced=false;
	
					if($isDate === true && strlen($query)>= 5 ){
					//	\OCP\Util::writeLog('calendar','search: ->'.$query, \OCP\Util::DEBUG);
						$tempQuery = strtotime($query);
					   $checkDate = date('Y-m-d',$tempQuery);
					   if(substr_count($object['startdate'],$checkDate) > 0){
					 	  $searchAdvanced = true;
					    }
					}
				
				if(array_key_exists($query,$allowedCommands) && $allowedCommands[$query]){
					if($query === '#dt'){
						$search=$object['startdate'];	
						if(substr_count($search,$today) > 0){
							$searchAdvanced = true;
							
						}
					}
					
					if($query=='#ra'){
						if($object['isalarm'] === 1){
							$searchAdvanced = true;
						}		
						
					}
		         }
				
				if(substr_count(strtolower($object['summary']), strtolower($query)) > 0 || $searchAdvanced === true) {
					$calendardata =  VObject::parse($object['calendardata']);
					$vevent = $calendardata->VEVENT;
					if (Object::getowner($object['id']) !== \OCP\USER::getUser()) {
						if (isset($vevent -> CLASS) && $vevent -> CLASS -> getValue() === 'CONFIDENTIAL') {
							continue;
						}
						if (isset($vevent -> CLASS) && ($vevent -> CLASS -> getValue() === 'PRIVATE' || $vevent -> CLASS -> getValue() === '')) {
							continue;
						}
					}
					
					
					$dtstart = $vevent->DTSTART;
					$dtend = Object::getDTEndFromVEvent($vevent);
					$start_dt = $dtstart->getDateTime();
					$start_dt->setTimezone(new \DateTimeZone($user_timezone));
					$end_dt = $dtend->getDateTime();
					$end_dt->setTimezone(new \DateTimeZone($user_timezone));
					if ($dtstart->getValueType() =='DATE') {
						$end_dt->modify('-1 sec');
						if($start_dt->format('d.m.Y') != $end_dt->format('d.m.Y')) {
							$info = $l->t('Date') . ': ' . $start_dt->format('d.m.Y') . ' - ' . $end_dt->format('d.m.Y');
						}else{
							$info = $l->t('Date') . ': ' . $start_dt->format('d.m.Y');
						}
					}else{
						$info = $l->t('Date') . ': ' . $start_dt->format('d.m.y H:i') . ' - ' . $end_dt->format('d.m.y H:i');
					}
					$link = \OC::$server->getURLGenerator()->linkToRoute(CalendarApp::$appname.'.page.index').'#'.urlencode($object['id']);
					
					$returnData['id'] = $object['id'];
					$returnData['description'] = $object['summary'].' '.$info;
					$returnData['link'] = $link;
					$returnData['type'] = 'calendar';
					//$results[]=$returnData;
					$results[]=new Result($returnData);//$name,$text,$link,$type
				}
			}
		}
		return $results;
	}
	
	public static function validateDate($Str){
	   $Stamp = strtotime( $Str );
	   $Month = date( 'm', $Stamp );
	   $Day   = date( 'd', $Stamp );
	   $Year  = date( 'Y', $Stamp );
	
	  return checkdate( $Month, $Day, $Year );
  }
}
