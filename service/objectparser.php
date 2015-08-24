<?php

/**
 * ownCloud - Calendar+
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
 
namespace OCA\CalendarPlus\Service;

use \OCA\CalendarPlus\VObject;
use \Sabre\VObject\Component\VCalendar;
use \Sabre\VObject\Component\VCard;

class ObjectParser  {
	
	private $userId;
	
	
	public function __construct($userId) {
 		$this->userId = $userId;
  }
	
	/**
	 * @brief Parses the VObject
	 * @param string VObject as string
	 * @returns Sabre_VObject or null
	 */
	public function parse($data) {
		try {
				
			$vobject = \Sabre\VObject\Reader::read($data,2);
			if ($vobject instanceof \Sabre\VObject\Component) {
				$vobject = $this->createVObject($vobject);
			}
			return $vobject;
		} catch (\Exception $e) {
			//OC_Log::write('vobject', $e->getMessage(), OC_Log::ERROR);
			return null;
		}
	}
	
	/**
	 *  create VObject
	 * @param string VObject as string
	 * @return Sabre_VObject or null
	 */
	private function createVObject($vobject_or_name){
		if (is_object($vobject_or_name)) {
			return $vobject_or_name;
		} else {
			$vcomponent = null;
				
			switch($vobject_or_name){
				case 'VCALENDAR':
				case 'VTODO':
				case 'VEVENT':
				case 'VALARM':
				case 'VFREEBUSY':	
				case 'VJOURNAL':
				case 'VTIMEZONE':	
					$vcomponent = new VCalendar();
					break;	
				case 'VCARD':
					$vcomponent = new VCard();
					break;
				default:
				 	$vcomponent = new VCalendar();
					break;			
			}
			
			if($vcomponent !== null){
				$vobject  = $vcomponent->createComponent($vobject_or_name);
			
				return $vobject;
				
			}else{
				return null;
			}
			
		}
	}
	
	/**
	 * @brief validates a request
	 * @param array $request
	 * @return mixed (array / boolean)
	 */
	public  function validateRequest($request) {
		$errnum = 0;
		
		$errarr = array(
			'title' => false,
			'cal'=> false, 
			'from' => false, 
			'fromtime' => false, 
			'to'=> false, 
			'totime'=> false, 
			'endbeforestart '=> false
		);
		
		if($request['title'] === '') {
			$errarr['title'] = true;
			$errnum++;
		}

		$fromday = substr($request['from'], 0, 2);
		$frommonth = substr($request['from'], 3, 2);
		$fromyear = substr($request['from'], 6, 4);
		if(!checkdate($frommonth, $fromday, $fromyear)) {
			$errarr['from'] = true;
			$errnum++;
		}
		
		$allday = isset($request['allday']);
		if(!$allday && $this->checkTime(urldecode($request['fromtime']))) {
			$errarr['fromtime'] = true;
			$errnum++;
		}

		$today = substr($request['to'], 0, 2);
		$tomonth = substr($request['to'], 3, 2);
		$toyear = substr($request['to'], 6, 4);
		
		if(!checkdate($tomonth, $today, $toyear)) {
			$errarr['to'] = true;
			$errnum++;
		}
		
		if($request['repeat'] != 'doesnotrepeat') {
				
			if(is_nan($request['interval']) && $request['interval'] !== '') {
				$errarr['interval'] = true;
				$errnum++;
			}
			
			if(array_key_exists('repeat', $request) && !array_key_exists($request['repeat'], Utility::getRepeatOptions(Utility::$l10n))) {
				$errarr['repeat'] = true;
				$errnum++;
			}
			
			if(array_key_exists('advanced_month_select', $request) && !array_key_exists($request['advanced_month_select'], Utility::getMonthOptions(Utility::$l10n))) {
				$errarr['advanced_month_select'] = true;
				$errnum++;
			}
			
			if(array_key_exists('advanced_year_select', $request) && !array_key_exists($request['advanced_year_select'], Utility::getYearOptions(Utility::$l10n))) {
				$errarr['advanced_year_select'] = true;
				$errnum++;
			}
			
			if(array_key_exists('weekofmonthoptions', $request) && !array_key_exists($request['weekofmonthoptions'], Utility::getWeekofMonth(Utility::$l10n))) {
				$errarr['weekofmonthoptions'] = true;
				$errnum++;
			}
			
			if($request['end'] !== 'never') {
					
				if(!array_key_exists($request['end'], Utility::getEndOptions(Utility::$l10n))) {
					$errarr['end'] = true;
					$errnum++;
				}
				
				if($request['end'] === 'count' && is_nan($request['byoccurrences'])) {
					$errarr['byoccurrences'] = true;
					$errnum++;
				}

				if($request['end'] === 'date') {
					list($bydate_day, $bydate_month, $bydate_year) = explode('-', $request['bydate']);
					if(!checkdate($bydate_month, $bydate_day, $bydate_year)) {
						$errarr['bydate'] = true;
						$errnum++;
					}
				}
			}
			
			if(array_key_exists('weeklyoptions', $request)) {
				foreach($request['weeklyoptions'] as $option) {
					if(!in_array($option, Utility::getWeeklyOptions(Utility::$l10n))) {
						$errarr['weeklyoptions'] = true;
						$errnum++;
					}
				}
			}

			if(array_key_exists('byyearday', $request)) {
				foreach($request['byyearday'] as $option) {
					if(!array_key_exists($option, Utility::getByYearDayOptions())) {
						$errarr['byyearday'] = true;
						$errnum++;
					}
				}
			}
			
			if(array_key_exists('weekofmonthoptions', $request)) {
				if(is_nan((double)$request['weekofmonthoptions'])) {
					$errarr['weekofmonthoptions'] = true;
					$errnum++;
				}
			}

			if(array_key_exists('bymonth', $request)) {
				foreach($request['bymonth'] as $option) {
					if(!in_array($option, Utility::getByMonthOptions(Utility::$l10n))) {
						$errarr['bymonth'] = true;
						$errnum++;
					}
				}
			}
			
			if(array_key_exists('byweekno', $request)) {
				foreach($request['byweekno'] as $option) {
					if(!array_key_exists($option, Utility::getByWeekNoOptions())) {
						$errarr['byweekno'] = true;
						$errnum++;
					}
				}
			}
			
			if(array_key_exists('bymonthday', $request)) {
				foreach($request['bymonthday'] as $option) {
					if(!array_key_exists($option, Utility::getByMonthDayOptions())) {
						$errarr['bymonthday'] = true;
						$errnum++;
					}
				}
			}
		}

		if(!$allday && $this->checkTime(urldecode($request['totime']))) {
			$errarr['totime'] = true;
			$errnum++;
		}
		
		if($today < $fromday && $frommonth == $tomonth && $fromyear == $toyear) {
			$errarr['endbeforestart'] = true;
			$errnum++;
		}

		if($today == $fromday && $frommonth > $tomonth && $fromyear == $toyear) {
			$errarr['endbeforestart'] = true;
			$errnum++;
		}
		
		if($today == $fromday && $frommonth == $tomonth && $fromyear > $toyear) {
			$errarr['endbeforestart'] = true;
			$errnum++;
		}
		
		if(!$allday && $fromday == $today && $frommonth == $tomonth && $fromyear == $toyear) {
			list($tohours, $tominutes) = explode(':', $request['totime']);
			list($fromhours, $fromminutes) = explode(':', $request['fromtime']);
			
			if($tohours < $fromhours) {
				$errarr['endbeforestart'] = true;
				$errnum++;
			}

			if($tohours == $fromhours && $tominutes < $fromminutes) {
				$errarr['endbeforestart'] = true;
				$errnum++;
			}
		}
		if ($errnum){
			return $errarr;
		}
		return false;
	}
	
	/**
	 * @brief creates an VCalendar Object from the request data
	 * @param array $request
	 * @return object created $vcalendar
	 */	
	 
	 public function createVCalendarFromRequest($request) {
		$vcalendar = new VObject('VCALENDAR');
		$vcalendar->add('PRODID', 'ownCloud Calendar');
		$vcalendar->add('VERSION', '2.0');

		$vevent = new VObject('VEVENT');
		
		
		$vcalendar->add($vevent);

		$vevent->setDateTime('CREATED', 'now');

		//$vevent->setUID();
		return $this->updateVCalendarFromRequest($request, $vcalendar);
	}

	
	/**
	 * @brief updates an VCalendar Object from the request data
	 * @param array $request
	 * @param object $vcalendar
	 * @return object updated $vcalendar
	 */
	public  function updateVCalendarFromRequest($request, $vcalendar) {
			
			
		$accessclass = $request["accessclass"];
		$title = $request["title"];
		$location = $request["location"];
		$categories = $request["categories"];
		$allday = isset($request["allday"]);
		$link = $request["link"];
		$from = $request["from"];
		$to  = $request["to"];
		
		$checkDateFrom=strtotime($from);
		$checkWeekDay=date("D",$checkDateFrom);
		$weekDay=Utility::getWeeklyOptionsCheck($checkWeekDay);
		
		if (!$allday) {
			$fromtime = $request['fromtime'];
			$totime = $request['totime'];
		}
		
		$vevent = $vcalendar->VEVENT;
		
		/*REMINDER NEW*/
		if($request['reminder']!='none'){
				
			if($vevent -> VALARM){
				$valarm = $vevent -> VALARM;
			}else{
				$valarm = new VObject('VALARM');
                $vevent->add($valarm);
			}
			
			if($request['reminder']=='OWNDEF' && ($request['reminderAdvanced']=='DISPLAY' || $request['reminderAdvanced']=='EMAIL')){
				
				$valarm->setString('ATTENDEE','');
					
				if($request['remindertimeselect'] !== 'ondate') {
				    $valarm->setString('TRIGGER',$request['sReminderRequest']);
				}
				
				if($request['remindertimeselect'] === 'ondate') {
				    	
				    $temp=explode('TRIGGER;VALUE=DATE-TIME:',$request['sReminderRequest']);
					$datetime_element = new \Sabre\VObject\Property\ICalendar\DateTime(new \Sabre\VObject\Component\VCalendar(),'TRIGGER');
					$datetime_element->setDateTime( new \DateTime($temp[1]), false);
					
	                $valarm->__set('TRIGGER',$datetime_element);
					$valarm->TRIGGER['VALUE'] = 'DATE-TIME';
					
				}

				if($request['reminderAdvanced'] === 'EMAIL'){
					//ATTENDEE:mailto:sebastian.doell@libasys.de
					$valarm->setString('ATTENDEE','mailto:'.$request['reminderemailinput']);
				}
			   $valarm->setString('DESCRIPTION', 'owncloud');
			   $valarm->setString('ACTION', $request['reminderAdvanced']);
			}else{
				$valarm->setString('ATTENDEE','');
				$valarm->setString('TRIGGER',$request['reminder']);
				$valarm->setString('DESCRIPTION', 'owncloud');
			    $valarm->setString('ACTION','DISPLAY');
			}
			
		}

		if($request['reminder']=='none'){
			if($vevent -> VALARM){
				$vevent->setString('VALARM','');
			}
		}
		
		$description = $request["description"];
		$repeat = $request["repeat"];
		//FIXME
		
		$firstDayOfWeek=';WKST='.(\OCP\Config::getUserValue($this->userId,'calendarplus', 'firstday', 'mo') == 'mo' ? 'MO' : 'SU');
		
		if($repeat != 'doesnotrepeat' && !array_key_exists('sRuleRequest', $request)) {
			$rrule = '';
			$interval = $request['interval'];
			$end = $request['end'];
			$byoccurrences = $request['byoccurrences'];
			
			switch($repeat) {
				case 'daily':
					$rrule .= 'FREQ=DAILY'.$firstDayOfWeek;
					break;
				case 'weekly':
					$rrule .= 'FREQ=WEEKLY'.$firstDayOfWeek;
					if(array_key_exists('rWeekday', $request)) {
						$rrule .= ';BYDAY=' . $request['rWeekday'];
					}
					break;
					case 'everymonth':
						$rrule .= 'FREQ=MONTHLY';
						break;
					case 'everyyear':
						$rrule .= 'FREQ=YEARLY';
						break;
					case 'everyweek':
						$rrule .= 'FREQ=WEEKLY';
						break;
									
				case 'weekday':
					$rrule .= 'FREQ=WEEKLY'.$firstDayOfWeek;
					$rrule .= ';BYDAY=MO,TU,WE,TH,FR';
					break;
				case 'biweekly':
					$rrule .= 'FREQ=WEEKLY'.$firstDayOfWeek;
					$interval = $interval * 2;
					break;
				case 'monthly':
					$rrule .= 'FREQ=MONTHLY'.$firstDayOfWeek;
					if($request['advanced_month_select'] == 'monthday') {
						if(array_key_exists('rMonthday', $request)) {
							
							$rrule .= ';BYMONTHDAY=' . $request['rMonthday'];
						}
					}elseif($request['advanced_month_select'] == 'weekday') {
						
						$rrule .= ';BYDAY=' . $request['rWeekday'];
						}
					break;
				case 'yearly':
					$rrule .= 'FREQ=YEARLY'.$firstDayOfWeek;
					if($request['advanced_year_select'] == 'bydate') {

					}elseif($request['advanced_year_select'] == 'byyearday') {
						list($_day, $_month, $_year) = explode('-', $from);
						$byyearday = date('z', mktime(0,0,0, $_month, $_day, $_year)) + 1;
						if(array_key_exists('byyearday', $request)) {
							foreach($request['byyearday'] as $yearday) {
								$byyearday .= ',' . $yearday;
							}
						}
						$rrule .= ';BYYEARDAY=' . $byyearday;
					}elseif($request['advanced_year_select'] == 'byweekno') {
						//Fix
						$days = array_flip(Utility::getWeeklyOptions(Utility::$l10n));
						$byweekno = '';
						foreach($request['byweekno'] as $weekno) {
							if($byweekno == '') {
								$byweekno = $weekno;
							}else{
								$byweekno .= ',' . $weekno;
							}
						}
						$rrule .= ';BYWEEKNO=' . $byweekno;
						$byday = '';
							foreach($request['weeklyoptions'] as $day) {
								if($byday == '') {
								      $byday .= $days[$day];
								}else{
								      $byday .= ',' . $days[$day];
								}
							}
							$rrule .= ';BYDAY=' . $byday;
						
						
					}elseif($request['advanced_year_select'] == 'bydaymonth') {
						//FIXED Removed Weekly Options
						
						if(array_key_exists('rMonth', $request)) {
                           $rrule .= ';BYMONTH=' . $request['rMonth'];
 						}
						if(array_key_exists('rMonthday', $request)) {
							$rrule .= ';BYMONTHDAY=' . $request['rMonthday'];
						}
					}
					break;
				default:
					break;
			}

			if($interval != '') {
				$rrule .= ';INTERVAL=' . $interval;
			}
			
			if($end === 'count') {
				$rrule .= ';COUNT=' . $byoccurrences;
			}

			if($end === 'date') {
				list($bydate_day, $bydate_month, $bydate_year) = explode('-', $request['bydate']);
				$rrule .= ';UNTIL=' . $bydate_year . $bydate_month . $bydate_day;
			}
			
			$vevent->setString('RRULE', $rrule);
			$repeat = "true";
			
		}else{
			if(array_key_exists('sRuleRequest',$request)){
				$end = $request['end'];
			    $byoccurrences = $request['byoccurrences'];	
				$rrule = $request['sRuleRequest'];	
				if($end == 'count') {
				$rrule .= ';COUNT=' . $byoccurrences;
				}
				if($end == 'date') {
					list($bydate_day, $bydate_month, $bydate_year) = explode('-', $request['bydate']);
					$rrule .= ';UNTIL=' . $bydate_year . $bydate_month . $bydate_day;
				}
				$vevent->setString('RRULE', $rrule);
				$repeat = "true";
				
				if(!$vcalendar->VTIMEZONE && $request['repeat'] != 'doesnotrepeat'){
						
					$tz = Utility::getTimezone();
					$ex=explode('/', $tz, 2);
					
					$aTzTimes = $this->getTzDaylightStandard();
		
					if(isset($ex[1]) && array_key_exists($ex[0], $aTzTimes)){
					
						$summerTime=$aTzTimes[$ex[0]]['daylight'];
						$winterTime=$aTzTimes[$ex[0]]['standard'];
						$dateOffsetSummer=new \DateTime($summerTime, new \DateTimeZone('UTC'));	
						$dateOffsetSummer -> setTimezone(new \DateTimeZone($tz));	
						$offsetSummer= $dateOffsetSummer->format('O') ;
						$offsetSummerTZ= $dateOffsetSummer->format('T') ;
						$dateOffsetWinter=new \DateTime($winterTime, new \DateTimeZone('UTC'));	
						$dateOffsetWinter -> setTimezone(new \DateTimeZone($tz));	
						$offsetWinter= $dateOffsetWinter->format('O') ;
						$offsetWinterTZ= $dateOffsetWinter->format('T') ;
						
						$vcalendar->add('VTIMEZONE', [
						'TZID' => $tz,
						'DAYLIGHT' => [
							'TZOFFSETFROM' => $offsetWinter,
							'RRULE' => 'FREQ=YEARLY;BYMONTH='.$aTzTimes[$ex[0]]['daylightstart'].';BYDAY=-1SU',
							'DTSTART' => $summerTime,
							'TZNAME' => $offsetSummerTZ,
							'TZOFFSETTO' => $offsetSummer,
						],
						'STANDARD' => [
							'TZOFFSETFROM' => $offsetSummer,
							'RRULE' => 'FREQ=YEARLY;BYMONTH='.$aTzTimes[$ex[0]]['daylightend'].';BYDAY=-1SU',
							'DTSTART' => $winterTime,
							'TZNAME' => $offsetWinterTZ,
							'TZOFFSETTO' => $offsetWinter,
						]
						]);
					}
					
				}
				
			}else{	
				$repeat = "false";
			}
		}
         if($request["repeat"] == 'doesnotrepeat') {
         	$vevent->setString('RRULE', '');
         }

		$vevent->setDateTime('LAST-MODIFIED', 'now');
		$vevent->setDateTime('DTSTAMP', 'now');
		$vevent->setString('SUMMARY', $title);
        
        $oldStartTime=$vevent->DTSTART;
		
	 //  if($request["repeat"] == 'doesnotrepeat') {
			if($allday) {
	                $start = new \DateTime($from);
	                $end = new \DateTime($to.' +1 day');
	                $vevent->setDateTime('DTSTART', $start);
					$vevent->DTSTART['VALUE']='DATE';
	                $vevent->setDateTime('DTEND', $end);
					$vevent->DTEND['VALUE']='DATE';
	        }else{
	                $timezone = Utility::getTimezone();
	                $timezone = new \DateTimeZone($timezone);
	                $start = new \DateTime($from.' '.$fromtime, $timezone);
	                $end = new \DateTime($to.' '.$totime, $timezone);
	                $vevent->setDateTime('DTSTART', $start);
	                $vevent->setDateTime('DTEND', $end);
	        }
	   //}else{
	   	
	   //}
        
		if($vevent->EXDATE){
			$calcStartOld=$oldStartTime->getDateTime()->format('U');	
			$calcStartNew= $start->format('U');
			$timeDiff=$calcStartNew-$calcStartOld;
			if($timeDiff!=0){
					$delta = new \DateInterval('P0D');	
					
					$dMinutes=(int)($timeDiff/60);
					//$dTage=(int) ($dMinutes/(3600*24));
					//$delta->d = $dTage;
					$delta->i = $dMinutes;
					
			}
			
		}
		
		
		unset($vevent->DURATION);

		$vevent->setString('CLASS', $accessclass);
		$vevent->setString('LOCATION', $location);
		$vevent->setString('DESCRIPTION', $description);
		$vevent->setString('CATEGORIES', $categories);
		$vevent->setString('URL', $link);


		return $vcalendar;
	}

	/**
	 * @brief Extracts data from a vObject-Object
	 * @param VObject_VObject $object
	 * @return array
	 *
	 * [type, start, end, summary, repeating, uid]
	 */
	public function extractData($object) {
		$return = array('',null,null,'',0,null,0,'');

		// Child to use
		$children = 0;
		$use = null;
		foreach($object->children as $property) {
			
			if($property->name == 'VEVENT') {
				$children++;
				$thisone = true;
               
				foreach($property->children as &$element) {
					if($element->name == 'VALARM') {
						$return[6] = 1;
					}
					if($element->name == 'RECURRENCE-ID') {
						$thisone = false;
					}
					if($element->name == 'UID') {
						$return[5] = $element->getValue();
					}
					
				} unset($element);

				if($thisone) {
					$use = $property;
				}
			}
			elseif($property->name == 'VTODO' || $property->name == 'VJOURNAL') {
				$return[0] = $property->name;
				foreach($property->children as &$element) {
						
					if($element->name == 'VALARM') {
						$return[6] = 1;
					}
					if($element->name == 'RELATED-TO') {
						$return[7] = $element->getValue();
					}
						
					if($element->name == 'SUMMARY') {
						$return[3] = $element->getValue();
					}
					if($element->name == 'UID') {
						$return[5] = $element->getValue();
					}
					if($element->name == 'DTSTART') {
						$return[1] = Utility::getUTCforMDB($element->getDateTime());
					}
					if($element->name == 'DUE') {
						$return[2] = Utility::getUTCforMDB($element->getDateTime());
					}
					
					if($element->name == 'RRULE') {
						$return[4] = 1;
						/*
						$rrule=explode(';', $element->getValue());
						foreach ($rrule as $rule) {
							list($attr, $val) = explode('=', $rule);
							if($attr=='UNTIL'){
								$return[2] = self::getUTCforMDB(new \DateTime($val));
								
							}
						}*/
					}
				};

				// Only one VTODO or VJOURNAL per object
				// (only one UID per object but a UID is required by a VTODO =>
				//    one VTODO per object)
				break;
			}
		}

		// find the data
		if(!is_null($use)) {
			$return[0] = $use->name;
			foreach($use->children as $property) {
				if($property->name == 'DTSTART') {
					$return[1] = Utility::getUTCforMDB($property->getDateTime());
				}
				elseif($property->name == 'DTEND') {
					$return[2] = Utility::getUTCforMDB($property->getDateTime());
				}
				elseif($property->name == 'SUMMARY') {
					$return[3] = $property->getValue();
				}
				elseif($property->name == 'RRULE') {
					$return[4] = 1;
				}
				elseif($property->name == 'UID') {
					$return[5] = $property->getValue();
				}
				
			}
		}

		// More than one child means reoccuring!
		if($children > 1) {
			$return[4] = 1;
		}
		return $return;
	}
	
	/**
	 * @brief Remove all properties which should not be exported for the AccessClass Confidential
	 * @param string $ownerid owner of object
	 * @param VObject $vobject Sabre VObject
	 * @return object
	 */
	public function cleanByAccessClass($ownerid, $vobject ) {

		// Do not clean your own calendar
		if($ownerid === \OCP\USER::getUser()) {
			return $vobject;
		}

		if(isset($vobject->VEVENT)) {
			$velement = $vobject->VEVENT;
		}
		elseif(isset($vobject->VJOURNAL)) {
			$velement = $vobject->VJOURNAL;
		}
		elseif(isset($vobject->VTODO)) {
			$velement = $vobject->VTODO;
		}

		if(isset($velement->CLASS) && $velement->CLASS->getValue() === 'CONFIDENTIAL') {
			foreach ($velement->children as &$property) {
				switch($property->name) {
					case 'CREATED':
					case 'DTSTART':
					case 'RRULE':
					case 'DURATION':
					case 'DTEND':
					case 'CLASS':
					case 'UID':
						break;
					case 'SUMMARY':
						$property->setValue(Utility::$l10n->t('Busy'));
						break;
					default:
						$velement->__unset($property->name);
						unset($property);
						break;
				}
			}
		}
		return $vobject;
	}
	/**
	 * @brief validates time
	 * @param object $vobject
	 * @return permissions || false
	 */
	public function getAccessClassPermissions($vobject) {
		$velement='';	
		if(isset($vobject->VEVENT)) {
			$velement = $vobject->VEVENT;
		}
		elseif(isset($vobject->VJOURNAL)) {
			$velement = $vobject->VJOURNAL;
		}
		elseif(isset($vobject->VTODO)) {
			$velement = $vobject->VTODO;
		}

		if($velement !== '') {
			if(isset( $velement->CLASS)){	
				$accessclass = $velement->CLASS->getValue();
		   		return Utility::getAccessClassPermissions($accessclass);
			}else {
				return false;
			}
		}else return false;
	}
	
	/**
	 * @brief validates time
	 * @param string $time
	 * @return boolean
	 */
	private function checkTime($time) {
			
		if(strpos($time, ':') === false ) {
			return true;
		}
		
		list($hours, $minutes) = explode(':', $time);
		
		return empty($time)
			|| $hours < 0 || $hours > 24
			|| $minutes < 0 || $minutes > 60;
	}
	
	
	/**
	 * @return (array) Daylight and Standard Beginntime timezone
	 */
	private function getTzDaylightStandard() {
			
		$aTzTimes=[
				'Europe' =>[
					'daylight' => '19810329T020000',
					'standard' => '19961027T030000',
					'daylightstart' => '3',
					'daylightend' => '10'
				],
				'America' =>[
					'daylight' => '19810308T020000',
					'standard' => '19961101T020000',
					'daylightstart' => '3',
					'daylightend' => '11'
				],
				'Australia' =>[
					'daylight' => '20150405T030000',
					'standard' => '20161002T020000',
					'daylightstart' => '4',
					'daylightend' => '10'
				],
		];
		
		return $aTzTimes;
	}
	
	
	
}
