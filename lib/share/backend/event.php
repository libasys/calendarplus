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

use OCA\CalendarPlus\Object;
use OCA\CalendarPlus\App;

class Event implements \OCP\Share_Backend {

	const FORMAT_EVENT = 0;

	private static $event;

	public function isValidSource($itemSource, $uidOwner) {
		$itemSource = App::validateItemSource($itemSource, App::SHAREEVENTPREFIX);
		self::$event = Object::find($itemSource);
		if (self::$event) {
			return true;
		}
		return false;
	}
    
	
	
	public function generateTarget($itemSource, $shareWith, $exclude = null) {
		$itemSource = App::validateItemSource($itemSource, App::SHAREEVENTPREFIX);
		if(!self::$event) {
			self::$event = Object::find($itemSource);
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
				$item['item_source'] = App::validateItemSource($item['item_source'], App::SHAREEVENTPREFIX);	
				
				if(!Object::checkSharedEvent($item['item_source'])){	
				$event = Object::find($item['item_source']);
				
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
