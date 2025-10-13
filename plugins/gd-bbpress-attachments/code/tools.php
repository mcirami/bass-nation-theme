<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GDATTTools {
	public function __construct() {
		add_action( 'after_setup_theme', array( $this, 'load' ) );
	}

	public static function instance() {
		static $instance = false;

		if ( $instance === false ) {
			$instance = new GDATTTools();
		}

		return $instance;
	}

	public function calculate_number_of_logged_errors() : array {
		global $wpdb;

		$sql = "SELECT `post_id`, COUNT(*) AS `items` FROM $wpdb->postmeta WHERE `meta_key` = '_bbp_attachment_upload_error' GROUP BY `post_id`";
		$raw = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		return array(
			'list'   => wp_list_pluck( $raw, 'items', 'post_id' ),
			'totals' => array(
				'posts'  => count( $raw ),
				'errors' => array_sum( wp_list_pluck( $raw, 'items' ) ),
			),
		);
	}

	public function delete_all_logged_errors() {
		global $wpdb;

		$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE `meta_key` = '_bbp_attachment_upload_error'" ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	public function process_action() {
		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
		$url   = remove_query_arg( array( '_wpnonce', 'action' ) );

		if ( wp_verify_nonce( $nonce, 'gdatt-clear-error-log' ) ) {
			$this->delete_all_logged_errors();
			$url .= '&tools-errors-clear';
		}

		wp_redirect( $url );
	}
}
