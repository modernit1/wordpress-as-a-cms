<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'wordpress');

/** MySQL database username */
define('DB_USER', 'username');

/** MySQL database password */
define('DB_PASSWORD', 'password');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8mb4');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'Y@ETYjneZjYH=,!&C4KIwEa,]NZxdIkm1yM>CI.#`CyD7}b8r8<xHRwN`X:f|g`X');
define('SECURE_AUTH_KEY',  ':|;;H%OvPdN!uETF]eo?7EWdjI<}g2.m|Qe.+1gN./x|1}yRo9Xt> @H<sOq`d6}');
define('LOGGED_IN_KEY',    '<LuQAs ,Pd;V]]DS6VG5w4uIC(Sz?~fa#m,ZDss `CC|Mh``>9Dfmqo|nTRnV:Mm');
define('NONCE_KEY',        '1~z5^9m~Wh3SF]3/Z#l289hCyt=fz3M,;CkKanb*e`q/$p.wm{Cd]eoFsk3JB;)F');
define('AUTH_SALT',        'H5s-+~d;DyuCX,Ytkud}G0j2!Gypuxd|Kv1sjGVlQeXpw$Ywz*s7/|);<vOM~x*,');
define('SECURE_AUTH_SALT', 'o}KJ {F#[.v[d<2m^3d{L,0@M|k*>8a;);e(UxEc5p0?ZGLJl77X;)Mw6+W;eA1q');
define('LOGGED_IN_SALT',   'l*Y(`n/0VlmCv$c)3.rW&fM1*)eFMq)iB{QD U`9g}0u#W~%b6q,NXToG%q7{fD,');
define('NONCE_SALT',       'xWRudgxJNjnA$Ac|Q-mOy@{orm79D+_w[O+/>vTeHCBSXT0b7=%f36gQO_0*Dz;=');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
