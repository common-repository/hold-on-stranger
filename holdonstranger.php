<?php
/**
 * Hold, on Stranger! plugin for Wordpress.
 *
 * @package Holdonstranger
 *
 * Plugin Name: Hold, on Stranger!
 * Plugin URI: https://holdonstranger.com/support/cms-wordpress
 * Description: Adds the Hold, on Stranger! popups to your website.
 * Version: 1.0
 * Author: K2C.
 * Author URI: https://holdonstranger.com
 * License: GPLv2 or later
 *
 * Hold, on Stranger! is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.

 * Hold, on Stranger! is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Hold, on Stranger!. If not, see
 * https://www.gnu.org/licenses/gpl-2.0.html.
 */

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
define( 'HOS_DOMAIN_URL', 'https://holdonstranger.com' );
define( 'HOS_SERVICE_NAME', 'Hold on, Stranger!' );

add_action( 'admin_menu', 'k2c_holdonstranger_admin_menu' );
add_action( 'admin_notices', 'k2c_holdonstranger_admin_notice' );
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'k2c_holdonstranger_add_actions_link' );
add_action( 'init', 'k2c_holdonstranger_load_script' );
add_action( 'plugins_loaded', 'k2c_holdonstranger_load_textdomain' );

/**
 * Create the holdonstranger settings menu.
 */
function k2c_holdonstranger_admin_menu() {
	global $k2c_holdonstranger_settings_page, $useWoo;

	$useWoo = in_array('woocommerce/woocommerce.php',
	apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) );

	$k2c_holdonstranger_settings_page =
	add_options_page(HOS_SERVICE_NAME,
		HOS_SERVICE_NAME,
		'manage_options',
		'k2c_holdonstranger_menu',
		'k2c_holdonstranger_settings_page'
	);
	add_action( 'admin_init', 'k2c_holdonstranger_register_settings' );
}

/**
 * Loads the plugin translations.
 */
function k2c_holdonstranger_load_textdomain() {
	load_plugin_textdomain( 'holdonstranger', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );
}

/**
 * Implements hook admin_notice().
 */
function k2c_holdonstranger_admin_notice() {
	$k2c_options = get_option( 'k2c_holdonstranger' );
	if ( empty( $k2c_options['public_key'] ) ) {
		$settings_page = admin_url( 'options-general.php?page=k2c_holdonstranger_menu' );
		$message = sprintf( __( 'Your %s widget is disabled. Please go to the <a href="%s">plugin settings</a> and enter your API key to activate it.', 'holdonstranger' ), HOS_SERVICE_NAME, $settings_page );
		$signup_message = __( 'Don\'t have an account yet? ', 'holdonstranger' )
						 . sprintf( '<a href="%s/signup" target="_blank">', HOS_DOMAIN_URL )
						 . sprintf( __( 'Try %s Free', 'holdonstranger' ), HOS_SERVICE_NAME )
						 . '</a>';
		// @codingStandardsIgnoreStart
		echo ('<div class="error"><p><strong>' . $message . '<br>' . $signup_message . '</strong></p></div>');
		// @codingStandardsIgnoreEnd
	}
}

/**
 * Add settings link on plugin page.
 * @param array $links Settings links.
 */
function k2c_holdonstranger_add_actions_link( $links ) {
	$settings_link = sprintf('<a href="%s">%s</a>',  admin_url( 'options-general.php?page=k2c_holdonstranger_menu' ),
	__( 'Settings' ));
	array_unshift( $links, $settings_link );
	return $links;
}

/**
 * Register holdonstranger settings.
 */
function k2c_holdonstranger_register_settings() {
	register_setting('k2c_settings_options',
		'k2c_holdonstranger',
		'k2c_holdonstranger_validate'
	);
}

/**
 * Renders the holdonstranger settings page
 */
function k2c_holdonstranger_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'You do not have sufficient permissions to access this page.' );
	}
	$hos_options = k2c_holdonstranger_get_options();
	if ( $hos_options['enable'] ) {
		$message = sprintf( __( 'Your %s widget is enabled.', 'holdonstranger' ), HOS_SERVICE_NAME );
	} else {
	 	$message = sprintf( __( 'Please enter your %s API key in the form bellow to activate the widget. If you don\'t know where to find it, click the "Get your API key" button.', 'holdonstranger' ), HOS_SERVICE_NAME );
	}
?>
<div class="wrap">
	<div id="icon-plugins" class="icon32"></div>
    <h2><?php echo esc_html( HOS_SERVICE_NAME ) ?></h2>
	<?php echo '<p>' . esc_html( $message ) . '<p>' ?>
    <form method="post" action="options.php">
        <?php
		settings_fields( 'k2c_settings_options' );
		do_settings_sections( 'k2c_settings_options' );

		if ( ! (bool) $hos_options['enable'] ) {
			k2c_holdonstranger_form_fields( $hos_options );
			submit_button();
		} else {
			submit_button( __( 'Reset settings', 'holdonstranger' ), 'delete' );
		}
		?>
    </form>
</div>
<?php
} // End k2c_holdonstranger_settings_page.

/**
 * Get k2c_holdonstranger options.
 */
function k2c_holdonstranger_get_options() {
	$hos_options = get_option( 'k2c_holdonstranger', array(
		'enable' => 0,
		'public_key' => '',
		)
	);
	return $hos_options;
}

/**
 * Render the holdonstranger form fields.
 *
 * @param array $hos_options The holdonstranger configure options.
 */
function k2c_holdonstranger_form_fields( $hos_options ) {
	$qs = '/apikey?cms=wordpress&store=' . urlencode( get_site_url() );
	$enable = ((int)$hos_options['enable']) ? 0 : 1;
?>
	<table class="form-table">
		<input type="hidden" name="k2c_holdonstranger[enable]" 
				value="<?php echo esc_html((int) $enable) ?>"/>
		<tr valign="top">
			<th scope="row"><?php esc_html_e( 'API key', 'holdonstranger' ) ?></th>
			<td><input type="text" size="36" name="k2c_holdonstranger[public_key]" 
				value="<?php echo esc_html( $hos_options['public_key'] )?>"/></td>
			<td>
				<a class="button" href="<?php echo esc_url( HOS_DOMAIN_URL . $qs )?>" 
					target="_blank"><?php esc_html_e( 'Get your API key', 'holdonstranger' )?></a>
			</td>
		</tr>
	</table>
<?php
}

/**
 * Validate holdonstranger options
 * @param array $input The input fields of the settings form.
 */
function k2c_holdonstranger_validate( $input ) {
	$api_key_re = '/^[a-fA-F0-9]{8}-[a-fA-F0-9]{4}-[a-fA-F0-9]{4}-[a-fA-F0-9]{4}-[a-fA-F0-9]{12}$/';
	$hos_options = k2c_holdonstranger_get_options();
	$valid = array();

	if ( $hos_options['enable'] || empty( $input['public_key'] ) ) {
		$valid['enable'] = 0;
		$valid['public_key'] = '';
	} else {
		if ( preg_match( $api_key_re, $input['public_key'] ) ) {
			$valid = k2c_holdonstranger_activate_widget( $input );
		} else {
			add_settings_error(
				'public_key',
				'k2c_holdonstranger_api_key_error',
				__( 'The provided API Key is not recognized.', 'holdonstranger' ),
				'error'
			);
		}
	}
	return $valid;

} // End k2c_holdonstranger_validate.

/**
 * Request for the holdonstranger public key.
 *
 * @param array $input The input fields of the settings form.
 */
function k2c_holdonstranger_activate_widget( $input ) {
	// Authorize.
	global $useWoo;

	$cms = $useWoo ? 'woocommerce' : 'wordpress';
	$storeUrl = urlencode( get_site_url() );
	$pk = urlencode( $input['public_key'] );

	$url = HOS_DOMAIN_URL . "/add-site?cms=$cms&store={$storeUrl}&pk={$pk}";

	$response = wp_remote_get( $url, array( 'timeout' => 45 ) );
	if ( is_array( $response ) ) {
		if ( 200 === $response['response']['code'] ) {
			$input['enable'] = 1; // Activate.
			return $input; // Done.
		} else {
			$error_message = '';
			if ( 400 === $response['response']['code'] ) {
				$error_message = __( 'Invalid API key', 'holdonstranger' );
			} else {
				$error_message = __( 'Could not validate your API key. Please try again later!', 'holdonstranger' );
			}
			add_settings_error(
				'k2c_holdonstranger_public_key',
				'k2c_holdonstranger_auth_error',
				$error_message,
				'error'
			);
		}
	}
	return $input;
}

/**
 * Laod the holdonstranger Javascript snippet.
 */
function k2c_holdonstranger_load_script() {
	$k2c_hos_options = get_option( 'k2c_holdonstranger', false );

	if ( $k2c_hos_options && $k2c_hos_options['enable'] ) {

		/* Check if WooCommerce is active */
		if ( in_array('woocommerce/woocommerce.php',
		apply_filters( 'active_plugins', get_option( 'active_plugins' ) )) ) {
			include 'holdonstranger-woocommerce.php';
		} else {
			include 'holdonstranger-wordpress.php';
		}
		k2c_holdonstranger_popup_wrapper();
	}
}
?>
