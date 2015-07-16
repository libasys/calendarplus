
<div style="width:100%;float:left;display:block;">
<table width="100%">
		<tr>
			<td>
				<input type="hidden" name="viewstart" id="viewstart" value="" />
				<input type="hidden" name="viewend" id="viewend" value="" />
				<input type="hidden" name="categories" id="categories" value="<?php p($_['categories']); ?>" />
				<input type="hidden" name="calendar" id="hiddenCalSelection" value="<?php p($_['calendar']); ?>">
				
				<input type="text" style="width:100%;width:calc( 100% - 15px );font-size:16px; color:#555;padding:5px;"  placeholder="<?php p($l->t("Title of the Event"));?>" value="<?php p(isset($_['title']) ? $_['title'] : '') ?>" maxlength="100" name="title" id="event-title" autofocus="autofocus"/>
				
				<div id="sCalSelect" class="combobox">
					<div class="selector"><?php p($l->t('Please choose a calendar')); ?></div>
					<ul>
						<?php
						foreach($_['calendar_options'] as $calInfo){
							if($calInfo['permissions'] & OCP\PERMISSION_CREATE) {
								$selected='';
								$addCheckedClass='';
								
								if($_['calendar']==$calInfo['id']) {
									$selected='class="isSelected"';
									$addCheckedClass='isSelectedCheckbox';
								}
								print_unescaped('<li data-id="'.$calInfo['id'].'" data-color="'.$calInfo['calendarcolor'].'" '.$selected.'><span class="colCal '.$addCheckedClass.'" style="background:'.$calInfo['calendarcolor'].'"></span>'.$calInfo['displayname'].'</li>');
							}
						}
						?>
					</ul>
				</div>
			</td>
		</tr>
		<tr>
			<td>
			<input type="text" style="width:100%;width:calc( 100% - 15px );font-size:14px;font-family:Arial, fontello;float:left;"  placeholder="&#xe852; <?php p($l->t("Location of the Event"));?>" value="<?php p(isset($_['location']) ? $_['location'] : '') ?>" maxlength="100"  name="location" />
             <?php  if(isset($_['eventid']) && $_['eventid'] !='new'){
             		 if($_['permissions'] & OCP\PERMISSION_SHARE && $_['isShareApi'] === 'yes') { ?>
		
						<a href="#" class="share action permanent icon-share" style="float:left;margin-top:2px;" 
							data-item-type="<?php p($_['sharetypeevent']) ?>" 
						    data-item="<?php p($_['sharetypeeventprefix'].$_['eventid']) ?>" 
						    data-link="true"
						    data-title="<?php p($_['title']) ?>"
							data-possible-permissions="<?php p( $_['permissions']) ?>"
							title="<?php p($l->t('Share Event')) ?>"
							>
						</a>
						
		<?php } 
             }
             ?>
			</td>
		</tr>
		<tr>
			<td>
				
			<div id="accordion">
				<h3>
					<?php
						if(isset($_['accessclass']) && $_['accessclass'] == 'CONFIDENTIAL'){
							print_unescaped('<i class="ioc ioc-eye" style="font-size:14px;"></i>');
						}
						if(isset($_['accessclass']) && $_['accessclass'] == 'PRIVATE'){
							print_unescaped('<i class="ioc ioc-lock" style="font-size:14px;"></i>');
						}
					?>
					
					<span id="ldatetime" style="color:#555;font-weight:normal;"></span>
					 
					</h3>
				<div>
					<span class="labelLeft"><?php p($l->t("From"));?> </span><input type="text" style="width:85px;" value="<?php p($_['startdate']);?>" name="from" id="from">
				
				<input type="text" style="width:50px;" value="<?php p($_['starttime']);?>" name="fromtime" id="fromtime">
				<br style="clear: both;" />
				<span class="labelLeft"><?php p($l->t("To"));?></span> <input type="text" style="width:85px;" value="<?php p($_['enddate']);?>" name="to" id="to">
				<input type="text" style="width:50px;" value="<?php p($_['endtime']);?>" name="totime" id="totime">
			<br style="clear: both;" />
			<span class="labelLeft"><?php p($l->t("All day"));?></span><input type="checkbox" <?php if($_['allday']) {print_unescaped('checked="checked"');} ?> id="allday_checkbox" name="allday" class="regular-checkbox"><label for="allday_checkbox"></label>
				
				<br style="clear: both;" />
				<span class="labelLeft"><i class="ioc ioc-eye" title="<?php p($l->t("Show As"));?>"></i></span>
				<select  name="accessclass">
					<?php
					if (!isset($_['calendar'])) {$_['calendar'] = false;}
					print_unescaped(OCP\html_select_options($_['access_class_options'], $_['accessclass']));
					?>
				</select>	
				</div>
				<h3>
					<span id="linfoRepeatReminder"><?php p($l->t("Add Reminder, Repeat")) ?></span>
					<span id="lRrule"></span>
					<span id="lReminder" style="float:left;"></span>
					</h3>
				<div>
					<table width="100%"  align="left">
						<tr>
							<td>
								<span class="labelLeft" style="line-height:16px;"><?php p($l->t("Repeat"));?></span>
								<div id="sRepeatSelect" class="combobox" style="margin:0;">
								<div class="comboSelHolder">	
								<div style="float:left;"><i title="<?php p($l->t("Repeat"));?>" style="font-size:18px;margin:0;margin-top:-1px;" class="ioc ioc-repeat"></i></div>	
					
							    <div class="selector">Please select</div>
							    <div class="arrow-down"><i class="ioc ioc-chevron-down"></i></div>
							    </div>
							    <ul>
							    	<?php
						    	  foreach($_['repeat_options'] as $KEY => $VALUE){
						    	  	$selected='';	
									$addCheckedClass='';
						    	  	if($_['repeat']==$KEY){
						    	  		$selected='class="isSelected"';
										$addCheckedClass='isSelectedCheckbox';
									}	
						    	  	print_unescaped('<li data-id="'.$KEY.'"  '.$selected.'><span class="colCal '.$addCheckedClass.'"></span>'.$VALUE.'</li>');
						    	  	 
								}
						    	?>
							    </ul>
						   </div>
						   <input type="hidden" id="repeat" name="repeat" value="<?php p($_['repeat']); ?>">
					     <br style="clear:both;" />
						
				</td>
				</tr>
				<tr  id="rEndRepeatOutput">
				<td style="white-space: normal;line-height:16px;">
					<input type="hidden" name="sRuleRequest" id="sRuleRequest" value="<?php p($_['repeat_rules']); ?>" />
					<span class="labelLeft">&nbsp;</span><div id="rruleoutput" style="line-height:28px;width:100%;">&nbsp;</div>
				</td></tr>

			<tr id="rEndRepeat">
					<td>
						<span class="labelLeft"><?php p($l->t('End')); ?></span>
						<select id="end" name="end" style="float:left;">
							<?php
							if($_['repeat_end'] == '') $_['repeat_end'] = 'never';
							print_unescaped(OCP\html_select_options($_['repeat_end_options'], $_['repeat_end']));
							?>
						</select>
					
						<span id="byoccurrences" style="display:none;">
				     			<input type="number" style="width:30px;padding:2px;" min="1" max="99999" id="until_count" name="byoccurrences" value="<?php p($_['repeat_count']); ?>"> <i title="<?php p($l->t('occurrences')); ?>" class="ioc ioc-calendar-empty"></i>
			          </span>
			          <span id="bydate" style="display:none;">
			          	<input type="text" style="width:80px;" name="bydate" value="<?php p($_['repeat_date']); ?>">
			          </span>
					</td>
				</tr>
					</table>
					<br style="clear:both;" /><br />
						<table width="100%" align="left">
							<tr>
									<td>
										<span class="labelLeft" style="line-height:16px;"><?php p($l->t("Reminder"));?></span>
										<div id="sReminderSelect" class="combobox" style="margin:0;">
										<div class="comboSelHolder">	
										<div style="float:left;"><i title="<?php p($l->t("Reminder"));?>" style="font-size:18px;margin:0;margin-top:-1px;" class="ioc ioc-clock"></i></div>	
									    <div class="selector">Please select</div>
									    <div class="arrow-down"><i class="ioc ioc-chevron-down"></i></div>
									    </div>
									    <ul>
									    	<?php
								    	  foreach($_['reminder_options'] as $KEY => $VALUE){
								    	  	$selected='';	
											$addCheckedClass='';
								    	  	if($_['reminder']==$KEY){
								    	  		$selected='class="isSelected"';
												$addCheckedClass='isSelectedCheckbox';
											}	
								    	  	print_unescaped('<li data-id="'.$KEY.'"  '.$selected.'><span class="colCal '.$addCheckedClass.'"></span>'.$VALUE.'</li>');
								    	  	 
										}
								    	?>
									    </ul>
								   </div>
								   	<input type="hidden" id="reminder" name="reminder" value="<?php p($_['reminder']); ?>">

								   <br style="clear:both;" />
									</td>
								</tr>
								<tr id="reminderTrOutput">
									<td style="white-space: normal;" style="line-height: 28px;">
										<input type="hidden" name="sReminderRequest" id="sReminderRequest" value="<?php p($_['reminder_rules']); ?>" />
										<span class="labelLeft">&nbsp;</span><div id="reminderoutput" style="line-height: 28px;width:100%;">&nbsp;</div>
									</td></tr>	
								
						</table>
				</div>
				<h3><?php p($l->t("Notice"));?><span class="lnotice ioc ioc-checkmark"></span>, <?php p($l->t("Tags"));?><span class="ltag ioc ioc-checkmark"></span> <?php p($l->t("or"));?> <?php p($l->t("URL"));?><span class="lurl ioc ioc-checkmark"></span> <?php p($l->t("Add"));?></h3>
				<div>
					<input type="text" style="font-family:Arial, fontello;width:100%;font-size:14px;" size="200" placeholder="&#xe84f; <?php p($l->t("URL"));?>" value="<?php p(isset($_['link']) ? $_['link'] : '') ?>" maxlength="200"  name="link" />
					<br />
					<textarea style="width:100%;height: 30px;font-family:Arial, fontello;font-size:14px;"  placeholder="&#xe845; <?php p($l->t("Description of the Event"));?>" name="description"><?php p(isset($_['description']) ? $_['description'] : '') ?></textarea>
					<br />
					<ul id="tagmanager" style="width:100%;line-height:20px;margin-top:6px;margin-bottom:5px;"></ul>
				</div>
			</div>		
			</td>
		</tr>
	</table>
	
</div>


<div id="showOwnDev">	
			<input type="hidden" name="logicCheckWD" id="logicCheckWD" value="<?php p($_['logicCheckWeekDay']); ?>" />
				H&auml;ufigkeit: 
				<select name="rAdvanced" id="rAdvanced">
					<?php
					print_unescaped(OCP\html_select_options($_['repeat_advancedoptions'], $_['advancedrepeat']));
					?>
				</select>
				
				<br />
				<label id="sInterval"><?php p($l->t('Interval')); ?></label>
						<input style="width:30px;padding:2px;" id="rInterval" type="number" min="1" size="4" max="1000" value="<?php p(isset($_['repeat_interval']) ? $_['repeat_interval'] : '1'); ?>" name="interval">
					<label id="sInterval1">&nbsp;</label>
				<br />
			
			<span id="advanced_bymonthday" class="advancedRepeat">
				
				 <input type="radio" name="radioMonth" value="every" <?php print_unescaped($_['rRadio0']); ?> class="regular-radio" id="radio-1-1" /><label  for="radio-1-1" style="float:left;margin-right:4px;"></label> <?php p($l->t("Every"));?> <br />
				 <ul id="rBymonthday" data-val="BYMONTHDAY" <?php print_unescaped($_['rClass0']); ?>>
				     <?php
				     
				       for($i=1; $i<32; $i++){
				       	$addSel='';	
				       	if(array_key_exists($i,$_['repeat_bymonthday']) && $i==$_['repeat_bymonthday'][$i]) {
				       		$addSel='class="ui-selected"';
						}	
				       	print_unescaped('<li data-val="'.$i.'" '.$addSel.'>'.$i.'</li>');
				       }
				     ?>
				     </ul>
				     <br style="clear:both;" />	
			</span>
			
			<span id="advanced_bymonth" class="advancedRepeat">
				
				<ul id="rBymonth" data-val="BYMONTH" style="padding-top:10px;">
				     <?php
				       foreach($_['repeat_monthshort_options'] as $key => $value){
				       	$addSel='';	
				       	if(array_key_exists($key,$_['repeat_bymonth']) && $key==$_['repeat_bymonth'][$key]) {
				       		$addSel='class="ui-selected"';
						}	
				       	print_unescaped('<li data-val="'.$key.'" '.$addSel.'>'.$value.'</li>');
				       }
				     ?>
				     </ul>
				<br style="clear:both;" />
			</span>
			
			<span id="advanced_weekofmonth" class="advancedRepeat">
				
				<span style="line-height:20px;display:block; float:left; padding-top:10px;">
					<div id="checkBoxVisible" style="float:left;">
				  <input type="checkbox" name="checkMonth"  value="onweekday" <?php print_unescaped($_['checkedMonth']); ?> id="check-1-1" class="regular-checkbox" /><label style="margin-top:4px;margin-right:4px;" for="check-1-1"></label> 
				  </div>
				  <div id="radioVisible" style="float:left;">
				  <input type="radio" name="radioMonth" id="radio-1-2" value="onweekday" <?php print_unescaped($_['rRadio1']) ?> class="regular-radio" /><label for="radio-1-2" style="margin-top:4px;margin-right:4px;"></label> 
				  </div>
				  <?php p($l->t("On"));?> 
				  
				     <select id="weekofmonthoptions" name="weekofmonthoptions">
							<?php
							print_unescaped(OCP\html_select_options($_['repeat_weekofmonth_options'], $_['repeat_weekofmonth']));
							?>
						</select>
						</span>
						<br style="clear:both;" />
			</span>
			<span id="advanced_weekdayWeek" class="advancedRepeat">
				
				     <ul id="rByweekdayWeek" data-val="BYDAY">
				     <?php
				      
				       foreach($_['repeat_weeklyshort_options'] as $key => $value){
				       	$addSel='';	
				       	if(array_key_exists($key,$_['repeat_weekdays']) && $key==$_['repeat_weekdays'][$key]) {
				       		$addSel='class="ui-selected"';
						}	
				       	print_unescaped('<li data-val="'.$key.'" '.$addSel.'>'.$value.'</li>');
				       }
				     ?>
				     </ul>
				     
				     <br style="clear:both;" /><br />
			</span>
			<span id="advanced_weekday" class="advancedRepeat">
				
				     <ul id="rByweekday" data-val="BYDAY" <?php print_unescaped($_['rClass1']); ?>>
				     <?php
				     
				       foreach($_['repeat_weeklyshort_options'] as $key => $value){
				       	$addSel='';	
				       	if(array_key_exists($key,$_['repeat_weekdaysSingle']) && $key==$_['repeat_weekdaysSingle'][$key]) {
				       		$addSel='class="ui-selected"';
						}	
				       	print_unescaped('<li data-val="'.$key.'" '.$addSel.'>'.$value.'</li>');
				       }
				     ?>
				     </ul>
				     <br style="clear:both;" /><br />
			</span>
			<span id="advanced_weekdayYear" class="advancedRepeat">
				
				     <ul id="rByweekdayYear" data-val="BYDAY" <?php print_unescaped($_['bdayClass']); ?>>
				     <?php
				      
				       foreach($_['repeat_weeklyshort_options'] as $key => $value){
				       	$addSel='';	
				       	if(array_key_exists($key,$_['repeat_weekdaysSingle']) && $key==$_['repeat_weekdaysSingle'][$key]) {
				       		$addSel='class="ui-selected"';
						}	
				       	print_unescaped('<li data-val="'.$key.'" '.$addSel.'>'.$value.'</li>');
				       }
				     ?>
				     </ul>
				     <br style="clear:both;" /><br />
			</span>
			
			<span style="width:100%;border-top:1px solid #bbb;display:block;padding-top:4px;">
				<div class="button-group" style="float:right;">
				<button id="rCancel" class="button"><?php p($l->t("Cancel"));?></button> 
				<button id="rOk" style="font-weight:bold;color:#0098E4; min-width:60px;"  class="button"><?php p($l->t("OK"));?></button>
				</div>
			</span>	
		</div>		
	
	<div id="showOwnReminderDev">	
		 <select name="reminderAdvanced" id="reminderAdvanced">
					<?php
					print_unescaped(OCP\html_select_options($_['reminder_advanced_options'], $_['reminder_advanced']));
					?>
			</select><br />
		 	<span id="reminderTable" class="advancedReminder">
					   <input type="number" style="width:30px;padding:2px;float:left;" min="1" max="365" maxlength="3" name="remindertimeinput" id="remindertimeinput" value="<?php p($_['remindertimeinput']); ?>" />
						<select id="remindertimeselect" name="remindertimeselect">
							<?php
							print_unescaped(OCP\html_select_options($_['reminder_time_options'], $_['remindertimeselect']));
							?>
						</select>
					</span>
					<span id="reminderdateTable" class="advancedReminder">
						<?php p($l->t("Date"));?> <input type="text" style="width:85px;" value="<?php p($_['reminderdate']);?>" name="reminderdate" id="reminderdate">
						&nbsp;
						<input type="text" style="padding:2px; width:40px;" value="<?php p($_['remindertime']);?>" name="remindertime" id="remindertime">
					</span>
						<span id="reminderemailinputTable" class="advancedReminder">
							<?php p($l->t("Email"));?> <input type="text" style="width:150px;" name="reminderemailinput" id="reminderemailinput" value="<?php p($_['reminderemailinput']); ?>" />
						</span><br />
					<span style="width:100%;border-top:1px solid #bbb;display:block;padding-top:4px;">
				<div class="button-group" style="float:right;">
				<button id="remCancel" class="button"><?php p($l->t("Cancel"));?></button> 
				<button id="remOk" style="font-weight:bold;color:#0098E4; min-width:60px;"  class="button"><?php p($l->t("OK"));?></button>
				</div>
			</span>		
		</div>