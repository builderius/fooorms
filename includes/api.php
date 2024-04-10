<?php

function fooorms_register_route() {
    register_rest_route( 'fooorms/v1', '/submit/(?P<key>[a-zA-Z0-9-_]+)', array(
        'methods'             => 'POST',
        'callback'            => 'fooorms_form_submission',
        'permission_callback' => '__return_true',
        'args'                => array(
            'key' => array(
                'validate_callback' => function ($param, $request, $key) {
                    return !empty( $param ) && FooormsInit()->fields_provider->is_valid_form_key( $param );
                }
            )
        ),
    ) );

    register_rest_route( 'fooorms/v1', '/config/(?P<key>[a-zA-Z0-9-_]+)', array(
        'methods'             => 'GET',
        'callback'            => 'fooorms_form_config',
        'permission_callback' => '__return_true',
        'args'                => array(
            'key' => array(
                'validate_callback' => function ($param, $request, $key) {
                    return !empty( $param ) && FooormsInit()->fields_provider->is_valid_form_key( $param );
                }
            )
        ),
    ) );
}

/**
 * @param $request
 */
function fooorms_form_submission( $request ) {
    $parameters = $request->get_params();
    $errorMsg = __( 'Something went wrong!', 'fooorms' );

    if ( empty( $parameters ) ) {
        return new WP_REST_Response([
            'success' => false,
            'data' => [
                'errorMsg' => $errorMsg
            ]
        ], 400);
    }

    $form_key = sanitize_text_field( $parameters['key'] );
    fooorms_record_submission_attempt($form_key);
    $registered_variables = FooormsInit()->fields_provider->form_field_names( $form_key, 'options' );

    if ( empty( $registered_variables ) ) {
        return new WP_REST_Response([
            'success' => false,
            'data' => [
                'errorMsg' => __( 'No registered variables.', 'fooorms' )
            ]
        ], 400);
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
        $media_types = fooorms_get_allowed_file_types();
        foreach ( $params_file as $var_name => $param_file ) {
            if ( in_array( $var_name, $variables_list ) && in_array( $param_file['type'], $media_types ) ) {
                if ( !is_array( $post_data[$var_name] ) ) {
                    $post_data[$var_name] = [];
                }
                $file                   = fooorms_handle_upload( $param_file );
                $attachemnt_id          = fooorms_insert_attachment( $file );
                $post_data[$var_name][] = $attachemnt_id;
                $attachments[]          = $attachemnt_id;
            } else {
                $attachments[] = new WP_Error(1, "Uploading file of this type ({$param_file['type']}) is not allowed");
            }
        }
    }

    if ( empty($validation_errors) && !empty( $post_data ) ) {
        do_action( 'fooorms_submit_successful', $post_data );

        $results_entries = fooorms_create_entry( $form_key, $post_data, $attachments );

        if (is_wp_error($results_entries)) {
            $error_msg = $results_entries->get_error_message();

            return new WP_REST_Response([
                'success' => false,
                'data' => [
                    'errorMsg' => $error_msg
                ]
            ], 400);
        }

        $results_email = fooorms_send_email( $form_key, $post_data, $attachments );

        if (is_wp_error($results_email)) {
            $error_msg = $results_email->get_error_message();

            return new WP_REST_Response([
                'success' => false,
                'data' => [
                    'errorMsg' => $error_msg
                ]
            ], 400);
        }

        fooorms_record_submission_success($form_key);

        $successMsg = __( 'We have received your message. Thank you!', 'fooorms' );

        return new WP_REST_Response([
            'success' => true,
            'data' => [
                'successMsg' => $successMsg
            ]
        ], 200);
    } else {
        do_action( 'fooorms_submit_failed', $post_data );
        $errorMsg = __( 'Something went wrong!', 'fooorms' );

        if (!empty($validation_errors)) {
            $errorMsg = implode(' ', $validation_errors);
        }

        return new WP_REST_Response([
            'success' => false,
            'data' => [
                'errorMsg' => $errorMsg
            ]
        ], 400);
    }
}

/**
 * @param $request
 */
function fooorms_form_config( $request )
{
    $parameters = $request->get_params();

    if (empty($parameters)) {
        wp_send_json_error();
    }

    $form_key = sanitize_text_field($parameters['key']);
    $rest_url = fooorms_get_form_endpoint_by_key( $form_key );
    $form_post = FooormsInit()->fields_provider->form_from_key($form_key);
    $eeValidations = fooorms_get_smartform_validation_schema($form_key, false);
    $jsValidations = fooorms_get_validatejs_validation_schema($form_key);

    return new WP_REST_Response([
        'submitUrl' => $rest_url,
        'successText' => $form_post['display']['success_message'],
        'eeValidation' => $eeValidations,
        'jsValidation' => $jsValidations
    ], 200);
}
