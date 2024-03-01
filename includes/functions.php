<?php
/**
 * @param $title
 * @return array|string|string[]
 */
function fooorms_sanitize_title_with_( $title ) {
    return str_replace( '-', '_', sanitize_title( $title ) );
}

/**
 * @param $data
 * @return array
 */
function fooorms_sanitize_data( $data ) {
    $new = [];

    array_walk(
        $data,
        function ( $val, $key ) use ( &$new ) {
            if ( is_string( $val ) || is_numeric( $val ) ) {
                $new[$key] = sanitize_text_field( $val );
            } elseif ( is_array( $val ) ) {
                $new[$key] = array_map( 'sanitize_text_field', $val );
            }
        }
    );

    return $new;
}

/**
 * @param $length
 * @param string $post_id
 * @param false $is_echo
 * @param null $more
 * @param null $custom_content
 * @return string|void
 */
function fooorms_excerpt( $length, $post_id = '', $is_echo = false, $more = null, $custom_content = null ) {
    if ( !empty( $post_id ) ) {
        $post = get_post( $post_id );
    } else {
        global $post;
    }

    $length = absint( $length );

    if ( null === $more ) {
        $more = '...';
    }

    if ( null == $custom_content ) {
        $content = $post->post_content;
    } else {
        $content = $custom_content;
    }
    $content = wp_strip_all_tags( $content );
    $content = strip_shortcodes( $content );
    if ( 'characters' == _x( 'words', 'word count: words or characters?', 'fdm' ) && preg_match( '/^utf\-?8$/i', get_option( 'blog_charset' ) ) ) {
        $content = trim( preg_replace( "/[\n\r\t ]+/", ' ', $content ), ' ' );
        preg_match_all( '/./u', $content, $words_array );
        $words_array = array_slice( $words_array[0], 0, $length + 1 );
        $sep         = '';
    } else {
        $words_array = preg_split( "/[\n\r\t ]+/", $content, $length + 1, PREG_SPLIT_NO_EMPTY );
        $sep         = ' ';
    }

    if ( count( $words_array ) > $length ) {
        array_pop( $words_array );
        $content = implode( $sep, $words_array );
        $content = $content . $more;
    } else {
        $content = implode( $sep, $words_array );
    }
    if ( $is_echo ) {
        echo '<p>' . $content . '</p>';
    } else {
        return $content;
    }
}

/**
 * @return false|PLL_Language|string
 */
function fooorms_get_cur_lang() {
    $lang_code = 'en';

    if ( function_exists( 'pll_current_language' ) ) {
        $lang_code = pll_current_language();
    }

    return $lang_code;
}

/**
 * @param $form_post_key
 * @return void
 */
function fooorms_record_submission_attempt($form_post_key) {
    $form_obj = FooormsInit()->fields_provider->form_from_key( $form_post_key );
    $submissions = get_post_meta( $form_obj['post_id'], 'form_num_of_submission_attempts', true );
    $submissions = $submissions ? $submissions + 1 : 1;
    update_post_meta( $form_obj['post_id'], 'form_num_of_submission_attempts', $submissions );
}

/**
 * @param $form_post_key
 * @return void
 */
function fooorms_record_submission_success($form_post_key) {
    $form_obj = FooormsInit()->fields_provider->form_from_key( $form_post_key );
    $submissions = get_post_meta( $form_obj['post_id'], 'form_num_of_submission_success', true );
    $submissions = $submissions ? $submissions + 1 : 1;
    update_post_meta( $form_obj['post_id'], 'form_num_of_submission_success', $submissions );
}

/**
 * @param $form_key
 * @return string
 */
function fooorms_get_form_endpoint_by_key( $form_key ) {
    return get_rest_url( null, 'fooorms/v1/submit/' . $form_key );;
}

/**
 * @param $form_key
 * @return string
 */
function fooorms_get_form_success_message( $form_key ) {
    return get_rest_url( null, 'fooorms/v1/submit/' . $form_key );;
}

/**
 * @param $form_key
 * @param $post_data
 * @param $attachments
 * @return int|bool|WP_Error
 */
function fooorms_create_entry( $form_key, $post_data, $attachments ) {
    $form_obj = FooormsInit()->fields_provider->form_from_key( $form_key );

    if ( empty( $form_obj['create_entries'] ) ) {
        return true;
    }

    $entry_content_starter = FooormsInit()->fields_provider->entry_content_starter( $form_key, 'options' );

    foreach ( $entry_content_starter as $key => $value ) {
        $entry_content_starter[$key]['value'] = isset( $post_data[$key] ) ? $post_data[$key] : '';
    }

    // Create entry post
    $post_data = array(
        'post_type'   => 'fooorms_entry',
        'post_status' => 'publish',
        'post_title'  => '',
    );

    $entry_id = wp_insert_post( $post_data );

    if ( !$entry_id ) {
        return new WP_Error( '1', 'Entry post has not been created' );
    }

    // Update post title
    $updated_title_data = [
        'ID'         => $entry_id,
        'post_title' => sprintf( '#%s', $entry_id ),
    ];
    wp_update_post( $updated_title_data );

    // Save general entry info
    update_post_meta( $entry_id, 'entry_form', $form_key );
    update_post_meta( $entry_id, 'entry_submission_date', date( 'Y-m-d H:i:s' ) );
    update_post_meta( $entry_id, 'entry_content', $entry_content_starter );

    if ( !empty( $attachments ) ) {
        foreach ( $attachments as $attachment_id ) {
            $updated_attachemnt_data = [
                'ID'          => $attachment_id,
                'post_parent' => $entry_id,
            ];
            wp_update_post( $updated_attachemnt_data );
        }
    }

    return $entry_id;
}

/**
 * @param $form_key
 * @param $post_data
 * @return bool|WP_Error
 */
function fooorms_send_email( $form_key, $post_data ) {
    $form_obj             = FooormsInit()->fields_provider->form_from_key( $form_key );
    $registered_variables = FooormsInit()->fields_provider->form_field_names( $form_key, 'options' );

    if ( empty( $form_obj['emails'] ) ) {
        return true;
    }

    $variables = fooorms_merge_with_permanent_vars( $post_data );
    $results = [];

    foreach ( $form_obj['emails'] as $email_data ) {
        $send_to = '';

        if ( 'field' === $email_data['fooorms_recipient_type'] &&
            !empty( $registered_variables[$email_data['fooorms_recipient_field']] ) &&
            !empty( $post_data[$registered_variables[$email_data['fooorms_recipient_field']]] ) ) {
            $send_to = $post_data[$registered_variables[$email_data['fooorms_recipient_field']]];
        } else if ( 'custom' === $email_data['fooorms_recipient_type'] ) {
            $send_to = $email_data['fooorms_recipient_custom'];
        }

        if ( !empty( $send_to ) && !is_email( $send_to ) ) {
            $send_to = '';
        }

        try {
            $results[] = Fooorms\Mail::init()
                ->setSMTP($form_obj['smtps'])
                ->from($email_data['fooorms_from'], $variables)
                ->to($send_to, $variables)
                ->subject($email_data['fooorms_subject'], $variables)
                ->templateHTML($email_data['fooorms_content'], $variables)
                ->send();
        } catch (\Exception $e) {
            FooormsInit()->set_smtp_log($e->getMessage());

            $results[] = false;
        }
    }

    if (in_array(false, $results)) {
        $smtp_logs = FooormsInit()->get_smtp_log();
        $error_msg = !empty($smtp_logs) ? implode('; ', $smtp_logs) : 'Something went wrong during sending emails';

        // clear smtp logs
        FooormsInit()->clear_smtp_log();

        return new WP_Error( '2', $error_msg );
    } else {
        return true;
    }
}

function fooorms_merge_with_permanent_vars( $data ) {
    $site_name = get_option( 'blogname' );
    $site_url  = wp_parse_url( network_home_url(), PHP_URL_HOST );

    if ( 'www.' === substr( $site_url, 0, 4 ) ) {
        $site_url = substr( $site_url, 4 );
    }

    return array_merge(
        [
            'site_name' => $site_name,
            'site_url'  => $site_url
        ],
        $data
    );
}

/**
 * @param $form_key
 * @return int[]|WP_Post[]
 */
function fooorms_get_entries( $form_key ) {
    $args = array(
        'post_type'      => 'fooorms_entry',
        'posts_per_page' => '-1',
        'meta_query'     => array(
            array(
                'key'   => 'entry_form',
                'value' => $form_key,
            ),
        ),
    );

    $query = new WP_Query( $args );

    return $query->posts;
}


/**
 * @param $form_key
 * @return int
 */
function fooorms_get_entry_count( $form_key ) {
    $args = array(
        'post_type'      => 'fooorms_entry',
        'posts_per_page' => '-1',
        'meta_query'     => array(
            array(
                'key'   => 'entry_form',
                'value' => $form_key,
            ),
        ),
        'fields'         => 'ids'
    );

    $query = new WP_Query( $args );

    return sizeof( $query->posts );
}

/**
 * @param $file
 * @param $overrides
 * @return array|WP_Error
 */
function fooorms_handle_upload( $file, $overrides = ['test_form' => false] ) {
    if ( !function_exists( 'wp_handle_upload' ) ) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
    }

    $time     = current_time( 'mysql' );
    $uploaded = \wp_handle_upload( $file, $overrides, $time );

    if ( isset( $uploaded['error'] ) ) {
        return new \WP_Error( 'upload_error', $uploaded['error'] );
    }

    return $uploaded;
}

/**
 * @param $file
 * @return int|mixed|WP_Error
 */
function fooorms_insert_attachment( $file ) {
    if ( is_wp_error( $file ) ) {
        return $file;
    }

    $name       = wp_basename( $file['file'] );
    $name_parts = pathinfo( $name );
    $name       = trim( substr( $name, 0, -(1 + strlen( $name_parts['extension'] )) ) );

    $url  = $file['url'];
    $type = $file['type'];
    $file = $file['file'];

    $attachment = [
        'guid'           => $url,
        'post_mime_type' => $type,
        'post_title'     => preg_replace( '/\.[^.]+$/', '', $name ),
        'post_content'   => '',
        'post_status'    => 'inherit'
    ];

    $id = wp_insert_attachment( $attachment, $file, 0, true, false );

    if ( is_wp_error( $id ) ) {
        if ( 'db_update_error' === $id->get_error_code() ) {
            $id->add_data( array('status' => 500) );
        } else {
            $id->add_data( array('status' => 400) );
        }

        return $id;
    }

    return $id;
}

/**
 * @return array
 */
function fooorms_get_media_types() {
    return array_values( get_allowed_mime_types() );
}

/**
 * @param $form_key
 * @return array
 */
function fooorms_get_smartform_validation_schema($form_key, $translate = true) {
    $form_fields = FooormsInit()->fields_provider->get_form_fields( $form_key, 'options' );
    $types = FooormsInit()->get_form_field_types();
    $keys = array_keys($types);
    $form_types = wp_list_pluck($types, 'type');
    $final_types = array_combine($keys, $form_types);
    $validation_schema = [];

    foreach ($form_fields as $form_cfg) {
        $field_label = $form_cfg['label'];
        $field_name = $form_cfg['name'];
        $field_type = $form_cfg['field_type'];
        $validators = [];

        if (!empty($form_cfg['required'])) {
            $msg = !empty($form_cfg['field_error_msg'])
                ? __($form_cfg['field_error_msg'], 'fooorms')
                : __("{$field_label} is required!", 'fooorms');

            if ($translate && class_exists('Builderius\Bundle\ExpressionLanguageBundle\ExpressionLanguage')) {
                $msg = "[[translate('$msg')]]";
            }

            $validators[] = ["!is_empty({$field_name}) && !is_null({$field_name})" => $msg];
        }

        if(!empty($form_cfg['field_validation_rules'])) {
            $items = array_values($form_cfg['field_validation_rules']);
            foreach ($items as $value) {
                $rules = array_values($value);
                $msg = $rules[1];

                if ($translate && class_exists('Builderius\Bundle\ExpressionLanguageBundle\ExpressionLanguage')) {
                    $msg = "[[translate('$msg')]]";
                }

                $validators[] = [$rules[0] => $msg];
            }
        }

        $validation_schema[$field_name] = [
            'type' => $final_types[$field_type],
            'validators' => $validators
        ];
    }

    return $validation_schema;
}

function fooorms_get_validatejs_validation_schema($form_key) {
    $form_fields = FooormsInit()->fields_provider->get_form_fields( $form_key, 'options' );
    $types = FooormsInit()->get_form_field_types();
    $keys = array_keys($types);
    $form_types = wp_list_pluck($types, 'type');
    $final_types = array_combine($keys, $form_types);
    $validation_schema = [];

    foreach ($form_fields as $form_cfg) {
        $field_label = $form_cfg['label'];
        $field_name = $form_cfg['name'];
        $field_type = $form_cfg['field_type'];

        $validation_schema[$field_name] = [
            'type' => $final_types[$field_type],
        ];

        if (!empty($form_cfg['required'])) {
            if (!empty($form_cfg['field_error_msg'])) {
                $validation_schema[$field_name]['presence']['message'] = __($form_cfg['field_error_msg'], 'fooorms');
            } else {
                $validation_schema[$field_name]['presence']['message'] = __("{$field_label} is required!", 'fooorms');
            }
        }

        if(!empty($form_cfg['field_validation_rules'])) {
            $items = array_values($form_cfg['field_validation_rules']);
            foreach ($items as $value) {
                $rules = array_values($value);
                $rules_value = $rules[0];
                $msg = $rules[1];

                $decoded = json_decode($rules_value, true);
                if (!empty($decoded) && is_array($decoded)) {
                    $validation_schema[$field_name] = array_merge($validation_schema[$field_name], $decoded);
                }
            }
        }
    }

    return $validation_schema;
}

/**
 * @param $form_key
 * @return string
 */
function fooorms_get_smartform_action($form_key) {
    return fooorms_get_form_endpoint_by_key( $form_key );
}

/**
 * @param $form_key
 * @return string
 */
function fooorms_get_smartform_success_msg($form_key, $translate = true) {
    $form = FooormsInit()->fields_provider->form_from_key( $form_key );
    $key = 'form_success_message';
    $post_id = $form['post_id'];
    $msg = function_exists('get_field')
        ? get_field($key, $post_id)
        : get_post_meta($post_id, $key, true);

    if ($translate && class_exists('Builderius\Bundle\ExpressionLanguageBundle\ExpressionLanguage')) {
        $msg = "[[translate('$msg')]]";
    }

    return $msg;
}
