<?php
/**
 * The base configuration for WordPress
 *
 * Environmental Platform WordPress Configuration
 * Connects to the existing environmental_platform database
 * Created during Phase 27: WordPress Core Setup & Configuration
 *
 * @package WordPress
 */

// ** Database settings - Environmental Platform Database ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'environmental_platform' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'vuf|TFXB<~7aDs.Ni|sW3#?q,9Z!3H=}{^F-a6fR8#Gct&`RxNUXbLn|$?(U>N+U');
define('SECURE_AUTH_KEY',  '}vDm!yRIE!KP/H%}Dn>).&~.~;1y&Vlh_-r[P>(D>Ggbj@ aTZ.AC);LT-s$E=.');
define('LOGGED_IN_KEY',    '*liVBf!>FK$jmKn?hz9%)6!m;>dzx|A9@[}32 Q@WTV:${BZ>;YiP?n?KII;&7F');
define('NONCE_KEY',        '?{S/v~h4>9S,KPtBk5vl-hzQ{d9{+-u#>Kl+FsDis/>heaWjL-+H:CeXf#~o&1:');
define('AUTH_SALT',        'vl<&/`Ye(|D SbO6.ihwJv+_Y=yZ:S0<~Ztiai{<RrRL{g?o,RficN[K5P1S[]g');
define('SECURE_AUTH_SALT', '}|9%^+_fZ.m1^-M50ap31`1D?o(6OcT6m[U|-:`ElyJ,E_fU$)sBtblrd|8$JGGn');
define('LOGGED_IN_SALT',   'ot[[E1b;ZJaBY#-1xe/3Wqlf@kVD!eP-jA*/SNAx/-/.{&1x!h1fue.!}P$&WE:b');
define('NONCE_SALT',       'U[Ci7C_M)]mQh7@D*Zd?whpe3QTg|A-[J1JT.B#/[^^<5!fVdcvA5^L-Z%%MDz8v');

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

/**
 * WordPress debugging and development settings.
 */
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
define( 'SCRIPT_DEBUG', true );

/**
 * WordPress memory and performance settings.
 */
define( 'WP_MEMORY_LIMIT', '512M' );
define( 'WP_MAX_MEMORY_LIMIT', '512M' );

/**
 * WordPress security settings.
 */
define( 'DISALLOW_FILE_EDIT', true );
define( 'DISALLOW_FILE_MODS', false );
define( 'FORCE_SSL_ADMIN', false );

/**
 * WordPress automatic updates.
 */
define( 'WP_AUTO_UPDATE_CORE', true );
define( 'AUTOMATIC_UPDATER_DISABLED', false );

/**
 * WordPress file permissions.
 */
define( 'FS_CHMOD_DIR', ( 0755 & ~ umask() ) );
define( 'FS_CHMOD_FILE', ( 0644 & ~ umask() ) );

/**
 * WordPress cache and optimization.
 */
define( 'WP_CACHE', true );
define( 'COMPRESS_CSS', true );
define( 'COMPRESS_SCRIPTS', true );
define( 'CONCATENATE_SCRIPTS', true );
define( 'ENFORCE_GZIP', true );

/**
 * Advanced caching configuration for Environmental Platform.
 */
define( 'WP_CACHE_KEY_SALT', 'environmental_platform_localhost' );
define( 'ENABLE_CACHE_REPLICATION', true );
define( 'WP_REDIS_HOST', '127.0.0.1' );
define( 'WP_REDIS_PORT', 6379 );
define( 'WP_REDIS_TIMEOUT', 1 );
define( 'WP_REDIS_DATABASE', 0 );

/**
 * Performance optimization constants.
 */
define( 'EMPTY_TRASH_DAYS', 7 );
define( 'WP_POST_REVISIONS', 3 );
define( 'AUTOSAVE_INTERVAL', 300 );
define( 'WP_CRON_LOCK_TIMEOUT', 60 );

/**
 * Database optimization.
 */
define( 'SAVEQUERIES', false );
// Define MySQL client flags for compression (commented out to avoid constant issues)
// define( 'MYSQL_CLIENT_FLAGS', MYSQL_CLIENT_COMPRESS );

/**
 * Image optimization.
 */
define( 'JPEG_QUALITY', 85 );
define( 'WP_IMAGE_EDITOR', 'WP_Image_Editor_Imagick' );

/**
 * Environmental Platform performance monitoring.
 */
define( 'ENV_PERFORMANCE_MONITORING', true );
define( 'ENV_CACHE_LOGGING', true );
define( 'ENV_SLOW_QUERY_THRESHOLD', 1.0 );

/**
 * Custom WordPress constants for Environmental Platform.
 */
define( 'ENVIRONMENT_PLATFORM_VERSION', '1.0.0' );
define( 'ENVIRONMENT_PLATFORM_DEBUG', true );

/* Add any custom values between this line and the "stop editing" line. */

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
