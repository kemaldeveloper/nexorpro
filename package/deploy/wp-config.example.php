<?php

/** Safe configuration example. Do not commit real secret values. */
$nexor_secret = static function ( string $name, string $fallback = '' ): string {
	$secret_file = '/run/secrets/' . $name;
	if ( is_readable( $secret_file ) ) {
		return trim( (string) file_get_contents( $secret_file ) );
	}
	$value = getenv( strtoupper( $name ) );
	return false === $value ? $fallback : (string) $value;
};

define( 'DB_NAME', getenv( 'WORDPRESS_DB_NAME' ) ?: 'wordpress' );
define( 'DB_USER', getenv( 'WORDPRESS_DB_USER' ) ?: 'wordpress' );
define( 'DB_PASSWORD', $nexor_secret( 'nexor_db_password' ) );
define( 'DB_HOST', getenv( 'WORDPRESS_DB_HOST' ) ?: 'database:3306' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );

$nexor_salt = $nexor_secret( 'nexor_wp_secret' );
foreach ( array( 'AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY', 'AUTH_SALT', 'SECURE_AUTH_SALT', 'LOGGED_IN_SALT', 'NONCE_SALT' ) as $key_name ) {
	define( $key_name, hash_hmac( 'sha256', $key_name, $nexor_salt ) );
}

define( 'NEXOR_TELEGRAM_BOT_TOKEN', $nexor_secret( 'nexor_telegram_token' ) );
define( 'NEXOR_TELEGRAM_CHAT_ID', $nexor_secret( 'nexor_telegram_chat_id' ) );
define( 'WP_ENVIRONMENT_TYPE', getenv( 'WP_ENVIRONMENT_TYPE' ) ?: 'production' );
define( 'DISALLOW_FILE_EDIT', true );

$table_prefix = getenv( 'WORDPRESS_TABLE_PREFIX' ) ?: 'wp_';

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

require_once ABSPATH . 'wp-settings.php';
