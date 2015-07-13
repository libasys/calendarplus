<?php
/**
 * ownCloud - CalendarPlus
 *
 * @author Sebastian Doell
 * @copyright 2015 sebastian doell sebastian@libasys.de
 *
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


/**
 * This class provides a streamlined interface to the Sabre VObject classes
 */
 namespace OCA\CalendarPlus;
 
class VObject{
	/** @var Sabre\VObject\Component */
	protected $vobject;
	protected $vcomponent;

	/**
	 * @returns Sabre\VObject\Component
	 */
	public function getVObject() {
		return $this->vobject;
	}

	/**
	 * @brief Parses the VObject
	 * @param string VObject as string
	 * @returns Sabre_VObject or null
	 */
	public static function parse($data) {
		try {
				
			$vobject = \Sabre\VObject\Reader::read($data,2);
			if ($vobject instanceof \Sabre\VObject\Component) {
				$vobject = new VObject($vobject);
			}
			return $vobject;
		} catch (\Exception $e) {
			//OC_Log::write('vobject', $e->getMessage(), OC_Log::ERROR);
			return null;
		}
	}

	/**
	 * @brief Escapes semicolons
	 * @param string $value
	 * @return string
	 */
	public static function escapeSemicolons($value) {
		foreach($value as &$i ) {
			$i = implode("\\\\;", explode(';', $i));
		}
		return implode(';', $value);
	}

	/**
	 * @brief Creates an array out of a multivalue property
	 * @param string $value
	 * @return array
	 */
	public static function unescapeSemicolons($value) {
		$array = explode(';', $value);
		for($i=0;$i<count($array);$i++) {
			if(substr($array[$i], -2, 2)=="\\\\") {
				if(isset($array[$i+1])) {
					$array[$i] = substr($array[$i], 0, count($array[$i])-2).';'.$array[$i+1];
					unset($array[$i+1]);
				}
				else{
					$array[$i] = substr($array[$i], 0, count($array[$i])-2).';';
				}
				$i = $i - 1;
			}
		}
		return $array;
	}

	/**
	 * Constuctor
	 * @param Sabre\VObject\Component or string
	 */
	public function __construct($vobject_or_name) {
		if (is_object($vobject_or_name)) {
			$this->vobject = $vobject_or_name;
		} else {
			switch($vobject_or_name){
				case 'VCALENDAR':
				case 'VTODO':
				case 'VEVENT':
				case 'VALARM':
				case 'VFREEBUSY':	
				case 'VJOURNAL':
				case 'VTIMEZONE':	
					$this->vcomponent = new \Sabre\VObject\Component\VCalendar();
					break;	
				case 'VCARD':
					$this->vcomponent = new \Sabre\VObject\Component\VCard();
					break;
				default:
				 	$this->vcomponent = new \Sabre\VObject\Component\VCalendar();
					break;			
			}
			
			$this->vobject  = $this->vcomponent->createComponent($vobject_or_name);	
			
		}
	}

	public function add($item, $itemValue = null) {
		if ($item instanceof VObject) {
			$item = $item->getVObject();
		}
		$this->vobject->add($item, $itemValue);
	}

	/**
	 * @brief Add property to vobject
	 * @param object $name of property
	 * @param object $value of property
	 * @param object $parameters of property
	 * @returns Sabre_VObject_Property newly created
	 */
	public function addProperty($name, $value, $parameters=array()) {
		if(is_array($value)) {
			$value = self::escapeSemicolons($value);
		}
		$vcalendar = new \Sabre\VObject\Component\VCalendar();
		$property = $vcalendar->createProperty( $name, $value );
		
		foreach($parameters as $name => $value) {
			$property->parameters[] = $vcalendar->createProperty( $name, $value );
		}

		$this->vobject->add($property);
		return $property;
	}

	public function setUID() {
		$uid = substr(md5(rand().time()), 0, 10);
		$this->vobject->add('UID', $uid);
	}

	public function setString($name, $string) {
		if ($string !== '') {
			$string = strtr($string, array("\r\n"=>"\n"));
			$this->vobject->__set($name, $string);
		}else{
			$this->vobject->__unset($name);
		}
	}

	/**
	 * Sets or unsets the Date and Time for a property.
	 * When $datetime is set to 'now', use the current time
	 * When $datetime is null, unset the property
	 *
	 * @param string property name
	 * @param DateTime $datetime
	 * @param int $dateType
	 * @return void
	 */
	public function setDateTime($name, $datetime, $floating=false) {
			
		if ($datetime == 'now') {
			$datetime = new \DateTime();
		}
		
		if ($datetime instanceof \DateTime) {
			$tThis=$this;
			if(!($tThis instanceof \Sabre\VObject\Component)){
				$tThis=new \Sabre\VObject\Component\VCalendar();
			}
					
			$datetime_element = new \Sabre\VObject\Property\ICalendar\DateTime($tThis,$name);
			$datetime_element->setDateTime($datetime, $floating);
			$this->vobject->__set($name,$datetime_element);
		}else{
			$this->vobject->__unset($name);
		}
		
		
		
	}

	public function getAsString($name) {
			
				
		return $this->vobject->__isset($name) ?
			$this->vobject->__get($name)->getValue() :
			'';
	}

	public function getAsArray($name) {
		$values = array();
		if ($this->vobject->__isset($name)) {
			$values = explode(',', $this->getAsString($name));
			$values = array_map('trim', $values);
		}
		return $values;
	}

	public function &__get($name) {
		if ($name == 'children') {
			return $this->vobject->children;
		}
		
		$return = $this->vobject->__get($name);
		
		if ($return instanceof \Sabre\VObject\Component) {
			$return = new VObject($return);
		}
		return $return;
	}
   
   
	public function __set($name, $value) {
			return $this->vobject->__set($name, $value);
	}

	public function __unset($name) {
		return $this->vobject->__unset($name);
	}

	public function __isset($name) {
		return $this->vobject->__isset($name);
	}
   
	public function __call($function, $arguments) {
		//if (is_callable(array($this->vobject, $function)) ){
			return call_user_func_array(array($this->vobject, $function), $arguments);
		//}
	}
}
