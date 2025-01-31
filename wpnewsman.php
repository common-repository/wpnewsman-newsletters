<?php
/*
Plugin Name: G-Lock WPNewsman Lite
Plugin URI: https://wpnewsman.com
Description: You get simple yet powerful newsletter solution for WordPress. Now you can easily add double optin subscription forms in widgets, articles and pages, import and manage your lists, create and send beautiful newsletters directly from your WordPress site. You get complete freedom and a lower cost compared to Email Service Providers. Free yourself from paying for expensive email campaigns. WPNewsman plugin updated regularly with new features.
Version: 1.9.1
Author: Alex Ladyga - G-Lock Software
Author URI: http://www.glocksoft.com
*/
/*  Copyright 2012-2013  Alex Ladyga (email : alexladyga@glocksoft.com)
	Copyright 2012-2021  G-Lock Software

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// litte helper function to bring some windows love
function newsman_ensure_correct_path($path) {
	return preg_replace('/[\\\\\/]+/', DIRECTORY_SEPARATOR, $path);
}

define('NEWSMAN', 'wpnewsman');
define('NEWSMAN_VERSION', '1.9.1');

define('NEWSMAN_MU_BUNDLED_VERSION', '1.0.7');

if ( preg_match('/.*?\.dev$/i', $_SERVER['HTTP_HOST']) ) {
	define('NEWSMAN_DEV_HOST', true);
	define('WPNEWSMAN_POKEBACK_URL', 'http://localhost:8888');
} else {
	define('WPNEWSMAN_POKEBACK_URL', 'http://pokeback.wpnewsman.com');
}

define('NEWSMAN_PLUGIN_URL', plugins_url( '', __FILE__ ));
define('NEWSMAN_PLUGIN_PATH', newsman_ensure_correct_path(WP_PLUGIN_DIR.DIRECTORY_SEPARATOR.basename(dirname(__FILE__))) );
define('NEWSMAN_PLUGIN_MAINFILE', __FILE__);
define('NEWSMAN_BLOG_ADMIN_URL', get_bloginfo('wpurl').'/wp-admin/');
define('NEWSMAN_CLASSES_PATH', NEWSMAN_PLUGIN_PATH.DIRECTORY_SEPARATOR.'classes');

define('NEWSMAN_PLUGIN_DIRNAME', basename(dirname(__FILE__))); // newsman2/newsman2.php
define('NEWSMAN_PLUGIN_PATHNAME', basename(dirname(__FILE__)).'/'.basename(__FILE__)); // newsman2/newsman2.php
define('NEWSMAN_PLUGIN_PRO_PATHNAME', 'wpnewsman-pro/wpnewsman-pro.php');

if ( defined('ICL_SITEPRESS_VERSION') ) {
	define('NEWSMAN_WPML_MODE', true);
}

define('NEWSMAN_WORKER_ERR_CANNOT_SET_LOCK', '1');

// Email Types
define('NEWSMAN_ET_WELCOME', 1);
define('NEWSMAN_ET_ADDRESS_CHANGED', 2);
define('NEWSMAN_ET_ADMIN_SUB_NOTIFICATION', 3);
define('NEWSMAN_ET_ADMIN_UNSUB_NOTIFICATION', 4);
define('NEWSMAN_ET_CONFIRMATION', 5);
define('NEWSMAN_ET_UNSUBSCRIBE', 6);
define('NEWSMAN_ET_UNSUBSCRIBE_CONFIRMATION', 7);
define('NEWSMAN_ET_RECONFIRM', 8);

define('NEWSMAN_SS_UNCONFIRMED', 0);
define('NEWSMAN_SS_CONFIRMED', 1);
define('NEWSMAN_SS_UNSUBSCRIBED', 2);

// transmission statuses
define('NEWSMAN_TS_PENDING', 0);
define('NEWSMAN_TS_SENDING', 1);
define('NEWSMAN_TS_SENT', 2);
define('NEWSMAN_TS_ERROR', 3);

define('NEWSMAN_ERR_INVALID_EMAIL_ADDR', 10);
define('NEWSMAN_ERR_SUBSCRIBER_NOT_FOUND', 11);
define('NEWSMAN_ERR_LIST_NOT_FOUND', 12);

define('NEWSMAN_ERR_TEMP_ERROR', 1);
define('NEWSMAN_ERR_CANNOT_CONNECT_TO_HOST', 2);
define('NEWSMAN_ERR_DOMAIN_BLOCKED_BY_BH', 3);



if ( strpos($_SERVER['REQUEST_URI'], 'frmGetPosts.php') !== false && !defined('INSERT_POSTS_FRAME') ) {
	define('INSERT_POSTS_FRAME', true);
}

function newsmanIsOnWindows() {
	return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
}

function newsmanStopActivation() {
	global $newsman_checklist;
	?>
		<style>
			.nesman-label {
				background: whiteSmoke;
				border: 1px solid #DFDFDF;
				-moz-border-radius: 3px;
				-webkit-border-radius: 3px;
				border-radius: 3px;
				width: 46px;
				display: inline-block;
				text-align: center;
				font-size: 11px;
				margin-right: .5em;
			}
			.newsman-label-passed {
				color: green;
			}
			.newsman-label-failed {
				color: red;
			}
			.newsman-label-help {
				color: gray;
				font-style: italic;
			}
		</style>
		<div class="error">

			<h3>Error: G-Lock WPNewsman compatibility check failed, the plugin cannot be activated.</h3>
			<p>Please, fix the issues below and try again.</p>
			<ul>
				<?php
					foreach ($newsman_checklist as $check) {
						if ( $check['passed'] ) {
							$lbl = 'Passed';
							$class = 'newsman-label-passed';
						} else {
							$lbl = 'Failed';
							$class = 'newsman-label-failed';
						}
						echo '<li><span class="nesman-label '.$class.'">'.$lbl.'</span> '.$check['name'].' (<span class="newsman-label-help">'.$check['help'].'</span>)</li>';
					}
				?>
			</ul>
		</div>
	<?php
	deactivate_plugins(NEWSMAN_PLUGIN_PATHNAME);
	unset($_GET['activate']); // to disable "Plugin activated" message

}

function newsmanCheckCompatibility() {
	global $newsman_checklist;

	$passed = true;
	$newsman_checklist = array();

	// 0. PHP version
	$newsman_checklist[] = array(
		'passed' => version_compare(phpversion(), '7.1.3', '>='),
		'name'  => __( 'PHP version >= 7.1', NEWSMAN),
		'help'  => sprintf( __('You have PHP %s installed.', NEWSMAN) , phpversion())
	);


	// 1. Multisite setup
	$is_ms = function_exists('is_multisite') && is_multisite();

	$newsman_checklist[] = array(
		'passed' => !$is_ms,
		'name'  => __('Single-site mode', NEWSMAN),
		'help'  => __('Doesn\'t work in MultiSite setup.', NEWSMAN)
	);

	// 2. OPENSSL

	$newsman_checklist[] = array(
		'passed' => function_exists('openssl_encrypt'),
		'name'  => __('Openssl library', NEWSMAN),
		'help'  => __('Openssl library is required to securely store your passwords in the database. Enable OpenSSL or contact your hosting provider if you\'re on a shared hosting.', NEWSMAN)
	);

	// 3. MBString module

	$newsman_checklist[] = array(
		'passed' => function_exists('mb_check_encoding'),
		'name' => __('MBString extension', NEWSMAN),
		'help' => __('MBString extension is required for correct processing of non unicode characters. Read <a href="http://www.php.net/manual/en/mbstring.installation.php">how to Install/Configure</a> or contact your hosting provider if you\'re on a shared hosting.', NEWSMAN)
	);

	if ( !defined('BYPASS_NEWSMAN_DIRECT_FS_ACCESS_CHECK') || !BYPASS_NEWSMAN_DIRECT_FS_ACCESS_CHECK ) {

		$dirs = wp_upload_dir();
		$ud = $dirs['basedir'];

		$cur_user = '';

		if ( function_exists('get_current_user') ) {
			$cur_user = '( <strong>'.@get_current_user().'</strong> user )';
		}

		$newsman_checklist[] = array(
			'passed' => is_writable($ud),
			'name' => __('Direct filesystem access', NEWSMAN),
			'help' => sprintf(__('Since version 1.5.7 direct access to the filesystem is required. Make sure that the uploads directory is writable by the web server%s.', NEWSMAN), $cur_user)
		);
	}

	// 5. Safe Mode check
	$newsman_checklist[] = array(
		'passed' => !ini_get('safe_mode'),
		'name' => __('Safe mode is turned off', NEWSMAN),
		'help' => __('Safe mode is deprecated in PHP and not supported by the plugin.(Set safe_mode = Off in php.ini. See <a href="http://www.php.net/manual/en/features.safe-mode.php">Safe Mode on php.net</a>)', NEWSMAN)
	);

	/// ----

	// 6. libbcmatch module check
	$newsman_checklist[] = array(
		'passed' => extension_loaded('bcmath') || extension_loaded('gmp'),
		'name' => __('bcmath or gmp extension is loaded', NEWSMAN),
		'help' => __('Since version 1.7.0 either <b>bcmath</b> or <b>gmp</b> PHP module is required for the plugin to work. According to PHP documentation <b>bcmath</b> should pre installed since PHP 4.0.4.', NEWSMAN)
	);

	/// ----



	foreach ($newsman_checklist as $check) {
		if ( $check['passed'] === false ) {
			$passed = false;
		}
	}

	if ( !$passed ) {
		add_action('admin_notices', 'newsmanStopActivation');
	}

	return $passed;
}

function wpnewsmanActivationHook() {
	if ( newsmanCheckCompatibility() ) {
		require_once(__DIR__.DIRECTORY_SEPARATOR."core.php");
		$n = newsman::getInstance();
		$n->onActivate();

	}
}

function wpnewsmanDeactivationHook() {
	if ( newsmanCheckCompatibility() ) {
		require_once(__DIR__.DIRECTORY_SEPARATOR."core.php");
		$n = newsman::getInstance();
		$n->onDeactivate();
	}
}

if ( newsmanCheckCompatibility() ) {
	require_once(__DIR__.DIRECTORY_SEPARATOR."core.php");
	$n = newsman::getInstance();
	newsman_register_worker('newsmanMailerWorker');
	newsman_register_worker('newsmanBounceHandlerWorker');
}

register_activation_hook( NEWSMAN_PLUGIN_MAINFILE, 'wpnewsmanActivationHook');
register_deactivation_hook( NEWSMAN_PLUGIN_MAINFILE, 'wpnewsmanDeactivationHook' );
