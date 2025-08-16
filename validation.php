// Prevent invalid or special characters in WooCommerce checkout + account edit forms
add_action( 'woocommerce_after_checkout_validation', 'bs_validate_shipping_checkout_fields', 10, 2 );
add_action( 'woocommerce_after_save_address_validation', 'bs_validate_shipping_account_fields', 10, 2 );

function bs_validate_shipping_checkout_fields( $data, $errors ) {
    $fields = [
        'shipping_first_name',
        'shipping_last_name',
        'shipping_phone',
        'shipping_email',
        'shipping_address_1',
        'shipping_address_2',
        'shipping_city',
        'shipping_postcode',
    ];

    // Manually include customer_note from POST
    $data['customer_note'] = isset($_POST['customer_note']) ? sanitize_text_field( wp_unslash( $_POST['customer_note'] ) ) : '';

    $fields[] = 'customer_note';

    bs_run_shipping_validations( $data, $errors, $fields );
}

function bs_validate_shipping_account_fields( $user_id, $load_address ) {
    if ( $load_address !== 'shipping' ) {
        return;
    }

    $errors = new WP_Error();
    $fields = [
        'shipping_first_name',
        'shipping_last_name',
        'shipping_phone',
        'shipping_email',
        'shipping_address_1',
        'shipping_address_2',
        'shipping_city',
        'shipping_postcode',
        'customer_note', // If you add it to account form
    ];

    $data = [];
    foreach ( $fields as $field ) {
        $data[$field] = isset($_POST[$field]) ? sanitize_text_field( wp_unslash( $_POST[$field] ) ) : '';
    }

    bs_run_shipping_validations( $data, $errors, $fields );

    if ( $errors->has_errors() ) {
        wc_add_notice( implode( '<br>', $errors->get_error_messages() ), 'error' );
    }
}

function bs_run_shipping_validations( $data, &$errors, $fields ) {
    $pattern_name  = '/[^a-zA-Z\s]/u';                     // only letters + spaces
    $pattern_phone = '/[^0-9]/u';                          // only numbers
    $pattern_email = '/^[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}$/'; // proper email
    $pattern_other = '/[^a-zA-Z0-9\s,.\-]/u';               // safe for address fields
    $pattern_notes = '/[^a-zA-Z0-9\s,.\-?!]/u';             // safe for notes

    foreach ( $fields as $field ) {
        if ( empty( $data[ $field ] ) ) {
            continue;
        }

        if ( in_array( $field, ['shipping_first_name', 'shipping_last_name'], true ) ) {
            if ( preg_match( $pattern_name, $data[ $field ] ) ) {
                $errors->add( $field, sprintf( __( '%s should contain only letters and spaces.', 'woocommerce' ), wc_get_form_field_label( $field ) ) );
            }
        }
        elseif ( $field === 'shipping_phone' ) {
            if ( preg_match( $pattern_phone, $data[ $field ] ) ) {
                $errors->add( $field, sprintf( __( '%s should contain only numbers.', 'woocommerce' ), wc_get_form_field_label( $field ) ) );
            }
        }
        elseif ( $field === 'shipping_email' ) {
            if ( ! preg_match( $pattern_email, $data[ $field ] ) ) {
                $errors->add( $field, sprintf( __( '%s is not a valid email address.', 'woocommerce' ), wc_get_form_field_label( $field ) ) );
            }
        }
        elseif ( $field === 'customer_note' ) {
            if ( preg_match( $pattern_notes, $data[ $field ] ) ) {
                $errors->add( $field, __( 'Order Notes contains invalid characters.', 'woocommerce' ) );
            }
        }
        else {
            if ( preg_match( $pattern_other, $data[ $field ] ) ) {
                $errors->add( $field, sprintf( __( '%s contains invalid characters.', 'woocommerce' ), wc_get_form_field_label( $field ) ) );
            }
        }
    }
}

if ( ! function_exists( 'wc_get_form_field_label' ) ) {
    function wc_get_form_field_label( $field_name ) {
        $labels = [
            'shipping_first_name'=> 'First Name',
            'shipping_last_name' => 'Last Name',
            'shipping_phone'     => 'Mobile Phone',
            'shipping_email'     => 'Email',
            'shipping_address_1' => 'Street Address',
            'shipping_address_2' => 'Apartment',
            'shipping_city'      => 'Town / City',
            'shipping_postcode'  => 'Postal Code',
            'customer_note'     => 'Order Notes',
        ];
        return isset( $labels[ $field_name ] ) ? $labels[ $field_name ] : ucfirst( str_replace( '_', ' ', $field_name ) );
    }
}
