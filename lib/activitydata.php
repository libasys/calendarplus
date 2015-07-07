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


namespace OCA\CalendarPlus;

class ActivityData{


/*
	 * Emit Hook add_event_activity  on lib/object.php add()
	 * 
	 *  @params array link, trans_type, summary, cal_user, cal_displayname
	 * **/
	 
     public static function logEventActivity($params,$syncedWithDav=false,$bCal=false){
   			if(\OC::$server->getAppManager()->isEnabledForUser('activity')){
				$sncDescr='';	
	   			if($syncedWithDav){
	   				$sncDescr='Syncing per CalDav -> ';
	   			}
				$prefixMode='event_';
				$prefixMode1='';
				if($bCal==true){
					$prefixMode='calendar_';
					$prefixMode1='_calendar';
				}
				 if ($params['cal_user'] !== \OCP\User::getUser()) {
			 	    
					    $subjParam=array($sncDescr.$params['trans_type'].' '.$params['summary'],\OCP\User::getUser(),$params['cal_displayname']); 
					   \OC::$server->getActivityManager()->publishActivity(App::$appname, $params['mode'].'_by_other', $subjParam, '', '','', $params['link'],$params['cal_user'], 'shared_event_'.$params['mode'], '');
				}
			 	
			
				$subjParam=array($sncDescr.$params['trans_type'].' '.$params['summary'],$params['cal_displayname']); 	
			 	\OC::$server->getActivityManager()->publishActivity(App::$appname,  $params['mode'].$prefixMode1.'_self', $subjParam, '', '','', $params['link'], \OCP\User::getUser(), $prefixMode.$params['mode'], '');
			}
	
     }
   
    

}