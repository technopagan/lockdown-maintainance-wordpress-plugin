<?php
/*
Plugin Name: Lockdown Maintainance
Plugin URI: http://eco.de/
Description: Activate to notify logged-in users of upcoming maintainance and start a timer (default: 5min) which, when done, will force-logout all users but admins and will put the WordPress admin-interface into a fake maintainance mode so all user-levels lower than admin cannot log in as long as the plugin is active. Deactivate the plugin to disable the maintainance mode.
Version: 0.1
Author: Tobias Baldauf
Author URI: http://www.tobias-baldauf.de/
*/

/*###########################################
* Configuration options
*/###########################################

// Define how long the interval (in seconds) is from activation of plugin to kicking out logged-in users
$logoutinterval_seconds = 300;


/*###########################################
* Main hooks & actions
*/###########################################

// Hook upon plugin activation
register_activation_hook( __FILE__, 'lockdown_maintainance_timer_activate' );

// Hook upon plugin deactivation
register_deactivation_hook( __FILE__, 'lockdown_maintainance_timer_deactivate' );

// Register actions to perform the check if the logout-time has come 
add_action('get_header', 'lockdown_maintainance_timer_processOnPageLoad', 1 );
add_action('admin_init', 'lockdown_maintainance_timer_processOnPageLoad', 1 );

// Hook up the messaging-system to notify all users of their impending doom
add_action('admin_notices', 'showAdminMessages');


/*###########################################
* Functions
*/###########################################

// Define the logout interval in seconds and set the marker containing the current time on activation 
function lockdown_maintainance_timer_activate() {
	global $logoutinterval_seconds;
	add_option( 'lockdown_maintainance_timer_logoutinterval', $logoutinterval_seconds );
	lockdown_maintainance_timer_setmarker();
}

// Simple deactivation hook to unset the logout interval
function lockdown_maintainance_timer_deactivate() {
	delete_option( 'lockdown_maintainance_timer_logoutinterval' );
}

// Sets the current time in a marker upon activation
function lockdown_maintainance_timer_setmarker() {
	if( is_user_logged_in() ) {
		update_usermeta( get_current_user_id(), 'lockdown_maintainance_timer_marker', time() );
	}
}

// Upon activity by the user (page loads within WP) we check if the logout time has arrived & kick the user accordingly
function lockdown_maintainance_timer_processOnPageLoad() {
	if( is_user_logged_in() ) {
		$marker = lockdown_maintainance_timer_getmarker();
		$logoutinterval = get_option( 'lockdown_maintainance_timer_logoutinterval' );
		if( $marker + $logoutinterval < time() && !current_user_can('administrator') ) {
			wp_logout();
			wp_die('<div id="message" class="updated"><p><b>Wartungsarbeiten</b><br>Wir aktualisieren das System. In K&uuml;rze wird alles wieder verf&uuml;gbar sein.</p></div>');
		}
	}
}

// Read the set time-marker
function lockdown_maintainance_timer_getmarker() {
	if (is_user_logged_in()) {
		return (int) get_usermeta( get_current_user_id(), 'lockdown_maintainance_timer_marker' );
	} else {
		return 0;
	}
}

// Simple function to show messages to logged-in users
function showMessage($message, $errormsg = false) {
	if ($errormsg) {
		echo '<div id="message" class="error">';
	} else {
		echo '<div id="message" class="updated fade">';
	}
	echo "<p><strong>$message</strong></p></div>";
} 

// Define the message to show. 2nd boalean parameter defines regular or error message style 
function showAdminMessages() {
	
	showMessage("In Kürze erfolgen Wartungsarbeiten am System!<br>Speichern Sie alle Änderungen und <a href=\"".wp_logout_url()."\">loggen Sie sich jetzt aus</a>.", true);
}
