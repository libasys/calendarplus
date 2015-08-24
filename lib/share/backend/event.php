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
namespace OCA\CalendarPlus\Share\Backend;

use OCA\CalendarPlus\Connector\CalendarConnector;
use OCA\CalendarPlus\Share\ShareConnector;


class Event implements \OCP\Share_Backend {

	const FORMAT_EVENT = 0;

	private static $event;
	
	public function __construct(){
		$this->calendarConnector = new CalendarConnector();
		$this->shareConnector = new ShareConnector();	
	}
	
	public function isValidSource($itemSource, $uidOwner) {
			
		$itemSource = $this->shareConnector->validateItemSource($itemSource, $this->shareConnector->getConstSharePrefixEvent());
		
		self::$event = $this->calendarConnector->findObject($itemSource);
		if (self::$event) {
			return true;
		}
		return false;
	}
    
	
	
	public function generateTarget($itemSource, $shareWith, $exclude = null) {
		
		$itemSource = $this->shareConnector->validateItemSource($itemSource, $this->shareConnector->getConstSharePrefixEvent());
		
		if(!self::$event) {
			self::$event = $this->calendarConnector->findObject($itemSource);
		}
		return self::$event['summary'];
	}

	public function isShareTypeAllowed($shareType) {
		return true;
	}

	public function formatItems($items, $format, $parameters = null) {
		$events = array();
		if ($format == self::FORMAT_EVENT) {
			
			foreach ($items as $item) {
					
				$item['item_source'] = $this->shareConnector->validateItemSource($item['item_source'], $this->shareConnector->getConstSharePrefixEvent());
				
				if(!$this->calendarConnector->checkIfObjectIsShared($item['item_source'])){
						
					$event =  $this->calendarConnector->findObject($item['item_source']); 
					
					$event['summary'] = $item['item_target'];
					$event['item_source'] = (int) $item['item_source'];
					$event['privat'] =false;
					$event['shared'] =false;
					$event['isalarm']=$event['isalarm'];
					$event['permissions'] = $item['permissions'];
					//$event['userid'] = $event['userid'];
					$event['orgevent'] =false;
					
					
					$events[] = $event;
				}
			}
		}
		return $events;
	}

}
