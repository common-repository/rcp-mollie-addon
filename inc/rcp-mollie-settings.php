<?php
/**
 * Functions for creating RCP Mollie settings
 * User: Sander de Wijs
 * Date: 25-8-2016
 */

require( 'helpers/mollie-rcp-helpers.php' );

function rcp_mollie_scripts_styles()
{
    wp_register_style('rcp-mollie-styles', plugins_url( 'assets/css/rcp-mollie-styles.css', dirname( __FILE__ ) ) , '', '1.0', '');
    wp_register_style('rcp-mollie-icons', plugins_url( 'assets/css/font-awesome.min.css', dirname( __FILE__ ) ) , '', '', '');
    wp_enqueue_script( 'rcp-mollie-form-script', plugins_url( 'assets/js/jquery.form.js', dirname( __FILE__ ) ) , array( 'jquery' ), '', true );
    wp_enqueue_script( 'rcp-mollie-scripts', plugins_url( 'assets/js/rcp_mollie.js', dirname( __FILE__ ) ) , array( 'jquery' ), '1.0', true );
    wp_enqueue_style('rcp-mollie-styles');
    wp_enqueue_style('rcp-mollie-icons');
}

/*
* Add a link to plugin settings in the admin menu
*/
if (!function_exists('rcp_mollie_settings_init')) {
    function rcp_mollie_settings_init()
    {
        register_setting('rcp-mollie-settings-group', 'rcp_mollie_settings');

        add_settings_section(
            'rcp_mollie_settings_section',
            'Mollie Gateway Settings',
            'rcp_mollie_section_callback',
            'mollie-rcp'
        );

        add_settings_field(
            'rcp_mollie_test_api_field',
            'Mollie test API key',
            'rcp_mollie_test_api_field_callback',
            'mollie-rcp',
            'rcp_mollie_settings_section'
        );
        add_settings_field(
            'rcp_mollie_live_api_field',
            'Mollie live API key',
            'rcp_mollie_live_api_field_callback',
            'mollie-rcp',
            'rcp_mollie_settings_section'
        );
        add_settings_field(
            'rcp_mollie_recurring_payments_field',
            'Recurring payments',
            'rcp_mollie_recurring_payments_field_callback',
            'mollie-rcp',
            'rcp_mollie_settings_section'
        );
        add_settings_field(
            'rcp_mollie_test_modus',
            'Mollie sandbox mode',
            'rcp_mollie_test_modus_field_callback',
            'mollie-rcp',
            'rcp_mollie_settings_section'
        );
        add_settings_field(
            'rcp_mollie_hide_extra_userfields',
            'Verberg extra checkout velden',
            'rcp_mollie_hide_extra_userfields_field_callback',
            'mollie-rcp',
            'rcp_mollie_settings_section'
        );
	    add_settings_field(
		    'rcp_mollie_fixr_api',
		    'Fixr API Key',
		    'rcp_mollie_fixr_api_field_callback',
		    'mollie-rcp',
		    'rcp_mollie_settings_section'
	    );
    }
}

if (!function_exists('rcp_mollie_test_api_field_callback')) {
    function rcp_mollie_test_api_field_callback()
    {
        // Hier komt de daadwerkelijke html voor het tekstveld
        $options = get_option('rcp_mollie_settings');
        if (!isset($options['rcp_mollie_test_api_field'])) $options['rcp_mollie_test_api_field'] = '';

        echo '<input type="text" id="rcp_mollie_test_api_field" name="rcp_mollie_test_api_field" value="' . $options['rcp_mollie_test_api_field'] . '">';
    }
}

if (!function_exists('rcp_mollie_live_api_field_callback')) {
    function rcp_mollie_live_api_field_callback()
    {
        // Hier komt de daadwerkelijke html voor het tekstveld
        $options = get_option('rcp_mollie_settings');
        if (!isset($options['rcp_mollie_live_api_field'])) $options['rcp_mollie_live_api_field'] = '';

        echo '<input type="text" id="rcp_mollie_live_api_field" name="rcp_mollie_live_api_field" value="' . $options['rcp_mollie_live_api_field'] . '">';
    }
}

if (!function_exists('rcp_mollie_recurring_payments_field_callback')) {
    function rcp_mollie_recurring_payments_field_callback()
    {
        // Hier komt de daadwerkelijke html voor het tekstveld
        $options = get_option('rcp_mollie_settings');
        if (!isset($options['rcp_mollie_recurring_payments_field'])) $options['rcp_mollie_recurring_payments_field'] = '0.01';

        $html = '<input type="text" id="rcp_mollie_recurring_payments_field" name="rcp_mollie_recurring_payments_field" value="' . $options['rcp_mollie_recurring_payments_field'] . '">';
        $html .= '<label for="rcp_mollie_recurring_payments_field">Bedrag voor eerste testbetaling (Meestal 0.01)</label>';
        $html .= '<p>Let op! Automatische incasso\'s zijn niet geschikt voor alle denkbare abonnementsvormen. Lees aub eerst de aanbevelingen op de <a href="https://www.degrinthorst.nl/ideal-voor-restrict-content-pro/" target="_blank">plugin pagina</a> alvorens deze optie in te schakelen!</p>';
        echo $html;
    }
}

if (!function_exists('rcp_mollie_test_modus_field_callback')) {
    function rcp_mollie_test_modus_field_callback()
    {
        // Hier komt de daadwerkelijke html voor het tekstveld
        $options = get_option('rcp_mollie_settings');
        if (!isset($options['rcp_mollie_test_modus'])) $options['rcp_mollie_test_modus'] = 0;

        $html = '<input type="checkbox" id="rcp_mollie_test_modus" name="rcp_mollie_test_modus" value="1"' . checked(1, $options['rcp_mollie_test_modus'], false) . '>';
        $html .= '<label for="rcp_mollie_test_modus">Schakel testmodus in</label>';
        echo $html;
    }
}

if (!function_exists('rcp_mollie_hide_extra_userfields_field_callback')) {
	function rcp_mollie_hide_extra_userfields_field_callback()
	{
		// Hier komt de daadwerkelijke html voor het tekstveld
		$options = get_option('rcp_mollie_settings');
		if (!isset($options['rcp_mollie_hide_extra_userfields'])) $options['rcp_mollie_hide_extra_userfields'] = 0;

		$html = '<input type="checkbox" id="rcp_mollie_hide_extra_userfields" name="rcp_mollie_hide_extra_userfields" ' . checked(1, $options['rcp_mollie_hide_extra_userfields'], false) . '>';
		$html .= '<label for="rcp_mollie_test_modus">Verberg extra checkout velden</label>';
		echo $html;
	}
}

if (!function_exists('rcp_mollie_fixr_api_field_callback')) {
	function rcp_mollie_fixr_api_field_callback()
	{
		// Hier komt de daadwerkelijke html voor het tekstveld
		$options = get_option('rcp_mollie_settings');
        if (!isset($options['rcp_mollie_fixr_api'])) $options['rcp_mollie_fixr_api'] = '';

		$html = '<input type="text" id="rcp_mollie_fixr_api" name="rcp_mollie_fixr_api" value="' . $options['rcp_mollie_fixr_api'] . '">';
		$html .= '<p>Om de Fixr API te gebruiken is een API key benodigd, deze is gratis tot 1000 valuta conversies per maand. Haal er een op via <a href="https://fixer.io/product">fixr.io</a></p>';
		echo $html;
	}
}

if (!function_exists('rcp_mollie_section_callback')) {
    function rcp_mollie_section_callback()
    {
        // Hier komt de HTML voor de settings section
    }
}

function add_rcp_mollie_options_page()
{
    add_menu_page(
        'Restrict Content Pro Mollie Gateway',
        'Mollie RCP',
        'manage_options',
        'mollie-rcp',
        'rcp_mollie_options_page',
        'dashicons-cart'
    );
}

if (!function_exists('rcp_mollie_options_page')) {
    function rcp_mollie_options_page()
    {
        $options = get_option('rcp_mollie_settings');

        if (isset($options['rcp_mollie_test_modus'])) {
            $mollie_api = $options['rcp_mollie_test_api_field'];
        } else {
            $mollie_api = $options['rcp_mollie_live_api_field'];
        }

        ?>
        <div class="wrap">
            <div id="icon-options-general" class="icon32"></div>
            <h2>Mollie IDEAL for Restrict Content Pro</h2>
            <div id="rcp-mollie-updated" class="updated">
                <p>Wijzigingen succesvol opgeslagen!</p>
            </div>
            <div id="rcp-mollie-error" class="error">
                <p>Er is een fout opgetreden</p>
            </div>
            <p><strong>Let op</strong>, de optie 'Mollie sandbox mode' overschrijft de sandbox-mode optie in de
                instellingen van
                Restrict Content Pro. Gebruik daarom alleen de optie op deze pagina om te wisselen tussen live en
                test betalingen.</p>

            <p><strong>Activeer na het invullen van de Mollie API keys de betaalmethodes in de <a
                        href="/wp-admin/admin.php?page=rcp-settings#payments">Restrict Content Pro
                        instellingen</a></strong>
            </p>

            <form id="mollie_rcp_settings_form" method="post" action="">
                <?php
                    settings_fields('rcp-mollie-settings-group');
                    do_settings_sections('mollie-rcp');
                    wp_nonce_field( 'molliercpUpdateOptions_html', 'molliercpUpdateOptions_nonce' );
                ?>
                <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Wijzigingen opslaan"> <i class="fa fa-spin fa-spinner" style="display: none;"></i></p>
                <input type="hidden" name="action" value="rcpMollieGatewayUpdate" />
                <hr>
                <?php
                    if ($options['rcp_mollie_test_modus'] == 1) {
                        $mollie_profile = 'test ';
                    } else {
                        $mollie_profile = 'live ';
                    }
                ?>
                <h3>Geactiveerde services</h3>

                <p>Dit zijn de services die geactiveerd zijn in je Mollie <?php echo $mollie_profile; ?>
                    websiteprofiel. Schakel meer betaalmethodes in via het <a
                        href="https://www.mollie.nl/beheer/betaalmethodes/" target="_blank">Mollie dashboard</a></p>
                <ul class="active-payment-methods">
                    <?php
                    $methods = "";
                    $options = get_option('rcp_mollie_settings');
                    //var_dump($options);die;
                    if (rcp_mollie_fields_validate($options['rcp_mollie_test_api_field']) == 'valid' && rcp_mollie_fields_validate($options['rcp_mollie_live_api_field']) == 'valid') {

                        if(!array_key_exists('mollie_gateways', $options)) {
                            echo '<p class="error">Er kunnen momenteel geen betaalmethodes worden geladen. Probeer aub je wijzigingen opnieuw op te slaan.</p>';
                        } else {
                            if(isset($options['mollie_gateways'])) {
                                $methods = $options['mollie_gateways'];

                                foreach ($methods as $method) {
                                    $html = '<li class="payment-method"><img src="' . $method['image'] . '">';
                                    $html .= '<span class="description">' . $method['label'] . '</span></li>';
                                    echo $html;
                                }
                            } else {
                                echo '<p>We kunnen geen Gateways inladen. Controlleer de API keys en probeer het opnieuw.</p>';
                            }

                        }
                    } else if (rcp_mollie_fields_validate($options['rcp_mollie_test_api_field']) == 'invalid' && rcp_mollie_fields_validate($options['rcp_mollie_live_api_field']) == 'invalid') {
                        echo '<p>Het lijkt erop dat je nog geen, of ongeldige, API keys hebt ingevuld. Deze zijn nodig om betalingen te verrichten met Mollie. Je vind je API keys in je betalingsprofiel in het <a href="https://www.mollie.nl/beheer/betaalmethodes/" target="_blank">Mollie dashboard</a>.</p>';
                    } else if (rcp_mollie_fields_validate($options['rcp_mollie_test_api_field']) == 'invalid') {
                        echo '<p>Het lijkt erop dat je nog geen, of een ongeldige, test API key hebt ingevuld. Je vind je API key in je betalingsprofiel in het <a href="https://www.mollie.nl/beheer/betaalmethodes/" target="_blank">Mollie dashboard</a>.</p>';
                    } else if (rcp_mollie_fields_validate($options['rcp_mollie_live_api_field']) == 'invalid') {
                        echo '<p>Het lijkt erop dat je nog geen, of een ongeldige, live API key hebt ingevuld.<br> Je vind je API key in je betalingsprofiel in het <a href="https://www.mollie.nl/beheer/betaalmethodes/" target="_blank">Mollie dashboard</a>.</p>';
                    } else {
                        return;
                    }
                    ?>
                </ul>
            </form>
            <script type="text/javascript">
                jQuery(document).on('submit', 'form', function(e){
                    jQuery("p.submit .fa-spinner").fadeIn();
                    e.preventDefault();
                    var formData = jQuery(this).serialize();
                       jQuery.post("<?php echo admin_url('admin-ajax.php', is_ssl() ? 'https' : 'http'); ?>", formData, function(response){
                           function delayedRefresh() {
                               timeoutID = window.setTimeout(location.reload(), 1000);
                           }
                           if(response == 0) {
                               jQuery("#rcp-mollie-error").fadeIn("slow");
                           }
                           if(response == 2) {
                               jQuery("#rcp-mollie-updated").fadeIn("slow");
                               delayedRefresh();
                           }
                        });
                });
            </script>
        </div>
        <?php
    }
}
