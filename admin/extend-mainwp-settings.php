<?php

function branded_auto_updates_for_mainwp_add_multiple_email_field( $website ) {
	?>
	<tr>
		<th scope="row">
			<?php _e( 'Notification Emails after Offline Checks', 'branded_auto_updates_for_mainwp' ); ?>
			<?php MainWPUtility::renderToolTip( 'Add a list of comma-separated emails for multiple notifications.' ); ?>
		</th>
		<td>
			<?php
			$emails = MainWPDB::Instance()->getWebsiteOption( $website, 'mwp_me_emails' );
			if ( empty( $emails ) ) {
				$emails = '';
			}
			?>
			<textarea style="height: 140px; width: 100%;" name="mwp-me-emails" id="mwp-me-emails"><?php echo $emails; ?></textarea>
		</td>
	</tr>
	<?php
}
add_action( 'mainwp_extension_sites_edit_tablerow', 'branded_auto_updates_for_mainwp_add_multiple_email_field' );

function branded_auto_updates_for_mainwp_update_site( $website_id ) {
	$website = MainWPDB::Instance()->getWebsiteById( $website_id );
	if ( ! empty( $_POST['mwp-me-emails'] ) ) {
		MainWPDB::Instance()->updateWebsiteOption( $website, 'mwp_me_emails', trim( $_POST['mwp-me-emails'] ) );
	} else {
		MainWPDB::Instance()->updateWebsiteOption( $website, 'mwp_me_emails', '' );
	}
}
add_action( 'mainwp_update_site', 'branded_auto_updates_for_mainwp_update_site' );