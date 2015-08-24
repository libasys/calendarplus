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
 
namespace OCA\CalendarPlus\AppInfo;

use OC\AppFramework\Utility\SimpleContainer;
use \OCP\AppFramework\App;
use \OCP\Share;
use \OCP\IContainer;
use OCP\AppFramework\IAppContainer;

use \OCA\CalendarPlus\Controller\PageController;
use \OCA\CalendarPlus\Controller\PublicController;
use \OCA\CalendarPlus\Controller\EventController;
use \OCA\CalendarPlus\Controller\CalendarController;
use \OCA\CalendarPlus\Controller\TasksController;
use \OCA\CalendarPlus\Controller\CalendarSettingsController;
use \OCA\CalendarPlus\Controller\ImportController;
use \OCA\CalendarPlus\Controller\ExportController;
use \OCA\CalendarPlus\Controller\RepeatController;
use \OCA\CalendarPlus\Db\EventDAO;
use \OCA\CalendarPlus\Db\CalendarDAO;
use \OCA\CalendarPlus\Db\RepeatDAO;
use \OCA\CalendarPlus\Service\ObjectParser;

class Application extends App {
	
	public function __construct (array $urlParams=array()) {
		
		parent::__construct('calendarplus', $urlParams);
        $container = $this->getContainer();
	
	
		$container->registerService('PageController', function(IContainer $c) {
			return new PageController(
			$c->query('AppName'),
			$c->query('Request'),
			$c->query('UserId'),
			$c->query('L10N'),
			$c->query('Settings')
			);
		});
		
		
		$container->registerService('PublicController', function(IContainer $c) {
			return new PublicController(
			$c->query('AppName'),
			$c->query('Request'),
			$c->query('L10N'),
			$c->query('Session'),
			$c->query('OCP\AppFramework\Utility\IControllerMethodReflector'),
			$c->query('ServerContainer')->getURLGenerator(),
			$c->query('EventController')
			);
		});
		
		$container->registerService('EventController', function(IContainer $c) {
			return new EventController(
			$c->query('AppName'),
			$c->query('Request'),
			$c->query('UserId'),
			$c->query('L10N'),
			$c->query('Settings'),
			$c->query('RepeatController'),
			$c->query('Session'),
			$c->query('AppConfig')
			);
		});
		
		$container->registerService('CalendarController', function(IContainer $c) {
			return new CalendarController(
			$c->query('AppName'),
			$c->query('Request'),
			$c->query('UserId'),
			$c->query('L10N'),
			$c->query('Settings'),
			$c->query('CalendarDAO')
			);
		});
		
		$container->registerService('TasksController', function(IContainer $c) {
			return new TasksController(
			$c->query('AppName'),
			$c->query('Request'),
			$c->query('UserId'),
			$c->query('L10N'),
			$c->query('Settings')
			);
		});
		
		$container->registerService('CalendarSettingsController', function(IContainer $c) {
			return new CalendarSettingsController(
			$c->query('AppName'),
			$c->query('Request'),
			$c->query('UserId'),
			$c->query('L10N'),
			$c->query('Settings'),
			$c->query('Session'),
			$c->query('RepeatController')
			);
		});
	
		$container->registerService('ImportController', function(IContainer $c) {
			return new ImportController(
			$c->query('AppName'),
			$c->query('Request'),
			$c->query('UserId'),
			$c->query('L10N'),
			$c->query('Settings')
			);
		});
		
		$container->registerService('ExportController', function(IContainer $c) {
			return new ExportController(
			$c->query('AppName'),
			$c->query('Request'),
			$c->query('UserId'),
			$c->query('L10N'),
			$c->query('Settings')
			);
		});
		
		$container->registerService('RepeatController', function(IContainer $c) {
			return new RepeatController(
			$c->query('AppName'),
			$c->query('Request'),
			$c->query('UserId'),
			$c->query('RepeatDAO')
			);
		});
		
		/**
         * Database Layer
         */
         
          $container->registerService('RepeatDAO', function(IContainer $c) {
            return new RepeatDAO(
            $c->query('ServerContainer')->getDb(),
            $c->query('UserId')
 			);
        });
		
          $container->registerService('EventDAO', function(IContainer $c) {
            return new EventDAO(
            $c->query('ServerContainer')->getDb(),
            $c->query('UserId'),
            $c->query('CalendarDAO')
 			);
        });
		
		$container->registerService('CalendarDAO', function(IContainer $c) {
            return new CalendarDAO(
            $c->query('ServerContainer')->getDb(),
            $c->query('UserId')
			);
        });
		
		
          /**
		 * Core
		 */
		 
		 $container->registerService('URLGenerator', function(IContainer $c) {
			/** @var \OC\Server $server */
			$server = $c->query('ServerContainer');
			return $server->getURLGenerator();
		});
		
		$container->registerService('AppConfig', function(IContainer $c) {
			/** @var \OC\Server $server */
			$server = $c->query('ServerContainer');
			return $server->getAppConfig();
		}); 
		
		$container -> registerService('UserId', function(IContainer $c) {
			$server = $c->query('ServerContainer');

			$user = $server->getUserSession()->getUser();
			return ($user) ? $user->getUID() : '';
			
		});
		
		$container -> registerService('L10N', function(IContainer $c) {
			return $c -> query('ServerContainer') -> getL10N($c -> query('AppName'));
		});
		
		$container->registerService('Settings', function($c) {
			return $c->query('ServerContainer')->getConfig();
		});
		
		$container->registerService('Session', function (IAppContainer $c) {
			return $c->getServer()
					 ->getSession();
			}
		);
		 $container->registerService('Token', function (IContainer $c) {
			return $c->query('Request') ->getParam('token');
			}
		);
	}
  
    

}

