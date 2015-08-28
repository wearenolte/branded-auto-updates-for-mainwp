<form id="branded-auto-updates-for-mainwp-settings-auto-updates" action="" method="">
<?php wp_nonce_field( 'branded_auto_updates_for_mainwp_settings' ); ?>

<input type="hidden" name="page" value="branded-auto-updates-for-mainwp-auto-updates">
<input type="hidden" name="tab" value="auto-updates">

<br>

<table class="wp-list-table widefat fixed striped sites">
	<thead>
		<tr>
			<th scope="col" id="" class="manage-column column- column-primary"><span>Site Group</span></th>
			<th scope="col" id="" class="manage-column column-"><span>Update Schedule</span></th>
			<th scope="col" id="" class="manage-column column-"><span>Last Updated</span></th>
			<th scope="col" id="" class="manage-column column-"><span>Update count</span></th>
		</tr>
	</thead>

	<tbody id="the-list">

	<?php                
		$groups = MainWPDB::Instance()->getGroupsForCurrentUser();
		foreach ( $groups as $group ) : // ->id ->name ?>
			<tr>
				<td class="blogname column-blogname has-row-actions column-primary" data-colname="URL">
					<a href="http://mainwp-child.dev/wp-admin/network/site-info.php?id=1" class="edit"><?php echo $group->name; ?></a>
					
					<div class="row-actions">
						<span class="edit"><span class="edit"><a href="http://mainwp-child.dev/wp-admin/network/site-info.php?id=1">Edit</a></span> | </span>
						<span class="backend"><span class="backend"><a href="http://mainwp-child.dev/wp-admin/" class="edit">Dashboard</a></span> | </span>
						<span class="visit"><span class="view"><a href="http://mainwp-child.dev/" rel="permalink">Visit</a></span></span>
					</div>

					<button type="button" class="toggle-row">
						<span class="screen-reader-text">Show more details</span>
					</button>
				</td>

				<td class="lastupdated column-lastupdated" data-colname="Last Updated"></td>

				<td class="registered column-registered" data-colname="Registered"></td>

				<td class="users column-users" data-colname="Users"></td>
			</tr>
		<?php endforeach; ?>
	</tbody>

	<tfoot>
	</tfoot>
</table>

</form>