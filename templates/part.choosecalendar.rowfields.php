
<td width="20px">
  <?php if($_['calendar']['userid'] == OCP\USER::getUser()) { ?>
  <input type="checkbox" id="active_<?php p($_['calendar']['id']) ?>" class="chooseCalendar-activeCalendar regular-checkbox" data-id="<?php p($_['calendar']['id']) ?>" <?php print_unescaped($_['calendar']['active'] ? ' checked="checked"' : '') ?>><label style="margin-right:4px;" for="active_<?php p($_['calendar']['id']) ?>"></label>
  <?php } ?>
</td>
<td id="<?php p(OCP\USER::getUser()) ?>_<?php p($_['calendar']['id']) ?>">
 <?php
        $displayName=$_['calendar']['displayname'].' (ID '.$_['calendar']['id'].')';
		if($_['calendar']['id'] === 'birthday_'.OCP\USER::getUser()){
			 $displayName=$_['calendar']['displayname'];
		}
			
         if($_['calendar']['userid'] != OCP\USER::getUser()){
  	        $displayName=$_['calendar']['displayname'].' (ID '.$_['calendar']['id'].') (' . $l->t('by') . ' ' .$_['calendar']['userid'].')';
        }
   ?>
  <label for="active_<?php p($_['calendar']['id']) ?>"><?php p($displayName) ?></label>
</td>

<td width="20px">
<?php
if($_['calendar']['userid'] == OCP\USER::getUser()){
	$caldav = rawurlencode(html_entity_decode($_['calendar']['uri'], ENT_QUOTES, 'UTF-8'));
}else{
	$caldav = rawurlencode(html_entity_decode($_['calendar']['uri'], ENT_QUOTES, 'UTF-8')) . '_shared_by_' . $_['calendar']['userid'];
}
?>
<?php
if($_['calendar']['id'] != 'birthday_'.OCP\USER::getUser()){?>
  <a href="#" class="chooseCalendar-showCalDAVURL action permanent" data-user="<?php p(OCP\USER::getUser()) ?>" data-caldav="<?php p($caldav) ?>" title="<?php p($l->t('CalDav Link')) ?>"><i class="ioc ioc-globe"></i></a>
<?php } ?>
  </td>
<td width="20px">
<?php	
if($_['calendar']['id'] != 'birthday_'.OCP\USER::getUser()){?>	
  <a href="<?php print_unescaped(\OC::$server->getURLGenerator()->linkToRoute($_['appname'].'.export.exportEvents') . '?calid=' . $_['calendar']['id']) ?>" title="<?php p($l->t('Download')) ?>" class="permanent action"><i class="ioc ioc-download"></i></a>
<?php } ?>
</td>
<td width="20px">
  <?php if($_['calendar']['userid'] == OCP\USER::getUser() && $_['calendar']['id'] != 'birthday_'.OCP\USER::getUser()){ ?>
  <a href="#" class="chooseCalendar-edit permanent action" data-id="<?php p($_['calendar']['id']) ?>" title="<?php p($l->t('Edit')) ?>"><i class="ioc ioc-rename"></i></a>
  <?php } ?>
</td>
<td width="20px">
  <?php if($_['calendar']['userid'] == OCP\USER::getUser() && $_['calendar']['id'] != 'birthday_'.OCP\USER::getUser()){ ?>
  <a href="#"  class="chooseCalendar-delete permanent action" data-id="<?php p($_['calendar']['id']) ?>" title="<?php p($l->t('Delete')) ?>"><i class="ioc ioc-delete"></i></a>
  <?php } ?>
</td>
