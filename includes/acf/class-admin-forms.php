<?php

namespace Fooorms\ACF;

class Admin_Forms {

    function __construct() {
        // Actions
        add_action( 'admin_init', [$this, 'add_fields_meta_box'], 10, 0 );
        add_action( 'edit_form_after_title', [$this, 'display_form_key'], 10, 0 );
        add_action( 'save_post', [$this, 'add_form_key'], 10, 3 );
        add_filter( 'add_post_metadata', [$this, 'should_add_form_key_meta'], 10, 3 );
        add_action( 'acf/init', [$this, 'register_fields'], 10, 0 );

        add_filter( 'manage_fooorms_form_posts_columns', [$this, 'manage_columns'], 10, 1 );
        add_action( 'manage_fooorms_form_posts_custom_column', [$this, 'custom_columns_content'], 10, 2 );
        add_filter( 'disable_months_dropdown', [$this, 'disable_months_filter'], 10, 2 );
    }

    /**
     * @param $post_id
     * @param $post
     * @param $update
     * @return void
     */
    function add_form_key( $post_id, $post, $update ) {
        if ( 'fooorms_form' == $post->post_type && !get_post_meta( $post->ID, 'form_key', true ) ) {
            $form_key = 'form_' . uniqid();
            update_post_meta( $post->ID, 'form_key', $form_key );
        }
    }

    /**
     * @param $check
     * @param $object_id
     * @param $meta_key
     * @return false|mixed
     */
    function should_add_form_key_meta( $check, $object_id, $meta_key ) {
        if ( 'form_key' !== $meta_key ) {
            return $check;
        }

        // If a form key already exists, we don't want to save another one
        if ( metadata_exists( 'post', $object_id, $meta_key ) ) {
            return false;
        }

        return $check;
    }

    /**
     * @return void
     */
    function display_form_key() {
        global $post;

        if ( 'fooorms_form' == $post->post_type && $form_key = get_post_meta( $post->ID, 'form_key', true ) ) {
            echo '<div id="edit-slug-box">';
            echo sprintf( '<strong>%s </strong>%s', __( 'Form key:', 'fooorms' ), $form_key );
            echo '</div>';
        }
    }

    /**
     * @return void
     */
    function add_fields_meta_box() {
        add_meta_box(
            'fooorms_form_fields',
            __( 'Fields', 'fooorms' ),
            array($this, 'fields_meta_box_callback'),
            'fooorms_form',
            'normal',
            'default',
            null
        );
    }

    /**
     * @return void
     */
    function fields_meta_box_callback() {
        global $post;

        $form = FooormsInit()->fields_provider->get_form( $post->ID );

        // Get field groups for the current form
        $field_groups = FooormsInit()->fields_provider->get_form_field_groups( $form['key'] );
        ?>

        <p><?php _e( 'Fields can be added by setting the location of your fields group to this form. The following fields are added:', 'fooorms' ); ?></p>

        <table class="widefat fooorms-field-group-table">
            <thead>
            <tr>
                <th scope="col"><?php _e( 'Label', 'fooorms' ) ?></th>
                <th scope="col"><?php _e( 'Name', 'fooorms' ) ?></th>
                <th scope="col"><?php _e( 'Type', 'fooorms' ) ?></th>
                <th scope="col"><?php _e( 'Is required?', 'fooorms' ) ?></th>
            </tr>
            </thead>
            <tbody>
            <?php if ( !empty( $field_groups ) ) : ?>
                <?php foreach ( $field_groups as $field_group ) : ?>
                    <?php
                    // Get all fields for this field group
                    $fields = acf_get_fields( $field_group );
                    ?>
                    <tr class="field-group-heading">
                        <td colspan="4">
                            <a href="<?php echo get_edit_post_link( $field_group['ID'] ); ?>"><?php echo $field_group['title']; ?></a>
                        </td>
                    </tr>
                    <?php foreach ( $fields as $field ) :
                        ?>
                        <tr>
                            <td><?php echo $field['label']; ?></td>
                            <td><?php echo $field['name']; ?></td>
                            <td><?php echo $field['field_type']; ?></td>
                            <td><?php echo !empty($field['required']) ? __('Yes', 'fooorms') : __('No', 'fooorms'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3">
                        <?php _e( 'No field groups connected to this form', 'fooorms' ); ?>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
        <a href="<?php echo admin_url( 'post-new.php?post_type=acf-field-group' ); ?>" class="button">
            <?php _e( 'Create field group', 'fooorms' ); ?>
        </a>
        <?php
    }

    /**
     * Adds custom columns to the listings page
     *
     * @since 1.0.0
     *
     */
    function manage_columns( $columns ) {
        $new_columns = array(
            'key'     => __( 'Submit Endpoint', 'fooorms' ),
            'fields'  => __( 'Fields', 'fooorms' ),
            'entries' => __( 'Entries', 'fooorms' ),
        );

        // Remove date column
        unset( $columns['date'] );
        return array_merge( array_splice( $columns, 0, 2 ), $new_columns, $columns );
    }

    /**
     * Outputs the content for the custom columns
     *
     * @since 1.0.0
     *
     */
    function custom_columns_content( $column, $post_id ) {
        $form = FooormsInit()->fields_provider->get_form( $post_id );

        if ( 'key' === $column ) {
            $form_key = get_post_meta( $post_id, 'form_key', true );
            echo '<p>' . __('Type', 'fooorms') . ': <code>POST</code>' . '</p>';
            echo '<p>' . __('URL', 'fooorms') . ': <code class="fooorms_endpoint_code">' . fooorms_get_form_endpoint_by_key( $form_key ) . '</code>' . '</p>';
        } else if ( 'fields' === $column ) {
            $count        = 0;
            $field_groups = FooormsInit()->fields_provider->get_form_field_groups( $form['key'] );

            // Count the number of fields in all field groups
            foreach ( $field_groups as $field_group ) {
                $fields = acf_get_fields( $field_group );
                $count  += count( $fields );
            }
            echo $count;
        } else if ( 'entries' === $column ) {
            $entries = fooorms_get_entry_count( $form['key'] );
            echo sprintf( '<a href="%s">%s</a>', admin_url() . 'edit.php?post_type=fooorms_entry&entry_form=' . $form['key'], $entries );
        }
    }

    /**
     * Hides the months filter on the forms listing page.
     *
     * @since 1.6.5
     *
     */
    function disable_months_filter( $disabled, $post_type ) {
        if ( 'fooorms_form' !== $post_type ) {
            return $disabled;
        }

        return true;
    }

    /**
     * Registers the form settings fields
     *
     * @since 1.0.0
     *
     */
    function register_fields() {
        $settings_field_group = array(
            'key'                   => 'group_form_settings',
            'title'                 => __( 'Form settings', 'fooorms' ),
            'fields'                => array(
                array(
                    'key'               => 'field_form_display_tab',
                    'label'             => '<span class="dashicons dashicons-visibility"></span>' . __( 'Display', 'fooorms' ),
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
                ),
                array(
                    'key'               => 'field_form_description',
                    'label'             => __( 'Description', 'fooorms' ),
                    'name'              => 'form_description',
                    'type'              => 'textarea',
                    'instructions'      => '',
                    'required'          => 0,
                    'conditional_logic' => 0,
                    'wrapper'           => array(
                        'width' => '',
                        'class' => '',
                        'id'    => '',
                    ),
                    'default_value'     => '',
                    'tabs'              => 'all',
                    'toolbar'           => 'full',
                    'media_upload'      => 1,
                ),
                array(
                    'key'               => 'field_form_success_message',
                    'label'             => __( 'Success message', 'fooorms' ),
                    'name'              => 'form_success_message',
                    'type'              => 'wysiwyg',
                    'instructions'      => __( 'The message displayed after a successful submission.', 'fooorms' ),
                    'required'          => 0,
                    'conditional_logic' => 0,
                    'wrapper'           => array(
                        'width' => '',
                        'class' => '',
                        'id'    => '',
                    ),
                    'default_value'     => '',
                    'tabs'              => 'all',
                    'toolbar'           => 'full',
                    'media_upload'      => 1,
                ),
                array(
                    'key'               => 'field_form_statistics_tab',
                    'label'             => '<span class="dashicons dashicons-chart-bar"></span>' . __( 'Statistics', 'fooorms' ),
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
                ),
                array(
                    'key'               => 'field_form_num_of_submission_attempts',
                    'label'             => __( 'Number of submission attempts', 'fooorms' ),
                    'name'              => 'form_num_of_submission_attempts',
                    'type'              => 'number',
                    'instructions'      => '',
                    'required'          => 0,
                    'conditional_logic' => 0,
                    'wrapper'           => array(
                        'width' => '50',
                        'class' => '',
                        'id'    => '',
                    ),
                    'default_value'     => 0,
                    'placeholder'       => '',
                    'prepend'           => '',
                    'append'            => '',
                    'min'               => '',
                    'max'               => '',
                    'step'              => '',
                    'readonly'          => true,
                ),
                array(
                    'key'               => 'field_form_num_of_submission_success',
                    'label'             => __( 'Number of successful submissions', 'fooorms' ),
                    'name'              => 'form_num_of_submission_success',
                    'type'              => 'number',
                    'instructions'      => '',
                    'required'          => 0,
                    'conditional_logic' => 0,
                    'wrapper'           => array(
                        'width' => '50',
                        'class' => '',
                        'id'    => '',
                    ),
                    'default_value'     => 0,
                    'placeholder'       => '',
                    'prepend'           => '',
                    'append'            => '',
                    'min'               => '',
                    'max'               => '',
                    'step'              => '',
                    'readonly'          => true,
                )
            ),
            'location'              => array(
                array(
                    array(
                        'param'    => 'post_type',
                        'operator' => '==',
                        'value'    => 'fooorms_form',
                    ),
                ),
            ),
            'menu_order'            => 0,
            'position'              => 'normal',
            'style'                 => 'default',
            'label_placement'       => 'top',
            'instruction_placement' => 'label',
            'hide_on_screen'        => '',
            'active'                => 1,
            'description'           => '',
        );

        $settings_field_group = apply_filters( 'fooorms/acf/form/settings_fields', $settings_field_group );
        acf_add_local_field_group( $settings_field_group );
    }
}