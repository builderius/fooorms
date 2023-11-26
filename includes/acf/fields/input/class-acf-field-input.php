<?php

/**
 * Defines the custom field type class.
 */

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * acf_field_input class.
 */
class class_fooorms_acf_field_field extends \acf_field {
    /**
     * Controls field type visibilty in REST requests.
     *
     * @var bool
     */
    public $show_in_rest = false;

    /**
     * Environment values relating to the theme or plugin.
     *
     * @var array $env Plugin or theme context such as 'url' and 'version'.
     */
    private $env;

    /**
     * Constructor.
     */
    public function __construct() {

        $this->name        = 'foorms_field';
        $this->label       = __( 'Form field', 'fooorms' );
        $this->category    = 'Fooorms';
        $this->description = __( 'A form field', 'acf' );

        /**
         * Defaults for your custom user-facing settings for this field type.
         */
        $this->defaults = array(
            'type' => 'text',
        );

        $this->env = array(
            'url'     => site_url( str_replace( ABSPATH, '', __DIR__ ) ),
            'version' => '1.0',
        );

        parent::__construct();
    }

    /**
     *
     * @param array $field
     * @return void
     */
    public function render_field_validation_settings( $field ) {
        $types = FooormsInit()->get_form_field_types();
        $keys = array_keys($types);
        $labels = wp_list_pluck($types, 'label');
        $final_labels = array_combine($keys, $labels);

        acf_render_field_setting( $field, array(
            'label'        => __( 'Required error message', 'fooorms' ),
            'instructions' => __( 'Set custom message for validation rule "required"', 'fooorms' ),
            'name'         => 'field_error_msg',
            'type'         => 'textarea',
            'value'        => !empty( $field['field_error_msg'] ) ? $field['field_error_msg'] : '',
            'choices'      => $final_labels,
            'conditions'   => [
                'field'    => 'required',
                'operator' => '==',
                'value'    => '1'
            ]
        ) );

        acf_render_field_setting( $field, array(
            'label'        => __( 'Form field', 'fooorms' ),
            'instructions' => __( 'Choose a form field type', 'fooorms' ),
            'name'         => 'field_type',
            'type'         => 'select',
            'value'        => !empty( $field['field_type'] ) ? $field['field_type'] : '',
            'choices'      => $final_labels
        ) );

        acf_render_field_setting( $field,
            array(
                'key'           => 'field_validation_rules',
                'label'         => __( 'Additional validation rules', 'fooorms' ),
                'hint'          => __( 'Define validation rules for the field', 'fooorms' ),
                'name'          => 'validation_rules',
                'type'          => 'repeater',
                'layout'        => 'row',
                'pagination'    => 0,
                'min'           => 0,
                'max'           => 0,
                'collapsed'     => '',
                'button_label'  => __( 'Add new rule', 'fooorms' ),
                'rows_per_page' => 20,
                'value'         => !empty( $field['field_validation_rules'] ) ? $field['field_validation_rules'] : [],
                'sub_fields'    => array(
                    array(
                        'key'               => 'field_646234e211bee',
                        'label'             => 'Rule (expression)',
                        'name'              => 'rule',
                        'aria-label'        => '',
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
                        'maxlength'         => '',
                        'rows'              => '',
                        'placeholder'       => '',
                        'new_lines'         => '',
                    ),
                    array(
                        'key'               => 'field_646234f611bef',
                        'label'             => 'Error message',
                        'name'              => 'msg',
                        'aria-label'        => '',
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
                        'maxlength'         => '',
                        'rows'              => '',
                        'placeholder'       => '',
                        'new_lines'         => '',
                    ),
                )
            )
        );
    }

    function render_field_presentation_settings( $field ) {
        return;
    }

    function render_field_conditional_logic_settings( $field ) {
        return;
    }

    /**
     * HTML content to show when a publisher edits the field on the edit screen.
     *
     * @param array $field The field settings and values.
     * @return void
     */
    public function render_field() {
        return;
    }

    /**
     * Enqueues CSS and JavaScript needed by HTML in the render_field() method.
     *
     * Callback for admin_enqueue_script.
     *
     * @return void
     */
    public function input_admin_enqueue_scripts() {
    }
}
