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
 * This class contains all hooks.
 */
 namespace OCA\CalendarPlus;
 
class Hooks{
	
	
	   public static function register() {
	   			
			
			\OCP\Share::registerBackend(App::SHARECALENDAR, '\OCA\CalendarPlus\Share\Backend\Calendar');
			\OCP\Share::registerBackend(App::SHAREEVENT, '\OCA\CalendarPlus\Share\Backend\Event');


		  	\OCP\Util::connectHook('OC_User', 'post_createUser', '\OCA\CalendarPlus\Hooks', 'createUser');
			\OCP\Util::connectHook('OC_User', 'post_deleteUser', '\OCA\CalendarPlus\Hooks', 'deleteUser');
			
			if(\OC::$server->getAppManager()->isEnabledForUser('activity')){
				\OCP\Util::connectHook('OCP\Share', 'post_shared', '\OCA\CalendarPlus\Hooks', 'share');
				\OCP\Util::connectHook('OCP\Share', 'post_unshare', '\OCA\CalendarPlus\Hooks', 'unshare');
			}
			
	//		\OCP\Util::connectHook('OCP\Share', 'share_internal_mail', 'OCA\Calendar\Hooks', 'shareInternalMail');
			\OCP\Util::connectHook('OC_Calendar', 'addEvent', '\OCA\CalendarPlus\Repeat', 'generate');
			\OCP\Util::connectHook('OC_Calendar', 'editEvent', '\OCA\CalendarPlus\Repeat', 'update');
			\OCP\Util::connectHook('OC_Calendar', 'deleteEvent', '\OCA\CalendarPlus\Repeat', 'clean');
			\OCP\Util::connectHook('OC_Calendar', 'moveEvent', '\OCA\CalendarPlus\Repeat', 'update');
			\OCP\Util::connectHook('OC_Calendar', 'deleteCalendar', '\OCA\CalendarPlus\Repeat', 'cleanCalendar');
			
			
	   }
	
	
 
	public static function createUser($parameters) {
		Calendar::addDefaultCalendars($parameters['uid']);

		return true;
	}

	/**
	 * @brief Deletes all calendars of a certain user
	 * @param paramters parameters from postDeleteUser-Hook
	 * @return array
	 */
	public static function deleteUser($parameters) {
		\OCP\Util::writeLog('calendarplus', 'Hook DEL ID-> '.$parameters['uid'], \OCP\Util::DEBUG);	
		$calendars = Calendar::allCalendars($parameters['uid']);

		foreach($calendars as $calendar) {
			if($parameters['uid'] === $calendar['userid']) {
				Calendar::deleteCalendar($calendar['id']);
			}
		}
		//delete preferences
		
		return true;
	}
	
	/**
	 * @brief Manage sharing events
	 * @param array $params The hook params
	 */
	public static function share($params) {
		
			if(($params['itemType'] === App::SHARECALENDAR || $params['itemType'] === App::SHAREEVENT || $params['itemType'] ===  App::SHARETODO) && $params['shareType']!==2){
				self::prepareActivityLog($params);
			}	
			
		
	}


     /**
	 * @brief Manage sharing events
	 * @param array $params The hook params
	 */
	public static function unshare($params) {
		
			if($params['itemType'] === App::SHARECALENDAR || $params['itemType'] === App::SHAREEVENT || $params['itemType'] ===  App::SHARETODO){
				self::prepareUnshareActivity($params);
			}	
			
		
	}    
	
    public static function prepareUnshareActivity($unshareData){
    	  
		    $l =  \OC::$server->getL10N(App::$appname);
			$type='unshared_calendar';
			
			$sType='';
			$sL10nDescr = '';	
			if($unshareData['itemType'] === App::SHARECALENDAR){
				$sType=App::SHARECALENDARPREFIX;
				$sL10nDescr = 'calendar';
			}	
			if($unshareData['itemType'] === App::SHAREEVENT){
				$sType=App::SHAREEVENTPREFIX;
				$sL10nDescr = 'event';
			}
			
			if($unshareData['itemType'] === App::SHARETODO){
					$sType= App::SHARETODOPREFIX;
					$sL10nDescr = 'todo';
				}
			
			if($unshareData['shareType'] === \OCP\Share::SHARE_TYPE_LINK){
				
			$description='';	
			
					
			 $unshareData['itemSource'] = App::validateItemSource($unshareData['itemSource'],$sType); 			    
			 
			 if($unshareData['itemType'] === App::SHARECALENDAR){
				$aCalendar = Calendar::find($unshareData['itemSource']);	
				 $description = $l->t('calendar').' '.$aCalendar['displayname'];
		     }else{
		     	$aObject = Object::find($unshareData['itemSource']);
				$aCalendar = Calendar::find($aObject['calendarid']);
				$description = $l->t($sL10nDescr).' '.$aObject['summary'].' ('.$l->t('calendar').' '.$aCalendar['displayname'].')';
		     }
	
					
				\OC::$server->getActivityManager()->publishActivity(App::$appname,'unshared_link_self_calendar', array($description), '', '','', '', \OCP\User::getUser(), $type, '');	
			
			}
			
			if($unshareData['shareType'] === \OCP\Share::SHARE_TYPE_USER){
					
				$unshareData['itemSource'] = App::validateItemSource($unshareData['itemSource'],$sType);
           		$description='';	
				
			   if($unshareData['itemType'] === App::SHAREEVENT || $unshareData['itemType'] ===  App::SHARETODO){
					$aObject=Object::find($unshareData['itemSource']);
					$aCalendar=Calendar::find($aObject['calendarid']);	
					$description=$aObject['summary'].' ('.$l->t('calendar').' '.$aCalendar['displayname'].')';
				}
				if($unshareData['itemType'] === App::SHARECALENDAR){
					$aCalendar=Calendar::find($unshareData['itemSource']);
					$description=$aCalendar['displayname'];
				}
				
				\OC::$server->getActivityManager()->publishActivity(App::$appname,'unshared_user_self_calendar', array($l->t($sL10nDescr).' '.$description,$unshareData['shareWith']), '', '','', '', \OCP\User::getUser(), $type, '');	
					
				\OC::$server->getActivityManager()->publishActivity(App::$appname,'unshared_with_by_calendar', array($l->t($sL10nDescr).' '.$description,\OCP\User::getUser()), '', '','', '', $unshareData['shareWith'], $type, '');	
					
			}

           if($unshareData['shareType'] === \OCP\Share::SHARE_TYPE_GROUP){
           	
				$description='';	
			   $unshareData['itemSource'] = App::validateItemSource($unshareData['itemSource'],$sType);
			   
		   	   if($unshareData['itemType'] === App::SHAREEVENT || $unshareData['itemType'] ===  App::SHARETODO){
					$aObject=Object::find($unshareData['itemSource']);
					$aCalendar=Calendar::find($aObject['calendarid']);	
					$description=$aObject['summary'].' ('.$l->t('calendar').' '.$aCalendar['displayname'].')';
				}
				if($unshareData['itemType'] === App::SHARECALENDAR){
					$aCalendar=Calendar::find($unshareData['itemSource']);
					$description=$aCalendar['displayname'];
				}
					
				\OC::$server->getActivityManager()->publishActivity(App::$appname,'unshared_group_self_calendar', array($l->t($sL10nDescr).' '.$description,$unshareData['shareWith']), '', '','', '', \OCP\User::getUser(), $type, '');	
					
				$usersInGroup = \OC_Group::usersInGroup($unshareData['shareWith']);
					
				foreach ($usersInGroup as $user) {
						\OC::$server->getActivityManager()->publishActivity(App::$appname,'unshared_with_by_calendar', array($l->t($sL10nDescr).' '.$description,\OCP\User::getUser()), '', '','', '', $user, $type, '');
				}
					
           }
    }
	
	
	
	public static function prepareActivityLog($shareData){
   		
         $aApp=array(App::SHARECALENDAR=>'calendar', App::SHAREEVENT=>'calendar', App::SHARETODO => App::SHARECALENDAR);
   		//shared_with_by, shared_user_self,shared_group_self,shared_link_self
  	   		
			if(array_key_exists($shareData['itemType'], $aApp)){
				$sType='';	
				$sL10nDescr = '';
				if($shareData['itemType'] === App::SHARECALENDAR){
					$sType=App::SHARECALENDARPREFIX;
					$sL10nDescr = 'calendar';
				}	
				if($shareData['itemType'] === App::SHAREEVENT){
					$sType=App::SHAREEVENTPREFIX;
					$sL10nDescr = 'event';
				}
				if($shareData['itemType'] ===  App::SHARETODO){
					$sType= App::SHARETODOPREFIX;
					$sL10nDescr = 'todo';
				}
				
				$sApp=$aApp[$shareData['itemType']];
				
				$l =  \OC::$server->getL10N(App::$appname);
				
				$type='shared_'.$sApp;
				
				if($shareData['token'] !=='' && $shareData['shareType'] === \OCP\Share::SHARE_TYPE_LINK){
						
					$shareData['itemSource'] = App::validateItemSource($shareData['itemSource'],$sType); 
						
					if($shareData['itemType'] === App::SHAREEVENT || $shareData['itemType'] === App::SHARECALENDAR){	
						$link = \OC::$server->getURLGenerator()->linkToRoute(App::$appname.'.public.index',['token' => $shareData['token']]);	
					}

					if($shareData['itemType'] ===  App::SHARETODO){	
						$link = \OC::$server->getURLGenerator()->linkToRoute('tasksplus.public.index',['token' => $shareData['token']]);	
					}
					if($shareData['itemType'] === App::SHAREEVENT || $shareData['itemType'] ===  App::SHARETODO){
						$aObject=Object::find($shareData['itemSource']);
						$aCalendar=Calendar::find($aObject['calendarid']);	
						$description = $l->t($sL10nDescr).' '.$aObject['summary'].' ('.$l->t('calendar').' '.$aCalendar['displayname'].')';
					}else{
						$description = $l->t($sL10nDescr).' '.$shareData['itemTarget'];
					}
					\OC::$server->getActivityManager()->publishActivity(App::$appname,'shared_link_self_'.$sApp, array($description), '', '','', $link, \OCP\User::getUser(), $type, '');	
				}
				
				if($shareData['shareType'] === \OCP\Share::SHARE_TYPE_USER){
					$link='';
					$shareData['itemSource'] = App::validateItemSource($shareData['itemSource'],$sType); 	
					if($shareData['itemType'] ===  App::SHARETODO){
						$link = \OC::$server->getURLGenerator()->linkToRoute('tasksplus.page.index').'#'.urlencode($shareData['itemSource']);
					}
					if($shareData['itemType'] === App::SHAREEVENT){
						$link = \OC::$server->getURLGenerator()->linkToRoute(App::$appname.'.page.index').'#'.urlencode($shareData['itemSource']);
					}
					$description=$shareData['itemTarget'];
					if($shareData['itemType']=== App::SHARETODO || $shareData['itemType'] === App::SHAREEVENT){
						 $aObject=Object::find($shareData['itemSource']);
						 $aCalendar=Calendar::find($aObject['calendarid']);	
						 $description=$aObject['summary'].' ('.$l->t('calendar').' '.$aCalendar['displayname'].')';
					}
						
					 	\OC::$server->getActivityManager()->publishActivity(App::$appname,'shared_user_self_'.$sApp, array($l->t($sL10nDescr).' '.$description,$shareData['shareWith']), '', '','', $link, \OCP\User::getUser(), $type, '');
				
						\OC::$server->getActivityManager()->publishActivity(App::$appname,'shared_with_by_'.$sApp, array($l->t($sL10nDescr).' '.$description,\OCP\User::getUser()), '', '','', $link, $shareData['shareWith'], $type, '');
					
				}
				
				if($shareData['shareType'] === \OCP\Share::SHARE_TYPE_GROUP){
					
					$link='';
					$shareData['itemSource'] = App::validateItemSource($shareData['itemSource'],$sType); 	
		
					if($shareData['itemType'] === App::SHARETODO){
						$link = \OC::$server->getURLGenerator()->linkToRoute('tasksplus.page.index').'#'.urlencode($shareData['itemSource']);
					}
					if($shareData['itemType'] === App::SHAREEVENT){
						$link = \OC::$server->getURLGenerator()->linkToRoute('calendar.page.index').'#'.urlencode($shareData['itemSource']);

					}
					
					$description=$shareData['itemTarget'];
					if($shareData['itemType']===  App::SHARETODO || $shareData['itemType']===App::SHAREEVENT){
						 $aObject=Object::find($shareData['itemSource']);
						 $aCalendar=Calendar::find($aObject['calendarid']);	
						 $description=$aObject['summary'].' ('.$l->t('calendar').' '.$aCalendar['displayname'].')';
					}
				
					\OC::$server->getActivityManager()->publishActivity(App::$appname,'shared_group_self_'.$sApp, array($l->t($sL10nDescr).' '.$description,$shareData['shareWith']), '', '','', $link, \OCP\User::getUser(), $type, '');
				
					$usersInGroup = \OC_Group::usersInGroup($shareData['shareWith']);
						
					foreach ($usersInGroup as $user) {
							\OC::$server->getActivityManager()->publishActivity(App::$appname,'shared_with_by_'.$sApp, array($l->t($sL10nDescr).' '.$description,\OCP\User::getUser()), '', '','', $link, $user, 'shared_'.$sApp, '');
					}
				}
			}
		
		
    }
  

   public static function prepareUserDisplayOutput($sUser){
   	      $displayName = \OCP\User::getDisplayName($sUser);
		  $sUser = \OCP\Util::sanitizeHTML($sUser);
		  $displayName = \OCP\Util::sanitizeHTML($displayName);
		  return '<div class="avatar" data-user="' . $sUser . '"></div><strong>' . $displayName . '</strong>';

   }
	
}
