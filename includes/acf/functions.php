<?php
/**
 * @return array
 */
function fooorms_acf_get_forms() {
    $forms = array();

    // Get all forms saved as posts
    $form_query = new WP_Query( [
        'post_type'      => 'fooorms_form',
        'posts_per_page' => -1,
    ] );

    if ( $form_query->have_posts() ) {
        foreach ( $form_query->posts as $form_post ) {
            $form    = fooorms_acf_form_from_post( $form_post );
            $forms[] = $form;
        }
    }

    return $forms;
}

/**
 * @param $form_post
 * @return false|mixed|void
 */
function fooorms_acf_form_from_post( $form_post ) {
    // Get post object if ID has been passed
    if ( is_numeric( $form_post ) ) {
        $form_post = get_post( $form_post );
    }

    // Make sure we have a post and that it's a form
    if ( !$form_post || 'fooorms_form' !== $form_post->post_type ) {
        return false;
    }

    $form_emails = get_field( 'form_emails', $form_post->ID );

    if (!is_array($form_emails)) {
        $form_emails = [];
    }

    return fooorms_acf_get_valid_form( array(
        'post_id'        => $form_post->ID,
        'title'          => $form_post->post_title,
        'key'            => get_post_meta( $form_post->ID, 'form_key', true ),
        'display'        => array(
            'description'     => get_field( 'form_description', $form_post->ID ),
            'success_message' => get_field( 'form_success_message', $form_post->ID ),
        ),
        'create_entries' => get_field( 'form_create_entries', $form_post->ID ),
        'emails'         => array_filter(
            $form_emails,
            function ( $v ) {
                return !empty($v['fooorms_active']);
            } )
    ) );
}

/**
 * @param $form
 * @return array|false
 */
function fooorms_acf_get_valid_form( $form ) {

    // A form key is always required
    if ( !isset( $form['key'] ) ) {
        return false;
    }

    $args = array(
        'key'            => '',
        'post_id'        => false,
        'title'          => '',
        'display'        => array(
            'description'     => '',
            'success_message' => '',
        ),
        'create_entries' => false,
        'emails'         => []
    );

    return wp_parse_args( $form, $args );
}

/**
 * @param $key
 * @return bool
 */
function fooorms_acf_is_valid_form_key( $key ) {
    if ( !is_string( $key ) ) {
        return false;
    }

    if ( 'form_' == substr( $key, 0, 5 ) ) {
        return true;
    }

    return false;
}

/**
 * @param $key
 * @return array|false|mixed|void
 */
function fooorms_acf_form_from_key( $key ) {
    $post = fooorms_acf_form_post_from_key( $key );

    if ( $post ) {
        return fooorms_acf_form_from_post( $post );
    }

    return false;
}

/**
 * @param $key
 * @return false|int|WP_Post
 */
function fooorms_acf_form_post_from_key( $key ) {
    $args = array(
        'post_type'      => 'fooorms_form',
        'posts_per_page' => '1',
        'meta_query'     => array(
            array(
                'key'   => 'form_key',
                'value' => $key,
            ),
        ),
    );

    $form_query = new WP_Query( $args );

    if ( $form_query->have_posts() ) {
        return $form_query->posts[0];
    }

    return false;
}

/**
 * @param $form_id_or_key
 * @return array|false|mixed|void
 */
function fooorms_acf_get_form( $form_id_or_key ) {
    $form = false;

    if ( fooorms_acf_is_valid_form_key( $form_id_or_key ) ) {
        $form = fooorms_acf_form_from_key( $form_id_or_key );

    } elseif ( is_numeric( $form_id_or_key ) ) {
        $form = fooorms_acf_form_from_post( $form_id_or_key );

    }

    return $form;
}

/**
 * @param $form_key
 * @return array
 */
function fooorms_acf_get_form_field_groups( $form_key ) {
    // If a full form array is passed
    if ( is_array( $form_key ) ) {
        $form_key = $form_key['key'];
    }

    // Location rule filter
    $args = array(
        'fooorms_form' => $form_key,
    );

    return acf_get_field_groups( $args );
}

/**
 * @param $form_key
 * @param $type
 * @return array
 */
function fooorms_acf_get_form_fields( $form_key, $type = 'all' ) {
    $exclude_types = array();
    $include_types = array();

    // Only pick fields which can be properly stringified (not repeaters, flexible fields etc.)
    if ( 'regular' === $type ) {
        $exclude_types = array('repeater', 'clone', 'flexible_content');
    }

    if ( 'options' === $type ) {
        $include_types = array(
            'foorms_field'
        );
    }

    $form_fields = array();

    $field_groups = fooorms_acf_get_form_field_groups( $form_key );

    if ( $field_groups ) {
        foreach ( $field_groups as $field_group ) {
            $fields = acf_get_fields( $field_group );
            if ( !empty ( $fields ) ) {
                foreach ( $fields as $field ) {
                    if ( 'regular' === $type && in_array( $field['type'], $exclude_types ) ||
                        'options' === $type && !in_array( $field['type'], $include_types ) ||
                        !in_array( $type, ['regular', 'options'] ) && $field['type'] !== $type ) {
                        continue;
                    }

                    $form_fields[] = $field;
                }
            }
        }
    }

    return $form_fields;
}

/**
 * @param $form_key
 * @param $type
 * @return array
 */
function _fooorms_acf_form_field_choices( $form_key, $type = 'all' ) {
    $form_fields = fooorms_acf_get_form_fields( $form_key, $type );
    $choices     = [];

    if ( !empty( $form_fields ) ) {
        foreach ( $form_fields as $field ) {
            $choices[$field['key']] = $field['label'];
        }
    }

    return $choices;
}

/**
 * @param $form_key
 * @param $type
 * @return array
 */
function _fooorms_acf_form_field_names( $form_key, $type = 'all' ) {
    $form_fields = fooorms_acf_get_form_fields( $form_key, $type );
    $choices     = [];

    if ( !empty( $form_fields ) ) {
        foreach ( $form_fields as $field ) {
            $choices[$field['key']] = $field['name'];
        }
    }

    return $choices;
}

/**
 * @param $form_key
 * @param $type
 * @return array
 */
function _fooorms_acf_entry_content_starter( $form_key, $type = 'all' ) {
    $form_fields = fooorms_acf_get_form_fields( $form_key, $type );
    $content     = [];

    if ( !empty( $form_fields ) ) {
        foreach ( $form_fields as $field ) {
            $content[$field['name']] = [
                'label' => $field['label'],
                'value' => ''
            ];
        }
    }

    return $content;
}