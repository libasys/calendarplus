<div class="datenavigation">
	<div id="datelabel"></div>
	<div class="view button-group">
	<button class="button" data-action="prev" data-view="false" data-weekends="false"><i class="ioc ioc-angle-left"></i></button>		
	<button class="button"  data-action="next" data-view="false" data-weekends="false"><i class="ioc ioc-angle-right"></i></button>	
</div>
</div>

<div style="clear:both;" id="datepickerNav"></div> 
<div class="view button-group" style="float:none;">
		<button class="button mode" data-action="agendaDay" data-view="true" data-weekends="true"><?php p($l->t('Day'));?></button>
		<button class="button mode" data-action="agendaWeek" data-view="true" data-weekends="true"><?php p($l->t('Week'));?></button>
	  	<button class="button mode" data-action="month" data-view="true" data-weekends="true"><?php p($l->t('Month'));?></button>
	   	 <button class="button nomode"  id="datecontrol_today"><?php p($l->t('Today'));?></button>
</div>
<div class="view button-group" style="float:none;">
		<button class="button mode" data-action="agendaThreeDays" data-view="true" data-weekends="true"><?php p($l->t('3-Days'));?></button>	
		<button class="button mode" data-action="agendaWorkWeek" data-view="true" data-weekends="false"><?php p($l->t('W-Week'));?></button>			
	   	<button class="button mode" data-action="year" data-view="true" data-weekends="true"><?php p($l->t('Year'));?></button>

	   <button class="button mode" data-action="list" data-view="true" data-weekends="true"><i class="ioc ioc-th-list" title="<?php p($l->t('List'));?>"></i></button>

</div>
<h3><i class="ioc ioc-calendar"></i>&nbsp;<?php p($l->t('Calendar')); ?><i id="importCal" title="<?php p($l->t('Import calendar per Drag & Drop')); ?>" class="toolTip icon-upload"></i><i id="addCal" title="<?php p($l->t('New Calendar')) ?>" class="toolTip icon-add"></i></h3>
<div id="drop-area"><?php p($l->t('Import calendar per Drag & Drop')); ?></div>						
<ul id="calendarList">
	<?php 
	foreach($_['calendars']['cal'] as $calInfo){
			  
		  $isActiveUserCal='';
		  $addCheckClass='';
		 
		 if($_['activeCal'] == $calInfo['id']){
		 	$isActiveUserCal='isActiveCal';
			 $addCheckClass='isActiveUserCal';
		 }
		
		$shareLink='';
		  if($calInfo['bShare'] === true) { 
			  $shareLink='<a href="#" class="share icon-share" 
			  	data-item-type="'.$_['shareType'].'" 
			    data-item="'.$_['shareTypePrefix'].$calInfo['id'].'" 
			    data-link="true"
			    data-title="'.$calInfo['displayname'].'"
				data-possible-permissions="'.$calInfo['permissions'].'"
				title="'.(string) $this->l10n->t('Share Calendar').'"
				style="position:absolute;right:30px;"
				>
				</a>';
		  }
		  
		$notice = '';	
		if($calInfo['bShare'] === false) {
				
			if($calInfo['shareInfoLink'] === true) {
				$notice='<b>Notice</b><br>This calendar is also shared by Link for public!<br>';
			}
			$shareLink = '<span style="position:absolute;right:30px;"><i class="toolTip ioc ioc-info" title="'.$notice.(string) $this->l10n->t('by') . ' ' .$calInfo['userid'].'<br />('.$calInfo['shareInfo'].')"></i></span>';
			
		}
		
		$actionCalender = '';
		if($calInfo['bAction'] === true){
			$actionCalender = '<li class="icon-rename"></li><li class="icon-delete"></li>';
		}
		
		 $checked = ($calInfo['isActive'] === 1) ? ' checked="checked"' : '';
		
		$checkBox = '
						<input class="activeCalendarNav regular-checkbox" data-id="'.$calInfo['id'].'" style="float:left;" id="edit_active_'.$calInfo['id'].'" type="checkbox" '.$checked.' />
						<label style="float:left;margin-left:8px;" class="toolTip" title="'.$this->l10n->t('show / hide calendar').'" for="edit_active_'.$calInfo['id'].'">
						</label>
						';
		
		$downloadLink ='<a class="icon-download" href="'.$calInfo['download'].'" title="'.$l->t('Download').'"></a>';
				 	
		$addMenu='<span class="app-navigation-entry-utils-menu-button"><button></button>
						  <span class="app-navigation-entry-menu" data-calendarid="'.$calInfo['id'].'">
							  <ul>
							  <li>'.$checkBox.'</li>
							  <li><i class="ioc ioc-globe"></i></li>
							  <li>'.$downloadLink.'</li>
							  '.$actionCalender.'
							  </ul>
						  </span>
					  </span>';
						  	
		$displayName='<span class="descr toolTip"  title="'.$calInfo['displayname'].'">'.$calInfo['displayname'].'</span>';
		$addKursiv = ($checked === '') ? ' kursiv' : '';
		 			
		print_unescaped('<li data-id="'.$calInfo['id'].'" class="calListen '.$isActiveUserCal.$addKursiv.'"><div class="colCal toolTip iCalendar '.$addCheckClass.'" title="'.$this->l10n->t('choose calendar as default').'" style="cursor:pointer;background:'.$calInfo['calendarcolor'].'">&nbsp;</div> '.$displayName.$shareLink.$addMenu.'</li>');
	}
	
	?>
<!--our clone for editing or creating -->	
<li class="app-navigation-entry-edit calclone" id="calendar-clone" data-calendar="">
	<input id="bgcolor" name="bgcolor" type="hidden" value="#333399" />
	<input type="text" name="displayname" value="" placeholder="<?php p($l->t('Displayname')) ?>" />
	<input type="text" name="caldavuri" readonly="readonly" value="" />
	<button class="icon-checkmark"></button>
</li>	
</ul>	
<h3><i class="ioc ioc-rss-alt"></i>&nbsp;<?php p($l->t('Subscription')); ?><i id="addSub" title="<?php p($l->t('New Subscription')) ?>" class="toolTip icon-add"></i></h3>
<ul id="abo">
	<?php 
	foreach($_['calendars']['abo'] as $calInfo){
		
		$shareLink='';
		
		  if($calInfo['bShare'] === true) { 
			  $shareLink='<a href="#" class="share icon-share" 
			  	data-item-type="'.$_['shareType'].'" 
			    data-item="'.$_['shareTypePrefix'].$calInfo['id'].'" 
			    data-link="true"
			    data-title="'.$calInfo['displayname'].'"
				data-possible-permissions="'.$calInfo['permissions'].'"
				title="'.(string) $this->l10n->t('Share Calendar').'"
				style="position:absolute;right:30px;"
				>
				</a>';
		  }
		  
		$notice = '';	
		if($calInfo['bShare'] === false) {
			
			if($calInfo['shareInfoLink'] === true) {
				$notice='<b>Notice</b><br>This calendar is also shared by Link for public!<br>';
			}
			$shareLink = '<span style="position:absolute;right:30px;"><i class="toolTip ioc ioc-info" title="'.$notice.(string) $this->l10n->t('by') . ' ' .$calInfo['userid'].'<br />('.$calInfo['shareInfo'].')"></i></span>';
			
		}
		
		 $checked = ($calInfo['isActive'] === 1) ? ' checked="checked"' : '';
		
		$checkBox = '
						<input class="activeCalendarNav regular-checkbox" data-id="'.$calInfo['id'].'" style="float:left;" id="edit_active_'.$calInfo['id'].'" type="checkbox" '.$checked.' />
						<label style="float:left;margin-left:8px;" class="toolTip" title="'.$this->l10n->t('show / hide calendar').'" for="edit_active_'.$calInfo['id'].'">
						</label>
						';
						
			//Action Menu Subscriptions			
			$refreshAction =	'';		
			if($calInfo['bRefresh'] === true){
		   		$refreshAction='<li><i title="'.$l->t('refresh subscribed calendar').'"  class="toolTip refreshSubscription ioc ioc-refresh"></i></li>';
			}

			$actionCalender = '';
			$downloadLink ='<a class="icon-download" href="'.$calInfo['download'].'" title="'.$l->t('Download').'"></a>';
			
			$actionAdditionalCalendar = '<li><i class="ioc ioc-globe" title="'.$l->t('Show caldav url').'"></i></li><li>'.$downloadLink.'</li>';
			if($calInfo['bAction'] === true){
				$actionCalender = '<li class="icon-rename" title="'.$l->t('Edit').'"></li><li class="icon-delete" title="'.$l->t('Delete').'"></li>';
			}
			
			if($calInfo['birthday'] === true){
				$actionCalender = '';
				$actionAdditionalCalendar = '';
				$refreshAction = '';
			}	
						
			$addMenu='<span class="app-navigation-entry-utils-menu-button"><button></button>
							  <span class="app-navigation-entry-menu" data-calendarid="'.$calInfo['id'].'">
								  <ul>
								  <li>'.$checkBox.'</li>
								  '.$refreshAction.'
								  '.$actionAdditionalCalendar.'
								  '.$actionCalender.'
								  </ul>
							  </span>
						  </span>';
						  	
		$displayName='<span class="descr toolTip"  title="'.$calInfo['displayname'].'">'.$calInfo['displayname'].'</span>';
		$addKursiv = ($checked === '') ? ' kursiv' : '';
		 
		 $calendarClass='';
		 if(isset($calInfo['className'])){
		 	$calendarClass = $calInfo['className'];
		 }			
		 $addCalendarStyle='';
		 if(isset($calInfo['calendarcolor'])){
		 	$addCalendarStyle = 'background-color:'.$calInfo['calendarcolor'].';';
		 }			
		print_unescaped('<li data-id="'.$calInfo['id'].'" class="calListen '.$isActiveUserCal.$addKursiv.'"><div class="colCal '.$calendarClass.'"   style="cursor:pointer;'.$addCalendarStyle.'">&nbsp;</div> '.$displayName.$shareLink.$addMenu.'</li>');
	}
	
	?>
</ul>



<h3 data-id="lCategory" style=" cursor:pointer; line-height:24px;">
	<label id="showCategory" class="toolTip" title="<?php p($l->t('Add per Drag & drop categories to your event')); ?>">
		<i style="font-size:22px;" class="ioc ioc-angle-down ioc-rotate-270"></i>&nbsp;
		<i class="ioc ioc-tags"></i>&nbsp;<?php p($l->t('Category')); ?>
		</label>
		<i id="addGroup" title="<?php p($l->t('New Tag')) ?>" class="toolTip icon-add"></i>
</h3>
<ul id="categoryCalendarList"></ul>

<div id="app-settings">
		<div id="app-settings-header">
			<button class="settings-button" data-apps-slide-toggle="#app-settings-content">
				<?php p($l->t('Settings'));?>
			</button>
		</div>
		<div id="app-settings-content">
		
		<table class="nostyle">
		<tr>
			<td width="80">
				<label class="heading" for="timezone"><?php p($l->t('Timezone'));?></label>
				&nbsp;&nbsp;
			</td>
			<td>
				<select style="display: none; width: 130px;" id="timezone" name="timezone">
				<?php
				$continent = '';
				foreach($_['timezones'] as $timezone):
					$ex=explode('/', $timezone, 2);//obtain continent,city
					//19810329T020000
					$dateOffset=new \DateTime(date('Ymd\THis'), new \DateTimeZone($timezone));
					$offsetTime= $dateOffset->format('O') ;
					if (!isset($ex[1])) {
						$ex[1] = $ex[0];
						$ex[0] = "Other";
					}
					if ($continent!=$ex[0]):
						if ($continent!="") print_unescaped('</optgroup>');
						print_unescaped('<optgroup label="'.\OCP\Util::sanitizeHTML($ex[0]).'">');
					endif;
					$city=strtr($ex[1], '_', ' ');
					$continent=$ex[0];
					print_unescaped('<option value="'.\OCP\Util::sanitizeHTML($timezone).'"'.($_['timezone'] == $timezone?' selected="selected"':'').'>'.\OCP\Util::sanitizeHTML($city.' ('.$offsetTime.')').'</option>');
				endforeach;?>
				</select>
				<br /><span class="msg msgTz"></span>
			</td>
		</tr>
		<tr>
			<td colspan="2">
				<?php 
					$checkedTZD='';
					if($_['timezonedetection'] == 'true'){
						$checkedTZD='checked="checked"';
					}
				?>
				<input type="checkbox" name="timezonedetection" id="timezonedetection" <?php print_unescaped($checkedTZD); ?>>
				&nbsp;
				<label for="timezonedetection"><?php p($l->t('Update timezone automatically')); ?></label>
				<br /><span class="msg msgTzd"></span>
			</td>
		</tr>
		<tr>
			<td>
		<label  class="heading" for="timeformat" ><?php p($l->t('Date format'));?></label>&nbsp;&nbsp;
		</td>
		<td>
		<?php 
				  
			  $aDateFormat=[
			   'd-m-Y' => date('d-m-Y') ,
			   'm/d/Y' => date('m/d/Y')
			  ];
			?>
			<select style="display: none; width: 130px;" id="dateformat" title="<?php p("Date format"); ?>" name="dateformat">
				<?php
				  foreach($aDateFormat as $key => $val){
				  	$selected='';
					  if($key == $_['dateformat']){
					  	$selected ='selected="selected"';
					  }
					  print_unescaped('<option value="'.$key.'"  '.$selected.'>'.$val.'</option>');
				  }
				  ?>
				
			</select>
			<br /><span class="msg msgDf"></span>
		</td></tr>
		<tr>
			<td>
		<label  class="heading" for="timeformat" ><?php p($l->t('Time format'));?></label>&nbsp;&nbsp;
		</td>
		<td>
		<?php 
				  
			  $aTimeFormat=[
			   '24' => ['id' => '24h', 'title' => $l->t("24h")],
			   'ampm' => ['id' => 'ampm', 'title' => $l->t("12h")]
			  ];
			?>
			<select style="display: none; width: 130px;" id="timeformat" title="<?php p("timeformat"); ?>" name="timeformat">
				<?php
				  foreach($aTimeFormat as $key => $val){
				  	$selected='';
					  if($key == $_['timeformat']){
					  	$selected ='selected="selected"';
					  }
					  print_unescaped('<option value="'.$key.'" id="'.$val['id'].'" '.$selected.'>'.$val['title'].'</option>');
				  }
				  ?>
				
			</select>
			<br /><span class="msg msgTf"></span>
		</td></tr>
		
		<tr><td>
			<label  class="heading" for="firstday" ><?php p($l->t('1. Day of Week'));?></label>	
			</td>
			<td>
			<?php 
					$aFirstDay=[
				   'mo' => ['id' => 'mo', 'title' => $l->t("Monday")],
				   'tu' =>  ['id' => 'tu', 'title' => $l->t("Tuesday")],
				   'we' => ['id' => 'we', 'title' => $l->t("Wednesday")],
				   'th' =>  ['id' => 'th', 'title' => $l->t("Thursday")],
				   'fr' =>  ['id' => 'fr', 'title' => $l->t("Friday")],
				   'sa' => ['id' => 'sa', 'title' => $l->t("Saturday")],
				   'su' => ['id' => 'su', 'title' => $l->t("Sunday")]
				   
				  ];
				?>
				<select style="display: none; width: 130px;" id="firstday" title="<?php p("First day"); ?>" name="firstday">
					<?php
					  foreach($aFirstDay as $key => $val){
					  	$selected='';
						  if($key == $_['firstday']){
						  	$selected ='selected="selected"';
						  }
						  print_unescaped('<option value="'.$key.'" id="'.$val['id'].'" '.$selected.'>'.$val['title'].'</option>');
					  }
					  ?>
					
				</select>
				<br /><span class="msg msgFd"></span>	
				</td></tr>
				<tr>
			<td colspan="2">
				<label class="heading"><?php p($l->t('Active Views'));?></label>
				&nbsp;&nbsp;
			</td>
			</tr>
			<tr>
			<td colspan="2">
			<input class="viewsettings regular-checkbox" type="checkbox" name="agendaDay" id="vday" <?php ($_['userConfig']->{'agendaDay'} === 'true')?print_unescaped('checked="checked"') :''; ?> >
			
			<label for="vday"></label>&nbsp; <label style="width:74px;display:inline-block;"><?php p($l->t('Day')); ?></label>
			
			<input class="viewsettings regular-checkbox" type="checkbox" name="agendaThreeDays" id="v3day" <?php ($_['userConfig']->{'agendaThreeDays'} === 'true')?print_unescaped('checked="checked"') :''; ?> >
			<label for="v3day"></label>&nbsp;
			<label for="v3day" style="width:74px;display:inline-block;"><?php p($l->t('3-Days')); ?></label>
			<br />
			<input class="viewsettings regular-checkbox" type="checkbox" name="agendaWorkWeek" id="vworkweek" <?php ($_['userConfig']->{'agendaWorkWeek'} === 'true')?print_unescaped('checked="checked"') :''; ?> >
			<label for="vworkweek"></label>&nbsp;
			<label for="vworkweek" style="width:74px;display:inline-block;"><?php p($l->t('W-Week')); ?></label>
			
			<input class="viewsettings regular-checkbox" type="checkbox" name="agendaWeek" id="vweek" <?php ($_['userConfig']->{'agendaWeek'} === 'true')?print_unescaped('checked="checked"') :''; ?> >
			<label for="vweek"></label>&nbsp;
			<label for="vweek" style="width:74px;display:inline-block;"><?php p($l->t('Week')); ?></label>
			<br />
			<input class="viewsettings regular-checkbox" type="checkbox" name="month" id="vmonth" <?php ($_['userConfig']->{'month'} === 'true')?print_unescaped('checked="checked"') :''; ?> >
			<label for="vmonth"></label>&nbsp;
			<label for="vmonth" style="width:74px;display:inline-block;"><?php p($l->t('Month')); ?></label>
			
			<input class="viewsettings regular-checkbox" type="checkbox" name="year" id="vyear" <?php ($_['userConfig']->{'year'} === 'true')?print_unescaped('checked="checked"') :''; ?> >
			<label for="vyear"></label>&nbsp;
			<label for="vyear" style="width:74px;display:inline-block;"><?php p($l->t('Year')); ?></label>
			<br />
			<input class="viewsettings regular-checkbox" type="checkbox" name="list" id="vlist" <?php ($_['userConfig']->{'list'} === 'true')?print_unescaped('checked="checked"') :''; ?> >
			<label for="vlist"></label>&nbsp;
			<label for="vlist" style="width:74px;display:inline-block;"><?php p($l->t('List')); ?></label>
			</td>
		</tr>
		<tr>
			<td colspan="2">
			<label  class="heading"><?php p($l->t('URLs')); ?></label>
		</td>
		</tr>
		<tr>
			<td colspan="2">
				<?php p($l->t('Calendar CalDAV syncing addresses')); ?><br />
				 (<a href="http://owncloud.org/synchronisation/" target="_blank"><?php p($l->t('more info')); ?></a>)
				<br />
				<label  class="heading"><?php p($l->t('Primary address (Kontact et al)')); ?></label>
				<br />
				<input type="text" style="width:220px;" value="<?php print_unescaped(OCP\Util::linkToRemote($_['appname'])); ?>" readonly />
				<br />
				<label  class="heading"><?php p($l->t('iOS/OS X')); ?></label>
				<br />
				<input type="text" style="width:220px;" value="<?php print_unescaped(OCP\Util::linkToRemote($_['appname'])); ?>principals/<?php p(OCP\USER::getUser()); ?>/" readonly />
				
			</td>
			</tr>
			
			</table>
			
		</div>
</div>	