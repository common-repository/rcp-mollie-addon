<?php
/**
 * Payment Gateway Mollie Class
 *
 * @package     RCP Mollie
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.0
 */

if (!class_exists('RCP_Payment_Gateway')) {
	return;
}

/**
 * Register the gateway
 *
 * @since 2.1
 */
class RCP_Payment_Gateway_Mollie extends RCP_Payment_Gateway
{

	/**
	 * @var string
	 */
	private $mollie_test_api;

	/**
	 * @var string
	 */
	private $mollie_live_api;

	/**
	 * @var array
	 */
	private $mollie_options;

	/**
	 * @var Mollie_API_Client|string|null
	 */
	private $mollie_client;

	/**
	 * @var boolean
	 */
	private $valid;

	/**
	 * @var array
	 */
	private $mollie_payment_methods;

	/**
	 * @var WP_User|string
	 */
	public $user;

	/**
	 * @var Mollie_API_Object_Customer
	 */
	public $MollieCustomer;

	/**
	 * @var string
	 */
	public $MollieCustomerID;

	/**
	 * @var string
	 */
	public $webhookUrl;

	/**
	 * @var array
	 */
	public $supportsRecurring;

	/**
	 * @var string
	 */
	protected $fixrKey;

	/**
	 * Get things going
	 *
	 * @since 2.1
	 */
	public function init()
	{
		global $rcp_options;

		$this->mollie_options = get_option('rcp_mollie_settings');
		$this->mollie_test_api = $this->mollie_options['rcp_mollie_test_api_field'];
		$this->mollie_live_api = $this->mollie_options['rcp_mollie_live_api_field'];
		$this->fixrKey = $this->mollie_options['rcp_mollie_fixr_api'];

		$this->webhookUrl = add_query_arg('listener', 'mollie', home_url());

		$this->mollie_payment_methods = $this->mollie_options['mollie_gateways'];
		$this->supportsRecurring = array('ideal_mollie', 'mistercash_mollie', 'belfius_mollie', 'sofort_mollie', 'creditcard_mollie', 'paypal_mollie');

		$this->supports[] = 'one-time';
		$this->supports[] = 'fees';

		if(isset($this->mollie_options['rcp_mollie_recurring_payments_field']) && !empty( $this->mollie_options['rcp_mollie_recurring_payments_field'] )) {
			$this->supports[] = 'recurring';
		}

		$this->valid = isset($this->mollie_live_api) && isset($this->mollie_test_api) ? true : false;
		$this->test_mode = ($this->mollie_options['rcp_mollie_test_modus'] == 1);
		if ($this->valid) {
			$this->mollie_client = mollieApiConnect();
		}
	}

	/**
	 * Process registration
	 *
	 * @since 2.1
	 */
	public function process_signup()
	{
		$amount = $this->getSubscriptionAmount();

		/**
		 * If the Fixr API key is not set and the currency is not in Euros,
		 * abort the payment otherwise non-euro amount would be passed to mollie as Euro's
		 */
		if(!$amount) {
			wp_redirect( $this->return_url . '?rcp_mollie_mss=' . urlencode("This gateway only supports Euros, please select another payment method or contact support.") );
			exit;
		}

		if (!$this->valid) {
			return;
		}

		if (!isset($_POST['rcp_gateway'])) {
			$gateway = 'ideal';
		} else {
			$gateway = str_replace('_mollie', '', sanitize_text_field($_POST['rcp_gateway']));
		}

		if ($this->auto_renew && in_array(sanitize_text_field($_POST['rcp_gateway']), $this->supportsRecurring)) {
			// First time recurring payment
			// Only process recurring payments for methods that support it

			/**
			 * Get a Mollie Customer ID via the Mollie_Customer_API
			 * only for new members
			 */
			if(!$this->getMollieCustomerId($this->user_id)) {
				try {
					$this->MollieCustomer = $this->mollie_client->customers->create(
						array(
							'name' => $this->user_name,
							'email' => $this->email,
						)
					);
				} catch (Mollie_API_Exception $e) {
					echo "API call failed: " . htmlspecialchars($e->getMessage());
				}
				$this->setMollieCustomerId($this->MollieCustomer->id, $this->user_id);
			}

			// Check if the user has an exsisting mandate, if so, check if valid and process the full order amount. No test payment is needed.
			if($this->getCustomerMandate($this->user_id) !== false) {
				$mandates = $this->mollie_client->customers_mandates
					->withParentId($this->getMollieCustomerId($this->user_id))
					->all();

				if($this->checkMandates($mandates) !== false) {
					// If the customer has a valid mandate, process the ondemand payment
					$member = new RCP_Member($this->user_id);

					// Cancel any existing memberships
					if( $member->just_upgraded() && rcp_can_member_cancel( $member->ID ) ) {
						$cancelled = rcp_cancel_member_payment_profile( $member->ID, false );
					}
					try {
						// If a member has an exsisting membership, use the initial amount to
						// account for possible prorated credit. If the prorated amount is negative,
						// set the amount to 0, because refunds for processed transactions are not possible at Mollie.
						$recurring_payment = $this->mollie_client
							->customers_subscriptions
							->withParentId( $this->getMollieCustomerId( $this->user_id ))
							->create([
								// Change amount into subscription amount
								'amount'        => $amount,
								'customerId'    => $this->getMollieCustomerId($this->user_id),
								'interval'      => $this->pluralizeTimeUnit( $this->length, $this->length_unit),       // important
								'description'   => $this->subscription_name . ' | Doorlopend abonnement',
								'webhookUrl'    => $this->webhookUrl,
							]);
						$member->set_payment_profile_id($recurring_payment->id);
						$member->add_note('New subscription created for user on checkout');
						$this->renew_member($this->auto_renew);

						// If the member doesn't need to checkout because of valid mandate, and the account is still active, there's nothing to do here
						wp_redirect($this->return_url);
						exit;

					} catch (Mollie_API_Exception $e) {
						echo "API call failed: " . htmlspecialchars($e->getMessage());
						wp_die();
					}
				}
			}

			// Create the first payment in which the customer gives permission
			// for ondemand payments. This creates a customer mandate.
			try {
				$payment = $this->mollie_client->payments->create(
					array(
						'amount' => $this->mollie_options['rcp_mollie_recurring_payments_field'],
						'customerId' => $this->getMollieCustomerId($this->user_id),
						'recurringType' => Mollie_API_Object_Payment::RECURRINGTYPE_FIRST,
						'description' => 'Eerste betaling',
						'redirectUrl' => $this->return_url,
						'webhookUrl' => $this->webhookUrl,
						'method' => $gateway,
						'metadata' => array(
							'subscriptionAmount' => $amount,
							'user_id' => $this->user_id,
							'item_number' => $this->subscription_key,
							'item_name' => $this->subscription_name,
							'interval' => $this->pluralizeTimeUnit( $this->length, $this->length_unit),
							'initialAmount' => $this->amount,
						)
					)
				);

				$redirect_to_mollie = $payment->getPaymentUrl();

				wp_redirect($redirect_to_mollie);
				exit;

			} catch (Mollie_API_Exception $e) {
				echo "API call failed: " . htmlspecialchars($e->getMessage());
			}

		} else {

			try {
				// Payment object for Mollie Payment Processing
				$payment = $this->mollie_client->payments->create(array(
					'amount' => $amount,
					'description' => $this->subscription_name,
					'redirectUrl' => $this->return_url,
					'webhookUrl' => $this->webhookUrl,
					'method' => $gateway,
					'metadata' => array(
						'user_id' => $this->user_id,
						'item_number' => $this->subscription_key,
						'item_name' => $this->subscription_name,
						'discount_code' => $this->discount_code,
						'payment_type' => 'Mollie',
						'date' => date('Y-m-d g:i:s', time()),
						'initialAmount' => $this->initial_amount,
					)
				));

				$redirect_to_mollie = $payment->getPaymentUrl();

				wp_redirect($redirect_to_mollie);
				exit;

			} catch (Mollie_API_Exception $e) {
				echo "API call failed: " . htmlspecialchars($e->getMessage());
			}
		}

		wp_redirect('/');
		exit;

	}

	/**
	 * Process webhooks
	 *
	 * @since 2.1
	 */
	public function process_webhooks()
	{

		if (!isset($_POST['id'])) {
			return;
		} else {
			$mollie_id = sanitize_text_field($_POST['id']);
		}

		$payment = $this->mollie_client->payments->get($mollie_id);

		// Create a payment object
		$mollie_payment = new RCP_Payments();
		$this->user_id = $payment->metadata->user_id;
		$member = new RCP_Member($this->user_id);

		// Check if webhook call is for a one time payment
		if (!isset($payment->recurringType) && !isset($payment->subscriptionId)) {
			$payment_data = array(
				'date' => date('Y-m-d g:i:s', time()),
				'subscription' => $payment->description,
				'payment_type' => 'Mollie',
				'subscription_key' => $payment->metadata->item_number,
				'amount' => $payment->metadata->initialAmount,
				'user_id' => $payment->metadata->user_id,
				'transaction_id' => $this->generate_transaction_id(),
			);

			switch ($payment->status) :

				case "paid" :
					// Check if this webhook call is a retry for a processed payment
					if($member->get_payment_profile_id() === $mollie_id) {
						die('All done here, payment was already processed');
					}
					// record this payment in the database
					$payment_id = $mollie_payment->insert($payment_data);
					$mollie_payment->add_meta( $payment_id, 'mollie_transaction_id', $mollie_id);

					$member->set_payment_profile_id($mollie_id);

					if (!isset($rcp_options['disable_new_user_notices'])) {
						// send welcome email here
						wp_new_user_notification($payment->metadata->user_id);
					}

					die('successful payment');

					break;

				case "cancelled" :
					die('successful payment_profile_cancel');

					break;

				case "expired" :
					// write_log('Status is Expired');
					die('successful payment_profile_expired');

					break;

			endswitch;
			die;
		} else if(isset($payment->recurringType) && $payment->recurringType == "first") {
			$payments = rcp_get_user_payments($payment->metadata->user_id);

			// Prevent processing payment again for webhook call
			foreach ($payments as $payment) {
				if($payment->amount == $this->mollie_options['rcp_mollie_recurring_payments_field']);
				die('Payment already recieved!');
			}

			// Check if webhook call is for a first recurring payment
			// Double check if the first payment amount is similar to amount in the payment
			if ($payment->amount !== $this->mollie_options['rcp_mollie_recurring_payments_field']) {
				die;
			}

			// Create ondemand payment via Mollie_Recurring_API
			// Check if the first payment has been processed, and client has given consent for recurring payments
			if ($payment->status !== "paid") {
				$member->add_note('First payment not completed, no user mandate for creating a subscription. Subscription creation cancelled');
				$member->cancel();
				die;
			}

			// Lookup the mandate and store it in the database
			$mandates = $this->mollie_client->customers_mandates
				->withParentId($this->getMollieCustomerId($payment->metadata->user_id))
				->all();

			// Check for valid mandates
			$check_mandates = $this->checkMandates($mandates);
			if(!$check_mandates) {
				$member->add_note('No valid mandates found');
				die('No valid mandates found');
			}

			// Update the mandate in the user's profile
			$this->setCustomerMandate($check_mandates, $payment->metadata->user_id);

			$member->add_note("First payment accepted by customer. New client ondemand payment was created");

			// Prepare RCP payment data
			$payment_data = array(
				'subscription' => $payment->description,
				'date' => date('Y-m-d g:i:s', time()),
				'amount' => $payment->amount,
				'user_id' => $payment->metadata->user_id,
				'payment_type' => 'Mollie',
				'subscription_key' => $payment->metadata->item_number,
				'transaction_id' => $this->generate_transaction_id(),
				'status' => 'complete',
			);

			$payment_id = $mollie_payment->insert($payment_data);

			$member->set_payment_profile_id($mollie_id);

			$this->renew_member(true);

			$mollie_payment->add_meta( $payment_id, 'mollie_transaction_id', $mollie_id);

			// If customer has a valid mandate, create the subscription
			try {

				$this->mollie_client
					->customers_subscriptions
					->withParentId( $this->getMollieCustomerId( $payment->metadata->user_id ))
					->create([
						// Change amount into subscription amount
						'amount'        => $payment->metadata->subscriptionAmount,
						'customerId'    => $this->getMollieCustomerId($payment->metadata->user_id),
						'interval'      => $payment->metadata->interval,       // important
						'description'   => $payment->metadata->item_name,
						'webhookUrl'    => $this->webhookUrl,
					]);

			} catch (Mollie_API_Exception $e) {
				echo "API call failed: " . htmlspecialchars($e->getMessage());
				wp_die();
			}

			wp_die('Successfully added user subscription');
		} else if(isset($payment->recurringType)) {
			// This is a subscription webhook call
			if ($payment->status === "paid") {
				$payment_data = array(
					'subscription'     => $payment->description,
					'date'             => date( 'Y-m-d g:i:s', time() ),
					'amount'           => $payment->amount,
					'user_id'          => $payment->metadata->user_id,
					'payment_type'     => 'Mollie',
					'subscription_key' => $payment->metadata->item_number,
					'transaction_id'   => $this->generate_transaction_id(),
					'status'           => 'complete',
				);
				$mollie_payment->insert($payment_data);
				$member->set_payment_profile_id($mollie_id);
				$this->renew_member(true);
			} else {
				$member = new RCP_Member($payment->metadata->user_id);
				$member->cancel();

			}
		}
		wp_die();
	}

	/**
	 * @param string $MollieCustomerID
	 * @param string $userID
	 * @return boolean
	 */
	function setMollieCustomerId($MollieCustomerID, $userID)
	{
		// Check if a userID exsists for this customer
		if ($this->getMollieCustomerId($userID) || $this->getMollieCustomerId($userID) == Null) {
			update_user_meta($userID, '_mollie_customer_id', $MollieCustomerID, $this->getMollieCustomerId($userID));
			$updated = true;
		} else {
			// Add the Mollie Customer ID to the user meta
			add_user_meta($userID, '_mollie_customer_id', $MollieCustomerID, true);
			$updated = true;
		}
		return $updated;
	}

	/**
	 * @param integer $userID
	 * @return string boolean|$MollieCustomerID
	 */
	public function getMollieCustomerId($userID)
	{
		if (get_user_meta($userID, '_mollie_customer_id', true) == '') {
			return false;
		}
		return get_user_meta($userID, '_mollie_customer_id', true);
	}

	/**
	 * @param string $mandate
	 * @param integer $userID;
	 * @return boolean
	 */
	private function setCustomerMandate($mandate, $userID)
	{
		if (!$mandate) {
			return false;
		}

		// Check if a userID exsists for this customer
		if (false !== $this->getCustomerMandate($userID)) {
			update_user_meta($userID, '_mollie_customer_mandate', $mandate, $this->getCustomerMandate($userID));
		} else {
			// Add the Mollie Customer ID to the user meta
			add_user_meta($userID, '_mollie_customer_mandate', $mandate, true);
		}
		return true;
	}

	/**
	 * @param integer $userID
	 * @return string boolean|$MollieCustomerID
	 */
	public function getCustomerMandate($userID)
	{
		if (get_user_meta($userID, '_mollie_customer_mandate', true) === '') {
			return false;
		}
		return get_user_meta($userID, '_mollie_customer_mandate', true);
	}

	/**
	 * Check if any of the customer mandates are valid.
	 * Returns valid mandate ID or false if no valid mandates were found
	 *
	 * @param object $mandates
	 * @return boolean|string
	 */
	public function checkMandates($mandates)
	{
		if(!is_object($mandates)) {
			return false;
		}

		if(!$mandates->totalCount || $mandates->totalCount == 0) {
			return false;
		}

		$mandates_data = $mandates->data;
		foreach ($mandates_data as $m) {
			if($m->status == "valid") {
				return $m->id;
			} else {
				return false;
			}
		}
		return false;
	}

	/**
	 * @param $number
	 * @param $unit
	 *
	 * @return string
	 */
	private function pluralizeTimeUnit($number, $unit)
	{
		if($number > 1) {
			return $number . ' ' . $unit . 's';
		} else {
			return $number . ' ' . $unit;
		}
	}

	/**
	 * @param int $amount
	 * @return float
	 */
	private function convertToEuro($amount)
	{
		$url = (is_ssl()) ? 'https://' : 'http://' . 'data.fixer.io/api/latest?symbols=' . $this->currency . '&access_key=' . $this->mollie_options['rcp_mollie_fixr_api'];
		$request = wp_remote_get($url);

		$result = json_decode(wp_remote_retrieve_body($request), true);

		return round($this->initial_amount / $result['rates'][$this->currency], 2);
	}

	/**
	 * @return float|int
	 */
	private function getSubscriptionAmount()
	{
		$euro = ($this->currency === 'EUR');

		if(!$euro && empty($this->mollie_options['rcp_mollie_fixr_api'])) {
			return false;
		}

		$recurring = $this->auto_renew;

		if($recurring && !$euro) {
			return $this->convertToEuro($this->amount);
		} else if($recurring && $euro) {
			return $this->amount;
		} else if(!$recurring && !$euro) {
			return $this->convertToEuro($this->initial_amount);
		} else if(!$recurring && $euro) {
			return $this->initial_amount;
		}
	}
}