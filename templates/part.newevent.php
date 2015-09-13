<div id="event">
	<div style="text-align: center;color: #FF1D1D;" id="errorbox"></div>
	
	<form id="event_form">
	
<?php print_unescaped($this->inc("part.eventform")); ?>
</form>
	<br style="clear: both;" />
	<div id="actions" style="float:left;width:100%;padding-top:10px;padding-bottom:5px;">
		
	<div  class="button-group" style="float:right;">
		<button id="closeDialog" class="button"><?php p($l->t("Cancel"));?></button> 
		<button  class="button primary-button" id="submitNewEvent" data-link="<?php print_unescaped(\OC::$server->getURLGenerator()->linkToRoute($_['appname'].'.event.newEvent')); ?>" style="min-width:60px;"><?php p($l->t("OK"));?></button> 	
	</div>
	</div>
	
</div>
