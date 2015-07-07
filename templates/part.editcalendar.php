<?php
/**
 * Copyright (c) 2011 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
?>
<td align="center" id="<?php p($_['new'] ? 'new' : 'edit') ?>calendar_dialog" title="<?php p($_['new'] ? $l->t("New calendar") : $l->t("Edit calendar")); ?>" colspan="7">
<div class="calendarDivForm">
<table class="calendarTableForm" style="width:100%; border:0" align="center">
<tr>
	<th width="90"><?php p($l->t('Displayname')) ?></th>
	<td>
		<input style="width:90%;" id="displayname_<?php p($_['calendar']['id']) ?>" type="text" value="<?php p($_['calendar']['displayname']) ?>">
	</td>
</tr>
<tr>
	<th>Extern Link</th>
	<td>
		<input style="width:90%;" id="externuri_<?php p($_['calendar']['id']) ?>" type="text" style="float:left;" value="<?php p($_['calendar']['externuri']) ?>">
	</td>
</tr>
<?php if (!$_['new']): ?>
<tr>
	<td></td>
	<td>
		<input id="edit_active_<?php p($_['calendar']['id']) ?>" type="checkbox"<?php p($_['calendar']['active'] ? ' checked="checked"' : '') ?>>
		<label for="edit_active_<?php p($_['calendar']['id']) ?>">
			<?php p($l->t('Active')) ?>
		</label>
	</td>
</tr>
<?php endif; ?>
<tr>
	<th><?php p($l->t('Calendar color')) ?></th>
	<td>
	  
	<input type="hidden" class="minicolor" id="calendarcolor_<?php p($_['calendar']['id']) ?>" value="<?php print_unescaped($_['calendar']['calendarcolor']) ?>" /> 
		
	</td>
</tr>
</table>
<input style="float: right; margin-left:5px;"  id="editCalendar-submit" type="button" data-id="<?php p($_['new'] ? "new" : $_['calendar']['id']) ?>" value="<?php p($_['new'] ? $l->t("Save") : $l->t("Submit")); ?>">
<input style="float: right; margin-left:5px;"  id="editCalendar-cancel"  type="button" data-id="<?php p($_['new'] ? "new" : $_['calendar']['id']) ?>" value="<?php p($l->t("Cancel")); ?>">
</div>
</td>
