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
 
namespace OCA\CalendarPlus\Db;

use \OCP\IDb;
use \OCP\Share;

class EventDAO  {

    private $db;
	private $userId;
	private $calendarDAO;
	private $objectTable = '*PREFIX*clndrplus_objects';
	private $calendarTable = '*PREFIX*clndrplus_calendars';

    public function __construct(IDb $db, $userId, $calendarDAO) {
        $this->db = $db;
		$this->userId = $userId;
		if(!is_null($calendarDAO)){
			$this->calendarDAO = $calendarDAO;
		}
    }
	
	/**
	 * @NoAdminRequired
	 * 
	 * @brief Gets the data of all events of a calendar
	 * @param integer $id
	 * 
	 * @return associative array || null
	 */
	
	public function all($id){
			
			$stmt = $this->db->prepareQuery( 'SELECT * FROM `'.$this->objectTable.'` WHERE `calendarid` = ? ');
			$result = $stmt->execute(array($id));
			//FIXME fetchall
			if($result !== false && $result !== null){
				//$calendarobjects = 	$result->fetchAll();
				/*
				$calendarobjects = array();
				while( $row = $result->fetchRow()) {
					$calendarobjects[] = $row;
				}*/
		
				return $result->fetchAll();
			}else{
				return null;
			}
	
	}
	
	/**
	 * @NoAdminRequired
	 * 
	 * @brief Gets the data of all events of a calendar per start and end date
	 * @param integer $id
	 * @param datetime $start
	 * @param datetime $end
	 * 
	 * @return associative array || null
	 */
	 
	public function allInPeriod($id, $start, $end) {
			
		
	   	$sharedwithByEvents = $this->getEventSharees();
  		$start = $this->getUTCforMDB($start);
		$end = $this->getUTCforMDB($end);
			
		$stmt = $this->db->prepareQuery( 'SELECT * FROM `'.$this->objectTable.'` WHERE `calendarid` = ? AND `objecttype`= ?' 
		.' AND ((`startdate` >= ? AND `enddate` <= ? AND `repeating` = 0)'
		.' OR (`enddate` >= ? AND `startdate` <= ? AND `repeating` = 0)'
		.' OR (`startdate` <= ? AND `repeating` = 1) )' );
		$result = $stmt->execute(array($id, 'VEVENT', $start, $end, $start, $end, $end));
    	
    	if($result !== false && $result !== null){
				
			$calendarobjects = array();
			while( $row = $result->fetchRow()) {
				$row['shared'] = 0;
				if(is_array($sharedwithByEvents) && isset($sharedwithByEvents[$row['id']])){
					 $row['shared'] = 1;
				}
				
				$calendarobjects[] = $row;
			}

			return $calendarobjects;
			
		}else{
			return null;
		}
	}
	
	/**
	 * @NoAdminRequired
	 * 
	 * @brief Gets the data of all events of a calendar per start and end date
	 * @param integer $id
	 * @param datetime $start
	 * @param datetime $end
	 * @param array $ids  - calendar ids
	 * 
	 * @return associative array || null
	 */
	 
	public function allInPeriodAlarm($id, $start, $end, $ids) {
		
		$id_sql = join(',', array_fill(0, count($ids), '?'));
		
		$aExec = array('1', 'VJOURNAL');
		foreach($ids as $id){
			array_push($aExec,$id);
		}
		array_push($aExec,$start, $end, $start, $end, $start);
		
		$repeatDB = new RepeatDAO($this->db,  $this->userId);	
			
		$stmt = $this->db->prepareQuery( 'SELECT * FROM `'.$this->objectTable.'` WHERE `isalarm` = ? AND `objecttype`!= ? AND `calendarid` IN ('.$id_sql.')'  
		.' AND ((`startdate` >= ? AND `enddate` <= ? AND `repeating` = 0)'
		.' OR (`enddate` >= ? AND `startdate` <= ? AND `repeating` = 0)'
		.' OR (`startdate` <= ? AND `repeating` = 1) )' );
		$result = $stmt -> execute($aExec);
		
		if($result !== false && $result !== null){
			$calendarobjects = array();
			while ($row = $result -> fetchRow()) {
					
				if($row['repeating']){
					//Change later to controller
					
					$cachedinperiod = $repeatDB->getEventInperiod($row['id'], $start, $end, true);
					
					$rowRepeat=array();
					foreach ($cachedinperiod as $cachedevent) {
						$rowRepeat['startdate'] = $cachedevent['startdate'];
						$rowRepeat['enddate'] = $cachedevent['enddate'];
						$rowRepeat['calendardata'] = $row['calendardata'];
						$rowRepeat['id'] = $row['id'];
						$rowRepeat['summary'] = $row['summary'];
						$calendarobjects[] = $rowRepeat;
					}
				}
				
				$calendarobjects[] = $row;
			}
			
			return $calendarobjects;
			
		}else{
			return null;
		}
		
	}
	
	
	/**
	 * @brief finds an object by its DAV Data
	 * @param integer $cid Calendar id
	 * @param string $uri the uri ('filename')
	 * @return associative array || null
	 */
	public function findWhereDAVDataIs($cid,$uri) {
			
		$stmt = $this->db->prepareQuery( 'SELECT * FROM `'.$this->objectTable.'` WHERE `calendarid` = ? AND `uri` = ?' );
		$result = $stmt->execute(array($cid,$uri));
		
		if($result !== false && $result !== null){
			return $result->fetchRow();
		}else{
			return null;
		}
	}
	
	
	/**
	 * @NoAdminRequired
	 * 
	 * @brief Returns true or false if event is an shared event
	 * @param integer $id
	 * 
	 * @return associative array || null
	 *
	 */

    public function checkSharedEvent($id){
    	  
		   $stmt = $this->db->prepareQuery('SELECT `id` FROM `'.$this->objectTable.'` WHERE `org_objid` = ? AND `userid` = ? AND `objecttype` = ?');
		   $result = $stmt->execute(array($id,$this->userId,'VEVENT'));
		   
		   if($result !== false && $result !== null){
		   			
		   		return $result->fetchRow();
		   }else{
		   	return null;
		   }
		   
    }  
	 
	/**
	 * @NoAdminRequired
	 * 
	 * @brief Returns an object
	 * @param integer $id
	 * @return associative array || null
	 */
	public function find($id) {
			
		$stmt = $this->db->prepareQuery( 'SELECT * FROM `'.$this->objectTable.'` WHERE `id` = ?' );
		$result = $stmt->execute(array($id));
		 
	 	if($result !== false && $result !== null){
	   		return $result->fetchRow();
	    }else{
	   	 	return null;
	    }
		 
	}
	
	 /**
	 * @NoAdminRequired
	  * 
	  * @brief Add data of one calendar
	 * @param integer $id
	  *@param string $type
	  *@param datetime $startdate
	  *@param datetime $enddate
	  *@param integer $repeating
	  *@param string $summary
	  *@param string $data
	  *@param string $uri
	  *@param integer $timeStampCreate
	  *@param integer $isAlarm   
	  *@param string $uid
	  *@param string $relatedTo
	  *@param integer $orgEventId
	  *@param string $userid
	  *@return integer id || null
	 */
	 
	public function add($id,$type,$startdate,$enddate,$repeating,$summary,$data,$uri,$timeStampCreate,$isAlarm,$uid,$relatedTo,$orgUserId,$userid){
		
		
		$stmt = $this->db->prepareQuery( 'INSERT INTO `'.$this->objectTable.'` (`calendarid`,`objecttype`,`startdate`,`enddate`,`repeating`,`summary`,`calendardata`,`uri`,`lastmodified`,`isalarm`,`eventuid`,`relatedto`,`org_objid`,`userid`) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?)' );
		$result = $stmt->execute(array($id,$type,$startdate,$enddate,$repeating,$summary,$data,$uri,$timeStampCreate,$isAlarm,$uid,$relatedTo,$orgUserId,$userid));
		
		if($result !== null && $result !== ''){
			return $this->db->getInsertId($this->objectTable);
		}else{
			return null;
		}
		
		
	} 
	 /**
	 * @NoAdminRequired
	  * 
	  * @brief update data of one calendar
	 * 
	  *@param string $type
	  *@param datetime $startdate
	  *@param datetime $enddate
	  *@param integer $repeating
	  *@param string $summary
	  *@param string $data
	  *@param integer $timeStampCreate
	  *@param integer $isAlarm
	  *@param string $uid 
	  *@param string $relatedTo      
	  *@param integer $id
	  *@return integer true || null
	 */
	 
	public function update($type,$startdate,$enddate,$repeating,$summary,$data,$timeStampCreate,$isAlarm, $uid, $relatedTo,$id){
			
		$stmt = $this->db->prepareQuery( 'UPDATE `'.$this->objectTable.'` SET `objecttype`=?,`startdate`=?,`enddate`=?,`repeating`=?,`summary`=?,`calendardata`=?,`lastmodified`= ?,`isalarm`= ? ,`eventuid`= ? ,`relatedto`= ? WHERE `id` = ?' );
		$result = $stmt->execute(array($type,$startdate,$enddate,$repeating,$summary,$data,$timeStampCreate,$isAlarm,$uid,$relatedTo, $id));
		
		if($result !== null && $result !== ''){
			return true;
		}else{
			return null;
		}
	}
	 
	 /**
	 * @NoAdminRequired
	 * 
	 * @brief delete a object
	 * @param integer $id
	 * @return true || null
	 */
	public function delete($id) {
			
		$stmt = $this->db->prepareQuery( 'DELETE FROM `'.$this->objectTable.'` WHERE `id` = ?' );
		$result = $stmt->execute(array($id));
		 
	 	if($result !== false && $result !== null){
	   		return true;
	    }else{
	   	 	return null;
	    }
		 
	}
	
	  /**
	 * @NoAdminRequired
	 * 
	 * @brief delete a object
	 * @param integer $id
	 * @return true || null
	 */
	public function deleteAllObjectsFromCalendar($id) {
		
		$stmt = $this->db->prepareQuery( 'DELETE FROM `'.$this->objectTable.'` WHERE `calendarid` = ?' );
		$result = $stmt->execute(array($id));
		 
	 	if($result !== false && $result !== null){
	   		return true;
	    }else{
	   	 	return null;
	    }
		 
	}
	 /**
	 * @NoAdminRequired
	 * 
	 * @brief delete a shared object
	 * @param integer $id
	 * @return true || null
	 */
	public function deleteSharedEvent($id) {
			
		$stmt = $this->db->prepareQuery( 'DELETE FROM `'.$this->objectTable.'` WHERE `org_objid` = ?' );
		$result = $stmt->execute(array($id));
		 
	 	if($result !== false && $result !== null){
	   		return true;
	    }else{
	   	 	return null;
	    }
		 
	}
	 
	 /**
	 * @NoAdminRequired
	 * 
	 * @brief deletes an  object with the data provided by \Sabredav
	 * @param integer $cid calendar id
	 * @param string $uri the uri of the object
	  *@return true || null
	 */
	public function deleteEventFromDAV($cid,$uri) {
			
		$stmt = $this->db->prepareQuery( 'DELETE FROM `'.$this->objectTable.'` WHERE `calendarid`= ? AND `uri`=?' );
		$result = $stmt->execute(array($cid,$uri));
		 
	 	if($result !== false && $result !== null){
	   		return true;
	    }else{
	   	 	return null;
	    }
		 
	}
	
	  /**
	 * @NoAdminRequired
	 * 
	 * @brief deletes an  object with the data provided by \Sabredav
	 * @param integer $cid calendar id
	 * @param integer $id the id of the object
	 *@return true || null 
	 */
	public function move($calendarid,$id) {
			
		$stmt = $this->db->prepareQuery( 'UPDATE `'.$this->objectTable.'` SET `calendarid`=? WHERE `id`=?' );
		$result = $stmt->execute(array($calendarid,$id));
		 
	 	if($result !== false && $result !== null){
	   		return true;
	    }else{
	   	 	return null;
	    }
		 
	}
	  /**
	 * @NoAdminRequired
	 * 
	 * @brief checks if an object is a duplicate or not
	 * @param string $type
	 * @param datetime $start
	 * @param datetime $end
	 * @param integer $repeating
	 * @param string $summary
	 * @param string $calendarData
	 * @return true || false || null 
	 */
	public function isDuplicate($type,$start,$end,$repeating,$summary,$calendarData) {
				
			$stmt = $this->db->prepareQuery('SELECT COUNT(*) AS `COUNTING` FROM `'.$this->objectTable.'` `CO`
									 INNER JOIN `'.$this->calendarTable.'` ON `CO`.`calendarid`=`'.$this->calendarTable.'`.`id`
									 WHERE `CO`.`objecttype`=? AND `CO`.`startdate`=? AND `CO`.`enddate`=? AND `CO`.`repeating`=? AND `CO`.`summary`=? AND `CO`.`calendardata`=? AND `'.$this->calendarTable.'`.`userid` = ?');
			$result = $stmt->execute(array($type,$start,$end,$repeating,$summary,$calendarData, $this->userid));
			
			if($result !== false && $result !== null){
		   		$result = $result->fetchRow();
				if($result['COUNTING'] >= 2) {
					return true;
				}else{
					return false;
				}
		    }else{
		   	 	return null;
		    }
			
	}
	 /**
	 * @brief DateTime to UTC string
	 * @param DateTime $datetime The date to convert
	 * @returns date as YYYY-MM-DD hh:mm
	 *
	 * This function creates a date string that can be used by MDB2.
	 * Furthermore it converts the time to UTC.
	 */
	private function getUTCforMDB($datetime) {
		return date('Y-m-d H:i', $datetime->format('U'));
	}
	  
}