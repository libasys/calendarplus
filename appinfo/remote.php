<?php
/**
 * ownCloud - CalendarPlus
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
 

/*
if(substr(\OC::$server->getRequest()->getRequestUri(),0,strlen(OC_App::getAppWebPath('calendar').'/caldav.php')) == OC_App::getAppWebPath('calendar'). '/caldav.php') {
	$baseuri = OC_App::getAppWebPath('calendar').'/caldav.php';
}*/

// only need authentication apps



// Backends
//$principalBackend = new OCA\Calendar\Connector\Sabre\Principal();

$authBackend = new \OC\Connector\Sabre\Auth();

$principalBackend = new \OC\Connector\Sabre\Principal(
	\OC::$server->getConfig(),
	\OC::$server->getUserManager()
);



$caldavBackend    = new OCA\CalendarPlus\Connector\Sabre\Backend();


	// Root nodes
	$Sabre_CalDAV_Principal_Collection = new \Sabre\CalDAV\Principal\Collection($principalBackend);
	$Sabre_CalDAV_Principal_Collection->disableListing = true; // Disable listening
	
	$calendarRoot = new OCA\CalendarPlus\Connector\Sabre\CalendarRoot($principalBackend, $caldavBackend);
	$calendarRoot->disableListing = true; // Disable listening
	
	$nodes = array(
		$Sabre_CalDAV_Principal_Collection,
		$calendarRoot,
		);
	
	
	// Fire up server
	$server = new \Sabre\DAV\Server($nodes);
	$server->httpRequest->setUrl(\OC::$server->getRequest()->getRequestUri());
	$server->setBaseUri($baseuri);
	// Add plugins
	$defaults = new OC_Defaults();
	$server->addPlugin(new \OC\Connector\Sabre\MaintenancePlugin());
	$server->addPlugin(new \OC\Connector\Sabre\DummyGetResponsePlugin());
	$server->addPlugin(new \Sabre\DAV\Auth\Plugin($authBackend,$defaults->getName()));
	$server->addPlugin(new \Sabre\CalDAV\Plugin());
	$server->addPlugin(new \Sabre\DAVACL\Plugin());
	$server->addPlugin(new \Sabre\CalDAV\ICSExportPlugin());
	$server->addPlugin(new \OC\Connector\Sabre\ExceptionLoggerPlugin('caldav', \OC::$server->getLogger()));
	$server->addPlugin(new \OC\Connector\Sabre\AppEnabledPlugin(
		'calendarplus',
		\OC::$server->getAppManager()
	));
	
	// And off we go!
	$server->exec();
	
