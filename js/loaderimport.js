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

var CalendarPlus = CalendarPlus || {};
CalendarPlus.appname='calendarplus';

CalendarPlus.Import =  {
	Store:{
		file: '',
		path: '',
		id: 0,
		method: '',
		overwrite: 0,
		calname: '',
		calcolor: '',
		progresskey: '',
		percentage: 0,
		isDragged : false,
		isSub:false,
		subUri:''
	},
	Dialog:{
		open: function(filename){
			OC.addStyle('calendarplus', 'import');
			
			CalendarPlus.Import.Store.file = filename;
			CalendarPlus.Import.Store.path = $('#dir').val();
			$('body').append('<div id="calendar_import"></div>');
			$('#calendar_import').load(OC.generateUrl('apps/'+CalendarPlus.appname+'/getimportdialogtplcalendar'), {
				filename:CalendarPlus.Import.Store.file, 
				path:CalendarPlus.Import.Store.path, 
				isDragged:CalendarPlus.Import.Store.isDragged,
				isSub:CalendarPlus.Import.Store.isSub
				},function(){
					CalendarPlus.Import.Dialog.init();
			});
		},
		close: function(){
			CalendarPlus.Import.reset();
			$('#calendar_import_dialog').ocdialog('close');
			$('#calendar_import_dialog').ocdialog('destroy').remove();
			$('#calendar_import_dialog').remove();
		},
		init: function(){
			//init dialog
			$('#calendar_import_dialog').ocdialog({
					modal: true,
					closeOnEscape: true,
					height: 'auto', width:500,
					close : function() {
					CalendarPlus.Import.Dialog.close();
					}
			});
			
			
			//init buttons
			$('#calendar_import_done').click(function(){
				CalendarPlus.Import.Dialog.close();
			});
			$('#calendar_import_submit').click(function(){
				CalendarPlus.Import.Core.process();
				return false;
			});
			
			if(CalendarPlus.Import.Store.isSub === true){
				$('.importdiv').show();
				$('#calendar_import_form').hide();
				$('#calendar_import_checkbox').hide();
				$('#checkurl').on('click',function(){
				  if(!$(this).hasClass('file-ok')){
					  if($('#calendar_import_url').val()!=''){
					  	CalendarPlus.Import.Core.checkUrl($('#calendar_import_url').val());
					  }
				  }
				});
				
			}else{
				$('.importdiv').hide();
			}
			
			$('#calendar_import_mergewarning').click(function(){
				$('#calendar_import_newcalendar').attr('value', $('#calendar_import_availablename').val());
				CalendarPlus.Import.Dialog.mergewarning($('#calendar_import_newcalendar').val());
				return false;
			});
			$('#calendar_import_calendar').change(function(){
				if($('#calendar_import_calendar option:selected').val() == 'newcal'){
					$('#calendar_import_newcalform').slideDown('slow');
					CalendarPlus.Import.Dialog.mergewarning($('#calendar_import_newcalendar').val());
				}else{
					$('#calendar_import_newcalform').slideUp('slow');
					$('#calendar_import_mergewarning').slideUp('slow');
				}
			});
			$('#calendar_import_newcalendar').keyup(function(){
				CalendarPlus.Import.Dialog.mergewarning($.trim($('#calendar_import_newcalendar').val()));
			});
			$('#calendar_import_newcalendar_color').colorPicker();
			
			//init progressbar
			$('#calendar_import_progressbar').progressbar({value: CalendarPlus.Import.Store.percentage});
			CalendarPlus.Import.Store.progresskey = $('#calendar_import_progresskey').val();
			
		},
		mergewarning: function(newcalname){
			$.post(OC.generateUrl('apps/'+CalendarPlus.appname+'/checkcalendarexistsimport'), {calname: newcalname}, function(data){
				if(data.message == 'exists'){
					$('#calendar_import_mergewarning').slideDown('slow');
				}else{
					$('#calendar_import_mergewarning').slideUp('slow');
				}
			});
		},
		update: function(){
			if(CalendarPlus.Import.Store.percentage == 100){
				return false;
			}
			$.post(OC.generateUrl('apps/'+CalendarPlus.appname+'/importeventscalendar'), {progresskey: CalendarPlus.Import.Store.progresskey, getprogress: true}, function(data){
 				if(data.status == 'success'){
 					if(data.percent == null){
	 					return false;
 					}
 					
 					CalendarPlus.Import.Store.percentage = parseInt(data.percent);
					$('#calendar_import_progressbar').progressbar('option', 'value', parseInt(data.percent));
					$('#calendar_import_process_message').text(data.currentmsg);
					if(data.percent < 100 ){
						window.setTimeout('CalendarPlus.Import.Dialog.update()', 100);
					}else{
						$('#calendar_import_done').css('display', 'block');
						
					}
				}else{
					$('#calendar_import_progressbar').progressbar('option', 'value', 100);
					$('#calendar_import_progressbar > div').css('background-color', '#FF2626');
					$('#calendar_import_status').html(data.message);
				}
			});
			return 0;
		},
		warning: function(selector){
			$(selector).addClass('calendar_import_warning');
			$(selector).focus(function(){
				$(selector).removeClass('calendar_import_warning');
			});
		}
	},
	Core:{
		checkUrl:function(url){
			$.post(OC.generateUrl('apps/'+CalendarPlus.appname+'/checkimporturl'),
			{
				importurl:url
			},function(data){
				if(data.status === 'success'){
					CalendarPlus.Import.Store.file = data.file;
					CalendarPlus.Import.Store.subUri = data.externUriFile;
					$('#calendar_import_calendar').val('newcal');
					$('#calendar_import_form').show();
					$('#importmsg').text('');
					$('#calendar_import_url').val(data.externUriFile).attr('readonly','readonly').addClass('url-ok');
					$('#checkurl').addClass('file-ok');
					$('#calendar_import_form_message').hide();
					$('#calendar_import_calendar').hide();
					$('#calendar_import_newcalform').show();
					CalendarPlus.Import.Dialog.mergewarning(data.guessedcalendarname);
					$('#calendar_import_newcalendar').val(data.guessedcalendarname);
					$('#calendar_import_newcalendar_color').val(data.guessedcalendarcolor);
					
				}
				if(data.status === 'error'){
					
					$('#importmsg').text(data.message);
				}
			});
		},
		process: function(){
			var validation = CalendarPlus.Import.Core.prepare();
			if(validation){
				$('#calendar_import_form').css('display', 'none');
				$('#calendar_import_process').css('display', 'block');
				$('#calendar_import_newcalendar').attr('readonly', 'readonly');
				$('#calendar_import_calendar').attr('disabled', 'disabled');
				$('#calendar_import_overwrite').attr('disabled', 'disabled');
				CalendarPlus.Import.Core.send();
				window.setTimeout('CalendarPlus.Import.Dialog.update()', 250);
			}
		},
		send: function(){
			
			$.post(OC.generateUrl('apps/'+CalendarPlus.appname+'/importeventscalendar'),
			{
				progresskey: CalendarPlus.Import.Store.progresskey, 
				method: String (CalendarPlus.Import.Store.method), 
				overwrite: String (CalendarPlus.Import.Store.overwrite), 
				calname: String (CalendarPlus.Import.Store.calname), 
				path: String (CalendarPlus.Import.Store.path), 
				file: String (CalendarPlus.Import.Store.file), 
				id: String (CalendarPlus.Import.Store.id), 
				calcolor: String (CalendarPlus.Import.Store.calcolor),
				isDragged:String (CalendarPlus.Import.Store.isDragged),
				isSub:String (CalendarPlus.Import.Store.isSub),
				subUri:String (CalendarPlus.Import.Store.subUri)
			}, function(data){
				if(data.status == 'success'){
					$('#calendar_import_progressbar').progressbar('option', 'value', 100);
					CalendarPlus.Import.Store.percentage = 100;
					$('.importdiv').hide();
					$('#calendar_import_progressbar').hide();
					$('#calendar_import_done').css('display', 'block');
					$('#calendar_import_process_message').text('').hide();
					$('#calendar_import_status').html(data.message);
					
					if(CalendarPlus.Import.Store.isDragged === true || CalendarPlus.Import.Store.isSub === true){
						
						if(data.eventSource !== ''){
							$('#fullcalendar').fullCalendar('addEventSource', data.eventSource);
							CalendarPlus.Util.rebuildCalView();
							CalendarPlus.initUserSettings(0);
						}else{
							$('#fullcalendar').fullCalendar('refetchEvents');
						}
					}
				}else{
					$('#calendar_import_progressbar').progressbar('option', 'value', 100);
					$('#calendar_import_progressbar > div').css('background-color', '#FF2626');
					$('#calendar_import_status').html(data.message);
				}
			});
		},
		prepare: function(){
			CalendarPlus.Import.Store.id = $('#calendar_import_calendar option:selected').val();
			
			if($('#calendar_import_calendar option:selected').val() == 'newcal'){
				CalendarPlus.Import.Store.method = 'new';
				CalendarPlus.Import.Store.calname = $.trim($('#calendar_import_newcalendar').val());
				if(CalendarPlus.Import.Store.calname == ''){
					CalendarPlus.Import.Dialog.warning('#calendar_import_newcalendar');
					return false;
				}
				CalendarPlus.Import.Store.calcolor = $.trim($('#calendar_import_newcalendar_color').val());
				if(CalendarPlus.Import.Store.calcolor == ''){
					CalendarPlus.Import.Store.calcolor = $('.calendar-colorpicker-color:first').attr('rel');
				}
			}else{
				CalendarPlus.Import.Store.method = 'old';
				CalendarPlus.Import.Store.overwrite = $('#calendar_import_overwrite').is(':checked') ? 1 : 0;
			}
			return true;
		}
	},
	reset: function(){
		CalendarPlus.Import.Store.file = '';
		CalendarPlus.Import.Store.path = '';
		CalendarPlus.Import.Store.id = 0;
		CalendarPlus.Import.Store.method = '';
		CalendarPlus.Import.Store.overwrite = 0;
		CalendarPlus.Import.Store.calname = '';
		CalendarPlus.Import.Store.progresskey = '';
		CalendarPlus.Import.Store.percentage = 0;
		CalendarPlus.Import.Store.isSub = false;
		CalendarPlus.Import.Store.isDragged = false;
		CalendarPlus.Import.Store.subUri = '';
		
	}
};

$(document).ready(function(){
	if(typeof FileActions !== 'undefined'){
		FileActions.register('text/calendar','importCalendar',  OC.PERMISSION_READ, '', CalendarPlus.Import.Dialog.open);
		FileActions.setDefault('text/calendar','importCalendar');
	};
});
