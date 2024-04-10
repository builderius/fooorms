<?php

namespace Fooorms\ACF;

class Admin_Entries {

    function __construct() {
        // Actions
        add_action( 'acf/init', [$this, 'register_custom_fields'], 10, 0 );
        add_action( 'manage_fooorms_entry_posts_custom_column', [$this, 'custom_columns_content'], 10, 2 );
        add_action( 'restrict_manage_posts', [$this, 'form_filter'], 10, 0 );
        add_action( 'pre_get_posts', [$this, 'filter_entries_by_form'], 10, 1 );

        // Filters
        add_filter( 'acf/prepare_field/name=entry_form', [$this, 'entry_form_field'], 10, 1 );
        add_filter( 'acf/prepare_field/name=entry_submission_info', [$this, 'entry_submission_info_field'], 10, 1 );
        add_filter( 'acf/prepare_field/name=entry_submission_data', [$this, 'entry_submission_data_field'], 10, 1 );
        add_filter( 'acf/prepare_field/name=entry_submission_attachments', [$this, 'entry_submission_attachments_field'], 10, 1 );
        add_filter( 'acf/prepare_field/name=form_create_entries', [$this, 'add_entries_link_to_instruction'], 10, 1 );
        add_filter( 'manage_fooorms_entry_posts_columns', [$this, 'add_custom_columns'], 10, 1 );
        add_filter( 'fooorms/acf/form/settings_fields', [$this, 'add_form_settings_fields'], 10, 1 );
    }

    /**
     * @param $field
     * @return array
     */
    function entry_form_field( $field ) {
        global $post;

        if ( $post && 'fooorms_entry' == $post->post_type ) {
            // Get the current form, should be false if we are creating a new entry
            $form = FooormsInit()->fields_provider->get_form( get_post_meta( $post->ID, 'entry_form', true ) );

            if ( $form ) {
                // Add a link to edit the form
                if ( $form['post_id'] ) {
                    $field['instructions'] = sprintf( '<a href="%s">%s</a>', get_edit_post_link( $form['post_id'] ), $form['title'] );
                }
            }
        }

        return $field;

    }


    /**
     * @param $field
     * @return mixed
     */
    function entry_submission_info_field( $field ) {
        global $post;

        if ( $post && 'fooorms_entry' == $post->post_type ) {

            $time = get_post_meta( $post->ID, 'entry_submission_date', true );

            $date_format = get_option( 'date_format' );
            $time_format = get_option( 'time_format' );

            $field['instructions'] = sprintf( '<strong>%s: </strong>#%d', __( 'Entry ID', 'fooorms' ), $post->ID );
            $field['instructions'] .= '<br>';
            $field['instructions'] .= sprintf( '<strong>%s: </strong>%s', __( 'Date', 'fooorms' ), get_date_from_gmt( $time, $date_format ) );
            $field['instructions'] .= '<br>';
            $field['instructions'] .= sprintf( '<strong>%s: </strong>%s', __( 'Time', 'fooorms' ), get_date_from_gmt( $time, $time_format ) );

        }

        return $field;

    }

    /**
     * @param $field
     * @return mixed
     */
    function entry_submission_data_field( $field ) {
        global $post;

        if ( $post && 'fooorms_entry' == $post->post_type ) {

            $entry_content = get_post_meta( $post->ID, 'entry_content', true );

            if ( !empty( $entry_content ) ) {
                foreach ( $entry_content as $field_name => $field_data ) {
                    if ( is_array( $field_data['value'] ) ) {
                        $values_data = [];
                        foreach ( $field_data['value'] as $attach_id ) {
                            if (is_wp_error($attach_id)) {
                                $values_data[] = implode( ', ', $attach_id->get_error_messages() );
                            } else {
                                $values_data[] = $attach_id;
                            }
                        }
                        $value = implode( ', ', $values_data );
                    } else {
                        $value = $field_data['value'];
                    }

                    $field['instructions'] .= sprintf(
                        '<div class="record"><p class="label"><strong>%s: </strong></p>%s</div>',
                        $field_data['label'],
                        wp_kses_post( wpautop( $value ) )
                    );
                }
            }

        }

        return $field;

    }


    function entry_submission_attachments_field( $field ) {
        global $post;

        if ( $post && 'fooorms_entry' == $post->post_type ) {

            $attachments = get_posts( array(
                'post_type'      => 'attachment',
                'posts_per_page' => -1,
                'post_parent'    => $post->ID
            ) );

            if ( !empty( $attachments ) ) {
                foreach ( $attachments as $attachment ) {
                    $field['instructions'] .= sprintf(
                        '<a href="%s" target="_blank">%s (#%d)</a>',
                        esc_url( wp_get_attachment_url( $attachment->ID ) ),
                        get_the_title( $attachment ),
                        $attachment->ID
                    );
                }
            } else {
                $field['instructions'] = __( 'No attachments added', 'fooorms' );
            }

        }

        return $field;

    }


    /**
     * @param $columns
     * @return array
     */
    function add_custom_columns( $columns ) {
        $new_columns = array(
            'form' => __( 'Form', 'fooorms' ),
        );

        return array_merge( array_splice( $columns, 0, 2 ), $new_columns, $columns );
    }


    /**
     * @param $column
     * @param $post_id
     * @return void
     */
    function custom_columns_content( $column, $post_id ) {
        if ( 'form' === $column ) {
            $form_id = get_post_meta( $post_id, 'entry_form', true );
            $form    = FooormsInit()->fields_provider->get_form( $form_id );

            echo sprintf( '<a href="%s">%s</a>', get_edit_post_link( $form['post_id'] ), $form['title'] );
        }
    }


    /**
     * Add drop down to filter by form on listings page
     *
     * @since 1.0.0
     *
     */
    function form_filter() {

        if ( !isset( $_GET['post_type'] ) || 'fooorms_entry' != $_GET['post_type'] ) {
            return;
        }

        $forms = FooormsInit()->fields_provider->get_forms();

        $current_form = '';
        if ( isset( $_GET['entry_form'] ) ) {
            $current_form = $_GET['entry_form'];
        }

        ?>

        <select name="entry_form">
            <option value=""><?php _e( 'All forms', 'fooorms' ); ?></option>

            <?php
            foreach ( $forms as $form ) {

                $selected = ($form['key'] == $current_form) ? 'selected' : '';
                echo sprintf( '<option value="%s" %s>%s</option>', $form['key'], $selected, $form['title'] );

            }
            ?>
        </select>

        <?php
    }


    /**
     * Filters by form if the dropdown has been set
     *
     * @since 1.0.0
     *
     */
    function filter_entries_by_form( $query ) {

        if ( is_admin() && isset( $_GET['entry_form'] ) && $_GET['entry_form'] != '' && 'fooorms_entry' == $query->query['post_type'] ) {

            $query->set( 'meta_query', array(
                array(
                    'key'   => 'entry_form',
                    'value' => $_GET['entry_form'],
                ),
            ) );

        }

    }


    /**
     * Adds a link to view entries for a form in the "Create entries?" form instructions
     *
     * @since 1.0.0
     *
     */
    function add_entries_link_to_instruction( $field ) {
        global $post;

        if ( $post && 'fooorms_form' == $post->post_type && get_post_meta( $post->ID, 'form_create_entries', true ) ) {
            $form                  = FooormsInit()->fields_provider->get_form( $post->ID );
            $field['instructions'] .= sprintf( '<a href="%s">%s</a>', admin_url() . '/edit.php?post_type=fooorms_entry&entry_form=' . $form['key'], __( 'View entries for this form', 'fooorms' ) );
        }

        return $field;
    }


    /**
     * Add form settings for entries
     *
     * @since 1.0.2
     *
     */
    function add_form_settings_fields( $field_group ) {

        $field_group['fields'][] = array(
            'key'               => 'field_form_entries_tab',
            'label'             => '<span class="dashicons dashicons-editor-table"></span>' . __( 'Entries', 'fooorms' ),
            'name'              => '',
            'type'              => 'tab',
            'instructions'      => '',
            'required'          => 0,
            'conditional_logic' => 0,
            'wrapper'           => array(
                'width' => '',
                'class' => '',
                'id'    => '',
            ),
            'placement'         => 'left',
            'endpoint'          => 0,
        );


        $field_group['fields'][] = array(
            'key'               => 'field_form_create_entries',
            'label'             => __( 'Create entries?', 'fooorms' ),
            'name'              => 'form_create_entries',
            'type'              => 'true_false',
            'instructions'      => __( 'When entries are enabled they will be automatically generated with form submissions, with all the submitted field data.', 'fooorms' ),
            'required'          => 0,
            'conditional_logic' => 0,
            'wrapper'           => array(
                'width' => '',
                'class' => '',
                'id'    => '',
            ),
            'message'           => '',
            'default_value'     => 0,
            'ui'                => 1,
            'ui_on_text'        => 'Yes',
            'ui_off_text'       => 'No',
        );


        return $field_group;

    }


    /**
     * Register ACF fields for general entry data
     *
     * @since 1.0.0
     *
     */
    function register_custom_fields() {
        acf_add_local_field_group( array(
            'key'                   => 'group_entry_data',
            'title'                 => __( 'Entry data', 'fooorms' ),
            'fields'                => array(
                array(
                    'key'               => 'field_entry_submission_info',
                    'label'             => __( 'Submission info', 'fooorms' ),
                    'name'              => 'entry_submission_info',
                    'type'              => 'message',
                    'instructions'      => '',
                    'required'          => 0,
                    'conditional_logic' => 0,
                    'wrapper'           => array(
                        'width' => '75',
                        'class' => '',
                        'id'    => '',
                    ),
                ),
                array(
                    'key'               => 'field_entry_form',
                    'label'             => __( 'Form', 'fooorms' ),
                    'name'              => 'entry_form',
                    'type'              => 'message',
                    'instructions'      => '',
                    'required'          => 0,
                    'conditional_logic' => 0,
                    'wrapper'           => array(
                        'width' => '25',
                        'class' => '',
                        'id'    => '',
                    ),
                ),
                array(
                    'key'               => 'field_entry_submission_data',
                    'label'             => __( 'Submission data', 'fooorms' ),
                    'name'              => 'entry_submission_data',
                    'type'              => 'message',
                    'instructions'      => '',
                    'required'          => 0,
                    'conditional_logic' => 0,
                    'wrapper'           => array(
                        'width' => '100',
                        'class' => '',
                        'id'    => '',
                    ),
                ),
                array(
                    'key'               => 'field_entry_submission_attachments',
                    'label'             => __( 'Submission attachments', 'fooorms' ),
                    'name'              => 'entry_submission_attachments',
                    'type'              => 'message',
                    'instructions'      => '',
                    'required'          => 0,
                    'conditional_logic' => 0,
                    'wrapper'           => array(
                        'width' => '100',
                        'class' => '',
                        'id'    => '',
                    ),
                )
            ),
            'location'              => array(
                array(
                    array(
                        'param'    => 'post_type',
                        'operator' => '==',
                        'value'    => 'fooorms_entry',
                    ),
                ),
            ),
            'menu_order'            => 0,
            'position'              => 'acf_after_title',
            'style'                 => 'default',
            'label_placement'       => 'top',
            'instruction_placement' => 'label',
            'hide_on_screen'        => '',
            'active'                => 1,
            'description'           => '',
        ) );

    }
}