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
 
/**
 * This class manages our calendars
 */
 namespace OCA\CalendarPlus;
 
 use \OCA\CalendarPlus\Share\Backend\Calendar as ShareCalendar;
 use \OCA\CalendarPlus\Db\CalendarDAO;
 use \OCA\CalendarPlus\AppInfo\Application;
 
class Calendar{
	/**
	 * @brief Returns the list of calendars for a specific user.
	 * @param string $uid User ID
	 * @param boolean $active Only return calendars with this $active state, default(=false) is don't care
	 * @param boolean $bSubscribe  return calendars with this $issubscribe state, default(=true) is don't care
	 * @return array
	 */
	public static function allCalendars($uid, $active=false, $bSubscribe = true) {
			
		$dbObject = \OC::$server->getDb();	
		$calendarDB = new CalendarDAO($dbObject,$uid);	
		
		$calendars = $calendarDB->all($active, $bSubscribe);
		
		$calendars = array_merge($calendars, \OCP\Share::getItemsSharedWith(App::SHARECALENDAR, ShareCalendar::FORMAT_CALENDAR));
       
	    \OCP\Util::emitHook('OCA\CalendarPlus', 'getCalendars', array('calendar' => &$calendars));
		
		return $calendars;
	}

	/**
	 * @brief Returns the list of calendars for a principal (DAV term of user)
	 * @param string $principaluri
	 * @return array
	 */
	public static function allCalendarsWherePrincipalURIIs($principaluri) {
		$uid = self::extractUserID($principaluri);
		
		return self::allCalendars($uid);
		
	}
   
   public static function checkGroupRightsForPrincipal($uid){
   	   $appConfig = \OC::$server->getAppConfig();
   	   $isEnabled=$appConfig->getValue(App::$appname,'enabled');
	   $bEnabled=false;
	   if ($isEnabled === 'yes') {
	   	   $bEnabled=true;
	   } 
	   else if ($isEnabled === 'no') {
	   	  $bEnabled=false;
	   }
	   else if ($isEnabled !== 'no') {
	   	   $groups = json_decode($isEnabled);
		   if (is_array($groups)) {
				foreach ($groups as $group) {
					$group = \OC::$server->getGroupManager()->get($group);	
					
					if ($group->inGroup($uid)) {
						 $bEnabled=true;
						break;
					}
				}
		   }
	   }
	   if($bEnabled==false){
	   	 throw new \Sabre\DAV\Exception\Forbidden();	
	   	 return false;
	   }else return true;
	   
   }

	/**
	 * @brief Gets the data of one calendar
	 * @param integer $id
	 * @return associative array
	 */
	public static function find($id) {
			
		$user = \OCP\User::getUser();
		
		$dbObject = \OC::$server->getDb();	
		$calendarDB = new CalendarDAO($dbObject,$user);	
		
		$calendarInfo = $calendarDB->find($id);
		
		if($calendarInfo !== null){
		
			if($calendarInfo['userid'] !== $user) {
					$userExists = \OC::$server->getUserManager()->userExists($user);	
						
					if(!$userExists){
						$sharedCalendar=\OCP\Share::getItemSharedWithByLink(App::SHARECALENDAR,App::SHARECALENDARPREFIX.$id,$calendarInfo['userid']);
					}else{
						$sharedCalendar = \OCP\Share::getItemSharedWithBySource(App::SHARECALENDAR, App::SHARECALENDARPREFIX.$id);
					}
					
					if ((!$sharedCalendar || !(isset($sharedCalendar['permissions']) && $sharedCalendar['permissions'] & \OCP\PERMISSION_READ))) {
						
						return $calendarInfo; // I have to return the row so e.g. Object::getowner() works.
					}
					
					 $calendarInfo['permissions'] = $sharedCalendar['permissions'];
					
					
				} else {
					$calendarInfo['permissions'] = \OCP\PERMISSION_ALL;
				}
				
				return $calendarInfo;
		}else{
			return null;
		}
	}

	/**
	 * @brief Creates a new calendar
	 * @param string $userid
	 * @param string $name
	 * @param string $components Default: "VEVENT,VTODO,VJOURNAL"
	 * @param string $timezone Default: null
	 * @param integer $order Default: 1
	 * @param string $color Default: null, format: '#RRGGBB(AA)'
	 * @return insertid
	 */
	public static function addCalendar( $userid, $name, $components = 'VEVENT,VTODO,VJOURNAL', $timezone = null , $order = 0, $color = "#C2F9FC", $issubscribe = 0, $externuri = '',$lastmodified = 0 ) {
		$all = self::allCalendars($userid);
		$uris = array();
		foreach($all as $i) {
			$uris[] = $i['uri'];
		}
        if($lastmodified === 0){
        	 $lastmodified = time();
		}
		
		$uri = self::createURI($name, $uris );
		
		$dbObject = \OC::$server->getDb();	
		$calendarDB = new CalendarDAO($dbObject, $userid);	
		
		$insertid = $calendarDB->add($name, $uri, $order, $color, $timezone, $components, $issubscribe, $externuri, $lastmodified);
		
		if($insertid !== null){
			\OCP\Util::emitHook('\OCA\CalendarPlus', 'addCalendar', $insertid);
				
		     $link = \OC::$server->getURLGenerator()->linkToRoute(App::$appname.'.page.index');
			
			$params=array(
			    'mode'=>'created',
			    'link' =>$link,
			    'trans_type' =>'',
			    'summary' => $name,
			    'cal_user'=>$userid,
			    'cal_displayname'=>$name,
			    );
				
			ActivityData::logEventActivity($params,false,true);
			return $insertid;
		}else{
			return null;
		}
	}

	/**
	 * @brief Creates default calendars
	 * @param string $userid
	 * @return boolean
	 */
	public static function addDefaultCalendars($userid = null) {
		if(is_null($userid)) {
			$userid = \OCP\USER::getUser();
		}
		
		$id = self::addCalendar($userid,$userid);
		\OCP\Config::setUserValue($userid, App::$appname, 'choosencalendar', $id);
		\OCP\Config::setUserValue($userid, App::$appname, 'calendarnav', 'true');

		return true;
	}
	/**
	 * @brief Creates default calendars
	 * @param string $userid
	 * @return boolean
	 */
	public static function addSharedCalendars($userid = null) {
		if(is_null($userid)) {
			$userid = \OCP\USER::getUser();
		}
		
		$id = self::addCalendar($userid,'shared_events_'.$userid);

		return true;
	}
  
	/**
	 * @brief Creates a new calendar from the data sabredav provides
	 * @param string $principaluri
	 * @param string $uri
	 * @param string $name
	 * @param string $components
	 * @param string $timezone
	 * @param integer $order
	 * @param string $color format: '#RRGGBB(AA)'
	 * @return insertid
	 */
	
	//$name, $uri, $order, $color, $timezone, $components, $issubscribe, $externuri, $lastmodified
	public static function addCalendarFromDAVData($principaluri,$uri,$name,$components,$timezone,$order,$color,$transparent) {
			
		$userid = self::extractUserID($principaluri);
		$all = self::allCalendars($userid);
		$uris = array();
		foreach($all as $i) {
			$uris[] = $i['uri'];
		}
       
	    $lastmodified=time();
		
		$uri = self::createURI($name, $uris );
		
		$dbObject = \OC::$server->getDb();	
		$calendarDB = new CalendarDAO($dbObject, $userid);	
		
		$insertid = $calendarDB->add($name, $uri, $order, $color, $timezone, $components, 0, '', 0);
		
		if($insertid !== null){
			\OCP\Util::emitHook('\OCA\CalendarPlus', 'addCalendar', $insertid);
			return $insertid;
		}else{
			return null;
		}
	}

	/**
	 * @brief Edits a calendar
	 * @param integer $id
	 * @param string $name Default: null
	 * @param string $components Default: null
	 * @param string $timezone Default: null
	 * @param integer $order Default: null
	 * @param string $color Default: null, format: '#RRGGBB(AA)'
	 * @return boolean
	 *
	 * Values not null will be set
	 */
	public static function editCalendar($id,$name=null,$components=null,$timezone=null,$order=null,$color=null, $transparent = null) {
		// Need these ones for checking uri
		$calendar = self::find($id);
		$userid = \OCP\User::getUser();
		
		if($calendar['userid'] !== $userid) {
			$sharedCalendar = \OCP\Share::getItemSharedWithBySource(App::SHARECALENDAR, App::SHARECALENDARPREFIX.$id);
			if (!$sharedCalendar || !($sharedCalendar['permissions'] & \OCP\PERMISSION_UPDATE)) {
				throw new \Exception(
					App::$l10n->t(
						'You do not have the permissions to update this calendar.'
					)
				);
			}
		}
		// Keep old stuff
		if(is_null($name)) $name = $calendar['displayname'];
		if(is_null($components)) $components = $calendar['components'];
		if(is_null($timezone)) $timezone = $calendar['timezone'];
		if(is_null($order)) $order = $calendar['calendarorder'];
		if(is_null($color)) $color = $calendar['calendarcolor'];
		if(is_null($transparent)) $transparent = $calendar['transparent'];
		
		
		$dbObject = \OC::$server->getDb();	
		$calendarDB = new CalendarDAO($dbObject, $userid);	
		
		$bUpdateCalendar = $calendarDB->update($name, $order, $color, $timezone, $components, $transparent, $id);
		
		if($bUpdateCalendar === true){
			\OCP\Util::emitHook('\OCA\CalendarPlus', 'editCalendar', $id);
			
			$link = \OC::$server->getURLGenerator()->linkToRoute(App::$appname.'.page.index');
			
			$params=array(
			    'mode'=>'edited',
			    'link' =>$link,
			    'trans_type' =>'',
			    'summary' => $calendar['displayname'],
			    'cal_user'=>$calendar['userid'],
			    'cal_displayname'=>$calendar['displayname'],
			    );
				
			ActivityData::logEventActivity($params,false,true);
			
			return true;
			
		}else{
			return null;
		}
	}

	/**
	 * @brief Sets a calendar (in)active
	 * @param integer $id
	 * @param boolean $active
	 * @return boolean
	 */
	public static function setCalendarActive($id,$active) {
		
		$userid = \OCP\User::getUser();
			
		if($id !== 'birthday_'. $userid){	
			$calendar = self::find($id);
			if ($calendar['userid'] !== $userid) {
				$sharedCalendar = \OCP\Share::getItemSharedWithBySource(App::SHARECALENDAR, App::SHARECALENDARPREFIX.$id);
				
				if($sharedCalendar){
					\OCP\Config::setUserValue($userid,App::$appname, 'calendar_'.$id, $active);
				}
				/*
				if (!$sharedCalendar || !($sharedCalendar['permissions'] & \OCP\PERMISSION_UPDATE)) {
					throw new \Exception(
						App::$l10n->t(
							'You do not have the permissions to update this calendar.'
						)
					);
				}*/
			}else{
				
				$dbObject = \OC::$server->getDb();	
				$calendarDB = new CalendarDAO($dbObject, $userid);	
				
				$bUpdateCalendar = $calendarDB->activate($active, $id);	
				
				return $bUpdateCalendar;
			}
	} else{
		\OCP\Config::setUserValue($userid, App::$appname, 'calendar_'.$id, $active);
	}
		return true;
	}

	/**
	 * @brief Updates ctag for calendar
	 * @param integer $id
	 * @return boolean
	 */
	public static function touchCalendar($id) {
			
		$userid = \OCP\User::getUser();
		
		$dbObject = \OC::$server->getDb();	
		$calendarDB = new CalendarDAO($dbObject, $userid);	
		
		$bUpdateCalendar = $calendarDB->touch($id);	
		
		return $bUpdateCalendar;	
		
	}

	/**
	 * @brief removes a calendar
	 * @param integer $id
	 * @return boolean
	 */
	public static function deleteCalendar($id) {
		$calendar = self::find($id);
		//\OCP\Util::writeLog('DAV', 'DEL ID-> '.$id, \OCP\Util::DEBUG);
		
		$group = \OC::$server->getGroupManager()->get('admin');
		$user = \OCP\User::getUser();
		
		if($calendar['userid'] != $user && !$group->inGroup(\OC::$server->getUserSession()->getUser())) {
			$sharedCalendar = \OCP\Share::getItemSharedWithBySource(App::SHARECALENDAR,App::SHARECALENDARPREFIX. $id);
			if (!$sharedCalendar || !($sharedCalendar['permissions'] & \OCP\PERMISSION_DELETE)) {
				throw new \Exception(
					App::$l10n->t(
						'You do not have the permissions to delete this calendar.'
					)
				);
			}
		}
		
		$dbObject = \OC::$server->getDb();	
		$calendarDB = new CalendarDAO($dbObject, $user);	
		$bDeleteCalendar = $calendarDB->delete($id);	

		if($bDeleteCalendar === true){
				
			$stmt = \OCP\DB::prepare( 'DELETE FROM `'.App::CldObjectTable.'` WHERE `calendarid` = ?' );
			$stmt->execute(array($id));
			
	
			\OCP\Share::unshareAll(App::SHARECALENDAR, App::SHARECALENDARPREFIX. $id);
	
			//\OCP\Util::emitHook('\OCA\CalendarPlus', 'deleteCalendar', $id);
			$app = new Application();
			$c = $app->getContainer();
			$repeatController = $c->query('RepeatController');
			$repeatController->cleanCalendar($id);
			
			$calendars = self::allCalendars(\OCP\USER::getUser(), false, false);
			
			if((\OCP\USER::isLoggedIn() && count($calendars) === 0) || (count($calendars) === 1 && $calendars[0]['id'] === 'birthday_'.$user)) {
				self::addDefaultCalendars($user);
			}
 		
	 		$link = \OC::$server->getURLGenerator()->linkToRoute(App::$appname.'.page.index');
			
			$params=array(
			    'mode'=>'deleted',
			    'link' =>$link,
			    'trans_type' =>'',
			    'summary' => $calendar['displayname'],
			    'cal_user' =>$user,
			    'cal_displayname'=>$calendar['displayname'],
			 );
				
			ActivityData::logEventActivity($params,false,true);
		
		return $bDeleteCalendar;
		}else{
			return $bDeleteCalendar;
		}
	}

	/**
	 * @brief merges two calendars
	 * @param integer $id1
	 * @param integer $id2
	 * @return boolean
	 */
	public static function mergeCalendar($id1, $id2) {
		$calendar = self::find($id1);
		$group = \OC::$server->getGroupManager()->get('admin');
		$user = \OCP\User::getUser();
		
		if($calendar['userid'] !== $user && !$group->inGroup($user)) {
		
			$sharedCalendar = \OCP\Share::getItemSharedWithBySource(App::SHARECALENDAR,App::SHARECALENDARPREFIX. $id1);
			if (!$sharedCalendar || !($sharedCalendar['permissions'] & \OCP\PERMISSION_UPDATE)) {
				throw new \Exception(
					App::$l10n->t(
						'You do not have the permissions to add to this calendar.'
					)
				);
			}
		}
		
		$dbObject = \OC::$server->getDb();	
		$calendarDB = new CalendarDAO($dbObject, $user);	
		$bMergeCalendar = $calendarDB->merge($id1, $id2);	
		
		if($bMergeCalendar === true){
			self::touchCalendar($id1);
			self::deleteCalendar($id2);
			return $bMergeCalendar;
		}else{
			return $bMergeCalendar;
		}
	}

	/**
	 * @brief Creates a URI for Calendar
	 * @param string $name name of the calendar
	 * @param array  $existing existing calendar URIs
	 * @return string uri
	 */
	public static function createURI($name,$existing) {
		$strip=array(' ','/','?','&');//these may break sync clients
		$name=str_replace($strip,'',$name);
		$name = strtolower($name);

		$newname = $name;
		$i = 1;
		while(in_array($newname,$existing)) {
			$newname = $name.$i;
			$i = $i + 1;
		}
		return $newname;
	}

	/**
	 * @brief gets the userid from a principal path
	 * @return string
	 */
	public static function extractUserID($principaluri) {
		list($prefix,$userid) = \Sabre\DAV\URLUtil::splitPath($principaluri);
		return $userid;
	}

	/**
	 * @brief returns the possible color for calendars
	 * @return array
	 */
	public static function getCalendarColorOptions() {
		return array(
			'#ff0000', // "Red"
			'#b3dc6c', // "Green"
			'#ffff00', // "Yellow"
			'#808000', // "Olive"
			'#ffa500', // "Orange"
			'#ff7f50', // "Coral"
			'#ee82ee', // "Violet"
			'#9fc6e7', // "light blue"
		);
	}

	/**
	 * @brief generates the Event Source Info for our JS
	 * @param array $calendar calendar data
	 * @return array
	 */
	public static function getEventSourceInfo($calendar, $isPublic = false) {
		$bEdit=true;
		if($calendar['issubscribe'] || $isPublic === true){
			$bEdit=false;
		}	
		
			
			$bgColor = '';
			$borderColor = '';
			$textColor = '';
			if(isset($calendar['calendarcolor'])){
				$bgColor = $calendar['calendarcolor'];
				$borderColor = $bgColor;
				$textColor = self::generateTextColor($bgColor);
			}
			
			$addClass = 'events-all-generic';
			if(isset($calendar['className'])){
				$addClass = $calendar['className'];
			}
			
		
		return array(
			'url' => \OC::$server->getURLGenerator()->linkToRoute(App::$appname.'.event.getEvents').'?calendar_id='.$calendar['id'],
			'backgroundColor' => $bgColor,
			'borderColor' => $borderColor,
			'textColor' => $textColor,
			'ctag'=>$calendar['ctag'],
			'id'=>$calendar['id'],
			'issubscribe'=>$calendar['issubscribe'],
			'startEditable'=> $bEdit,
			'className' => $addClass,
			'cache' => false,
		);
	}

	/*
	 * @brief checks if a calendar name is available for a user
	 * @param string $calendarname
	 * @param string $userid
	 * @return boolean
	 */
	public static function isCalendarNameavailable($calendarname, $userid) {
		$calendars = self::allCalendars($userid);
		foreach($calendars as $calendar) {
			if($calendar['displayname'] == $calendarname) {
				return false;
			}
		}
		return true;
	}

	/*
	 * @brief generates the text color for the calendar
	 * @param string $calendarcolor rgb calendar color code in hex format (with or without the leading #)
	 * (this function doesn't pay attention on the alpha value of rgba color codes)
	 * @return boolean
	 */
	public static function generateTextColor($calendarcolor) {
		if(substr_count($calendarcolor, '#') == 1) {
			$calendarcolor = substr($calendarcolor,1);
		}
		$red = hexdec(substr($calendarcolor,0,2));
		$green = hexdec(substr($calendarcolor,2,2));
		$blue = hexdec(substr($calendarcolor,4,2));
		//recommendation by W3C
		$computation = ((($red * 299) + ($green * 587) + ($blue * 114)) / 1000);
		return ($computation > 130)?'#000000':'#FAFAFA';
	}
	
	public static function permissionReader($iPermission){
			
			$l = App::$l10n;
			
			
			$aPermissionArray=array(
			   16 =>(string) $l->t('share'),
			   8 => (string)$l->t('delete'),
			   4 => (string)$l->t('create'),
			   2 =>(string) $l->t('update'),
			   1 =>(string) $l->t('readonly')
			);
			
			if($iPermission==1) return (string) $l->t('readonly');
			if($iPermission==31) return (string) $l->t('full access');
			
			$outPutPerm='';
			foreach($aPermissionArray as $key => $val){
				if($iPermission>= $key){
					if($outPutPerm=='') $outPutPerm.=$val;
					else $outPutPerm.=', '.$val;
					$iPermission-=$key;
				}
			}
			return $outPutPerm;
		
	}
	/**
	 * @brief Get the email address of a user
	 * @returns the email address of the user

	 * This method returns the email address of selected user.
	 */
	public static function getUsersEmails($names) {
		return \OCP\Config::getUserValue(\OCP\User::getUser(), 'settings', 'email');
	}
	
}
