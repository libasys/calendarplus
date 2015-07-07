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
 
namespace OCA\CalendarPlus;


use \OCA\CalendarPlus\AppInfo\Application;

$application = new Application();
$application->registerRoutes($this, ['routes' => [
		['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
		['name' => 'public#index', 'url' => '/s/{token}', 'verb' => 'GET'],
		['name' => 'public#index','url'  => '/s/{token}', 'verb' => 'POST', 'postfix' => 'auth'],
		['name' => 'public#getGuestSettingsCalendar', 'url' => '/publicgetguestsettingscalendar', 'verb' => 'GET'],
		['name' => 'public#getGuessTimeZone', 'url' => '/publicgetguesstimezone', 'verb' => 'POST'],
		['name' => 'public#getEventsPublic', 'url' => '/geteventspublic', 'verb' => 'GET'],
		['name' => 'event#getEvents', 'url' => '/getevents', 'verb' => 'GET'],
		['name' => 'public#changeViewCalendarPublic', 'url' => '/changeviewcalendarpublic', 'verb' => 'POST'],
		['name' => 'calendar#changeViewCalendar', 'url' => '/changeviewcalendar', 'verb' => 'POST'],
		['name' => 'event#getReminderEvents', 'url' => '/getreminderevents', 'verb' => 'POST'],
		['name' => 'event#getEventsDayView', 'url' => '/geteventsdayview', 'verb' => 'POST'],
		['name' => 'event#addCategorieToEvent', 'url' => '/addcategorietoevent', 'verb' => 'POST'],
		['name' => 'event#addSharedEvent', 'url' => '/addsharedevent', 'verb' => 'POST'],
		['name' => 'event#addSubscriberEvent', 'url' => '/addsubscriberevent', 'verb' => 'POST'],
		['name' => 'event#deleteExdateEvent', 'url' => '/deleteexdateevent', 'verb' => 'POST'],
		['name' => 'event#deleteSingleRepeatingEvent', 'url' => '/deletesinglerepeatingevent', 'verb' => 'POST'],
		['name' => 'event#deleteEvent', 'url' => '/deleteevent', 'verb' => 'POST'],
		['name' => 'event#moveEvent', 'url' => '/moveevent', 'verb' => 'POST'],
		['name' => 'event#resizeEvent', 'url' => '/resizeevent', 'verb' => 'POST'],
		['name' => 'event#getShowEvent', 'url' => '/getshowevent', 'verb' => 'POST'],
		['name' => 'event#getEditFormEvent', 'url' => '/geteditformevent', 'verb' => 'GET'],
		['name' => 'event#getQuickInfoEvent', 'url' => '/getquickinfoevent', 'verb' => 'GET'],
		['name' => 'event#editEvent', 'url' => '/editevent', 'verb' => 'POST'],
		['name' => 'event#getNewFormEvent', 'url' => '/getnewformevent', 'verb' => 'GET'],
		['name' => 'event#newEvent', 'url' => '/newevent', 'verb' => 'POST'],
		['name' => 'event#sendEmailEventIcs', 'url' => '/sendemaileventics', 'verb' => 'POST'],
		['name' => 'calendarSettings#index', 'url' => '/calendarsettingsindex', 'verb' => 'GET'],
		['name' => 'calendarSettings#setTimeZone', 'url' => '/calendarsettingssettimezone', 'verb' => 'POST'],
		['name' => 'calendarSettings#setTimeFormat', 'url' => '/calendarsettingssettimeformat', 'verb' => 'POST'],
		['name' => 'calendarSettings#setFirstDay', 'url' => '/calendarsettingssetfirstday', 'verb' => 'POST'],
		['name' => 'calendarSettings#timeZoneDectection', 'url' => '/calendarsettingstimezonedetection', 'verb' => 'POST'],
		['name' => 'calendarSettings#reScanCal', 'url' => '/calendarsettingsrescancal', 'verb' => 'GET'],
		['name' => 'calendarSettings#setTaskNavActive', 'url' => '/calendarsettingssettasknavactive', 'verb' => 'POST'],
		['name' => 'calendarSettings#setCalendarNavActive', 'url' => '/calendarsettingssetcalendarnavactive', 'verb' => 'POST'],
		['name' => 'calendarSettings#getUserSettingsCalendar', 'url' => '/calendarsettingsgetusersettingscalendar', 'verb' => 'GET'],
		['name' => 'calendarSettings#saveUserViewSettings', 'url' => '/calendarsettingssaveuserview', 'verb' => 'POST'],
		['name' => 'calendarSettings#getGuessTimeZoneUser', 'url' => '/calendarsettingsgetguesstimezoneuser', 'verb' => 'POST'],
		['name' => 'calendar#getNewFormCalendar', 'url' => '/getnewformcalendar', 'verb' => 'GET'],
		['name' => 'calendar#getEditFormCalendar', 'url' => '/geteditformcalendar', 'verb' => 'POST'],
		['name' => 'calendar#newCalendar', 'url' => '/newcalendar', 'verb' => 'POST'],
		['name' => 'calendar#editCalendar', 'url' => '/editcalendar', 'verb' => 'POST'],
		['name' => 'calendar#deleteCalendar', 'url' => '/deletecalendar', 'verb' => 'POST'],
		['name' => 'calendar#setActiveCalendar', 'url' => '/setactivecalendar', 'verb' => 'POST'],
		['name' => 'calendar#setMyActiveCalendar', 'url' => '/setmyactivecalendar', 'verb' => 'POST'],
		['name' => 'calendar#touchCalendar', 'url' => '/touchcalendar', 'verb' => 'POST'],
		['name' => 'calendar#rebuildLeftNavigation', 'url' => '/rebuildleftnavigationcalendar', 'verb' => 'POST'],
		['name' => 'calendar#refreshSubscribedCalendar', 'url' => '/refreshsubscribedcalendar', 'verb' => 'POST'],
		['name' => 'tasks#rebuildTaskViewRight', 'url' => '/rebuildtaskviewrightcalendar', 'verb' => 'POST'],
		['name' => 'tasks#setCompletedTask', 'url' => '/setcompletedtaskcalendar', 'verb' => 'POST'],
		['name' => 'import#getImportDialogTpl', 'url' => '/getimportdialogtplcalendar', 'verb' => 'POST'],
		['name' => 'import#checkCalendarExists', 'url' => '/checkcalendarexistsimport', 'verb' => 'POST'],
		['name' => 'import#importEvents', 'url' => '/importeventscalendar', 'verb' => 'POST'],
		['name' => 'import#importEventsPerDrop', 'url' => '/importeventsperdropcalendar', 'verb' => 'POST'],
		['name' => 'export#exportEvents', 'url' => '/exporteventscalendar', 'verb' => 'GET'],
		]
		]);


\OCP\API::register('get',
		'/apps/calendarplus/api/v1/shares',
		array('\OCA\CalendarPlus\API\Local', 'getAllShares'),
		'calendarplus');
\OCP\API::register('get',
		'/apps/calendarplus/api/v1/shares/{id}',
		array('\OCA\CalendarPlus\API\Local', 'getShare'),
		'calendarplus');
		