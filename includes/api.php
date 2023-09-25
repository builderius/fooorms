<?php

function fooorms_register_route() {
    register_rest_route( 'fooorms/v1', '/submit/(?P<key>[a-zA-Z0-9-_]+)', array(
        'methods'             => 'POST',
        'callback'            => 'fooorms_form_submission',
        'permission_callback' => '__return_true',
        'args'                => array()
    ) );
}

/**
 * @param $request
 */
function fooorms_form_submission( $request ) {
    $parameters = $request->get_params();

    if ( empty( $parameters ) ) {
        wp_send_json_error();
    }

    $form_key = sanitize_text_field( $parameters['key'] );

    if ( empty( $form_key ) || !fooorms_acf_is_valid_form_key( $form_key ) ) {
        wp_send_json_error();
    }

    $registered_variables = _fooorms_acf_form_field_names( $form_key, 'options' );

    if ( empty( $registered_variables ) ) {
        wp_send_json_error( ["errorMsg" => "No registered variables"] );
    }

    unset( $parameters['key'] );
    $variables_list = array_values( $registered_variables );
    $parameters     = fooorms_sanitize_data( $parameters );
    $post_data      = [];
    $validation_errors = [];

    foreach ( $variables_list as $variable ) {
        if ( isset( $parameters[$variable] ) ) {
            $post_data[$variable] = $parameters[$variable];
        } else {
            $post_data[$variable] = '';
        }
    }

    $validations = fooorms_get_smartform_validation_schema($form_key, false);

    if (class_exists('Builderius\Bundle\ExpressionLanguageBundle\ExpressionLanguage') && !empty($validations)) {
        $ee = new Builderius\Bundle\ExpressionLanguageBundle\ExpressionLanguage();
        $ee->registerProvider(new \Builderius\Bundle\ExpressionLanguageBundle\Provider\ArrayFunctionsProvider());
        $ee->registerProvider(new \Builderius\Bundle\ExpressionLanguageBundle\Provider\EscapeFunctionsProvider());
        $ee->registerProvider(new \Builderius\Bundle\ExpressionLanguageBundle\Provider\StandardFunctionsProvider());

        foreach ($validations as $name => $data) {
            foreach ($data['validators'] as $validator) {
                foreach ($validator as $rule => $msg) {
                    $res = $ee->evaluate( $rule, $post_data );

                    if ( !$res ) {
                        $validation_errors[] = $msg;
                    }
                }
            }
        }
    }

    $params_file = $request->get_file_params();
    $attachments = [];
    if ( !empty( $params_file ) ) {
        $media_types = fooorms_get_media_types();
        foreach ( $params_file as $var_name => $param_file ) {
            if ( in_array( $var_name, $variables_list ) && in_array( $param_file['type'], $media_types ) ) {
                if ( !is_array( $post_data[$var_name] ) ) {
                    $post_data[$var_name] = [];
                }
                $file                   = fooorms_handle_upload( $param_file );
                $attachemnt_id          = fooorms_insert_attachment( $file );
                $post_data[$var_name][] = $attachemnt_id;
                $attachments[]          = $attachemnt_id;
            }
        }
    }

    if ( empty($validation_errors) && !empty( $post_data ) ) {
        do_action( 'fooorms_submit_successful', $post_data );

        fooorms_create_entry( $form_key, $post_data, $attachments );
        fooorms_send_email( $form_key, $post_data );

        $successMsg = __( 'We have received your message. Thank you!', 'fooorms' );

        wp_send_json_success( [
            'successMsg' => $successMsg
        ] );
    } else {
        do_action( 'fooorms_submit_failed', $post_data );
        $errorMsg = __( 'Something went wrong!', 'fooorms' );

        if (!empty($validation_errors)) {
            $errorMsg = implode(' ', $validation_errors);
        }

        wp_send_json_error( [
            'errorMsg' => $errorMsg
        ] );
    }
}