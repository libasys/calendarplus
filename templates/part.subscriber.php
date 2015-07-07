<?php
$eventid = isset($_['eventid']) ? $_['eventid'] : null;
?>

<?php if($_['organzier']!=''){ ?>
   <table>
   <tr>
		<th class="leftDescr" style="vertical-align: top;">Organzier</th>
		<td><?php p($_['organzier']);?></td>
	</tr>
	<?php if($_['attendees']!=''){ ?>
	<tr>
		<th class="leftDescr" style="vertical-align: top;">Attendees</th>
		<td>
			<ul>
		         <?php foreach($_['attendees']  as $value): ?>
				   <li class="attendeerow" data-email="<?php p($value); ?>"><?php p($value); ?><?php if($_['permissions'] & OCP\PERMISSION_DELETE) { ?> <img style="cursor:pointer;margin-top:2px;margin-bottom:-2px;" class="svg" src="<?php p(OCP\Util::imagePath('core', 'actions/delete.svg')) ?>"><?php } ?></li>
				   	<?php endforeach; ?>
		           </ul>
		</td>
	</tr>
	<?php }?>	
	</table>	
<?php }?>	
<br />
Teilnehmer E-Mail: <input type="text" id="addSubscriberEmail" /><button id="addSubscriber" data-eventid="<?php p($eventid);?>" class="button">Los</button>
<br /><br style="clear:both;" />
