<?php

namespace OCA\CalendarPlus\VObject;

use \Sabre\VObject;

class StringPropertyCategories extends \Sabre\VObject\Property\Text {
	
	
	/**
	* Turns the object back into a serialized blob.
	*
	* @return string
	*/
	public function serialize() {
		
			$str = $this->name;
			if ($this->group) {
				$str = $this->group . '.' . $this->name;
			}
			
			
			$src = array(
				';',
			);
			$out = array(
				',',
			);
			
			if(is_array($this->value)){
				$this->value = implode(',',$this->value);
			}
			
			$value = strtr($this->value, array('\,' => ',', '\;' => ';'));
			$str.=':' . str_replace($src, $out, $value);
			
			$out = '';
			while(strlen($str) > 0) {
				if (strlen($str) > 75) {
					$out .= mb_strcut($str, 0, 75, 'utf-8') . "\r\n";
					$str = ' ' . mb_strcut($str, 75, strlen($str), 'utf-8');
				} else {
					$out .= $str . "\r\n";
					$str = '';
					break;
				}
			}
		
		return $out;
		
	}
	
	
}