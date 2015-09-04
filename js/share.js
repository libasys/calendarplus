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

var myAppName = 'calendarplus';

CalendarShare={
   calendarConfig:null,
   popOverElem:null,
   availableViews:{
   	 'prev':{'action':'prev','view':false,'weekend':false,'title':'<i class="ioc ioc-angle-left"></i>'},
   	 'agendaDay':{'action':'agendaDay','view':true,'weekend':true,'title':t(myAppName,'Day')},
   	 'agendaThreeDays':{'action':'agendaThreeDays','view':true,'weekend':true,'title':t(myAppName,'3-Days')},
   	 'agendaWorkWeek':{'action':'agendaWorkWeek','view':true,'weekend':false,'title':t(myAppName,'W-Week')},
   	 'agendaWeek':{'action':'agendaWeek','view':true,'weekend':true,'title':t(myAppName,'Week')},
   	 'month':{'action':'month','view':true,'weekend':true,'title':t(myAppName,'Month')},
   	 'year':{'action':'year','view':true,'weekend':true,'title':t(myAppName,'Year')},
   	 'list':{'action':'list','view':true,'weekend':true,'title':t(myAppName,'List')},
   	 'next':{'action':'next','view':false,'weekend':false,'title':'<i class="ioc ioc-angle-right"></i>'},
   },
	init:function(){
		var token = ($('#fullcalendar').data('token') !== undefined) ? $('#fullcalendar').data('token') : '';
		
							
		if(CalendarShare.calendarConfig == null){
			$.getJSON(OC.generateUrl('apps/'+myAppName+'/publicgetguestsettingscalendar'),{t:token}, function(jsondata){
				if(jsondata.status == 'success'){
					CalendarShare.calendarConfig=[];
					
					if(CalendarShare.defaultConfig[jsondata.calendarId] !== undefined){
						CalendarShare.defaultConfig = CalendarShare.defaultConfig[jsondata.calendarId];
					}else{
						CalendarShare.defaultConfig = CalendarShare.defaultConfig[0];
					}
					
					CalendarShare.calendarConfig['defaultView'] = CalendarShare.defaultConfig['defaultView'];
					CalendarShare.calendarConfig['agendatime'] = CalendarShare.defaultConfig['agendatime'];
					CalendarShare.calendarConfig['defaulttime'] = CalendarShare.defaultConfig['defaulttime'];
					CalendarShare.calendarConfig['dateformat'] = CalendarShare.defaultConfig['dateformat'];
					CalendarShare.calendarConfig['timeformat'] = CalendarShare.defaultConfig['timeformat'];
					CalendarShare.calendarConfig['firstDay'] = CalendarShare.defaultConfig['firstDay'];
					CalendarShare.calendarConfig['eventSources'] = jsondata.eventSources;
					CalendarShare.calendarConfig['calendarcolors'] = jsondata.calendarcolors;
					CalendarShare.calendarConfig['myRefreshChecker'] = jsondata.myRefreshChecker;
					
					
					if(CalendarShare.defaultConfig['smallCalendarLeft'] === true){
						CalendarShare.buildLeftNavigation();
					}else{
						$('#app-navigation').remove();
					}
					if(CalendarShare.defaultConfig['header'] === false){
						$('header').remove();
						$('#controls').css('top',0);
						$('#fullcalendar').css('top','50px');
						$('#app-navigation').css('top','50px');
						if(CalendarShare.defaultConfig['calendarViews'] === null 
						&& CalendarShare.defaultConfig['showTodayButton'] === false
						&& CalendarShare.defaultConfig['showTimeZone'] === false
						){
							$('#fullcalendar').css('top','10px');
							$('#app-navigation').css('top','40px');
							$('#controls').remove();
						}
					}else{
						$('#header').show();
					}
					
					if(CalendarShare.defaultConfig['showTodayButton'] === false){
						$('.leftControls').remove();
					}else{
						$('.leftControls').show();
					}
					
					if(CalendarShare.defaultConfig['footer'] === false){
						$('footer').remove();
					}else{
						$('footer').show();
					}
					
					if(CalendarShare.defaultConfig['showTimeZone'] === false){
						$('#timezoneDiv').html('');
					}else{
						$('#timezoneDiv').show();
					}	
					
					CalendarShare.buildAvailableViews();
					
				
					var timezone = jstz.determine();
					var timezoneName = timezone.name();
				
					$.post(OC.generateUrl('apps/'+myAppName+'/publicgetguesstimezone'), {timezone: timezoneName},
						function(data){
							
							if (data.status == 'success' && typeof(data.message) != 'undefined'){
								$('#notification').html(data.message);
								$('#notification').slideDown();
								window.setTimeout(function(){$('#notification').slideUp();}, 5000);
								$('#fullcalendar').fullCalendar('refetchEvents');
								
								
							}
						});
						
					$.post(OC.generateUrl('apps/'+myAppName+'/publicgetdatetimeformat'), 
						{
							dateformat: CalendarShare.calendarConfig['dateformat'],
							timeformat: CalendarShare.calendarConfig['timeformat']
						},
						function(data){
							
						});
						
					if(CalendarShare.defaultConfig['showTimeZone'] === true){
						CalendarShare.buildtimeZoneSelectBox();
						 //$('#timezone').val(timezoneName);
						 //$('#timezone').chosen();
					}
					
					CalendarShare.initCalendar();
					
				}
				
			});
		}else{
			CalendarShare.initCalendar();
		}
		
		
		
	},
	initCalendar:function(){
    	
    	var bWeekends = true;
		if (CalendarShare.calendarConfig['defaultView'] == 'agendaWorkWeek') {
			bWeekends = false;
		}
	   
		var firstHour = new Date().getUTCHours() + 2;
	
		
	
		var monthNames=[
			t(myAppName, 'January'),
			t(myAppName, 'February'),
			t(myAppName, 'March'),
			t(myAppName, 'April'),
			t(myAppName, 'May'),
			t(myAppName, 'June'),
			t(myAppName, 'July'),
			t(myAppName, 'August'),
			t(myAppName, 'September'),
			t(myAppName, 'October'),
			t(myAppName, 'November'),
			t(myAppName, 'December')
		];
		
		var monthNamesShort=[
			t(myAppName, 'Jan.'),
			t(myAppName, 'Feb.'),
			t(myAppName, 'Mar.'),
			t(myAppName, 'Apr.'),
			t(myAppName, 'May.'),
			t(myAppName, 'Jun.'),
			t(myAppName, 'Jul.'),
			t(myAppName, 'Aug.'),
			t(myAppName, 'Sep.'),
			t(myAppName, 'Oct.'),
			t(myAppName, 'Nov.'),
			t(myAppName, 'Dec.')
		];
		
		var dayNames=[
			t(myAppName, 'Sunday'),
			t(myAppName, 'Monday'),
			t(myAppName, 'Tuesday'),
			t(myAppName, 'Wednesday'),
			t(myAppName, 'Thursday'),
			t(myAppName, 'Friday'),
			t(myAppName, 'Saturday')
		];
		
		var dayNamesShort=[
			t(myAppName, 'Sun.'),
			t(myAppName, 'Mon.'),
			t(myAppName, 'Tue.'),
			t(myAppName, 'Wed.'),
			t(myAppName, 'Thu.'),
			t(myAppName, 'Fri.'),
			t(myAppName, 'Sat.')
		];
		
		$('#fullcalendar').fullCalendar({
			header : false,
			firstDay : CalendarShare.calendarConfig['firstDay'],
			editable : false,
			startEditable:false,
			defaultView : CalendarShare.calendarConfig['defaultView'],
			aspectRatio : 1.5,
			weekNumberTitle :  t(myAppName, 'CW '),
			weekNumbers : true,
			weekMode : 'fixed',
			yearColumns: CalendarShare.defaultConfig['yearColumns'],
			firstMonth:CalendarShare.defaultConfig['firstMonth'],
			lastMonth:CalendarShare.defaultConfig['lastMonth'],
			hiddenMonths:CalendarShare.defaultConfig['hiddenMonths'],
			monthClickable:CalendarShare.defaultConfig['monthClickable'],
			firstHour : firstHour,
			weekends : bWeekends,
			timeFormat : {
				agenda : CalendarShare.calendarConfig['agendatime'],
				'' : CalendarShare.calendarConfig['defaulttime']
			},
			columnFormat : {
				month : t(myAppName, 'ddd'), // Mon
				week : t(myAppName, 'ddd M/d'), // Mon 9/7
				agendaThreeDays : t(myAppName, 'dddd M/d'), // Mon 9/7
				day : t(myAppName, 'dddd M/d') // Monday 9/7
			},
			titleFormat : {
				month : t(myAppName, 'MMMM yyyy'),
				// September 2009
				week : t(myAppName, "MMM d[ yyyy]{ '&#8212;'[ MMM] d yyyy}"),
				// Sep 7 - 13 2009
				day : t(myAppName, 'dddd, MMM d, yyyy'),
				// Tuesday, Sep 8, 2009
			},
			axisFormat : CalendarShare.calendarConfig['defaulttime'],
			monthNames : monthNames,
			monthNamesShort : monthNamesShort,
			dayNames : dayNames,
			dayNamesShort : dayNamesShort,
			allDayText : t(myAppName, 'All day'),
			viewRender : CalendarShare.UI.viewRender,
			slotMinutes : 15,
			eventClick : CalendarShare.UI.showEvent,
			eventRender: CalendarShare.UI.renderEvents,
			loading : CalendarShare.UI.loading,
			eventSources : CalendarShare.calendarConfig['eventSources'],
		
		});
		
		
		var heightToSet=0;
		if(CalendarShare.defaultConfig['footer'] === false && CalendarShare.defaultConfig['header'] === false){
			heightToSet+= 60; 
			$('#app-content').css('top','-50px');
			$('#app-navigation').css('top',0);
			$('#app-navigation').height($(window).height());
		}else{
			$('#app-navigation').height($(window).height()-55);
		}
		if(CalendarShare.defaultConfig['footer'] === true){
			heightToSet+=50; 
		}
		if(CalendarShare.defaultConfig['header'] === true){
			heightToSet+=80; 
			
		}
		if(CalendarShare.defaultConfig['footer'] === true && CalendarShare.defaultConfig['header'] === true){
			$('#app-navigation').height($(window).height()-$('#header').height()-5);
		}
		var calcWidth =$(window).width()-$('#app-navigation').width()-20;
		
		$("#fullcalendar").height(($(window).height()-heightToSet));
		$('#app-content').width(calcWidth);
		
		$('#fullcalendar').width(calcWidth -20);
	
		$('#fullcalendar').fullCalendar('option', 'height', $(window).height()-heightToSet);
		$('#app-content').height($(window).height()-heightToSet+50);
		
		var view = $('#fullcalendar').fullCalendar('getView');
		$('#datelabel').html(view.title);
		$('#datepickerNav').hide();
		$('#datelabel').click(function(){
			if (! $('#datepickerNav').is(':visible')) {
				$('#datepickerNav').slideDown();
			}else{
				$('#datepickerNav').slideUp();
			}
		});
		
		CalendarShare.UI.setTimeline();
    },
    buildLeftNavigation:function(){
    
    	$("#datepickerNav").datepicker({
			minDate:null,
			onSelect : function(value, inst) {
				var date = inst.input.datepicker('getDate');

				$('#fullcalendar').fullCalendar('gotoDate', date);

				var view = $('#fullcalendar').fullCalendar('getView');

				if (view.name !== 'month') {
					$("[class*='fc-col']").removeClass('activeDay');
					daySel = CalendarShare.Util.getDayOfWeek(date.getDay());
					$('td.fc-' + daySel).addClass('activeDay');
				}
				
				if (view.name == 'month' || view.name == 'year') {
					$('td.fc-day').removeClass('activeDay');
					prettyDate = $.datepicker.formatDate('yy-mm-dd', date); 
					$('td[data-date=' + prettyDate + ']').addClass('activeDay');
				}

			}
		});
		
    },
     buildAvailableViews:function(){
     	var availabeViews = CalendarShare.defaultConfig['calendarViews'];
     	if(availabeViews !== null && availabeViews !== false){
	     	var views = [];
	     	$.each(availabeViews,function(i,el){
	     		views[i]=$('<button/>')
	     		.attr({
	     			'data-action' : CalendarShare.availableViews[el].action,
	     			'data-view' :  CalendarShare.availableViews[el].view,
	     			'data-weekends' : CalendarShare.availableViews[el].weekend,
	     		})
	     		.html(CalendarShare.availableViews[el].title)
	     		.click(function(){
		     		if(!$(this).hasClass('nomode')){
			     		 if($(this).data('view') === false){
								$('#fullcalendar').fullCalendar($(this).data('action'));
						   }else{
					   	   $('#fullcalendar').fullCalendar('option', 'weekends', $(this).data('weekends'));
						   	   $('#fullcalendar').fullCalendar('changeView',$(this).data('action'));
						   }
					   }
		     	});
	     	});
	     	$('#view').append(views);
	     	
	     	$('.view button.fixed').each(function(i, el) {
						$(el).on('click', function() {
							if ($(this).data('view') === false) {
								$('#fullcalendar').fullCalendar($(this).data('action'));
							} else {
								$('#fullcalendar').fullCalendar('option', 'weekends', $(this).data('weekends'));
								$('#fullcalendar').fullCalendar('changeView', $(this).data('action'));
				
							}
						});
					
				});
	     	
	     	/*
	     	if($('#view button').length === 2){
	     		if($('#view button[data-action="prev"]').length === 1 &&  $('#view button[data-action="next"]').length === 1){
	     			$('#datecontrol_today').remove();
	     			var TodayButton=$('<button />').attr({'id':'datecontrol_today','class':'button'}).text(t(myAppName,'Today'));
	     			$('#view button[data-action="prev"]').after(TodayButton);
	     		}
	     	}*/
	     	
     	}
     	
     },
      buildtimeZoneSelectBox:function(){
      		 
      		 $('#timezone').change( function(){
				var post = $( '#timezone' ).serialize();
				//$.post( OC.generateUrl('apps/'+myAppName+'/calendarsettingssettimezone'), post, function(data){
				 $('#fullcalendar').fullCalendar('refetchEvents');
				//	});
				return false;
			});
		    $('#timezone').chosen();
      },
   Util:{
   	addIconsCal:function(title,src,width){
			return '<div class="eventIcons"><i title="' + title + '"  class="ioc ioc-' + src + '"></i></div>';
		},
	getDayOfWeek : function(iDay) {
			var weekArray = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];
			return weekArray[iDay];
		},
	 rruleToText : function(sRule) {

			if (sRule != '' && sRule != undefined) {
				sTemp = sRule.split(';');
				sTemp2 = [];

				$.each(sTemp, function(i, el) {
					sTemp1 = sTemp[i].split('=');
					sTemp2[sTemp1[0]] = sTemp1[1];
				});
				iInterval = sTemp2['INTERVAL'];

				soFreq = t(myAppName, sTemp2['FREQ']);
				if (iInterval > 1) {
					if (sTemp2['FREQ'] == 'DAILY') {
						soFreq = t(myAppName, 'All') + ' ' + iInterval + ' ' + t(myAppName, 'Days');
					}
					if (sTemp2['FREQ'] == 'WEEKLY') {
						soFreq = t(myAppName, 'All') + ' ' + iInterval + ' ' + t(myAppName, 'Weeks');
					}
					if (sTemp2['FREQ'] == 'MONTHLY') {
						soFreq = t(myAppName, 'All') + ' ' + iInterval + ' ' + t(myAppName, 'Months');
					}
					if (sTemp2['FREQ'] == 'YEARLY') {
						soFreq = t(myAppName, 'All') + ' ' + iInterval + ' ' + t(myAppName, 'Years');
					}
					//tmp=soFreq.toString();
					//tmp.split(" ");

					//soFreq=tmp[0]+' '+iInterval+'. '+tmp[1];
				}

				saveMonth = '';
				if (sTemp2['BYMONTH']) {
					sTempBm = sTemp2['BYMONTH'].split(',');
					iCpBm = sTempBm.length;
					$.each(sTempBm, function(i, el) {
						if (saveMonth == '')
							saveMonth = ' im ' + monthNames[(el - 1)];
						else {
							if (iCpBm != (i + 1)) {
								saveMonth += ', ' + monthNames[(el - 1)];
							} else {
								saveMonth += ' ' + t(myAppName, 'and') + ' ' + monthNames[(el - 1)];
							}
						}
					});
				}
				saveMonthDay = '';
				if (sTemp2['BYMONTHDAY']) {
					sTempBmd = sTemp2['BYMONTHDAY'].split(',');
					iCpBmd = sTempBmd.length;
					$.each(sTempBmd, function(i, el) {
						if (saveMonthDay == '')
							saveMonthDay = ' ' + t(myAppName, 'on') + ' ' + el + '.';
						else {
							if (iCpBmd != (i + 1)) {
								saveMonthDay += ', ' + el + '.';
							} else {
								saveMonthDay += ' ' + t(myAppName, 'and') + ' ' + el + '.';
							}
						}
					});
				}

				saveDay = '';
				if (sTemp2['BYDAY']) {
					sTemp3 = sTemp2['BYDAY'].split(',');
					iCpBd = sTemp3.length;
					$.each(sTemp3, function(i, el) {
						var elLength = el.length;
						if (elLength == 2) {
							if (saveDay == '')
								saveDay = ' ' + t(myAppName, 'on') + ' ' + t(myAppName, el);
							else {
								if (iCpBd != (i + 1)) {
									saveDay += ', ' + t(myAppName, el);
								} else {
									saveDay += ' ' + t(myAppName, 'and') + ' ' + t(myAppName, el);
								}
							}
						}
						if (elLength == 3) {
							var week = el.substring(0, 1);
							var day = el.substring(1, 3);
							if (saveDay == '')
								saveDay = ' ' + t(myAppName, 'on') + ' ' + week + '. ' + t(myAppName, day);
							else
								saveDay += ', ' + t(myAppName, day);
						}
						if (elLength == 4) {
							var week = el.substring(1, 2);
							var day = el.substring(2, 4);
							if (saveDay == '')
								saveDay = ' ' + t(myAppName, 'on') + ' ' + week + '. ' + t(myAppName, day);
							else
								saveDay += ', ' + t(myAppName, day);
						}
					});
				}
				//#rruleoutput
				var returnVal = soFreq + saveMonthDay + saveDay + saveMonth;
				return returnVal;
			} else
				return false;
			//alert(soFreq+saveMonthDay+saveDay+saveMonth);
		},
		reminderToText : function(sReminder) {
			if (sReminder != '') {
				
				var sReminderTxt = '';
				if (sReminder.indexOf('-PT') != -1) {
					//before
					var sTemp = sReminder.split('-PT');
					var sTempTF = sTemp[1].substring((sTemp[1].length - 1));
					if (sTempTF == 'S') {
						sReminderTxt = t(myAppName, 'Seconds before');
					}
					if (sTempTF == 'M') {
						sReminderTxt = t(myAppName, 'Minutes before');
					}
					if (sTempTF == 'H') {
						sReminderTxt = t(myAppName, 'Hours before');
					}
					if (sTempTF == 'D') {
						sReminderTxt = t(myAppName, 'Days before');
					}
					if (sTempTF == 'W') {
						sReminderTxt = t(myAppName, 'Weeks before');
					}
					var sTime = sTemp[1].substring(0, (sTemp[1].length - 1));
					sReminderTxt = sTime + ' ' + sReminderTxt;
					if(sTime == 0){
						sReminderTxt = t(myAppName, 'Just in time');
					}
				} else if (sReminder.indexOf('+PT') != -1) {
					var sTemp = sReminder.split('+PT');
					var sTempTF = sTemp[1].substring((sTemp[1].length - 1));
					if (sTempTF == 'S') {
						sReminderTxt = t(myAppName, 'Seconds after');
					}
					if (sTempTF == 'M') {
						sReminderTxt = t(myAppName, 'Minutes after');
					}
					if (sTempTF == 'H') {
						sReminderTxt = t(myAppName, 'Hours after');
					}
					if (sTempTF == 'D') {
						sReminderTxt = t(myAppName, 'Days after');
					}
					if (sTempTF == 'W') {
						sReminderTxt = t(myAppName, 'Weeks after');
					}
					var sTime = sTemp[1].substring(0, (sTemp[1].length - 1));
					sReminderTxt = sTime + ' ' + sReminderTxt;
					if(sTime == 0){
						sReminderTxt = t(myAppName, 'Just in time');
					}
				} else {
					//onDate
					if (sReminder.indexOf('DATE-TIME') != -1) {
						sReminderTxt = t(myAppName, 'on');
						
						var sTemp = sReminder.split('DATE-TIME:');
						var sDateTime = sTemp[1].split('T');
						var sYear = sDateTime[0].substring(0, 4);
						var sMonth = sDateTime[0].substring(4, 6);
						var sDay = sDateTime[0].substring(6, 8);
					    var sHour='';
					    var sMinute='';
					    var sHM='';
					    
					    if(sDateTime.length > 1){
							 sHour = sDateTime[1].substring(0, 2);
							 sMinute = sDateTime[1].substring(2, 4);
							 sHM =  sHour + ':' + sMinute;
							 if(CalendarShare.calendarConfig['timeformat'] === 'H:i'){
							 	 sHM =  sHour + ':' + sMinute;
							 }else{
							 	var sHM = $.fullCalendar.formatDate(new Date(sYear, sMonth, sDay, sHour, sMinute),'hh:mm tt');
							 }
						}
						if(CalendarShare.calendarConfig['dateformat'] == 'm/d/Y'){
							sReminderTxt = sReminderTxt + ' ' + sMonth + '/' + sDay + '/' + sYear + ' ' +sHM;
						}else{
							sReminderTxt = sReminderTxt + ' ' + sDay + '.' + sMonth + '.' + sYear + ' ' +sHM;
						}
					}else{
						sReminderTxt = t(myAppName, 'Could not read alarm!');
					}
				}

				return sReminderTxt;
			} else
				return false;
		},
   },
   UI:{
   	  loading: function(isLoading){
			if (isLoading){
				$('#loading').show();
			}else{
				
				$('#loading').hide();
				
			}
			
		},
	 timerCheck:null,
	 destroyExisitingPopover : function() {
			if($('.webui-popover').length>0){
				if(CalendarShare.popOverElem !== null){
					CalendarShare.popOverElem.webuiPopover('destroy');
					CalendarShare.popOverElem = null;
					$('#event').remove();
					$('.webui-popover').each(function(i,el){
						var id = $(el).attr('id');
						$('[data-target="'+id+'"]').removeAttr('data-target');
						$(el).remove();
					});
				}
			}
		},
   	  showEvent:function(calEvent, jsEvent, view){
			
			var id = calEvent.id;
			 var choosenDate ='';
			if(typeof calEvent.start!='undefined'){
			   choosenDate = Math.round(calEvent.start.getTime()/1000);
			}
			
			CalendarShare.UI.destroyExisitingPopover();
			
			CalendarShare.popOverElem=$(jsEvent.target);
			var sConstrain = 'horizontal';
				
			if(CalendarShare.calendarConfig['defaultView'] == 'month' || CalendarShare.calendarConfig['defaultView'] == 'year'){
				sConstrain = 'vertical';
			}
			if(CalendarShare.calendarConfig['defaultView'] == 'agendaDay'){
				sConstrain = null;
			}
			
			CalendarShare.popOverElem.webuiPopover({
				url:OC.generateUrl('apps/'+myAppName+'/getshowevent'),
				
				async:{
					type:'POST',
					data:{
						id : id,
						choosendate : choosenDate
					},
					success:function(that,data){
						that.displayContent();
						CalendarShare.UI.startShowEventDialog(CalendarShare.popOverElem,that);
						return false;
					}
				},
				multi:false,
				closeable:false,
				animation:'pop',
				placement:'auto',
				constrains:sConstrain,
				cache:false,
				type:'async',
				trigger:'manual',
				width:400,
				height:50,
			}).webuiPopover('show');
			
		},
		
		startShowEventDialog:function(targetElem,that){
			//CalendarShare.UI.loading(false);
			
			$('#fullcalendar').fullCalendar('unselect');
			
			that.getContentElement().css('height','auto');
			
			$('#closeDialog').on('click', function() {
				CalendarShare.popOverElem.webuiPopover('destroy');
				CalendarShare.popOverElem = null;
			});

			
			$('.tipsy').remove();
		   
			var sReminderReader = '';
			$('input.sReminderRequest').each(function(i, el) {
				sRead = CalendarShare.Util.reminderToText($(this).val());
				if (sReminderReader == ''){
					sReminderReader = sRead;
				}else {
					sReminderReader += '<br />' + sRead;
				}
			});
			$('#reminderoutput').html(sReminderReader);
			

			var sRuleReader=CalendarShare.Util.rruleToText($('#sRuleRequest').val());
             $("#rruleoutput").text(sRuleReader);
             
			$( "#showLocation" ).tooltip({
					items: "img, [data-geo], [title]",
					position: { my: "left+15 center", at: "right center" },
					content: function() {
					var element = $( this );
					if ( element.is( "[data-geo]" ) ) {
					var text = element.text();
					return "<img class='map' alt='" + text +
					"' src='http://maps.google.com/maps/api/staticmap?" +
					"zoom=14&size=350x350&maptype=terrain&sensor=false&center=" +
					text + "'>";
					}
					if ( element.is( "[title]" ) ) {
					return element.attr( "title" );
					}
					if ( element.is( "img" ) ) {
					return element.attr( "alt" );
					}
					}
				});
				
				 that.reCalcPos();
			
		},
		viewRender:function(view,element){
			 $( "#datepickerNav" ).datepicker("setDate", $('#fullcalendar').fullCalendar('getDate'));
				$('#datelabel').html(view.title);
			    if (view.name != CalendarShare.calendarConfig['defaultView']) {
				$.post(OC.generateUrl('apps/'+myAppName+'/changeviewcalendarpublic'), {
					v : view.name
				});
				CalendarShare.calendarConfig['defaultView'] = view.name;
			}
			
			 $('#view button').removeClass('active');
			 $('#view button[data-action='+view.name+']').addClass('active');
			 try {
					CalendarShare.UI.setTimeline();
				} catch(err) {
				}
		},
		renderEvents : function(event, element) {
				
				var EventInner=element.find('.fc-event-inner');

				if (event.isrepeating) {
					EventInner.prepend(CalendarShare.Util.addIconsCal('repeating','repeat','14'));
				}
				if (event.isalarm) {
					EventInner.prepend(CalendarShare.Util.addIconsCal('repeating','clock','14'));
				}
				if (event.privat == 'confidential') {
					EventInner.prepend(CalendarShare.Util.addIconsCal('confidential','eye','12'));
				}
				
				if (event.bday) {
					
					EventInner.prepend(CalendarShare.Util.addIconsCal(t(myAppName, 'Birthday of ')+event.title, 'birthday', '14'));
					
				}

		  
		},
		
		setTimeline:function() {
			var curTime = new Date();
			
			var parentDiv = $(".fc-agenda-slots:visible").parent();
			var timeline = parentDiv.children(".timeline");
			var timelineBall = parentDiv.children(".timeline-ball");
			var timelineText =parentDiv.children(".timeline-text");
			var timeInternational =  $.fullCalendar.formatDate(curTime,CalendarShare.calendarConfig['agendatime']);
			
			if (timeline.length === 0) {//if timeline isn't there, add it
				timeline = $("<hr>").addClass("timeline");
				parentDiv.prepend(timeline);
				timelineBall = $('<div/>').addClass('timeline-ball toolTip').attr('title',timeInternational);
			    parentDiv.prepend(timelineBall);
			    //CalendarPlus.calendarConfig['agendatime']
			    
			    timelineText = $('<div/>').addClass('timeline-text').text(timeInternational);
			    parentDiv.prepend(timelineText);
			}else{
				 timelineBall.attr('title',timeInternational);
				 timelineText.text(timeInternational);
			}
					
			var curCalView = $('#fullcalendar').fullCalendar("getView");
			if (curCalView.visStart < curTime && curCalView.visEnd > curTime) {
				timeline.show();
				timelineBall.show();
				timelineText.show();
			} else {
				timeline.hide();
				timelineBall.hide();
				timelineText.hide();
			}
			if($(".fc-today").length > 0){
				var curSeconds = (curTime.getHours() * 60 * 60) + (curTime.getMinutes() * 60) + curTime.getSeconds();
				var percentOfDay = curSeconds / 86400;
				//24 * 60 * 60 = 86400, # of seconds in a day
				var topLoc = Math.floor(parentDiv.height() * percentOfDay);
	
				timeline.css({'top':topLoc + 'px','left':$(".fc-today").position().left+'px','width':$(".fc-today").width()});
				timelineText.css({'top': (topLoc - 10) + 'px','left':$(".fc-today").position().left+'px'});
				timelineBall.css({'top': (topLoc - 4) + 'px','left':($(".fc-today").position().left-4)+'px'});
			}
		},
   }	
};

if($.fullCalendar !== undefined){
	
	function ListView(element, calendar) {
		var $this = this;
	
		// imports
		jQuery.fullCalendar.views.month.call($this, element, calendar);
		//jQuery.fullCalendar.BasicView.call(t, element, calendar, 'month');
		var opt = $this.opt;
		var trigger = $this.trigger;
		var eventElementHandlers = $this.eventElementHandlers;
		var reportEventElement = $this.reportEventElement;
		var formatDate = calendar.formatDate;
		var formatDates = calendar.formatDates;
		var addDays = $.fullCalendar.addDays;
		var cloneDate = $.fullCalendar.cloneDate;
		//var clearTime =  $.fullCalendar.clearTime;
		var skipHiddenDays = $this.skipHiddenDays;
	
		function clearTime(d) {
			d.setHours(0);
			d.setMinutes(0);
			d.setSeconds(0);
			d.setMilliseconds(0);
			return d;
		}
	
		function skipWeekend(date, inc, excl) {
			inc = inc || 1;
			while (!date.getDay() || (excl && date.getDay() == 1 || !excl && date.getDay() == 6)) {
				addDays(date, inc);
			}
			return date;
		}
	
		// overrides
		$this.name = 'list';
		$this.render = render;
		$this.renderEvents = renderEvents;
		$this.setHeight = setHeight;
		$this.setWidth = setWidth;
		$this.clearEvents = clearEvents;
	
		function setHeight(height, dateChanged) {
		}
	
		function setWidth(width) {
		}
	
		function clearEvents() {
			//this.reportEventClear();
		}
	
		// main
		function sortEvent(a, b) {
			return a.start - b.start;
		}
	
		function render(date, delta) {
			
			var viewDays = moment(date.toISOString(), "YYYY-MM").daysInMonth();
			
			if (delta) {
				addDays(date, delta * viewDays);
			}
	
			var start = cloneDate(date, true);
			viewDays = moment(start.toISOString(), "YYYY-MM").daysInMonth();
			start.setDate(1);
			
			var end = addDays(cloneDate(start), viewDays);
	
			var visStart = cloneDate(start);
			skipHiddenDays(visStart);
	
			var visEnd = cloneDate(end);
			skipHiddenDays(visEnd, -1, true);
	
			$this.title = formatDate(start, opt('titleFormat', 'month'));
			$this.start = start;
			$this.end = end;
			$this.visStart = visStart;
			$this.visEnd = visEnd;
	
		}
	
		function eventsOfThisDay(events, theDate) {
			var start = cloneDate(theDate, true);
			var end = addDays(cloneDate(start), 1);
			var retArr = new Array();
	
			$.each(events, function(i, value) {
				var event_end = $this.eventEnd(events[i]);
				if (events[i].start < end && event_end >= start) {
					retArr.push(events[i]);
				}
			});
			return retArr;
		}
	
		function renderEvent(event) {
			if (event.allDay) {//all day event
				var time = opt('allDayText');
			} else {
	
				var time = formatDates(event.start, event.end, opt('timeFormat', 'agenda'));
			}
			var classes = ['fc-event', 'fc-list-event'];
			classes = classes.concat(event.className);
	
			if (event.source) {
				classes = classes.concat(event.source.className || []);
			}
	
			var bgColor = '#D4D5AA';
			var color = '#000000';
			if ( typeof CalendarShare.calendarConfig['calendarcolors'][event.calendarid] != 'undefined') {
				bgColor = CalendarShare.calendarConfig['calendarcolors'][event.calendarid]['bgcolor'];
				color = CalendarShare.calendarConfig['calendarcolors'][event.calendarid]['color'];
			}
			var imgBday = '';
			if (event.bday) {
				imgBday=CalendarShare.Util.addIconsCal('Happy Birthday', 'birthday-cake', '14');
	
			}
			var imgReminder = '';
			if (event.isalarm) {
			   imgReminder=CalendarShare.Util.addIconsCal(t(myAppName, 'Reminder'), 'clock', '14');
			}
	
			var imgShare = '';
			if (event.shared) {
				 imgShare=CalendarShare.Util.addIconsCal(t('core', 'Shared'), 'share', '14');
			}
	
			var imgPrivate = '';
	
			if (event.privat == 'private') {
				imgPrivate=CalendarShare.Util.addIconsCal(t(myAppName, 'Show As'), 'lock', '14');
			}
			if (event.privat == 'confidential') {
				imgPrivate=CalendarShare.Util.addIconsCal(t(myAppName, 'Show As'), 'eye', '14');
			}
			eventLocation = '';
			if (event.location != '' && event.location != null && typeof event.location != 'undefined') {
	
				eventLocation = '<span class="location">' + event.location + '</span>';
			}
			var imgRepeating = '';
			if (event.isrepeating) {
			    imgRepeating=CalendarShare.Util.addIconsCal(t(myAppName, 'Repeat'), 'repeat', '14');
			}
	
			var Kategorien = '';
			if (event.categories.length > 0) {
	
				Kategorien = '<div style="float:right;margin-top:2px;" class="categories">';
	
				$(event.categories).each(function(i, category) {
					Kategorien += '<a class="catColPrev" style="background-color:#ccc;color:#555;" title="'+category+'">' + category.substring(0, 1) + '</a>';
					
				});
				Kategorien += '</div>';
			}
			var html = '<tr class="fc-list-row">' + '<td>&nbsp;</td>' + '<td class="fc-list-time ">' + time + '</td>' + '<td>&nbsp;</td>' + '<td class="fc-list-event">' + '<span id="list' + event.id + '"' + ' class="' + classes.join(' ') + '"' + '>' + '<span class="colorCal-list" style="margin-top:6px;background-color:' + bgColor + ';">' + '&nbsp;' + '</span>' + '<span class="list-icon">' + imgBday + imgShare + ' ' + imgPrivate + ' ' + imgRepeating + ' ' + imgReminder + '&nbsp;' + '</span>' + '<span class="fc-event-title">' + escapeHTML(event.title) + '</span>' + '<span>' + Kategorien + '</span>' + '<span>' + eventLocation + '</span>' + '</span>' + '</td>' + '</tr>';
	
			return html;
		}
	
		function renderDay(date, events) {
	
			var today = clearTime(new Date());
	
			var addTodayClass = '';
			if (+date == +today) {
				addTodayClass = 'fc-list-today';
	
			}
	
			var dayRows = $('<tr>' + '<td colspan="4" class="fc-list-date ' + addTodayClass + '">' + '&nbsp;<span>' + formatDate(date, opt('titleFormat', 'day')) + '</span>' + '</td>' + '</tr>');
	
			$.each(events, function(i, value) {
	
				var event = events[i];
				var eventElement = $(renderEvent(event));
				triggerRes = trigger('eventRender', event, event, eventElement);
				if (triggerRes === false) {
					eventElement.remove();
				} else {
					if (triggerRes && triggerRes !== true) {
						eventElement.remove();
						eventElement = $(triggerRes);
					}
					$.merge(dayRows, eventElement);
					eventElementHandlers(event, eventElement);
					reportEventElement(event, eventElement);
				}
			});
			return dayRows;
		}
	
		function renderEvents(events, modifiedEventId) {
			events = events.sort(sortEvent);
	
			var table = $('<table class="fc-list-table" align="center"></table>');
			var total = events.length;
			if (total > 0) {
				var date = cloneDate($this.visStart);
				while (date <= $this.visEnd) {
					var dayEvents = eventsOfThisDay(events, date);
					if (dayEvents.length > 0) {
						table.append(renderDay(date, dayEvents));
					}
					date = addDays(date, 1);
				}
			} else {
				table = $('<div>').text('No Events');
	
			}
	
			this.element.html(table);
		}
	
	};
	$.fullCalendar.views.list = ListView;
}

var  liveReminderCheck=function(){
	
	//event.stopPropagation();
	
	
		var url =  OC.generateUrl('/apps/'+myAppName+'/getreminderevents');
		if($('#eventPublic').length==0){
		
		
		
		 if (CalendarShare.UI.timerCheck){
			window.clearInterval(CalendarShare.UI.timerCheck);
		}
		
		 CalendarShare.UI.timerCheck = window.setInterval( function() {
			if(CalendarShare.calendarConfig != null){
				var myRefChecker=CalendarShare.calendarConfig['myRefreshChecker'];
				//alert(myRefChecker);
				$.post(url,{EvSource:myRefChecker},function(jasondata){
					if(jasondata.status == 'success'){
						  // alert(jasondata.refresh);
						  if(jasondata.refresh){
							myRefreshChecker[jasondata.refresh.id]=jasondata.refresh.ctag;
							$('#fullcalendar').fullCalendar('refetchEvents');
							}
							CalendarShare.UI.setTimeline();
					}
				
					//
				});
			}
		}, 60000);
		
	}
	
	//window.clearInterval(myTimer);
};
var resizeTimeout = null;
$(window).resize(_.debounce(function() {
	if (resizeTimeout)
		clearTimeout(resizeTimeout);
	resizeTimeout = setTimeout(function() {
		if($("#fullcalendar").length === 1){
			var heightToSet=0;
			if(CalendarShare.defaultConfig['footer'] === true){
				heightToSet+=50; 
			}
			if(CalendarShare.defaultConfig['header'] === true){
				heightToSet+=80; 
			}
			
			var calcWidth =$(window).width()-$('#app-navigation').width()-20;
			var calcHeight = $(window).height();
			
			$('#app-content').width(calcWidth);
			$('#fullcalendar').width(calcWidth -20);
			$('#app-content').height(calcHeight-50);
			$("#fullcalendar").height((calcHeight-heightToSet));
			
			
		
			$('#fullcalendar').fullCalendar('option', 'height', $(window).height() - heightToSet);
		  CalendarShare.UI.setTimeline();
		   
		}
	
	}, 500);
}));

$(document).ready(function(){
	
	$('#body-public').addClass('appbody-calendar');
	$('#body-public').removeClass('appbody-gallery');
	
	if($('#eventPublic').length>0){
		
		var sRuleReader=CalendarShare.Util.rruleToText($('#sRuleRequestSingle').val());
        $("#rruleoutput").text(sRuleReader);
        
        $("#showLocation").tooltip({
				items : "img, [data-geo], [title]",
				position : {
					my : "left+15 center",
					at : "right center"
				},
				content : function() {
					var element = $(this);
					if (element.is("[data-geo]")) {
						var text = element.text();
						return "<img class='map' alt='" + text + "' src='http://maps.google.com/maps/api/staticmap?" + "zoom=14&size=350x350&maptype=terrain&sensor=false&center=" + text + "'>";
					}
					if (element.is("[title]")) {
						return element.attr("title");
					}
					if (element.is("img")) {
						return element.attr("alt");
					}
				}
			});
        
	}
	
	if($('#eventPublic').length==0){
	
	
	
	CalendarShare.init();
	
	
	$('#datecontrol_today').click(function(){
		$('#fullcalendar').fullCalendar('today');
	});
  
   
   }
});