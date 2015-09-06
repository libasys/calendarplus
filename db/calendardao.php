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

class CalendarDAO  {

    private $db;
	private $userId;
	
	private $calendarTable = '*PREFIX*clndrplus_calendars';

    public function __construct(IDb $db, $userId) {
        $this->db = $db;
		$this->userId = $userId;
   }
	
	/**
	 * @brief Gets the data of all calendar
	 * @param bool $active
	 * @param bool $bSubscribe
	 * @return associative array
	 */
	
	public function all($active=false, $bSubscribe = true){
	
		$values = array($this->userId);
		
		$active_where = '';
		if ($active === true) {
			$active_where = ' AND `active` = ?';
			$values[] = (int)$active;
		}
		
		$subscribe_where ='';
		if ($bSubscribe === false) {
			$subscribe_where = ' AND `issubscribe` = ?';
			$values[] = (int)$bSubscribe;
		}
		
		
		$stmt = $this->db->prepareQuery( 'SELECT * FROM `'.$this->calendarTable.'` WHERE `userid` = ?' . $active_where.$subscribe_where .' ORDER BY `displayname` ASC');
		$result = $stmt->execute($values);
		
		if($result !== null && $result !== ''){
			$calendars = array();
			while( $row = $result->fetchRow()) {
				$row['permissions'] = \OCP\PERMISSION_ALL;
				if($row['issubscribe']) {
					$row['permissions'] = \OCP\PERMISSION_SHARE;
				}
				$row['description'] = '';
				$row['active'] = (int) $row['active'];
				$calendars[] = $row;
			}
			
			return $calendars;
			
		}else{
			return null;
		}
		
	}
	
	/**
	 * @NoAdminRequired
	 * 
	 * @brief set UserId
	 * @param integer $userid
	 * 
	 */
	public function setUserId($userid){
			
		$this->userId = $userid;
		
	}
	
	/**
	 * @NoAdminRequired
	 * 
	 * @brief Gets the data of one calendar
	 * @param integer $id
	 * @return associative array
	 */
	public function find($id){
			
		$stmt = $this->db->prepareQuery( 'SELECT * FROM `'.$this->calendarTable.'` WHERE `id` = ?' );
		$result = $stmt->execute(array($id));
		
		if($result !== null && $result !== ''){
			return $result->fetchRow();
		}else{
			return null;
		}
		
		
	}
	/**
	 * @NoAdminRequired
	 * 
	 * @brief Gets the data of one calendar
	 * @param string $uri
	 * @return associative array
	 */
	public function findByUri($uri){
			
		$stmt = $this->db->prepareQuery( 'SELECT COUNT(*) AS COUNTCAL FROM `'.$this->calendarTable.'` WHERE `userid` = ? AND `uri` = ?' );
		$result = $stmt->execute(array($this->userId, $uri));
		$rowCount = $result->fetchRow();
	
		if($rowCount['COUNTCAL'] > 0){
			return  $result->fetchRow();
		}else{
			return null;
		}
	}
	
	
	
	 /**
	 * @brief Add data of one calendar
	 * @param string $name
	  *@param string $uri
	  *@param integer $order
	  *@param string $color
	  *@param string $timezone
	  *@param string $components
	  *@param integer $issubscribe
	  *@param integer $externuri
	  *@param string $lastmodified   
	 * @return integer id
	 */
	 
	public function add($name, $uri, $order, $color, $timezone, $components, $issubscribe, $externuri, $lastmodified){
		
		$stmt = $this->db->prepareQuery( 'INSERT INTO `'.$this->calendarTable.'` (`userid`,`displayname`,`uri`,`ctag`,`calendarorder`,`calendarcolor`,`timezone`,`components`,`issubscribe`,`externuri`,`lastmodifieddate`) VALUES(?,?,?,?,?,?,?,?,?,?,?)' );
		$result = $stmt->execute(array($this->userId,$name,$uri,1,$order,$color,$timezone,$components,$issubscribe,$externuri,$lastmodified));
	
		if($result !== null && $result !== ''){
			return $this->db->getInsertId($this->calendarTable);
		}else{
			return null;
		}
		
		
	} 
	  
	 /**
	 * @brief Update data of one calendar
	 * @param string $name
	  *@param integer $order
	  *@param string $color
	  *@param string $timezone
	  *@param string $components
	  *@param string $transparent
	  *@param integer $id   
	 * @return null || true
	 */
	 
	public function update($name, $order, $color, $timezone, $components, $transparent, $id){
		
		$stmt = $this->db->prepareQuery( 'UPDATE `'.$this->calendarTable.'` SET `displayname`= ?,`calendarorder`= ?,`calendarcolor`= ?,`timezone`= ?,`components`= ?,`ctag`=`ctag`+1, `transparent`= ?  WHERE `id`= ?' );
		$result = $stmt->execute(array($name, $order, $color, $timezone, $components, $transparent, $id));
		
		if($result !== null && $result !== ''){
			return true;
		}else{
			return null;
		}
		
		
	}  
	
	 /**
	 * @brief set active or not calendar
	  *@param integer $active
	  *@param integer $id   
	  *@return null || true
	 * 
	 */
	 
	public function activate($active, $id){
			
		$stmt = $this->db->prepareQuery( 'UPDATE `'.$this->calendarTable.'` SET `active` = ? WHERE `id` = ?' );
		$result = $stmt->execute(array($active, $id));
			
		if($result !== null && $result !== ''){
			return true;
		}else{
			return null;
		}
		
		
	} 
	
	 /**
	 * @brief increment ctag of calendar
	  *@param integer $id   
	  *@return null || true
	 * 
	 */
	 
	public function touch($id){
			
		$stmt = $this->db->prepareQuery( 'UPDATE `'.$this->calendarTable.'` SET `ctag` = `ctag` + 1 WHERE `id` = ?' );
		$result = $stmt->execute(array($id));
			
		if($result !== null && $result !== ''){
			return true;
		}else{
			return null;
		}
		
		
	}       
	 
	  /**
	 * @brief delete calendar
	  *@param integer $id   
	  *@return null || true
	 * 
	 */
	 
	public function delete($id){
			
		$stmt = $this->db->prepareQuery( 'DELETE FROM `'.$this->calendarTable.'` WHERE `id` = ?' );
		$result = $stmt->execute(array($id));
			
		if($result !== null && $result !== ''){
			return true;
		}else{
			return null;
		}
		
		
	}
	
	  /**
	 * @brief merge calendar
	  *@param integer $id1
	  *@param integer $id2    
	  *@return null || true
	 * 
	 */
	 
	public function merge($id1, $id2){
			
		$stmt = $this->db->prepareQuery( 'UPDATE `'.$this->calendarTable.'` SET `calendarid` = ? WHERE `calendarid` = ?' );
		$result = $stmt->execute(array($id1, $id2));
			
		if($result !== null && $result !== ''){
			return true;
		}else{
			return null;
		}
		
		
	}       
	
	       
}