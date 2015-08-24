<input type="hidden" name="mailNotificationEnabled" id="mailNotificationEnabled" value="<?php p($_['mailNotificationEnabled']) ?>" />
<input type="hidden" name="allowShareWithLink" id="allowShareWithLink" value="<?php p($_['allowShareWithLink']) ?>" />
<input type="hidden" name="mailPublicNotificationEnabled"  value="<?php p($_['mailPublicNotificationEnabled']) ?>" />
<div id="searchresults" class="hidden" data-appfilter="calendarplus"></div>
<div id="loading">
<i style="margin-top:20%;" class="ioc-spinner ioc-spin"></i>
</div>
<div id="notification" style="display:none;"></div>
<!--
<div id="controls">
	<div class="leftControls">
	<div class="button-group" id="first-group">	
		<button id="calendarnavActive" class="toolTip button <?php p($_['buttonCalAktive']) ?>" title="<?php p($l->t('Show / hide left sidebar'));?>"><i class="ioc ioc-calendar"></i></button>	

	
	<button class="button"  id="printCal"><?php p($l->t('Print'));?></button>
	</div>
	
	</div>
	<div class="centerControls">
		
		
  
	</div>
	<div class="rightControls">
		<div class="button-group" style="float:right;right:5px;">	
			<?php 	if(\OC::$server->getAppManager()->isEnabledForUser('tasksplus')){?>
				<button id="tasknavActive" class="toolTip button <?php p($_['buttonTaskAktive']) ?>" title="<?php p($l->t('Show / hide tasksbar'));?>"><i class="ioc ioc-tasks"></i></button>	
			<?php } ?>
		</div>
	</div>	

</div>
 -->
<div id="app-navigation"  <?php print_unescaped($_['isHiddenCal']); ?>>


</div>
<div id="app-content">
	    <div id="dayMore" style="display:none;">
	    	<div id="showDayOfMonth"></div>
	    	<div id="datepickerDayMore"></div>
	    	<div id="DayListMore"></div>
	    	</div>
		<div id="fullcalendar" class="PrintArea"></div>
	
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