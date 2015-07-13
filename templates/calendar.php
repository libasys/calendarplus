<input type="hidden" name="mailNotificationEnabled" id="mailNotificationEnabled" value="<?php p($_['mailNotificationEnabled']) ?>" />
<input type="hidden" name="allowShareWithLink" id="allowShareWithLink" value="<?php p($_['allowShareWithLink']) ?>" />
<input type="hidden" name="mailPublicNotificationEnabled"  value="<?php p($_['mailPublicNotificationEnabled']) ?>" />
<div id="searchresults" class="hidden" data-appfilter="calendarplus"></div>
<div id="loading">
<i style="margin-top:20%;" class="ioc-spinner ioc-spin"></i>
</div>
<div id="notification" style="display:none;"></div>
<div id="controls">
	<div class="leftControls">
	<div class="button-group" id="first-group">	
		<button id="calendarnavActive" class="toolTip button <?php p($_['buttonCalAktive']) ?>" title="<?php p($l->t('Show / hide left sidebar'));?>"><i class="ioc ioc-calendar"></i></button>	

	<button class="button"  id="datecontrol_today"><?php p($l->t('Today'));?></button>
	<!--<button class="button"  id="printCal"><?php p($l->t('Print'));?></button> -->
	</div>
	
	</div>
	<div class="centerControls">
		
		<div class="view button-group" style="float:none;">
		<button class="button" data-action="prev" data-view="false" data-weekends="false"><i class="ioc ioc-angle-left"></i></button>		
		<button class="button viewaction" data-action="agendaDay" data-view="true" data-weekends="true"><?php p($l->t('Day'));?></button>
		<button class="button viewaction" data-action="agendaThreeDays" data-view="true" data-weekends="true"><?php p($l->t('3-Days'));?></button>	
		<button class="button viewaction" data-action="agendaWorkWeek" data-view="true" data-weekends="false"><?php p($l->t('W-Week'));?></button>			
		<button class="button viewaction" data-action="agendaWeek" data-view="true" data-weekends="true"><?php p($l->t('Week'));?></button>
	  <button class="button viewaction" data-action="month" data-view="true" data-weekends="true"><?php p($l->t('Month'));?></button>
	   	  <button class="button viewaction" data-action="year" data-view="true" data-weekends="true"><?php p($l->t('Year'));?></button>

	   <button class="button viewaction" data-action="list" data-view="true" data-weekends="true"><i class="ioc ioc-th-list" title="<?php p($l->t('List'));?>"></i></button>
	  <button class="button"  data-action="next" data-view="false" data-weekends="false"><i class="ioc ioc-angle-right"></i></button>	
		
	  </div>
  
	</div>
	<div class="rightControls">
		<div class="button-group" style="float:right;right:5px;">	
			<button id="editCategoriesList" class="button" title="<?php p($l->t('Edit categories')); ?>"><i class="ioc ioc-tags"></i></button>
			<?php 	if(\OC::$server->getAppManager()->isEnabledForUser('tasksplus')){?>
				<button id="tasknavActive" class="toolTip button <?php p($_['buttonTaskAktive']) ?>" title="<?php p($l->t('Show / hide tasksbar'));?>"><i class="ioc ioc-tasks"></i></button>	
			<?php } ?>
			<button id="choosecalendarGeneralsettings" class="button" title="<?php p($l->t('Settings')); ?>"><i class="ioc ioc-cog"></i></button>
		</div>
	</div>	

</div>

<div id="app-navigation"  <?php print_unescaped($_['isHiddenCal']); ?>>
<div id="leftcontent">

	 
</div>

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