<?php
/**
 * ownCloud - Calendar+ app
 *
 * @author Thomas Müller
 * @copyright 2014 Thomas Müller thomas.mueller@tmit.eu
 * @copyright 2015 Sebastian Döll
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

 
namespace OCA\CalendarPlus\Service {

	class ContactsIntegration
	{
		/**
		 * @var \OCP\Contacts\IManager
		 */
		private $contactsManager;

		public function __construct($contactsManager) {
			$this->contactsManager = $contactsManager;
		}
		
		
		
		/**
		 * Extracts all matching contacts with email address and name
		 *
		 * @param string $term
		 * @return array
		 */
		public function getMatchingLocaction($term) {
			if (!$this->contactsManager->isEnabled()) {
				return array();
			}

			$result = $this->contactsManager->search($term, array('FN', 'ADR','N'));
			
			$addressDefArray=array('0'=>'','1'=>'','2'=>'street','3'=>'city','4'=>'','5'=>'postalcode','6'=>'country');
			$aDefNArray=array('0'=>'lname','1'=>'fname','2'=>'anrede', '3'=>'title');
			
			$receivers = array();
			foreach ($result as $r) {
				$id = $r['id'];
				$fn = $r['FN'];
				
				$name = '';
				if(isset($r['N'])){
					list($lastname,$surename,$gender, $title) = $r['N'];
					$name = (!empty($surename)?$surename:'');
					$name .= (!empty($lastname)? ' '.$lastname:'');
					$name = '('.$name.')';
					//$name .= (!empty($gender)?$gender:'');
					//$name .= (!empty($title)?' '.$title:'');
				}
				
				$address = $r['ADR'];
				
				if (!is_array($address)) {
					$address = array($address);
				}
		
				// loop through all email addresses of this contact
				foreach ($address as $e) {
					$aAddr='';	
					list($zero,$one,$street, $city, $four,$postalcode,$country) = $e;
					$aAddr = (!empty($street)?$street:'');
					$aAddr .= (!empty($postalcode)? ', '.$postalcode.' ':'');
					$aAddr .= (!empty($city)?$city:'');
					$aAddr .= (!empty($country)?', '.$country:'');
					
					
						
					$displayName = "\"$fn\" $name $aAddr";
					$valueAddr =  $aAddr;
					$receivers[] = array('id'    => $id,
										 'label' => $displayName,
										 'value' => $valueAddr);
				}
			}

			return $receivers;
		}

		
	}
}
