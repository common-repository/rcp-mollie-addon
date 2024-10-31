<?php
/**
Plugin Name: Mollie addon for Restrict Content Pro
Plugin URI: https://www.degrinthorst.nl/downloads/mollie-rcp-plugin
Description: An addon which adds the Mollie payments gateway to Restrict Content Pro
Version: 2.3.8.1
Author: Sander de Wijs
Author URI: https://www.degrinthorst.nl
License: GPL2
 */

// Prevent this file from being called directly
if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

// Make sure this plugin is loaded after RCP!
add_action( 'activated_plugin', 'rcp_mollie_load_first' );
function rcp_mollie_load_first()
{
	$path = str_replace( WP_PLUGIN_DIR . '/', '', __FILE__ );
	if ( $plugins = get_option( 'active_plugins' ) ) {
		if ( $key = array_search( $path, $plugins ) ) {
			array_splice( $plugins, $key, 1 );
			array_push( $plugins, $path );
			update_option( 'active_plugins', $plugins );
		}
	}
}

add_action('admin_menu', 'add_rcp_mollie_options_page');
add_action('admin_init', 'rcp_mollie_settings_init');
add_action('admin_enqueue_scripts', 'rcp_mollie_scripts_styles');
add_action('plugins_loaded', 'load_rcp_mollie_classes');

// RCP Mollie settings
require_once( 'inc/rcp-mollie-settings.php' );

$options = get_option('rcp_mollie_settings');

if(!isset($options['rcp_mollie_hide_extra_userfields']) || $options['rcp_mollie_hide_extra_userfields'] === 0 ) {
	// RCP Mollie custom user fields
	require_once( 'inc/helpers/user-fields.php' );
}

// Function for loading RCP classes after plugins loaded
function load_rcp_mollie_classes() {
	require_once('inc/gateway/class-rcp-payment-gateway-mollie.php');
}

/**
 * Output an infobox with status message when user returns to the subscription details page after redirect
 * This is nessesary because the status of an ondemand payment is pending by default and gets updated later.
 * Thus the subscription cannot be activated directly after creating an ondemand payment.
 *
 * @param string $content
 * @return string $content
 */
function rcp_mollie_callout($content) {
	if(isset($_GET['rcp_mollie_mss']) && has_shortcode($content, 'subscription_details') || isset($_GET['rcp_mollie_mss']) && has_shortcode($content, 'rcp_profile_editor')) {
		$callout = '<div data-alert class="alert-box info radius">'. urldecode(sanitize_text_field($_GET['rcp_mollie_mss'])) . '</div>';
		$callout .= $content;
		return $callout;
	} else if(isset($_GET['rcp_mollie_mss'])) {
		$callout = '<div style="background-color: orangered;padding: .5rem 1rem;font-weight: bold;color: white;border-radius: 5px;margin-bottom: 1.5em;"><p>Error: '. urldecode(sanitize_text_field($_GET['rcp_mollie_mss'])) . '</p></div>';
		$callout .= $content;
		return $callout;
	} else {
		return $content;
	}
}
add_filter('the_content', 'rcp_mollie_callout');

/**
 * Store Mollie Payment methods in the database
 * to minimize API calls. Update payment methods only when RCP MOllie settings
 * are saved.
 *
 * @param $gateways
 * @since 2.0
 */
function rcp_load_mollie_methods($gateways)
{

	$options = get_option('rcp_mollie_settings');
	if(!$options || !is_array( $options)) {
		return;
	}
	$mollie_gateways = array();
	$options['mollie_gateways'][] = $mollie_gateways;
	$methods = $gateways;

	foreach ($methods as $method) {
		$gateway = json_decode(json_encode($method->id), true);
		$label = json_decode(json_encode($method->description), true);
		$admin_label = json_decode(json_encode($method->description), true) . ' (Mollie)';
		$img = htmlspecialchars($method->image->normal);

		$mollie_gateways[$gateway] = array(
			'id'    => $gateway,
			'label' => $label,
			'admin_label' => $admin_label,
			'image' => $img,
			'class' => 'RCP_Payment_Gateway_Mollie',
		);
	}
	$options['mollie_gateways'] = $mollie_gateways;
	update_option('rcp_mollie_settings', $options);
}
add_action( 'wp_ajax_rcpMollieGatewayUpdate', 'rcpMollieGatewayUpdate' );

/**
 * Hooks into the rcp_process_member_cancellation action.
 * Cancels the users subscription via the Mollie_Subscriptions_API
 */
function cancel_mollie_subscription() {
	$mollie_client = mollieApiConnect();

	// Get current user's Mollie Subscription ID
	$mollie_subscriptionId  = get_user_meta(get_current_user_id(), '_mollie_subscription_id', true);
	$mollie_client_id       = get_user_meta(get_current_user_id(), '_mollie_customer_id', true);
	$cancel = $mollie_client->customers_subscriptions->withParentId($mollie_client_id)->delete($mollie_subscriptionId);
	$cancelled_user = new RCP_Member(get_current_user_id());
	if($cancel->status == "cancelled") {
		$cancelled_user->add_note("Mollie membership cancelled by user");
	}
}

add_action('rcp_process_member_cancellation', 'cancel_mollie_subscription');

/**
 * Allow members to cancel their recurring membership
 */

function rcp_mollie_members_can_cancel($user_id, $ret) {
	$ret = false;
	$member = new RCP_Member(get_current_user_id());

	$profile_id = $member->get_payment_profile_id();
	// Check if customer has a recurring membership with Mollie
	if( false !== strpos( $profile_id, 'tr_' ) && $member->get_status() !== 'cancelled' ) {
		$ret = true;
	}
	return $ret;
}

add_filter( 'rcp_member_can_cancel', 'rcp_mollie_members_can_cancel', 10, 2);

/**
 * Cancel member subscription via the Mollie API
 */
//
function rcp_mollie_cancel_membership() {

	if( ! isset( $_GET["rcp-action"] ) || $_GET["rcp-action"] !== 'cancel' ) {
		return;
	}

	$user_id = sanitize_text_field($_GET["member-id"]);

	if( ! is_user_logged_in() ) {
		return;
	}

	if(!rcp_is_mollie_subscriber($user_id)) {
		return;
	}

	if( wp_verify_nonce( $_GET['_wpnonce'], 'cancel' ) ) {
		global $rcp_options;

		$redirect = remove_query_arg( array( 'rcp-action', '_wpnonce', 'member-id' ), rcp_get_current_url() );

		$mollie = mollieApiConnect();
		$member = new RCP_Member($user_id);

		// Revoke active mandates
		$mandates = $mollie->customers_mandates->withParentId(get_user_meta($user_id, '_mollie_customer_id', true))->all();
		$found_mandates = $mandates->data;

		if($mandates->data === []) {
			$member->cancel();

			$redirect = add_query_arg( 'profile', 'cancelled', $redirect );
			$member->add_note('Mollie Customer Subscription cancelled');
			return;
		}

		// If an active mandate is found, revoke it and cancel the membership
		foreach ($found_mandates as $found_mandate) {
			if($found_mandate->status === 'valid') {
				$mollie->customers_mandates->delete($found_mandate->id);
				update_user_meta($user_id, '_mollie_customer_mandate', "");
				$member->cancel();

				$redirect = add_query_arg( 'profile', 'cancelled', $redirect );
				$member->add_note('Mollie Customer Subscription cancelled');
			}
		}
		wp_redirect( $redirect . '?rcp_mollie_mss=' . urlencode($mandates->data) ); exit;
	}
}

add_action('init', 'rcp_mollie_cancel_membership');

/**
 * Process Ajax call from the settings page. Update Mollie API keys, active gateways
 * and other settings and save them in the database.
 */
function rcpMollieGatewayUpdate() {
	$options = array();

	if(isset( $_POST['molliercpUpdateOptions_nonce']) && wp_verify_nonce( $_POST['molliercpUpdateOptions_nonce'], 'molliercpUpdateOptions_html' )) {
		if(isset($_POST['rcp_mollie_live_api_field'])) {
			$mollie_live_api = sanitize_text_field($_POST['rcp_mollie_live_api_field']);
		} else {
			$mollie_live_api = "";
		}
		$options[ 'rcp_mollie_live_api_field' ] = $mollie_live_api;

		if(isset($_POST['rcp_mollie_test_api_field'])) {
			$mollie_test_api = sanitize_text_field($_POST['rcp_mollie_test_api_field']);
		} else {
			$mollie_test_api = "";
		}
		$options['rcp_mollie_test_api_field'] = $mollie_test_api;

		if(isset($_POST['rcp_mollie_recurring_payments_field'])) {
			$mollie_recurring_payments = sanitize_text_field($_POST['rcp_mollie_recurring_payments_field']);

			// Search for comma's in amount and replace
			if(false !== strpos($mollie_recurring_payments, ',')) {
				$mollie_recurring_payments = str_replace(',', '.', $mollie_recurring_payments);
			}

			// Search for Euro symbol and remove
			if(false !== strpos($mollie_recurring_payments, '€')) {
				$mollie_recurring_payments = str_replace('€', '', $mollie_recurring_payments);
			}

			// Remove any remaining whitespace
			$mollie_recurring_payments = str_replace(' ', '', $mollie_recurring_payments);

		} else {
			$mollie_recurring_payments = "";
		}
		$options['rcp_mollie_recurring_payments_field'] = $mollie_recurring_payments;

		if(isset($_POST['rcp_mollie_test_modus'])) {
			$rcp_mollie_test_mode = htmlspecialchars($_POST['rcp_mollie_test_modus']);
		} else {
			$rcp_mollie_test_mode = false;
		}
		$options['rcp_mollie_test_modus'] = $rcp_mollie_test_mode;

		if(isset($_POST['rcp_mollie_hide_extra_userfields'])) {
			$rcp_mollie_hide_userfields = htmlspecialchars($_POST['rcp_mollie_hide_extra_userfields']);
		} else {
			$rcp_mollie_hide_userfields = 0;
		}
		$options['rcp_mollie_hide_extra_userfields'] = $rcp_mollie_hide_userfields;

		if(isset($_POST['rcp_mollie_fixr_api'])) {
			$options['rcp_mollie_fixr_api'] = sanitize_text_field($_POST['rcp_mollie_fixr_api']);
		} else {
			$options['rcp_mollie_fixr_api'] = false;
		}

		update_option('rcp_mollie_settings', $options);
		$mollie_connect = mollieApiConnect();

		// Get enabled payment methods from Mollie
		try {
			$methods = $mollie_connect->methods->all();
		} catch (Mollie_API_Exception $e) {
			echo "API call failed: " . htmlspecialchars($e->getMessage());
		}
		if(!empty($methods)) {
			rcp_load_mollie_methods($methods);
			$response = 2;
		} else {
			$response = 0;
		}

		echo json_encode($response);
		wp_die();
	} else {
		return;
	}
}

/**
 * Upgrade notice message
 */

function upgrade_notice_rcp_mollie() {
	if(get_transient('disable_rcp_mollie_admin_notice') === 'disabled') {
		return;
	}
	?>
    <div class="notice notice-warning rcp-mollie-notice is-dismissible">
        <p><?php _e( 'Let op! Na deze update van RCP-Mollie is het nodig om je betaalmethodes opnieuw te activeren. Ga hiervoor naar de <a href="/wp-admin/admin.php?page=rcp-settings#payments">Restrict Content Pro instellingen</a>', 'rcp' ); ?></p>
    </div>
	<?php
}
add_action( 'admin_notices', 'upgrade_notice_rcp_mollie' );

/**
 * Dismiss admin notice and keep it hidden for at least a year
 */
function set_rcp_mollie_notice_cookie()
{
	set_transient( 'disable_rcp_mollie_admin_notice', 'disabled', 12 * MONTH_IN_SECONDS );
}
add_action('wp_ajax_set_rcp_mollie_notice_cookie', 'set_rcp_mollie_notice_cookie');
add_action('wp_ajax_nopriv_set_rcp_mollie_notice_cookie', 'set_rcp_mollie_notice_cookie');