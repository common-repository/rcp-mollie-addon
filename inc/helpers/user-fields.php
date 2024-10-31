<?php
/**
 * Additional user fields for RCP Mollie
 * User: Sander de Wijs
 * Date: 12-1-2016
 * Time: 11:01
 */

function rcp_mollie_add_user_fields() {

	$street = get_user_meta( get_current_user_id(), 'rcp_street', true );
	$zip   = get_user_meta( get_current_user_id(), 'rcp_zip', true );
	$city   = get_user_meta( get_current_user_id(), 'rcp_city', true );
	$phone   = get_user_meta( get_current_user_id(), 'rcp_phone', true );
	$birthdate   = get_user_meta( get_current_user_id(), 'rcp_birthdate', true );

	?>
    <p>
        <label for="rcp_street"><?php _e( 'Straatnaam', 'rcp' ); ?></label>
        <input name="rcp_street" id="rcp_street" type="text" value="<?php echo esc_attr( $street ); ?>"/>
    </p>
    <p>
        <label for="rcp_zip"><?php _e( 'Postcode', 'rcp' ); ?></label>
        <input name="rcp_zip" id="rcp_zip" type="text" value="<?php echo esc_attr( $zip ); ?>"/>
    </p>
    <p>
        <label for="rcp_city"><?php _e( 'Stad', 'rcp' ); ?></label>
        <input name="rcp_city" id="rcp_city" type="text" value="<?php echo esc_attr( $city ); ?>"/>
    </p>
    <p>
        <label for="rcp_phone"><?php _e( 'Telefoon', 'rcp' ); ?></label>
        <input name="rcp_phone" id="rcp_phone" type="text" value="<?php echo esc_attr( $phone ); ?>"/>
    </p>
    <p>
        <label for="rcp_birthdate"><?php _e( 'Geboortedatum', 'rcp' ); ?></label>
        <input name="rcp_birthdate" id="rcp_birthdate" type="text" value="<?php echo esc_attr( $birthdate ); ?>"/>
    </p>
	<?php
}
add_action( 'rcp_after_password_registration_field', 'rcp_mollie_add_user_fields' );
add_action( 'rcp_profile_editor_after', 'rcp_mollie_add_user_fields' );

/**
 * Adds the custom fields to the member edit screen
 *
 */
function rcp_mollie_add_member_edit_fields( $user_id = 0 ) {

	$street = get_user_meta( $user_id, 'rcp_street', true );
	$zip   = get_user_meta( $user_id, 'rcp_zip', true );
	$city   = get_user_meta( $user_id, 'rcp_city', true );
	$phone   = get_user_meta( $user_id, 'rcp_phone', true );
	$birthdate   = get_user_meta( $user_id, 'rcp_birthdate', true );
	?>
    <tr valign="top">
        <th scope="row" valign="top">
            <label for="rcp_street"><?php _e( 'Straatnaam', 'rcp' ); ?></label>
        </th>
        <td>
            <input name="rcp_street" id="rcp_street" type="text" value="<?php echo esc_attr( $street ); ?>"/>
            <p class="description"><?php _e( 'Straatnaam', 'rcp' ); ?></p>
        </td>
    </tr>
    <tr valign="top">
        <th scope="row" valign="top">
            <label for="rcp_zip"><?php _e( 'Postcode', 'rcp' ); ?></label>
        </th>
        <td>
            <input name="rcp_zip" id="rcp_zip" type="text" value="<?php echo esc_attr( $zip ); ?>"/>
            <p class="description"><?php _e( 'Postcode', 'rcp' ); ?></p>
        </td>
    </tr>
    <tr valign="top">
        <th scope="row" valign="top">
            <label for="rcp_city"><?php _e( 'Stad', 'rcp' ); ?></label>
        </th>
        <td>
            <input name="rcp_city" id="rcp_city" type="text" value="<?php echo esc_attr( $city ); ?>"/>
            <p class="description"><?php _e( 'Stad', 'rcp' ); ?></p>
        </td>
    </tr>
    <tr valign="top">
        <th scope="row" valign="top">
            <label for="rcp_phone"><?php _e( 'Telefoon', 'rcp' ); ?></label>
        </th>
        <td>
            <input name="rcp_phone" id="rcp_phone" type="text" value="<?php echo esc_attr( $phone ); ?>"/>
            <p class="description"><?php _e( 'Telefoon', 'rcp' ); ?></p>
        </td>
    </tr>
    <tr valign="top">
        <th scope="row" valign="top">
            <label for="rcp_birthdate"><?php _e( 'Geboortedatum', 'rcp' ); ?></label>
        </th>
        <td>
            <input name="rcp_birthdate" id="rcp_birthdate" type="text" value="<?php echo esc_attr( $birthdate ); ?>"/>
            <p class="description"><?php _e( 'Geboortedatum', 'rcp' ); ?></p>
        </td>
    </tr>
	<?php
}
add_action( 'rcp_edit_member_after', 'rcp_mollie_add_member_edit_fields' );

/**
 * Determines if there are problems with the registration data submitted
 *
 */
function rcp_mollie_validate_user_fields_on_register( $posted ) {
//    if( empty( $posted['rcp_street'] ) ) {
//        rcp_errors()->add( 'invalid_street', __( 'Please enter your street name', 'rcp' ), 'register' );
//    }
//    if( empty( $posted['rcp_zip'] ) ) {
//        rcp_errors()->add( 'invalid_zip', __( 'Please enter your Zip code', 'rcp' ), 'register' );
//    }
//    if( empty( $posted['rcp_city'] ) ) {
//        rcp_errors()->add( 'invalid_city', __( 'Please enter your city', 'rcp' ), 'register' );
//    }
//    if( empty( $posted['rcp_phone'] ) ) {
//        rcp_errors()->add( 'invalid_phone', __( 'Please enter your phone number', 'rcp' ), 'register' );
//    }
//    if( empty( $posted['rcp_birthdate'] ) ) {
//        rcp_errors()->add( 'invalid_birthdate', __( 'Please enter your birthdate', 'rcp' ), 'register' );
//    }
}
add_action( 'rcp_form_errors', 'rcp_mollie_validate_user_fields_on_register', 10 );

/**
 * Stores the information submitted during registration
 *
 */
function rcp_mollie_save_user_fields_on_register( $posted, $user_id ) {
	if( ! empty( $posted['rcp_street'] ) ) {
		update_user_meta( $user_id, 'rcp_street', sanitize_text_field( $posted['rcp_street'] ) );
	}
	if( ! empty( $posted['rcp_zip'] ) ) {
		update_user_meta( $user_id, 'rcp_zip', sanitize_text_field( $posted['rcp_zip'] ) );
	}
	if( ! empty( $posted['rcp_city'] ) ) {
		update_user_meta( $user_id, 'rcp_city', sanitize_text_field( $posted['rcp_city'] ) );
	}
	if( ! empty( $posted['rcp_phone'] ) ) {
		update_user_meta( $user_id, 'rcp_phone', sanitize_text_field( $posted['rcp_phone'] ) );
	}
	if( ! empty( $posted['rcp_birthdate'] ) ) {
		update_user_meta( $user_id, 'rcp_birthdate', sanitize_text_field( $posted['rcp_birthdate'] ) );
	}
}
add_action( 'rcp_form_processing', 'rcp_mollie_save_user_fields_on_register', 10, 2 );