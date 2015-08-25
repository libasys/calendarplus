<input type="hidden" name="mailNotificationEnabled" id="mailNotificationEnabled" value="<?php p($_['mailNotificationEnabled']) ?>" />
<input type="hidden" name="allowShareWithLink" id="allowShareWithLink" value="<?php p($_['allowShareWithLink']) ?>" />
<input type="hidden" name="mailPublicNotificationEnabled"  value="<?php p($_['mailPublicNotificationEnabled']) ?>" />
<div id="searchresults" class="hidden" data-appfilter="calendarplus"></div>
<div id="notification" style="display:none;"></div>

<div id="app-navigation"  <?php print_unescaped($_['isHiddenCal']); ?>>


</div>
<div id="app-content">
	    <div id="dayMore" style="display:none;">
	    	<div id="showDayOfMonth"></div>
	    	<div id="datepickerDayMore"></div>
	    	<div id="DayListMore"></div>
	    	</div>
		<div id="fullcalendar" class="PrintArea"><div id="loading" class="icon-loading"></div></div>
	
		<div id="rightCalendarNav" <?php print_unescaped($_['isHidden']); ?>>
			<?php if($_['rightnavAktiv']==='true') {?>
			
			<?php print_unescaped($_['taskOutput']); ?>
			<?php } ?>
		</div>
		
</div>	
<div id="overlay" class="overlay"></div>
<div id="dialog_message" style="width:0;height:0;top:0;left:0;display:none;"></div>	
<div id="dialogSmall" style="width:0;height:0;top:0;left:0;display:none;"></div>
<div id="dialog_holder" style="width:0;height:5px;top:0;left:0;display:none;position:absolute;"></div>
<div id="appsettings" class="popup topright hidden"></div>