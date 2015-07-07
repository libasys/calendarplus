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
//SEARCH
OC.search.resultTypes['calendar']=t('calendarplus','Cal.');
OC.search.resultTypes['tasks']=t('aufgaben','Tasks');
OC.search.resultTypes['contacts']=t('kontakte','Contacts');

(function($){

  $.extend({
    playSound: function(){
    
      return $('<audio autoplay="autoplay"><source src="'+arguments[0]+'.mp3" type="audio/mpeg"><source src="'+arguments[0]+'.ogg" type="audio/ogg"></audio>').prependTo('#reminderBox');
    }
  });

})(jQuery);

$(document).ready(function(){
	if($('#reminderBox').length === 0){
		$('<div id="reminderBox" style="width:0;height:0;top:0;left:0;z-index:300;display:none;">').appendTo($('body')[0]);
	}
	liveReminderCheck();
	
});

var timerRefresher=null;

/**
 * Calls the server periodically every 1 min to check live calendar events
 * 
 */
function liveReminderCheck(){
	
		var url = OC.generateUrl('/apps/calendarplus/getreminderevents');
		var myRefChecker='';
		 if (timerRefresher){
			window.clearInterval(timerRefresher);
		}
		
		var timerRefresher = window.setInterval(function(){
			
			if($('#fullcalendar').length === 1 && CalendarPlus.calendarConfig != null){
			//calId = ctag
				myRefChecker=CalendarPlus.calendarConfig['myRefreshChecker'];
			}
			
			$.post(url,{EvSource:myRefChecker},function(jasondata){
					
					if($('#fullcalendar').length==1){
					  if(jasondata.refresh !== 'onlyTimeLine'){
						
							CalendarPlus.calendarConfig['myRefreshChecker'][jasondata.refresh.id]=jasondata.refresh.ctag;
							if(CalendarPlus.UI.timerLock == false) {
								$('#fullcalendar').fullCalendar('refetchEvents');
							}
							if(CalendarPlus.UI.timerLock == true) {
								CalendarPlus.UI.timerLock=false;
							}
							CalendarPlus.Util.setTimeline();
						}
						if(jasondata.refresh=='onlyTimeLine'){
							CalendarPlus.Util.setTimeline();
							//alert(jasondata.refresh);
						}
					}
					//
					if(jasondata.data!=''){
						openReminderDialog(jasondata.data);
					} 
				
				//
			});
			
		
			
		}, 60000);
		
		
	
}

var openReminderDialog=function(data){
			//var output='<audio autoplay="autoplay"><source src="'+OC.filePath('calendar','audio', 'ring.ogg')+'"></source><source src="'+OC.filePath('calendar','audio','ring.mp3')+'"></source></audio>';
			
			
			 var output='';
			 $.each(data, function(i, elem) {
				  output+='<b>'+elem.startdate+'</b><br />';
				  output+='<i class="ioc ioc-'+elem.icon+'"></i> <a href="'+elem.link+'">'+elem.summary+'</a><br />';
				
				});
			$( "#reminderBox" ).html(output);	
			 $.playSound(oc_webroot+'/apps/calendarplus/audio/ring');
			$( "#reminderBox" ).dialog({
			resizable: false,
			title : t('calendarplus', 'Reminder Alert'),
			width:350,
			height:200,
			modal: true,
			buttons: 
			[  { text:t('calendarplus', 'Ready'), click: function() {
			    	$( "#reminderBox" ).html('');	
			    	$( this ).dialog( "close" );
			    }
			    } 
			],
	
		});
  	 
		return false;

			
};