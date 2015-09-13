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
		 if (timerRefresher !== null){
			window.clearInterval(timerRefresher);
		}
		
		var timerRefresher = window.setInterval(function(){
			
			if($('#fullcalendar').length === 1 && CalendarPlus.calendarConfig != null){
			//calId = ctag
				myRefChecker=CalendarPlus.calendarConfig['myRefreshChecker'];
				//CalendarPlus.UI.loading(true);
				//shows all 5 minutes
				if(CalendarPlus.UI.timerRun === 5 ){
					$.each(CalendarPlus.calendarConfig['mycalendars'], function(i, elem) {
						if(elem.issubscribe === 1 && elem.uri !== 'bdaycpltocal_'+oc_current_user){
							
								CalendarPlus.UI.Calendar.autoRefreshCalendar(elem.id);
								
						}
					});
					CalendarPlus.UI.timerRun = 0;
					if(CalendarPlus.UI.isRefresh === true) {
						$('#fullcalendar').fullCalendar('refetchEvents');
						CalendarPlus.UI.isRefresh = false;
					}
					
				}
				
				
			}
			
			$.post(url,{EvSource:myRefChecker},function(jasondata){
					
					if($('#fullcalendar').length === 1){
					  if(jasondata.refresh !== 'onlyTimeLine'){
						
							//CalendarPlus.calendarConfig['myRefreshChecker'][jasondata.refresh.id]=jasondata.refresh.ctag;
							
							
						}
						
						
					}
					//
					if(jasondata.data != ''){
						
						openReminderDialog(jasondata.data);
					} 
					
				//
			});
			
			if($('#fullcalendar').length === 1){
				CalendarPlus.Util.setTimeline();
				CalendarPlus.UI.timerRun ++;
			}
			
			
		}, 60000);
		
		
	
}

var openReminderDialog=function(data){
			//var output='<audio autoplay="autoplay"><source src="'+OC.filePath('calendar','audio', 'ring.ogg')+'"></source><source src="'+OC.filePath('calendar','audio','ring.mp3')+'"></source></audio>';
			$('body').append('<div id="reminderBox"></div>');
			
			 var output='';
			 $.each(data, function(i, elem) {
				  output+='<b>'+elem.startdate+'</b><br />';
				  output+='<i class="ioc ioc-'+elem.icon+'"></i> <a href="'+elem.link+'">'+elem.summary+'</a><br />';
				
				});
				 $.playSound(oc_webroot+'/apps/calendarplus/audio/ring');
				$('#reminderBox').html(output).ocdialog({
					modal: true,
					closeOnEscape: true,
					title : t('calendarplus', 'Reminder Alert'),
					height: 'auto', width: 'auto',
					buttons: 
					[{ 
						text:t('calendarplus', 'Ok'), click: function() {
					    	$(this).ocdialog("close");
					   },
					   defaultButton: true
					}],
					close: function(/*event, ui*/) {
					$(this).ocdialog('destroy').remove();
					$('#reminderBox').remove();
					
					},
				});
			
  	 
		return false;

			
};