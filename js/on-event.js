/**
 * ownCloud - CalendarPlus
 *
 * @author Sebastian Doell
 * @copyright 2015 sebastian doell sebastian@libasys.de
 *
 * Copyright (c) 2012 Georg Ehrke <ownclouddev at georgswebsite dot de>
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

/**SETTINGS EVENTS**/
		$(document).on('click', '.chooseCalendar-activeCalendar', function () {
			CalendarPlus.UI.Calendar.activation(this,$(this).data('id'));
		});
		
		$(document).on('click', '.chooseCalendar-showCalDAVURL', function () {
			CalendarPlus.UI.showCalDAVUrl($(this).data('user'), $(this).data('caldav'));
		});
		
		$(document).on('click', '.chooseCalendar-edit', function () {
			CalendarPlus.UI.Calendar.edit($(this), $(this).data('id'));
		});

		$(document).on('click', '.chooseCalendar-delete', function () {
			CalendarPlus.UI.Calendar.deleteCalendar($(this).data('id'));
		});
		
		$(document).on('click', '#caldav_url_close', function () {
			$('#caldav_url').hide();$('#caldav_url_close').hide();
		});

		$(document).on('mouseover', '#caldav_url', function () {
			$('#caldav_url').select();
		});
		
		$(document).on('click', '#newCalendar', function () {
			CalendarPlus.UI.Calendar.newCalendar(this);
		});
		
/**END**/

$(document).on('click', '#editCategories', function () {
	$(this).tipsy('hide');OC.Tags.edit('event');
});

$(document).on('click', '#allday_checkbox', function () {
	CalendarPlus.UI.lockTime();
});

/*
$(document).on('click', '#submitNewEvent', function () {
	Calendar.UI.validateEventForm($(this).data('link'));
});

$(document).on('click', '#editEvent-submit', function () {
	Calendar.UI.validateEventForm($(this).data('link'));
});*/

$(document).on('click', '#allday_checkbox', function () {
	Calendar.UI.lockTime();
});



/**NEW**/
/*
$(document).on('click', '#editEvent-delete-single', function () {
	Calendar.UI.submitDeleteEventSingleForm($(this).data('link'));
});*/

$(document).on('click', '#editEvent-export', function () {
	window.location = $(this).data('link');
});





