<?php
/**
 * Mollie RCP Helper functions
 * Author: Sander de Wijs
 * Date: 2-1-2016
 * Time: 16:12
 */

if(!class_exists('Mollie_API_Autoloader')) {
	require_once( 'Mollie/API/Autoloader.php' );
}

/**
 * Connect to Mollie via the API
 * @return Mollie_API_Client|null|string
 * @since 2.0
 */
function mollieApiConnect()
{
	$options = get_option('rcp_mollie_settings');

	if (NULL !== $options['rcp_mollie_test_api_field'] && NULL !== $options['rcp_mollie_live_api_field']) {
		$mollie = NULL;
		if ($options['rcp_mollie_test_modus'] === '1' ) {
			$mollie_api = $options['rcp_mollie_test_api_field'];
		} else {
			$mollie_api = $options['rcp_mollie_live_api_field'];
		}

		try {
			$mollie = new Mollie_API_Client;
		} catch (Mollie_API_Exception $e) {
			echo "API call failed: " . htmlspecialchars($e->getMessage());
		}

		try {
			$mollie->setApiKey($mollie_api);
		} catch (Mollie_API_Exception $e) {
			echo "<p>" . "API call failed: " . htmlspecialchars($e->getMessage()) . "</p>";
		}
	} else {
		$mollie = 'No valid API keys set';
	}
	return $mollie;
}

/**
 * Validate if API keys are set
 * @return bool
 * @since 1.2
 */
if(!function_exists( 'rcp_validate_mollie_gateway')) {
	/**
	 * @return bool
	 */
	function rcp_validate_mollie_gateway()
	{
		$options = get_option('rcp_mollie_settings');
		$validation = false;
		if (isset($options['rcp_mollie_test_api_field']) && isset($options['rcp_mollie_live_api_field'])) {
			$validation = true;
		}
		return $validation;
	}
}

/**
 * Validate Mollie API keys to prevent errors in settings page
 * @param string $mollie_api
 * @return string $api_check
 * @since 1.2
 */
if(!function_exists( 'rcp_mollie_fields_validate')) {
	/**
	 * @param $mollie_api
	 *
	 * @return string
	 */
	function rcp_mollie_fields_validate($mollie_api)
	{
		$mollie_api = htmlspecialchars($mollie_api);
		if (strpos($mollie_api, 'test_') === 0 || strpos($mollie_api, 'live_') === 0) {
			$api_check = 'valid';
		} else {
			$api_check = 'invalid';
		}
		return $api_check;
	}
}

/**
 * Load active Mollie payment methods in RCP
 * @param array $gateways
 * @return $gateways|boolean
 * @since 2.0
 */
if(!function_exists( 'rcp_load_mollie_gateways')) {
	/**
	 * @param array $gateways
	 *
	 * @return array $gateways
	 */
	function rcp_load_mollie_gateways($gateways) {

		/**
		 * Unset RCP PayPal and creditcard gateways
		 */

		$options = get_option( 'rcp_mollie_settings' );
		if(isset($options['mollie_gateways'])) {
			$methods = $options['mollie_gateways'];

			foreach ($methods as $method) {
				$gateway = $method['id'] . '_mollie';

				$label = $method['label'];
				$admin_label = $method['admin_label'];

				$gateways[$gateway] = array(
					'label' => $label,
					'admin_label' => $admin_label,
					'class' => 'RCP_Payment_Gateway_Mollie'
				);
			}
			return $gateways;
		}
		return $gateways;
	}
	add_filter('rcp_payment_gateways','rcp_load_mollie_gateways');
}



/**
 * Determine if a member is a Mollie Customer
 *
 * @since       2.4
 * @access      public
 * @param       $user_id INT the ID of the user to check
 * @return      bool
 */
function rcp_is_mollie_subscriber( $user_id = 0 ) {

	if( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	$ret = false;

	$member = new RCP_Member( $user_id );

	$profile_id = $member->get_payment_profile_id();

	// Check if the member is a Stripe customer
	if( false !== strpos( $profile_id, 'tr_' ) ) {

		$ret = true;

	}

	return (bool) apply_filters( 'rcp_is_mollie_subscriber', $ret, $user_id );
}