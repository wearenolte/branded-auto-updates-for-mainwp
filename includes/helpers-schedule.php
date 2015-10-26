<?php
/**
 * Get the 12-hour time equivalent of a 24-hour time input.
 *
 * @todo Remove this function.
 *
 * @deprecated 0.2.0
 *
 * @param $_24_hour_time
 */
function baufm_format_scheduled_time_of_day( $_24_hour_time ) {
	$suffix = ( $_24_hour_time >= 12 ) ? 'PM' : 'AM';
	$time   = ( $_24_hour_time <= 12 ) ? $_24_hour_time : $_24_hour_time - 12;
	$time   = ( $time < 10 ) ? '0' . $time : $time;
	$time  .= ':00 ' . $suffix;

	return $time;
}

/**
 * Callback function for updating the child sites belonging to a given group
 * at the current time.
 *
 * @todo Remove this temporary function and replace it.
 *
 * @since 0.2.0
 */
function _baufm_update_now( $group_id, $scheduled_action ) {
	BAUFM_Updater::_instance()->pre_update_setup();
	BAUFM_Updater::_instance()->update_group( $group_id, $scheduled_action );
}
add_action( 'baufm_update_now', '_baufm_update_now', 10, 2 );
