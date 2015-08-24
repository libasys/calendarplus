<?php
/**
 * ownCloud - Mail
 *
 * @author Sebastian Döll
 * @copyright 2013 Sebastian Döll doell@libasyscloud.de
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\CalendarPlus\Db;

use OC\AppFramework\Db\Db;

class CalendarDaoTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var MailAccountMapper
	 */
	private $calendarPDO;

	/**
	 * @var  \OCP\IDBConnection;
	 */
	private $db;

	/**
	 * @var OCP\User
	 */
	private $userId;

	/**
	 * @var  integer;
	 */
	private $calendarId;

	/**
	 * Initialize calendar pdo
	 */
	public function setup(){
		$db = \OC::$server->getDatabaseConnection();
		$this->db = new Db($db);
		
		//$userid = '81stable';
		$this->userId = '81stable';
		$this->calendarPDO = new CalendarDao($this->db, $this->userId);

	}
	
	public function testFind(){
		/*
			$name, 
			$uri, 
			$order, 
			$color, 
			$timezone, 
			$components, 
			$issubscribe, 
			$externuri, 
			$lastmodified
		
		*/
		$this->calendarId = $this->calendarPDO->add('Marco','marco',0,'#ff0000',null,'VEVENT,VTODO',0,'',time());
		
		$result = $this->calendarPDO->find($this->calendarId);
		
		$this->assertEquals($this->calendarId, $result['id']);
		

	}
	
	public function testUpdate(){
		/*	
		$name, $order, $color, $timezone, $components, $transparent, $id*/
		$allCalendars = $this->calendarPDO->all(true,false);
		foreach($allCalendars as $calendarInfo){
			if($calendarInfo['uri'] === 'marco'){
				$this->calendarId = $calendarInfo['id'];
				break;
			}
		}
		
		$bUpdate = $this->calendarPDO->update('Marco Polo',1,'#ff00ff',null,'VEVENT,VTODO',null,$this->calendarId);
		 $this->assertEquals(true, $bUpdate);
		
		$result = $this->calendarPDO->find($this->calendarId);
		$this->assertEquals('#ff00ff', $result['calendarcolor']);
		$this->assertEquals(1, $result['calendarorder']);
		$this->assertEquals('Marco Polo', $result['displayname']);
		
		$bDelete = $this->calendarPDO->delete($this->calendarId);
		$this->assertEquals(true, $bDelete);
		 
	}
	
	public function tearDown(){
		
	}
	
}