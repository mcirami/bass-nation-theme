<?php

// Map your normal prices, periods, and first-cycle discounts
function db_winback_price_map() {
	return [
		'normal' => [ 1 => 9.99, 2 => 28.99, 3 => 54.99, 4 => 99.99 ],
		'period' => [ 1 => 'month', 2 => '3 months', 3 => '6 months', 4 => 'year' ],
		'pct'    => [ 1 => 20, 2 => null, 3 => 20, 4 => 25 ],
		'dollar' => [ 1 => null, 2 => 6 , 3 => null, 4 => null ],
	];
}

// (A) After checkout, remember the discount code used (for later email filters)
add_action( 'pmpro_after_checkout', function ( $user_id, $morder ) {
	$dc = isset( $_REQUEST['pmpro_discount_code'] ) ? strtoupper( sanitize_text_field( $_REQUEST['pmpro_discount_code'] ) ) : '';
	if ( ! $user_id || ! $dc ) {
		return;
	}
	// Store on the user as a last-used flag (simple and reliable)
	update_user_meta( $user_id, '_db_last_discount_code', $dc );
}, 10, 2 );

// (B) Populate custom template variables for emails
add_filter( 'pmpro_email_data', function ( $data, $email ) {
	// Only adjust member-facing checkout emails; feel free to expand as needed.
	$templates_to_touch = [ 'checkout_paid', 'checkout_free', 'checkout_express' ];
	if ( ! in_array( $email->template, $templates_to_touch, true ) ) {
		$data['winback_details'] = ''; // ensure placeholder exists

		return $data;
	}

	// Figure out which level and whether BNATION25 was used
	$level_id = 0;
	if ( ! empty( $data['membership_level'] ) && ! empty( $data['membership_level']->id ) ) {
		$level_id = (int) $data['membership_level']->id;
	} elseif ( ! empty( $data['level_id'] ) ) {
		$level_id = (int) $data['level_id'];
	}

	$user_id      = ! empty( $data['user_id'] ) ? (int) $data['user_id'] : 0;
	$last_dc      = $user_id ? strtoupper( (string) get_user_meta( $user_id, '_db_last_discount_code', true ) ) : '';
	$used_bnation = ( $last_dc === 'BNATION25' || ( ! empty( $_REQUEST['pmpro_discount_code'] ) && strtoupper( $_REQUEST['pmpro_discount_code'] ) === 'BNATION25' ) );

	if ( ! $used_bnation || ! $level_id ) {
		$data['winback_details'] = ''; // nothing special to show

		return $data;
	}

	// Build the line: "Free 7 days, then $X for first cycle, then $Y per period."
	$map = db_winback_price_map();
	if ( empty( $map['normal'][ $level_id ] ) ) {
		$data['winback_details'] = '';

		return $data;
	}
	$normal = $map['normal'][ $level_id ];
	if ($map['pct'][ $level_id ]) {
		$first  = round( $normal * ( 1 - ( $map['pct'][ $level_id ] / 100 ) ), 2 );
	} else {
		$first = round( $normal - $map['dollar'][ $level_id ], 2 );
	}

	$period = $map['period'][ $level_id ];

	// Compute first-charge date = today + 7 days (Subscription Delay)
	$first_date = date_i18n( get_option( 'date_format' ), current_time( 'timestamp' ) + 7 * DAY_IN_SECONDS );

	$data['winback_details'] =
		'<p style="margin:0 0 10px 0;"><strong>Special offer applied:</strong> Free for 7 days (until ' .
		esc_html( $first_date ) . '), then <strong>$' . number_format( $first, 2 ) . '</strong> for the first ' .
		esc_html( $period ) . ', then <strong>$' . number_format( $normal,
			2 ) . '</strong> per ' . esc_html( $period ) .
		' thereafter.</p>';

	return $data;
}, 10, 2 );
