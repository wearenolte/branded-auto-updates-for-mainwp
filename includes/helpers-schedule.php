<?php

/**
 *
 */
function baufm_get_scheduled_day_of_week( $group_id, $format = 'text' ) {
	$schedule_in_week = (int) get_option( "baufm_schedule_in_week_group_$group_id", 0 );

	if ( 'int' === $format ) {
		return $schedule_in_week;
	}

	if ( 'text' === $format ) {
		return baufm_format_scheduled_day_of_week( $schedule_in_week );
	}

}

/**
 *
 */
function baufm_format_scheduled_time_of_day( $_24_hour_time ) {
	$suffix = ( $_24_hour_time >= 12 ) ? 'PM' : 'AM';
	$time   = ( $_24_hour_time <= 12 ) ? $_24_hour_time : $_24_hour_time - 12;
	$time   = ( $time < 10 ) ? '0' . $time : $time;
	$time  .= ':00 ' . $suffix;

	return $time;
}

/**
 *
 */
function baufm_format_scheduled_day_of_week( $schedule_in_week ) {
	$days = array(
		__( 'Nothing scheduled.', 'baufm' ),
		__( 'Everyday', 'baufm' ),
		__( 'Sunday' , 'baufm' ),
		__( 'Monday', 'baufm' ),
		__( 'Tuesday', 'baufm' ),
		__( 'Wednesday', 'baufm' ),
		__( 'Thursday', 'baufm' ),
		__( 'Friday', 'baufm' ),
		__( 'Saturday', 'baufm' ),
	);

	if ( ! array_key_exists( $schedule_in_week, $days ) ) {
		$schedule_in_week = 0;
	}

	return $days[ $schedule_in_week ];
}

function baufm_get_scheduled_time_of_day( $group_id, $format = 'text' ) {
	$schedule_in_day = get_option( "baufm_schedule_in_day_group_$group_id", 0 );

	if ( 'int' === $format ) {
		return $schedule_in_day;
	}

	return baufm_format_scheduled_time_of_day( $schedule_in_day );
}
