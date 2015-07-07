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


$(document).ready(function(){
	    
		$('.viewsettings').change( function(){
			$.post( OC.generateUrl('apps/'+CalendarPlus.appname+'/calendarsettingssaveuserview'), {
				'checked' : $(this).is(':checked'),
				'name' : $(this).attr('name')
			}, function(jsondata){
				if(jsondata.status == 'success'){
					CalendarPlus.calendarConfig['userconfig'][jsondata.data.name]= jsondata.data.checked;
					if(jsondata.data.checked === 'true'){
						$('.view button[data-action="'+jsondata.data.name+'"]').show();
					}else{
						$('.view button[data-action="'+jsondata.data.name+'"]').hide();
					}
				}
				//OC.msg.finishedSaving('.msgTzd', jsondata);
			});
			return false;
		});
		
		$('#timeformat').chosen();
		$('#firstday').chosen();
		$('#timezone').chosen();
		
		$('#timezone').change( function(){
			var post = $( '#timezone' ).serialize();
			$.post( OC.generateUrl('apps/'+CalendarPlus.appname+'/calendarsettingssettimezone'), post, function(jsondata){
				$('#fullcalendar').fullCalendar('destroy');
				CalendarPlus.init();
				OC.msg.finishedSaving('.msgTz', jsondata);
				});
			return false;
		});
		
		$('#timeformat').change( function(){
			var data = $('#timeformat').serialize();
			$.post( OC.generateUrl('apps/'+CalendarPlus.appname+'/calendarsettingssettimeformat'), data, function(jsondata){
				OC.msg.finishedSaving('.msgTf', jsondata);
				CalendarPlus.calendarConfig['agendatime'] = jsondata.data.agendaTime;
				CalendarPlus.calendarConfig['defaulttime'] = jsondata.data.defaultTime;
				$('#fullcalendar').fullCalendar('destroy');
				CalendarPlus.init();
				});
			return false;
		});
		
		$('#firstday').change( function(){
			var data = $('#firstday').serialize();
			$.post( OC.generateUrl('apps/'+CalendarPlus.appname+'/calendarsettingssetfirstday'), data, function(jsondata){
				OC.msg.finishedSaving('.msgFd', jsondata);
				CalendarPlus.calendarConfig['firstDay'] = jsondata.firstday;
				$("#datepickerNav").datepicker('option', 'firstDay', jsondata.firstday);
				$('#fullcalendar').fullCalendar('destroy');
				CalendarPlus.init();
				
			});
			return false;
		});
		
		$('#timezonedetection').change( function(){
			var data = $('#timezonedetection').serialize();
			$.post( OC.generateUrl('apps/'+CalendarPlus.appname+'/calendarsettingstimezonedetection'), data, function(jsondata){
				OC.msg.finishedSaving('.msgTzd', jsondata);
			});
			return false;
		});
		
		$('#cleancalendarcache').click(function(){
			$.getJSON(OC.generateUrl('apps/'+CalendarPlus.appname+'/calendarsettingsrescancal'), function(jsondata){
				OC.msg.finishedSaving('.msgCcc', jsondata);
			});
	});

	

});



