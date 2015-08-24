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


class RepeatDAO  {

    private $db;
	private $userId;
	private $calendarRepeatTable = '*PREFIX*clndrplus_repeat';

    public function __construct(IDb $db, $userId) {
        $this->db = $db;
		$this->userId = $userId;
   }
	
	/**
	 * @brief returns the cache of an event
	 * @param integer $id 
	 * @return array || null
	 */
	public function getEvent($id) {
			
		$stmt = $this->db->prepareQuery('SELECT * FROM `'.$this->calendarRepeatTable.'` WHERE `eventid` = ?');
		$result = $stmt->execute(array($id));
		
		if($result !== null && $result !== '' ){
			$return = array();	
			while($row = $result->fetchRow()) {
				$return[] = $row;
			}
			
			return $return;
		}else{
			return null;
		}
		
	}
	
	/**
	 * @brief returns the cache of an event in a specific peroid
	 * @param integer $id - id of the event
	 * @param (DateTime) $from - start for period in UTC
	 * @param (DateTime) $until - end for period in UTC
	 * @param bool $bAlarm
	 * @return array || null
	 */
	public function getEventInperiod($id, $from, $until,$bAlarm) {
			
		$values = array();
		if($bAlarm === true){
			$values = [ $id, $from, $until, $from, $until ];
		}else{
			$values = [$id,
						$this->getUTCforMDB($from), $this->getUTCforMDB($until),
						$this->getUTCforMDB($from), $this->getUTCforMDB($until)
					];
		}	
			
		$stmt = $this->db->prepareQuery( 'SELECT * FROM `'.$this->calendarRepeatTable.'` WHERE `eventid` = ?'
		.' AND ((`startdate` >= ? AND `startdate` <= ?)'
		.' OR (`enddate` >= ? AND `enddate` <= ?))');
		$result = $stmt->execute($values);
		
		if($result !== null && $result !==''){
			return $result->fetchAll();	
		}else{
			return null;
		}
		
	}
	
	
	/**
	 * @brief returns the cache of all repeating events of a calendar
	 * @param integer $id
	 * @return array || null
	 */
	public function getCalendar($id) {
			
		$stmt = $this->db->prepareQuery('SELECT * FROM `'.$this->calendarRepeatTable.'` WHERE `calid` = ?');
		$result = $stmt->execute(array($id));
		
		if($result !== null && $result !== ''){
			$return = array();	
			while($row = $result->fetchRow()) {
				$return[] = $row;
			}
			
			return $return;
		}else{
			return null;
		}
		
	}
	
	 /**
	 * @brief returns the cache of all repeating events of a calendar in a specific period
	 * @param integer $id - id of the event
	 * @param string $from - start for period in UTC
	 * @param string $until - end for period in UTC
	 * @return array || null
	 */
	public function getCalendarInperiod($id, $from, $until) {
			
		$stmt = $this->db->prepareQuery( 'SELECT * FROM `'.$this->calendarRepeatTable.'` WHERE `calid` = ?'
		.' AND ((`startdate` >= ? AND `startdate` <= ?)'
		.' OR (`enddate` >= ? AND `enddate` <= ?))');
		$result = $stmt->execute(array($id, $from, $until, $from, $until));
					
		if($result !== null && $result !== ''){
			$return = array();	
			while($row = $result->fetchRow()) {
				$return[] = $row;
			}
			
			return $return;
		}else{
			return null;
		}
	}
	
	 /**
	 * @brief returns the cache of all repeating events of a calendar in a specific period
	 * @param integer $id - id of the event
	  *@param integer $calendarId - id of the calendar
	 * @param string $start - start for period in UTC
	 * @param string $end - end for period in UTC
	 * @return true || null
	 */
	public function insertEvent($id, $calendarId, $start, $end) {
			
		$stmt = $this->db->prepareQuery('INSERT INTO `'.$this->calendarRepeatTable.'` (`eventid`,`calid`,`startdate`,`enddate`) VALUES(?,?,?,?)');
		$result = $stmt->execute(array($id,$calendarId,$start,$end));
		
		if($result !== null && $result !==''){
			return true;
		}else{
			return null;
		}
		
	}

	/**
	 * @brief removes the cache of an event
	 * @param integer $id
	 * @return true || null
	 */
	public function cleanEvent($id) {
		$stmt = $this->db->prepareQuery('DELETE FROM `'.$this->calendarRepeatTable.'` WHERE `eventid` = ?');
		$result = $stmt->execute(array($id));
		
		if($result !== null && $result !==''){
			return true;
		}else{
			return null;
		}
	}
	
	
	/**
	 * @brief removes the cache of all events of a calendar
	 * @param integer $id 
	 * @return true || null
	 */
	public function cleanCalendar($id) {
		$stmt = $this->db->prepareQuery('DELETE FROM `'.$this->calendarRepeatTable.'` WHERE `calid` = ?');
		$result = $stmt->execute(array($id));
		
		if($result !== null && $result !== ''){
			return true;
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