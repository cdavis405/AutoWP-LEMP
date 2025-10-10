<?php
/**
 * Plugin Name: Retaguide Security Hardening
 * Description: Baseline security controls for Retaguide deployments.
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'DISALLOW_FILE_EDIT' ) ) {
	define( 'DISALLOW_FILE_EDIT', true );
}

add_filter( 'xmlrpc_enabled', '__return_false' );

add_filter(
	'rest_authentication_errors',
	function ( $result ) {
		if ( ! empty( $result ) ) {
			return $result;
		}

		if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
			return new WP_Error( 'rest_disabled', __( 'REST API restricted during XML-RPC requests.', 'retaguide' ), array( 'status' => 403 ) );
		}

		return $result;
	}
);

add_action(
	'send_headers',
	function (): void {
		header( 'X-Frame-Options: SAMEORIGIN', false );
		header( 'X-Content-Type-Options: nosniff', false );
		header( 'Referrer-Policy: strict-origin-when-cross-origin', false );
		header( 'X-XSS-Protection: 1; mode=block', false );
	}
);

add_filter(
	'authenticate',
	function ( $user, string $username ) {
		if ( ! $username ) {
			return $user;
		}

		$ip   = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
		$key  = 'retaguide_login_' . md5( $ip );
		$data = get_transient( $key );

		if ( isset( $data['locked'] ) && $data['locked'] > time() ) {
			return new WP_Error( 'too_many_attempts', __( 'Too many failed login attempts. Try again in 15 minutes.', 'retaguide' ) );
		}

		return $user;
	},
	30,
	2
);

add_action(
	'wp_login_failed',
	function ( string $username ): void {
		$ip   = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
		$key  = 'retaguide_login_' . md5( $ip );
		$data = get_transient( $key );

		$count = isset( $data['count'] ) ? (int) $data['count'] : 0;
		$count++;

		$data = array(
			'count'  => $count,
			'locked' => $count >= 5 ? time() + 15 * MINUTE_IN_SECONDS : 0,
		);

		set_transient( $key, $data, 30 * MINUTE_IN_SECONDS );
	}
);

add_action(
	'wp_login',
	function (): void {
		$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
		$key = 'retaguide_login_' . md5( $ip );
		delete_transient( $key );
	}
);
