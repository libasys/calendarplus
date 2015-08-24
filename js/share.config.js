/*Options  

defaultView: month, agendaDay, agendaThreeDays, agendaWorkWeek, agendaWeek, list
firstDay: 0- 6 (0 = Sunday, 1 = Monday,...)
agendatime: timeformat 24 hours HH:mm { - HH:mm} or for timeformat 12 hours  hh:mm tt { - hh:mm tt}
defaulttime:  timeformat 24 hours  HH:mm  or for timeformat 12 hours hh:mm tt
calendarViews: 'prev','agendaDay','agendaThreeDays','agendaWorkWeek','agendaWeek','month','year','list','next' //choosen buttons 
smallCalendarLeft: true / false //shows small calendar on the left side
showTimeZone: true / false //shows the selectbox for timezone selection, if false it shows only the text
header: false //Removes the header
footer: false //removes the footer
//Parameters for yearview
   yearColumns: 2,  // Month per Row
	firstMonth:0, 
	lastMonth:6,
	monthClickable: true/false
	hiddenMonths:[1,2], // disable some month
 * */

CalendarShare.defaultConfig ={};

CalendarShare.defaultConfig[0]={
	'defaultView' : 'month' , 	  
	'agendatime' : 'HH:mm { - HH:mm}',
	'defaulttime' : 'HH:mm',
	'firstDay' : 1,
	'calendarViews': ['agendaDay','agendaWeek','month'],
	'smallCalendarLeft': true,
	'showTimeZone': false,
	'showTodayButton': true,
	'header': true,
	'footer' : true,
	'yearColumns' : 2,
	'monthClickable': true,
	'firstMonth':0,
	'lastMonth':12,
	'hiddenMonths':null
};

// the index is the id of the calendar created in the database for example calendar with ID = 9
/*
CalendarShare.defaultConfig[11]={
	'defaultView' : 'year' , 	  
	'agendatime' : 'HH:mm { - HH:mm}',
	'defaulttime' : 'HH:mm',
	'firstDay' : 1,
	'calendarViews':null,
	'smallCalendarLeft': false,
	'showTimeZone': false,
	'header': false,
	'footer' : false,
	'yearColumns' : 2,
	'monthClickable': false,
	'showTodayButton': false,
	'firstMonth':0,
	'lastMonth':6,
	'hiddenMonths':[2,3]
};*/
