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
 * This class manages our calendar objects
 */
 namespace OCA\CalendarPlus;
 
class Object{
	/**
	 * @brief Returns all objects of a calendar
	 * @param integer $id
	 * @return array
	 *
	 * The objects are associative arrays. You'll find the original vObject in
	 * ['calendardata']
	 */
	public static function all($id) {
		$stmt = \OCP\DB::prepare( 'SELECT * FROM `'.App::CldObjectTable.'` WHERE `calendarid` = ? ');
		$result = $stmt->execute(array($id));

		$calendarobjects = array();
		while( $row = $result->fetchRow()) {
			$calendarobjects[] = $row;
		}

		return $calendarobjects;
	}
   
   /**
	 * @brief Returns true or false if event is an shared event
	 * @param integer $id
	 * 
	 * @return true or false
	 *
	 */

    public static function checkSharedEvent($id){
    	  
		   $stmt = \OCP\DB::prepare('
    	   SELECT `id` FROM `'.App::CldObjectTable.'`
     	   WHERE `org_objid` = ? AND `userid` = ? AND `objecttype` = ?');
		   $result = $stmt->execute(array($id,\OCP\User::getUser(),'VEVENT'));
		   $row = $result->fetchRow();
		   if(is_array($row)) return $row;
		   else return false;
    }
	
	/**
	 * @brief Returns all  shared events where user is owner
	 * 
	 * @return array
	 */
	 
    public static function getEventSharees(){
   	
		$SQL='SELECT `item_source`, `item_type` FROM `*PREFIX*share` WHERE `uid_owner` = ? AND `item_type` = ?';
		$stmt = \OCP\DB::prepare($SQL);
		$result = $stmt->execute(array(\OCP\User::getUser(),App::SHAREEVENT));
		$aSharees = '';
		while( $row = $result->fetchRow()) {
			$itemSource = App::validateItemSource($row['item_source'], App::SHAREEVENTPREFIX);	
			$aSharees[$itemSource] = 1;
		}
		
		if(is_array($aSharees)) return $aSharees;
		else return false;
    }
	
	
	/**
	 * @brief Returns all  shared calendar where user is owner
	 * 
	 * @return array
	 */
	
	public static function getCalendarSharees(){
    	
		$SQL='SELECT item_source,share_with,share_type,permissions,item_type FROM `*PREFIX*share` 
		WHERE `uid_owner` = ? AND `item_type` = ? 
		ORDER BY `item_source` ASC
		';
		$stmt = \OCP\DB::prepare($SQL);
		$result = $stmt->execute(array(\OCP\User::getUser(),App::SHARECALENDAR));
		$aSharees = '';
		
		while( $row = $result->fetchRow()) {
			$shareWith='';
			$itemSource = App::validateItemSource($row['item_source'],App::SHARECALENDARPREFIX);
			
			if($row['share_with'] && $row['share_type'] != 3 ) {
				$shareWith=': '.$row['share_with'];
			}
			
			if($row['share_with'] && $row['share_type'] == 3 ) {
				$shareWith=': password protected ';
			}
			
			
			$shareDescr = self::shareTypeDescription($row['share_type']).' '.$shareWith.' ('.Calendar::permissionReader($row['permissions']).")<br>";
			//$aSharees[][$itemSource]=array('myShare'=>1,'shareTypeDescr'=>$shareDescr);
			$aSharees[]=['itemSource' => $itemSource, 'descr' => $shareDescr];
		}
		
		if(is_array($aSharees)){
			$aReturn=[];
			$oldId='';
			foreach($aSharees as $shareInfo){
				if($shareInfo['itemSource'] != $oldId){
					$aReturn[$shareInfo['itemSource']] = $shareInfo['descr'];
				}else{
					$aReturn[$shareInfo['itemSource']] .= $shareInfo['descr'];
				}
				$oldId = $shareInfo['itemSource'];
			}	
				
			return $aReturn;
		} 
		else return false;
    }
    
	public static function shareTypeDescription($ShareType){
		
			$ShareTypeDescr='';	
			if($ShareType==0) $ShareTypeDescr=App::$l10n->t('user');
			if($ShareType==1) $ShareTypeDescr=App::$l10n->t('group');
			if($ShareType==3) $ShareTypeDescr='By Link';
			
			return $ShareTypeDescr;
		
	}

	/**
	 * @brief Returns all objects of a calendar between $start and $end
	 * @param integer $id
	 * @param DateTime $start
	 * @param DateTime $end
	 * @return array
	 *
	 * The objects are associative arrays. You'll find the original vObject
	 * in ['calendardata']
	 */
	 
	 public static function allInPeriod($id, $start, $end) {
		
	   $sharedwithByEvents = self::getEventSharees();
   
			
		$stmt = \OCP\DB::prepare( 'SELECT * FROM `'.App::CldObjectTable.'` WHERE `calendarid` = ? AND `objecttype`= ?' 
		.' AND ((`startdate` >= ? AND `enddate` <= ? AND `repeating` = 0)'
		.' OR (`enddate` >= ? AND `startdate` <= ? AND `repeating` = 0)'
		.' OR (`startdate` <= ? AND `repeating` = 1) )' );
		$start = self::getUTCforMDB($start);
		$end = self::getUTCforMDB($end);
		
		$result = $stmt->execute(array($id,'VEVENT',
					$start, $end,					
					$start, $end,
					$end));
    
		$calendarobjects = array();
		while( $row = $result->fetchRow()) {
			
			
			$row['shared'] = 0;
			if(is_array($sharedwithByEvents) && isset($sharedwithByEvents[$row['id']])){
				 $row['shared'] = 1;
				\OCP\Util::writeLog(App::$appname,'Events Shared Found: ->'.$row['id'], \OCP\Util::DEBUG);
			}
			
			$calendarobjects[] = $row;
			
			
				
		}

		return $calendarobjects;
	}
	
	
	
	
	/**
	 * @brief Returns an object
	 * @param integer $id
	 * @return associative array
	 */
	public static function find($id) {
		$stmt = \OCP\DB::prepare( 'SELECT * FROM `'.App::CldObjectTable.'` WHERE `id` = ?' );
		$result = $stmt->execute(array($id));

		return $result->fetchRow();
	}

	/**
	 * @brief finds an object by its DAV Data
	 * @param integer $cid Calendar id
	 * @param string $uri the uri ('filename')
	 * @return associative array
	 */
	public static function findWhereDAVDataIs($cid,$uri) {
		$stmt = \OCP\DB::prepare( 'SELECT * FROM `'.App::CldObjectTable.'` WHERE `calendarid` = ? AND `uri` = ?' );
		$result = $stmt->execute(array($cid,$uri));

		return $result->fetchRow();
	}

	/**
	 * @brief Adds an object
	 * @param integer $id Calendar id
	 * @param string $data  object
	 * @return insertid
	 */
	public static function add($id,$data, $shared = false, $eventid=0) {
		$calendar = Calendar::find($id);
		if ($calendar['userid'] != \OCP\User::getUser()) {
			$sharedCalendar = \OCP\Share::getItemSharedWithBySource(App::SHARECALENDAR, App::SHARECALENDARPREFIX.$id);
			if (!$sharedCalendar || !($sharedCalendar['permissions'] & \OCP\PERMISSION_CREATE)) {
				throw new \Exception(
					App::$l10n->t(
						'You do not have the permissions to add events to this calendar.'
					)
				);
			}
		}
		
		
		$object = VObject::parse($data);
		list($type,$startdate,$enddate,$summary,$repeating,$uid,$isAlarm,$relatedTo) = self::extractData($object);
		
		if(is_null($uid)) {
			$object->setUID();
			$data = $object->serialize();
		}

		$uri = 'owncloud-'.md5($data.rand().time()).'.ics';
		$values=[$id,$type,$startdate,$enddate,$repeating,$summary,$data,$uri,time(),$isAlarm,$uid,$relatedTo];
		if($shared === true){
			$values[]=$eventid;
			$values[]=\OCP\User::getUser();
		}else{
			$values[]=0;
			$values[]=null;
		}
		
		$stmt = \OCP\DB::prepare( 'INSERT INTO `'.App::CldObjectTable.'` (`calendarid`,`objecttype`,`startdate`,`enddate`,`repeating`,`summary`,`calendardata`,`uri`,`lastmodified`,`isalarm`,`eventuid`,`relatedto`,`org_objid`,`userid`) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?)' );
		$stmt->execute($values);
		$object_id = \OCP\DB::insertid(App::CldObjectTable);
        
		
		
		//\OCP\Util::writeLog('core','FINDERCAL->'.$object->VEVENT->CATEGORIES->getParts(), \OCP\Util::DEBUG);		
		
		App::loadCategoriesFromVCalendar($object_id, $object);

		Calendar::touchCalendar($id);
		\OCP\Util::emitHook('\OCA\CalendarPlus', 'addEvent', $object_id);
		
		 $linkTypeApp=App::$appname;
	     if($type=='VTODO') {
	     	$linkTypeApp='tasksplus';
	     }
	     
		$link = \OC::$server->getURLGenerator()->linkToRoute($linkTypeApp.'.page.index').'#'.urlencode($object_id);
		$params=array(
		    'mode'=>'created',
		    'link' =>$link,
		    'trans_type' =>App::$l10n->t($type),
		    'summary' => $summary,
		    'cal_user'=>$calendar['userid'],
		    'cal_displayname'=>$calendar['displayname'],
		    );
			
		ActivityData::logEventActivity($params);
		
		
		
		return $object_id;
	}

    /**
	 * @brief Adds an object
	 * @param integer $id Calendar id
	 * @param string $data  object
	 * @return insertid
	 */
	public static function addSharedEvent($id,$calid) {
		$shareevent = self::find($id);
		
		self::add($calid,$shareevent['calendardata'],true,$id);
		
	}


	/**
	 * @brief Adds an object with the data provided by sabredav
	 * @param integer $id Calendar id
	 * @param string $uri   the uri the card will have
	 * @param string $data  object
	 * @return insertid
	 */
	public static function addFromDAVData($id,$uri,$data) {
		$calendar = Calendar::find($id);
		if ($calendar['userid'] != \OCP\User::getUser()) {
			$sharedCalendar = \OCP\Share::getItemSharedWithBySource(App::SHARECALENDAR,App::SHARECALENDARPREFIX. $id);
			if (!$sharedCalendar || !($sharedCalendar['permissions'] & \OCP\PERMISSION_CREATE)) {
				throw new \Sabre\DAV\Exception\Forbidden(
					App::$l10n->t(
						'You do not have the permissions to add events to this calendar.'
					)
				);
			}
		}
		$object = VObject::parse($data);
		list($type,$startdate,$enddate,$summary,$repeating,$uid,$isAlarm,$relatedTo) = self::extractData($object);
      
		
		$stmt = \OCP\DB::prepare( 'INSERT INTO `'.App::CldObjectTable.'` (`calendarid`,`objecttype`,`startdate`,`enddate`,`repeating`,`summary`,`calendardata`,`uri`,`lastmodified`,`isalarm`,`eventuid`,`relatedto`) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)' );
		$stmt->execute(array($id,$type,$startdate,$enddate,$repeating,$summary,$data,$uri,time(),$isAlarm,$uid,$relatedTo));
		$object_id = \OCP\DB::insertid(App::CldObjectTable);

		Calendar::touchCalendar($id);
		\OCP\Util::emitHook('\OCA\CalendarPlus', 'addEvent', $object_id);
		
		$linkTypeApp=App::$appname;
	     if($type=='VTODO'){
	     	$linkTypeApp = 'tasksplus';
	     } 
		 
	    $link = \OC::$server->getURLGenerator()->linkToRoute($linkTypeApp.'.page.index').'#'.urlencode($object_id);

		
		$params=array(
		    'mode'=>'created',
		    'link' =>$link,
		    'trans_type' =>App::$l10n->t($type),
		    'summary' => $summary,
		    'cal_user'=>$calendar['userid'],
		    'cal_displayname'=>$calendar['displayname'],
		    );
			
		ActivityData::logEventActivity($params,true);
		
		return $object_id;
	}

    public static function checkShareMode($calid){
       
	    $bCheckCalUser=false;
		$stmt = \OCP\DB::prepare( 'SELECT share_with, share_type FROM `*PREFIX*share` WHERE `item_type`= ? AND `item_source` = ? ' );
		$result = $stmt->execute(array(App::SHARECALENDAR,App::SHARECALENDARPREFIX.$calid));
        while( $row = $result->fetchRow()) {
			if($row['share_type'] === 1 && \OC::$server->getGroupManager()->groupExists($row['share_with'])){
				$group = \OC::$server->getGroupManager()->get($row['share_with']);	
				if ($row['share_with'] == \OCP\User::getUser() || $group->inGroup(\OCP\User::getUser())) {
					 $bCheckCalUser=true;
				}
			}else{
				if ($row['share_with'] == \OCP\User::getUser()) {
					 $bCheckCalUser=true;
				}
			}
		}
		 return $bCheckCalUser;
		
    }
	
	public static function checkShareEventMode($eventid){
    	  //$usersInGroup = \OC_Group::usersInGroup($row['share_with']);  inGroup( $uid, $gid )
    
	    $bCheckCalUser=false;
		$stmt = \OCP\DB::prepare( 'SELECT share_with,share_type FROM `*PREFIX*share` WHERE `item_type`= ? AND `item_source` = ?' );
		$result = $stmt->execute(array(App::SHAREEVENT,App::SHAREEVENTPREFIX.$eventid));
       
		while( $row = $result->fetchRow()) {
			if($row['share_type'] === 1 && \OC::$server->getGroupManager()->groupExists($row['share_with'])){
				$group = \OC::$server->getGroupManager()->get($row['share_with']);	
				if ($row['share_with'] == \OCP\User::getUser() || $group->inGroup(\OCP\User::getUser())) {
					 $bCheckCalUser=true;
				}
			}else{
				if ($row['share_with'] == \OCP\User::getUser()) {
					 $bCheckCalUser=true;
				}
			}
		}
		
		 return $bCheckCalUser;
		
    }
	
	/**
	 * @brief edits an object
	 * @param integer $id id of object
	 * @param string $data  object
	 * @return boolean
	 */
	public static function edit($id, $data) {
		$oldobject = self::find($id);
		$calid = self::getCalendarid($id);
		$calendar = Calendar::find($calid);
		
		if ($calendar['userid'] == \OCP\User::getUser() && $calendar['issubscribe']) {
			exit();
		}
		
		$oldvobject = VObject::parse($oldobject['calendardata']);
		
		if ($calendar['userid'] != \OCP\User::getUser()) {
				
			$shareMode=self::checkShareMode($calid);
			if($shareMode){
				$sharedCalendar = \OCP\Share::getItemSharedWithBySource(App::SHARECALENDAR, App::SHARECALENDARPREFIX.$calid); //calid, not objectid !!!! 1111 one one one eleven
			}else{
				$sharedCalendar = \OCP\Share::getItemSharedWithBySource(App::SHAREEVENT,App::SHAREEVENTPREFIX. $id); 
			}
			
			$sharedAccessClassPermissions = Object::getAccessClassPermissions($oldvobject);
			
			if (!$sharedCalendar || !($sharedCalendar['permissions'] & \OCP\PERMISSION_UPDATE) || !($sharedAccessClassPermissions & \OCP\PERMISSION_UPDATE)) {
				
				throw new \Exception(
					App::$l10n->t('You do not have the permissions to edit this event.')
				);
			}
		}
		
		
		$object = VObject::parse($data);
		App::loadCategoriesFromVCalendar($id, $object);
		list($type,$startdate,$enddate,$summary,$repeating,$uid,$isAlarm) = self::extractData($object);

        //check Share
        $stmtShare = \OCP\DB::prepare("SELECT COUNT(*) AS COUNTSHARE FROM `*PREFIX*share` WHERE `item_source` = ? AND `item_type`= ? ");
        $result=$stmtShare->execute(array(App::SHAREEVENTPREFIX.$id,App::SHAREEVENT));
		$row = $result->fetchRow();
		
        if($row['COUNTSHARE']>=1){
        		$stmtShareUpdate = \OCP\DB::prepare( "UPDATE `*PREFIX*share` SET `item_target`= ? WHERE `item_source` = ? AND `item_type` = ? ");
		        $stmtShareUpdate->execute(array($summary,App::SHAREEVENTPREFIX.$id,App::SHAREEVENT));
				
				$stmt = \OCP\DB::prepare( 'UPDATE `'.App::CldObjectTable.'` SET `objecttype`=?,`startdate`=?,`enddate`=?,`repeating`=?,`summary`=?,`calendardata`=?,`lastmodified`= ?,`isalarm`= ? WHERE `org_objid` = ?' );
		        $stmt->execute(array($type,$startdate,$enddate,$repeating,$summary,$data,time(),$isAlarm,$id));
        }
		$stmt = \OCP\DB::prepare( 'UPDATE `'.App::CldObjectTable.'` SET `objecttype`=?,`startdate`=?,`enddate`=?,`repeating`=?,`summary`=?,`calendardata`=?,`lastmodified`= ?,`isalarm`= ? WHERE `id` = ?' );
		$stmt->execute(array($type,$startdate,$enddate,$repeating,$summary,$data,time(),$isAlarm,$id));

		Calendar::touchCalendar($oldobject['calendarid']);
		\OCP\Util::emitHook('\OCA\CalendarPlus', 'editEvent', $id);
		
		/****Activity New ***/
		
		
		$link = \OC::$server->getURLGenerator()->linkToRoute(App::$appname.'.page.index') . '#' . urlencode($id);
		$params=array(
		    'mode'=>'edited',
		    'link' =>$link,
		    'trans_type' =>App::$l10n->t($type),
		    'summary' => $summary,
		    'cal_user'=>$calendar['userid'],
		    'cal_displayname'=>$calendar['displayname'],
		    );
			
		ActivityData::logEventActivity($params);
		
		
		return true;
	}

	/**
	 * @brief edits an object with the data provided by sabredav
	 * @param integer $id calendar id
	 * @param string $uri   the uri of the object
	 * @param string $data  object
	 * @return boolean
	 */
	public static function editFromDAVData($cid,$uri,$data) {
		$oldobject = self::findWhereDAVDataIs($cid,$uri);

		$calendar = Calendar::find($cid);
		$oldvobject = VObject::parse($oldobject['calendardata']);
		if ($calendar['userid'] != \OCP\User::getUser()) {
			$sharedCalendar = \OCP\Share::getItemSharedWithBySource(App::SHARECALENDAR,App::SHARECALENDARPREFIX. $cid);
			$sharedAccessClassPermissions = Object::getAccessClassPermissions($oldvobject);
			if (!$sharedCalendar || !($sharedCalendar['permissions'] & \OCP\PERMISSION_UPDATE) || !($sharedAccessClassPermissions & \OCP\PERMISSION_UPDATE)) {
				throw new \Sabre\DAV\Exception\Forbidden(
					App::$l10n->t(
						'You do not have the permissions to edit this event.'
					)
				);
			}
		}
		$object = VObject::parse($data);
		list($type,$startdate,$enddate,$summary,$repeating,$uid,$isAlarm,$relatedTo) = self::extractData($object);
	

		$stmt = \OCP\DB::prepare( 'UPDATE `'.App::CldObjectTable.'` SET `objecttype`=?,`startdate`=?,`enddate`=?,`repeating`=?,`summary`=?,`calendardata`=?,`lastmodified`= ?,`isalarm`= ?,`eventuid`= ?,`relatedto`= ? WHERE `id` = ?' );
		$stmt->execute(array($type,$startdate,$enddate,$repeating,$summary,$data,time(),$isAlarm,$uid,$relatedTo,$oldobject['id']));

		/*
		if ($repeating == 1 && $type=='VTODO') {
					$due = new \DateTime($startdate);
					$object -> expand($due, $due);
				
				foreach ($object->getComponents() as $singleevent) {
					if (!($singleevent instanceof \Sabre\VObject\Component\VTodo)) {
						continue;
					}
					$dynamicoutput=self::generateStartEndDate($singleevent -> DTSTART, self::getDTEndFromVEvent($singleevent), false, \OCA\Calendar\App::$tz);

					//$output[] = array_merge($staticoutput, $dynamicoutput);

				}
		}*/
		Calendar::touchCalendar($oldobject['calendarid']);
		\OCP\Util::emitHook('\OCA\CalendarPlus', 'editEvent', $oldobject['id']);
        
        $linkTypeApp=App::$appname;
	     if($type=='VTODO') {
	     	$linkTypeApp = 'tasksplus';
		} 
	    
		$link = \OC::$server->getURLGenerator()->linkToRoute($linkTypeApp.'.page.index').'#'.urlencode($oldobject['calendarid']);
		$params=array(
		    'mode'=>'edited',
		    'link' =>$link,
		    'trans_type' =>App::$l10n->t($type),
		    'summary' => $summary,
		    'cal_user'=>$calendar['userid'],
		    'cal_displayname'=>$calendar['displayname'],
		    );
			
		ActivityData::logEventActivity($params,true);
		
		return true;
	}

	/**
	 * @brief deletes an object
	 * @param integer $id id of object
	 * @return boolean
	 */
	public static function delete($id) {
		$oldobject = self::find($id);
		$calid = self::getCalendarid($id);
		
		$calendar = Calendar::find($calid);
		$oldvobject = VObject::parse($oldobject['calendardata']);
		if ($calendar['userid'] != \OCP\User::getUser()) {
			$shareMode=self::checkShareMode($calid);
			if($shareMode){
				$sharedCalendar = \OCP\Share::getItemSharedWithBySource(App::SHARECALENDAR,App::SHARECALENDARPREFIX. $calid); //calid, not objectid !!!! 1111 one one one eleven
			}else{
				$sharedCalendar = \OCP\Share::getItemSharedWithBySource(App::SHAREEVENT, App::SHAREEVENTPREFIX.$id); 
			}
			
			$sharedAccessClassPermissions = Object::getAccessClassPermissions($oldvobject);
			if (!$sharedCalendar || !($sharedCalendar['permissions'] & \OCP\PERMISSION_DELETE) || !($sharedAccessClassPermissions & \OCP\PERMISSION_DELETE)) {
				throw new \Exception(
					App::$l10n->t(
						'You do not have the permissions to delete this event.'
					)
				);
			}
		}
		$stmt = \OCP\DB::prepare( 'DELETE FROM `'.App::CldObjectTable.'` WHERE `id` = ?' );
		$stmt->execute(array($id));
		
        
		//DELETE SHARED ONLY EVENT
		if(\OCP\Share::unshareAll(App::SHAREEVENT,App::SHAREEVENTPREFIX. $id)){
			//if($delId=Object::checkSharedEvent($id)){
				$stmt = \OCP\DB::prepare( 'DELETE FROM `'.App::CldObjectTable.'` WHERE `org_objid` = ?' );
		        $stmt->execute(array($id));
			//}
		}
		
        Calendar::touchCalendar($oldobject['calendarid']);
		
		\OCP\Util::emitHook('\OCA\CalendarPlus', 'deleteEvent', $id);
		
		
		$params=array(
		    'mode'=>'deleted',
		    'link' =>'',
		    'trans_type' =>App::$l10n->t($oldobject['objecttype']),
		    'summary' => $oldobject['summary'],
		    'cal_user'=>$calendar['userid'],
		    'cal_displayname'=>$calendar['displayname'],
		    );
			
		ActivityData::logEventActivity($params);

        

		//App::getVCategories()->purgeObject($id);

		return true;
	}

	/**
	 * @brief deletes an  object with the data provided by \Sabredav
	 * @param integer $cid calendar id
	 * @param string $uri the uri of the object
	 * @return boolean
	 */
	public static function deleteFromDAVData($cid,$uri) {
		$oldobject = self::findWhereDAVDataIs($cid, $uri);
		$calendar = Calendar::find($cid);
		if ($calendar['userid'] != \OCP\User::getUser()) {
			$sharedCalendar = \OCP\Share::getItemSharedWithBySource(App::SHARECALENDAR, App::SHARECALENDARPREFIX.$cid);
			if (!$sharedCalendar || !($sharedCalendar['permissions'] & \OCP\PERMISSION_DELETE)) {
				throw new VObject_DAV_Exception_Forbidden(
					App::$l10n->t(
						'You do not have the permissions to delete this event.'
					)
				);
			}
		}
		$stmt = \OCP\DB::prepare( 'DELETE FROM `'.App::CldObjectTable.'` WHERE `calendarid`= ? AND `uri`=?' );
		$stmt->execute(array($cid,$uri));
		Calendar::touchCalendar($cid);
		\OCP\Util::emitHook('\OCA\CalendarPlus', 'deleteEvent', $oldobject['id']);
       
	    
		
		$params=array(
		    'mode'=>'deleted',
		    'link' =>'',
		    'trans_type' =>App::$l10n->t($oldobject['objecttype']),
		    'summary' => $oldobject['summary'],
		    'cal_user'=>$calendar['userid'],
		    'cal_displayname'=>$calendar['displayname'],
		    );
			
		ActivityData::logEventActivity($params,true);


		return true;
	}

	public static function moveToCalendar($id, $calendarid) {
		$calendar = Calendar::find($calendarid);
		if ($calendar['userid'] != \OCP\User::getUser()) {
			$sharedCalendar = \OCP\Share::getItemSharedWithBySource(App::SHARECALENDAR,App::SHARECALENDARPREFIX. $calendarid);
			if (!$sharedCalendar || !($sharedCalendar['permissions'] & \OCP\PERMISSION_DELETE)) {
				throw new \Exception(
					App::$l10n->t(
						'You do not have the permissions to add events to this calendar.'
					)
				);
			}
		}
		$stmt = \OCP\DB::prepare( 'UPDATE `'.App::CldObjectTable.'` SET `calendarid`=? WHERE `id`=?' );
		$stmt->execute(array($calendarid,$id));

		Calendar::touchCalendar($calendarid);
		\OCP\Util::emitHook('\OCA\CalendarPlus', 'moveEvent', $id);

		return true;
	}

	/**
     * @brief Creates a UID
     * @return string
     */
    protected static function createUID() {
        return substr(md5(rand().time()),0,10);
    }

	/**
	 * @brief Extracts data from a vObject-Object
	 * @param VObject_VObject $object
	 * @return array
	 *
	 * [type, start, end, summary, repeating, uid]
	 */
	public static function extractData($object) {
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
						$return[1] = self::getUTCforMDB($element->getDateTime());
					}
					if($element->name == 'DUE') {
						$return[2] = self::getUTCforMDB($element->getDateTime());
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
					$return[1] = self::getUTCforMDB($property->getDateTime());
				}
				elseif($property->name == 'DTEND') {
					$return[2] = self::getUTCforMDB($property->getDateTime());
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
	 * @brief DateTime to UTC string
	 * @param DateTime $datetime The date to convert
	 * @returns date as YYYY-MM-DD hh:mm
	 *
	 * This function creates a date string that can be used by MDB2.
	 * Furthermore it converts the time to UTC.
	 */
	public static function getUTCforMDB($datetime) {
		return date('Y-m-d H:i', $datetime->format('U'));
	}

	/**
	 * @brief returns the DTEND of an $vevent object
	 * @param object $vevent vevent object
	 * @return object
	 */
	public static function getDTEndFromVEvent($vevent) {
		if ($vevent->DTEND) {
			$dtend = $vevent->DTEND;
		}else{
			$dtend = clone $vevent->DTSTART;
			// clone creates a shallow copy, also clone DateTime
			$dtend->setDateTime(clone $dtend->getDateTime());
			if ($vevent->DURATION) {
				$duration = strval($vevent->DURATION);
				$invert = 0;
				if ($duration[0] == '-') {
					$duration = substr($duration, 1);
					$invert = 1;
				}
				if ($duration[0] == '+') {
					$duration = substr($duration, 1);
				}
				$interval = new \DateInterval($duration);
				$interval->invert = $invert;
				$dtend->getDateTime()->add($interval);
			}
		}
		return $dtend;
	}

	/**
	 * @brief Remove all properties which should not be exported for the AccessClass Confidential
	 * @param string $id Event ID
	 * @param VObject_VObject $vobject Sabre VObject
	 * @return object
	 */
	public static function cleanByAccessClass($id, $vobject) {

		// Do not clean your own calendar
		if(Object::getowner($id) === \OCP\USER::getUser()) {
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

		if(isset($velement->CLASS) && $velement->CLASS->getValue() == 'CONFIDENTIAL') {
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
						$property->setValue(App::$l10n->t('Busy'));
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
	 * @brief Get the permissions determined by the access class of an event/todo/journal
	 * @param Sabre_VObject $vobject Sabre VObject
	 * @return (int) $permissions - CRUDS permissions
	 * @see \OCP\Share
	 */
	public static function getAccessClassPermissions($vobject) {
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

		if($velement!='') {
			$accessclass = $velement->getAsString('CLASS');
		   return App::getAccessClassPermissions($accessclass);
		}else return false;
	}

	/**
	 * @brief returns the options for the access class of an event
	 * @return array - valid inputs for the access class of an event
	 */
	public static function getAccessClassOptions($l10n) {
		return array(
			'PUBLIC'       => (string)$l10n->t('Show full event'),
			'PRIVATE'      => (string)$l10n->t('Hide event'),
			'CONFIDENTIAL' => (string)$l10n->t('Show only busy')
		);
	}

	/**
	 * @brief returns the options for the repeat rule of an repeating event
	 * @return array - valid inputs for the repeat rule of an repeating event
	 */
	public static function getRepeatOptions($l10n) {
		return array(
			'doesnotrepeat' => (string)$l10n->t('Does not repeat'),
			'DAILY'         => (string)$l10n->t('DAILY'),
			'WEEKLY'        => (string)$l10n->t('WEEKLY'),
			'MONTHLY'       => (string)$l10n->t('MONTHLY'),
			'YEARLY'       => (string)$l10n->t('YEARLY'),
			'OWNDEF'        => (string)$l10n->t('Customize ...')
		);
	}
	
	
	/**
	 * @brief returns the options for the repeat rule of an repeating event
	 * @return array - valid inputs for the repeat rule of an repeating event
	 */
	public static function getAdvancedRepeatOptions($l10n) {
		return array(
			'DAILY'         => (string)$l10n->t('DAILY'),
			'WEEKLY'        => (string)$l10n->t('WEEKLY'),
			'MONTHLY'       => (string)$l10n->t('MONTHLY'),
			'YEARLY'       => (string)$l10n->t('YEARLY')
			
		);
	}

	/**
	 * @brief returns the options for the end of an repeating event
	 * @return array - valid inputs for the end of an repeating events
	 */
	public static function getEndOptions($l10n) {
		return array(
			'never' => (string)$l10n->t('never'),
			'count' => (string)$l10n->t('by occurrences'),
			'date'  => (string)$l10n->t('by date')
		);
	}

	/**
	 * @brief returns the options for an monthly repeating event
	 * @return array - valid inputs for monthly repeating events
	 */
	public static function getMonthOptions($l10n) {
		return array(
			'monthday' => (string)$l10n->t('by monthday'),
			'weekday'  => (string)$l10n->t('by weekday')
		);
	}

	/**
	 * @brief returns the options for an weekly repeating event
	 * @return array - valid inputs for weekly repeating events
	 */
	public static function getWeeklyOptions($l10n) {
		return array(
			'MO' => (string)$l10n->t('Monday'),
			'TU' => (string)$l10n->t('Tuesday'),
			'WE' => (string)$l10n->t('Wednesday'),
			'TH' => (string)$l10n->t('Thursday'),
			'FR' => (string)$l10n->t('Friday'),
			'SA' => (string)$l10n->t('Saturday'),
			'SU' => (string)$l10n->t('Sunday')
		);
	}
	
	/**
	 * @brief returns the options for an weekly repeating event
	 * @return array - valid inputs for weekly repeating events
	 */
	public static function getWeeklyOptionsShort($l10n) {
		return array(
			'MO' => (string)$l10n->t('Mon.'),
			'TU' => (string)$l10n->t('Tue.'),
			'WE' => (string)$l10n->t('Wed.'),
			'TH' => (string)$l10n->t('Thu.'),
			'FR' => (string)$l10n->t('Fri.'),
			'SA' => (string)$l10n->t('Sat.'),
			'SU' => (string)$l10n->t('Sun.')
		);
	}
    public static function getWeeklyOptionsCheck($sWeekDay) {
		 $checkArray=array(
			'Mon' =>'MO',
			'Tue' => 'TU',
			'Wed' => 'WE',
			'Thu' =>'TH',
			'Fri' => 'FR',
			'Sat' =>'SA',
			'Sun' => 'SU'
		);
		return $checkArray[$sWeekDay];
	}
	/**
	 * @brief returns the options for an monthly repeating event which occurs on specific weeks of the month
	 * @return array - valid inputs for monthly repeating events
	 */
	public static function getWeekofMonth($l10n) {
		return array(
			'+1' => (string)$l10n->t('first'),
			'+2' => (string)$l10n->t('second'),
			'+3' => (string)$l10n->t('third'),
			'+4' => (string)$l10n->t('fourth'),
			'-1' => (string)$l10n->t('last')
		);
	}

	/**
	 * @brief returns the options for an yearly repeating event which occurs on specific days of the year
	 * @return array - valid inputs for yearly repeating events
	 */
	public static function getByYearDayOptions() {
		$return = array();
		foreach(range(1,366) as $num) {
			$return[(string) $num] = (string) $num;
		}
		return $return;
	}

	/**
	 * @brief returns the options for an yearly or monthly repeating event which occurs on specific days of the month
	 * @return array - valid inputs for yearly or monthly repeating events
	 */
	public static function getByMonthDayOptions() {
		$return = array();
		foreach(range(1,31) as $num) {
			$return[(string) $num] = (string) $num;
		}
		return $return;
	}

	/**
	 * @brief returns the options for an yearly repeating event which occurs on specific month of the year
	 * @return array - valid inputs for yearly repeating events
	 */
	public static function getByMonthOptions($l10n) {
		return array(
			'1'  => (string)$l10n->t('January'),
			'2'  => (string)$l10n->t('February'),
			'3'  => (string)$l10n->t('March'),
			'4'  => (string)$l10n->t('April'),
			'5'  => (string)$l10n->t('May'),
			'6'  => (string)$l10n->t('June'),
			'7'  => (string)$l10n->t('July'),
			'8'  => (string)$l10n->t('August'),
			'9'  => (string)$l10n->t('September'),
			'10' => (string)$l10n->t('October'),
			'11' => (string)$l10n->t('November'),
			'12' => (string)$l10n->t('December')
		);
	}

   /**
	 * @brief returns the options for an yearly repeating event which occurs on specific month of the year
	 * @return array - valid inputs for yearly repeating events
	 */
	public static function getByMonthShortOptions($l10n) {
		return array(
			'1'  => (string)$l10n->t('Jan.'),
			'2'  => (string)$l10n->t('Feb.'),
			'3'  => (string)$l10n->t('Mar.'),
			'4'  => (string)$l10n->t('Apr.'),
			'5'  => (string)$l10n->t('May.'),
			'6'  => (string)$l10n->t('Jun.'),
			'7'  => (string)$l10n->t('Jul.'),
			'8'  => (string)$l10n->t('Aug.'),
			'9'  => (string)$l10n->t('Sep.'),
			'10' => (string)$l10n->t('Oct.'),
			'11' => (string)$l10n->t('Nov.'),
			'12' => (string)$l10n->t('Dec.')
		);
	}

	/**
	 * @brief returns the options for an yearly repeating event
	 * @return array - valid inputs for yearly repeating events
	 */
	public static function getYearOptions($l10n) {
			/*'byweekno'  => (string)$l10n->t('by weeknumber(s)'),*/
		return array(
			'bydaymonth'  => (string)$l10n->t('by day and month')
		);
	}

	/**
	 * @brief returns the options for an yearly repeating event which occurs on specific week numbers of the year
	 * @return array - valid inputs for yearly repeating events
	 */
	public static function getByWeekNoOptions() {
		return range(1, 52);
	}

     /**
	 * @brief returns the options for reminder choose
	 * @return array - valid inputs for reminder options
	 */
	public static function getReminderOptions($l10n) {
		/*'messageaudio'  => (string)$l10n->t('messageaudio'),*/	
		return array(
			'none' => (string)$l10n->t('None'),
			'-PT5M' => '5 '.(string)$l10n->t('Minutes before'),
			'-PT10M' => '10 '.(string)$l10n->t('Minutes before'),
			'-PT15M' => '15 '.(string)$l10n->t('Minutes before'),
			'-PT30M' => '30 '.(string)$l10n->t('Minutes before'),
			'-PT1H' => '1 '.(string)$l10n->t('Hours before'),
			'-PT2H' => '2 '.(string)$l10n->t('Hours before'),
			'-PT1D' => '1 '.(string)$l10n->t('Days before'),
			'-PT2D' => '2 '.(string)$l10n->t('Days before'),
			'-PT1W' => '1 '.(string)$l10n->t('Weeks before'),
			'OWNDEF'        => (string)$l10n->t('Customize ...')
		);
	}
	
	 /**
	 * @brief returns the options for reminder choose
	 * @return array - valid inputs for reminder options
	 */
	public static function getAdvancedReminderOptions($l10n) {
		/*'messageaudio'  => (string)$l10n->t('messageaudio'),*/	
		return array(
			'DISPLAY' => (string)$l10n->t('Message'),
			'EMAIL'  => (string)$l10n->t('Email'),
		);
	}
	
	
     /**
	 * @brief returns the options for reminder timing choose
	 * @return array - valid inputs for reminder timing options
	 */
	public static function getReminderTimeOptions($l10n) {
		return array(
			'minutesbefore' => (string)$l10n->t('Minutes before'),
			'hoursbefore'  => (string)$l10n->t('Hours before'),
			'daysbefore'  => (string)$l10n->t('Days before'),
			'minutesafter' => (string)$l10n->t('Minutes after'),
			'hoursafter'  => (string)$l10n->t('Hours after'),
			'daysafter'  => (string)$l10n->t('Days after'),
			'weeksafter'  => (string)$l10n->t('Weeks after'),
			'weeksbefore'  =>  (string)$l10n->t('Weeks before'),
			'ondate'  => (string)$l10n->t('on'),
		);
	}

     /**
	 * @brief returns the options for reminder timing choose
	 * @return array - valid inputs for reminder timing options
	 */
	public static function getReminderTimeParsingOptions() {
		return array(
			'minutesbefore' =>array('timedescr'=>'M','timehistory'=>'-PT'),
			'hoursbefore'  => array('timedescr'=>'H','timehistory'=>'-PT'),
			'daysbefore'  => array('timedescr'=>'D','timehistory'=>'-PT'),
			'minutesafter' => array('timedescr'=>'M','timehistory'=>'+PT'),
			'hoursafter'  => array('timedescr'=>'H','timehistory'=>'+PT'),
			'daysafter'  => array('timedescr'=>'D','timehistory'=>'+PT'),
			'weeksafter'  => array('timedescr'=>'W','timehistory'=>'+PT'),
			'weeksbefore'  => array('timedescr'=>'W','timehistory'=>'-PT'),
			'ondate'  =>array('timedescr'=>'D','timehistory'=>'+PT'),
		);
	}
	/**
	 * @brief validates a request
	 * @param array $request
	 * @return mixed (array / boolean)
	 */
	public static function validateRequest($request) {
		$errnum = 0;
		$errarr = array('title'=>'false', 'cal'=>'false', 'from'=>'false', 'fromtime'=>'false', 'to'=>'false', 'totime'=>'false', 'endbeforestart'=>'false');
		if($request['title'] == '') {
			$errarr['title'] = 'true';
			$errnum++;
		}

		$fromday = substr($request['from'], 0, 2);
		$frommonth = substr($request['from'], 3, 2);
		$fromyear = substr($request['from'], 6, 4);
		if(!checkdate($frommonth, $fromday, $fromyear)) {
			$errarr['from'] = 'true';
			$errnum++;
		}
		$allday = isset($request['allday']);
		if(!$allday && self::checkTime(urldecode($request['fromtime']))) {
			$errarr['fromtime'] = 'true';
			$errnum++;
		}

		$today = substr($request['to'], 0, 2);
		$tomonth = substr($request['to'], 3, 2);
		$toyear = substr($request['to'], 6, 4);
		if(!checkdate($tomonth, $today, $toyear)) {
			$errarr['to'] = 'true';
			$errnum++;
		}
		if($request['repeat'] != 'doesnotrepeat') {
			if(is_nan($request['interval']) && $request['interval'] != '') {
				$errarr['interval'] = 'true';
				$errnum++;
			}
			if(array_key_exists('repeat', $request) && !array_key_exists($request['repeat'], self::getRepeatOptions(App::$l10n))) {
				$errarr['repeat'] = 'true';
				$errnum++;
			}
			if(array_key_exists('advanced_month_select', $request) && !array_key_exists($request['advanced_month_select'], self::getMonthOptions(App::$l10n))) {
				$errarr['advanced_month_select'] = 'true';
				$errnum++;
			}
			if(array_key_exists('advanced_year_select', $request) && !array_key_exists($request['advanced_year_select'], self::getYearOptions(App::$l10n))) {
				$errarr['advanced_year_select'] = 'true';
				$errnum++;
			}
			if(array_key_exists('weekofmonthoptions', $request) && !array_key_exists($request['weekofmonthoptions'], self::getWeekofMonth(App::$l10n))) {
				$errarr['weekofmonthoptions'] = 'true';
				$errnum++;
			}
			if($request['end'] != 'never') {
				if(!array_key_exists($request['end'], self::getEndOptions(App::$l10n))) {
					$errarr['end'] = 'true';
					$errnum++;
				}
				if($request['end'] == 'count' && is_nan($request['byoccurrences'])) {
					$errarr['byoccurrences'] = 'true';
					$errnum++;
				}
				if($request['end'] == 'date') {
					list($bydate_day, $bydate_month, $bydate_year) = explode('-', $request['bydate']);
					if(!checkdate($bydate_month, $bydate_day, $bydate_year)) {
						$errarr['bydate'] = 'true';
						$errnum++;
					}
				}
			}
			if(array_key_exists('weeklyoptions', $request)) {
				foreach($request['weeklyoptions'] as $option) {
					if(!in_array($option, self::getWeeklyOptions(App::$l10n))) {
						$errarr['weeklyoptions'] = 'true';
						$errnum++;
					}
				}
			}
			if(array_key_exists('byyearday', $request)) {
				foreach($request['byyearday'] as $option) {
					if(!array_key_exists($option, self::getByYearDayOptions())) {
						$errarr['byyearday'] = 'true';
						$errnum++;
					}
				}
			}
			if(array_key_exists('weekofmonthoptions', $request)) {
				if(is_nan((double)$request['weekofmonthoptions'])) {
					$errarr['weekofmonthoptions'] = 'true';
					$errnum++;
				}
			}
			if(array_key_exists('bymonth', $request)) {
				foreach($request['bymonth'] as $option) {
					if(!in_array($option, self::getByMonthOptions(App::$l10n))) {
						$errarr['bymonth'] = 'true';
						$errnum++;
					}
				}
			}
			if(array_key_exists('byweekno', $request)) {
				foreach($request['byweekno'] as $option) {
					if(!array_key_exists($option, self::getByWeekNoOptions())) {
						$errarr['byweekno'] = 'true';
						$errnum++;
					}
				}
			}
			if(array_key_exists('bymonthday', $request)) {
				foreach($request['bymonthday'] as $option) {
					if(!array_key_exists($option, self::getByMonthDayOptions())) {
						$errarr['bymonthday'] = 'true';
						$errnum++;
					}
				}
			}
		}
		if(!$allday && self::checkTime(urldecode($request['totime']))) {
			$errarr['totime'] = 'true';
			$errnum++;
		}
		if($today < $fromday && $frommonth == $tomonth && $fromyear == $toyear) {
			$errarr['endbeforestart'] = 'true';
			$errnum++;
		}
		if($today == $fromday && $frommonth > $tomonth && $fromyear == $toyear) {
			$errarr['endbeforestart'] = 'true';
			$errnum++;
		}
		if($today == $fromday && $frommonth == $tomonth && $fromyear > $toyear) {
			$errarr['endbeforestart'] = 'true';
			$errnum++;
		}
		if(!$allday && $fromday == $today && $frommonth == $tomonth && $fromyear == $toyear) {
			list($tohours, $tominutes) = explode(':', $request['totime']);
			list($fromhours, $fromminutes) = explode(':', $request['fromtime']);
			if($tohours < $fromhours) {
				$errarr['endbeforestart'] = 'true';
				$errnum++;
			}
			if($tohours == $fromhours && $tominutes < $fromminutes) {
				$errarr['endbeforestart'] = 'true';
				$errnum++;
			}
		}
		if ($errnum)
		{
			return $errarr;
		}
		return false;
	}

	/**
	 * @brief validates time
	 * @param string $time
	 * @return boolean
	 */
	protected static function checkTime($time) {
		if(strpos($time, ':') === false ) {
			return true;
		}
		list($hours, $minutes) = explode(':', $time);
		return empty($time)
			|| $hours < 0 || $hours > 24
			|| $minutes < 0 || $minutes > 60;
	}

	/**
	 * @brief creates an VCalendar Object from the request data
	 * @param array $request
	 * @return object created $vcalendar
	 */	public static function createVCalendarFromRequest($request) {
		$vcalendar = new VObject('VCALENDAR');
		$vcalendar->add('PRODID', 'ownCloud Calendar');
		$vcalendar->add('VERSION', '2.0');

		$vevent = new VObject('VEVENT');
		
		
		$vcalendar->add($vevent);

		$vevent->setDateTime('CREATED', 'now');

		//$vevent->setUID();
		return self::updateVCalendarFromRequest($request, $vcalendar);
	}

	/**
	 * @brief updates an VCalendar Object from the request data
	 * @param array $request
	 * @param object $vcalendar
	 * @return object updated $vcalendar
	 */
	public static function updateVCalendarFromRequest($request, $vcalendar) {
			
			
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
		$weekDay=self::getWeeklyOptionsCheck($checkWeekDay);
		
		if (!$allday) {
			$fromtime = $request['fromtime'];
			$totime = $request['totime'];
		}
		
		$vevent = $vcalendar->VEVENT;
		/*REMINDER NEW*/
		if($request['reminder']!='none'){
			//$aTimeTransform=self::getReminderTimeParsingOptions();	
			if($vevent -> VALARM){
				$valarm = $vevent -> VALARM;
			}else{
				$valarm = new VObject('VALARM');
                $vevent->add($valarm);
			}
			//sReminderRequest
			
			
			if($request['reminder']=='OWNDEF' && ($request['reminderAdvanced']=='DISPLAY' || $request['reminderAdvanced']=='EMAIL')){
				
				$valarm->setString('ATTENDEE','');
					
				if($request['remindertimeselect'] !== 'ondate') {
					//$tTime=$aTimeTransform[$request['remindertimeselect']]['timehistory'].intval($request['remindertimeinput']).$aTimeTransform[$request['remindertimeselect']]['timedescr']	;
				    $valarm->setString('TRIGGER',$request['sReminderRequest']);
				}
				if($request['remindertimeselect'] === 'ondate') {
				     $temp=explode('TRIGGER;VALUE=DATE-TIME:',$request['sReminderRequest']);
					$datetime_element = new \Sabre\VObject\Property\ICalendar\DateTime(new \Sabre\VObject\Component\VCalendar(),'TRIGGER');
					$datetime_element->setDateTime( new \DateTime($temp[1]), false);
	                $valarm->__set('TRIGGER',$datetime_element);
					$valarm->TRIGGER['VALUE'] = 'DATE-TIME';
				}
				if($request['reminderAdvanced']=='EMAIL'){
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
		
		/*	
        $valarm = new VObject('VALARM');
        $vevent->add($valarm);
		$valarm->addProperty('TRIGGER','-PT45M');
		$valarm->addProperty('ACTION','DISPLAY');
		$valarm->addProperty('DESCRIPTION','owncloud alarm');*/
		
		//ORGANIZER;CN=email@email.com;EMAIL=email@email.com:MAILTO:email@email.com
		//$vevent->addProperty('ORGANIZER;CN='.$email.';EMAIL='.$email,'MAILTO:'.$email);
		//ATTENDEE;CN="Ryan Gr�nborg";CUTYPE=INDIVIDUAL;EMAIL="ryan@tv-glad.org";PARTSTAT=ACCEPTED:mailto:ryan@tv-glad.org
		//ATTENDEE;ROLE=REQ-PARTICIPANT;PARTSTAT=NEEDS-ACTION;RSVP=TRUE; CN="Full Name":MAILTO:user@domain.com
		//ATTENDEE;CN="admin";CUTYPE=INDIVIDUAL;PARTSTAT=ACCEPTED:/oc50/remote.php/caldav/principals/admin/
		//$vevent->addProperty('ATTENDEE;ROLE=REQ-PARTICIPANT;PARTSTAT=NEEDS-ACTION;RSVP=TRUE;CN=admin','MAILTO:'.$email);
		//$vevent->addProperty('ATTENDEE;CN="admin";CUTYPE=INDIVIDUAL;PARTSTAT=ACCEPTED','http://127.0.0.1/oc50/remote.php/caldav/principals/admin/');
		//$vevent->addProperty('ATTENDEE;CN="sebastian";CUTYPE=INDIVIDUAL;PARTSTAT=ACCEPTED','http://127.0.0.1/oc50/remote.php/caldav/principals/sebastian/');
		
		$description = $request["description"];
		$repeat = $request["repeat"];
		$firstDayOfWeek=';WKST='.(\OCP\Config::getUserValue(\OCP\USER::getUser(), App::$appname, 'firstday', 'mo') == 'mo' ? 'MO' : 'SU');
		
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
						$days = array_flip(self::getWeeklyOptions(App::$l10n));
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
			if($end == 'count') {
				$rrule .= ';COUNT=' . $byoccurrences;
			}
			if($end == 'date') {
				list($bydate_day, $bydate_month, $bydate_year) = explode('-', $request['bydate']);
				$rrule .= ';UNTIL=' . $bydate_year . $bydate_month . $bydate_day;
			}
			$vevent->setString('RRULE', $rrule);
			$repeat = "true";
		
			
			//\OCP\Util::writeLog('calendar','VTIMEZONE'.$vtimezone ->TZID, \OCP\Util::DEBUG);
						
			
			/**BEGIN:VTIMEZONE
			TZID:Europe/Berlin
			BEGIN:DAYLIGHT
			TZOFFSETFROM:+0100
			RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
			DTSTART:19810329T020000
			TZNAME:MESZ
			TZOFFSETTO:+0200
			END:DAYLIGHT
			BEGIN:STANDARD
			TZOFFSETFROM:+0200
			RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
			DTSTART:19961027T030000
			TZNAME:MEZ
			TZOFFSETTO:+0100
			END:STANDARD
			END:VTIMEZONE**/	    
			
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
						
					$tz=App::getTimezone();
					$ex=explode('/', $tz, 2);
					$aTzTimes = App::getTzDaylightStandard();
		
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
	                $timezone = App::getTimezone();
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
					
					\OCP\Util::writeLog(App::$appname,'edit: ->'.$dMinutes, \OCP\Util::DEBUG);
					
					/*
					if ($allday) {
						$start_type = \Sabre\VObject\Property\DateTime::DATE;
					}else{
						$start_type = \Sabre\VObject\Property\DateTime::LOCALTZ;
					}	
					$calcStart=new \DateTime($oldStartTime);
					$aExt=$vevent->EXDATE;
					$vevent->setString('EXDATE','');
					 $timezone = App::getTimezone();
					foreach($aExt as $param){
						$dateTime = new \DateTime($param->getValue());
						$datetime_element = new \Sabre\VObject\Property\DateTime('EXDATE');
						$datetime_element -> setDateTime($dateTime->add($delta),$start_type);
					    $vevent->addProperty('EXDATE;TZID='.$timezone,(string) $datetime_element);
						//$output.=$dateTime->format('Ymd\THis').':'.$datetime_element.'success';
					}*/
			}
			
		}
		
		
		unset($vevent->DURATION);

		$vevent->setString('CLASS', $accessclass);
		$vevent->setString('LOCATION', $location);
		$vevent->setString('DESCRIPTION', $description);
		$vevent->setString('CATEGORIES', $categories);
		$vevent->setString('URL', $link);

		/*if($repeat == "true") {
			$vevent->RRULE = $repeat;
		}*/

		return $vcalendar;
	}

	/**
	 * @brief returns the owner of an object
	 * @param integer $id
	 * @return string
	 */
	public static function getowner($id) {
		$event = self::find($id);
		$cal = Calendar::find($event['calendarid']);
		//\OCP\Util::writeLog(App::$appname,'OWNER'.$event['calendarid'].' of '.$event['summary'], \OCP\Util::DEBUG);
		if($cal === false || is_array($cal) === false){
			return null;
		}
		if(array_key_exists('userid', $cal)){
			return $cal['userid'];
		}else{
			return null;
		}
	}

	/**
	 * @brief returns the calendarid of an object
	 * @param integer $id
	 * @return integer
	 */
	public static function getCalendarid($id) {
		$event = self::find($id);
		return $event['calendarid'];
	}

	/**
	 * @brief checks if an object is repeating
	 * @param integer $id
	 * @return boolean
	 */
	public static function isrepeating($id) {
		$event = self::find($id);
		return ($event['repeating'] == 1)?true:false;
	}

	/**
	 * @brief converts the start_dt and end_dt to a new timezone
	 * @param object $dtstart
	 * @param object $dtend
	 * @param boolean $allday
	 * @param string $tz
	 * @return array
	 */
	public static function generateStartEndDate($dtstart, $dtend, $allday, $tz) {
		$start_dt = $dtstart->getDateTime();
		$end_dt = $dtend->getDateTime();
		//\OCP\Util::writeLog(App::$appname,'TZ: ->'.$tz, \OCP\Util::DEBUG);
		$return = array();
		if($allday) {
			$return['start'] = $start_dt->format('Y-m-d');
			$return['startlist'] = $start_dt->format('Y/m/d');
			$end_dt->modify('-1 minute');
			while($start_dt >= $end_dt) {
				$end_dt->modify('+1 day');
			}
			$return['end'] = $end_dt->format('Y-m-d');
			$return['endlist'] = $end_dt->format('Y/m/d');
		}else{
			$start_dt->setTimezone(new \DateTimeZone($tz));
			$end_dt->setTimezone(new \DateTimeZone($tz));
			$return['start'] = $start_dt->format('Y-m-d H:i:s');
			$return['end'] = $end_dt->format('Y-m-d H:i:s');
			$return['startlist'] = $start_dt->format('Y/m/d H:i:s');
			$return['endlist'] = $end_dt->format('Y/m/d H:i:s');
		}
		return $return;
	}
}
