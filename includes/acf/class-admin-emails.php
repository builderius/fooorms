<?php

namespace Fooorms\ACF;

class Admin_Emails {

    function __construct() {
        add_filter( 'acf/load_field/name=fooorms_recipient_field', [$this, 'populate_email_field_choices'], 10, 1 );
        add_filter( 'acf/load_field/name=fooorms_subject', [$this, 'add_variables_to_instructions'], 10, 1 );
        add_filter( 'acf/load_field/name=fooorms_content', [$this, 'add_variables_to_instructions'], 10, 1 );
        add_filter( 'fooorms/acf/form/settings_fields', [$this, 'email_acf_fields'], 10, 1 );
    }

    /**
     * @param $field
     * @return mixed
     */
    function populate_email_field_choices( $field ) {
        global $post;

        if ( $post && 'fooorms_form' == $post->post_type ) {
            $form_key         = get_post_meta( $post->ID, 'form_key', true );
            $field['choices'] = _fooorms_acf_form_field_choices( $form_key, 'options' );
        }

        return $field;
    }

    /**
     * @param $field
     * @return mixed
     */
    function add_variables_to_instructions( $field ) {
        global $post;

        if ( $post && 'fooorms_form' == $post->post_type ) {
            $form_key  = get_post_meta( $post->ID, 'form_key', true );
            $variables = _fooorms_acf_form_field_names( $form_key, 'options' );
            $variables = array_values( $variables );

            if ( !empty( $variables ) ) {
                $vars                  = array_map(
                    function ( $var ) {
                        return "<span>{{" . $var . "}}</span>";
                    },
                    $variables
                );
                
                $field['instructions'] .= ' ';
                $field['instructions'] .= sprintf(
                    __( 'You can use these dynamic variables from the form: %s. Also, these persistent variables: %s', 'fooorms' ),
                    join( ', ', $vars ),
                    join( ', ', ['<span>{{site_name}}</span>', '<span>{{site_url}}</span>'] )
                );
            }
        }

        return $field;
    }

    /**
     * @param $field_group
     * @return mixed|void
     */
    function email_acf_fields( $field_group ) {
        $field_group['fields'][] = array(
            'key'               => 'field_form_notifications_tab',
            'label'             => '<span class="dashicons dashicons-email-alt"></span>' . __( 'Notifications', 'fooorms' ),
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
            'key'               => 'field_form_emails',
            'label'             => __( 'Emails', 'fooorms' ),
            'name'              => 'form_emails',
            'type'              => 'repeater',
            'instructions'      => __( 'The emails defined here will be sent upon successful submission.', 'fooorms' ),
            'required'          => 0,
            'conditional_logic' => 0,
            'wrapper'           => array(
                'width' => '',
                'class' => '',
                'id'    => '',
            ),
            'collapsed'         => 'field_form_email_name',
            'min'               => '',
            'max'               => '',
            'layout'            => 'block',
            'button_label'      => __( 'Add new email', 'fooorms' ),
            'sub_fields'        => array(
                array(
                    'key'               => 'field_form_email_name',
                    'label'             => __( 'Name', 'fooorms' ),
                    'name'              => 'fooorms_name',
                    'type'              => 'text',
                    'instructions'      => '',
                    'required'          => 1,
                    'conditional_logic' => 0,
                    'wrapper'           => array(
                        'width' => '66',
                        'class' => '',
                        'id'    => '',
                    ),
                    'default_value'     => '',
                    'placeholder'       => 'Name this email for your reference',
                    'prepend'           => '',
                    'append'            => '',
                    'maxlength'         => '',
                ),
                array(
                    'key'               => 'field_form_email_active',
                    'label'             => __( 'Active?', 'fooorms' ),
                    'name'              => 'fooorms_active',
                    'type'              => 'true_false',
                    'instructions'      => '',
                    'required'          => 0,
                    'conditional_logic' => 0,
                    'wrapper'           => array(
                        'width' => '33',
                        'class' => '',
                        'id'    => '',
                    ),
                    'message'           => 'Yes',
                    'default_value'     => 1,
                ),
                array(
                    'key'               => 'field_form_email_recipient_type',
                    'label'             => __( 'Send to', 'fooorms' ),
                    'name'              => 'fooorms_recipient_type',
                    'type'              => 'radio',
                    'instructions'      => '',
                    'required'          => 0,
                    'conditional_logic' => 0,
                    'wrapper'           => array(
                        'width' => '',
                        'class' => '',
                        'id'    => '',
                    ),
                    'choices'           => array(
                        'field'  => __( 'Select from field', 'fooorms' ),
                        'custom' => __( 'Custom recipient', 'fooorms' ),
                    ),
                    'allow_null'        => 0,
                    'other_choice'      => 0,
                    'save_other_choice' => 0,
                    'default_value'     => '',
                    'layout'            => 'horizontal',
                    'return_format'     => 'value',
                ),
                array(
                    'key'               => 'field_form_email_recipient_field',
                    'label'             => __( 'Recipient field', 'fooorms' ),
                    'name'              => 'fooorms_recipient_field',
                    'type'              => 'select',
                    'instructions'      => '',
                    'required'          => 0,
                    'conditional_logic' => array(
                        array(
                            array(
                                'field'    => 'field_form_email_recipient_type',
                                'operator' => '==',
                                'value'    => 'field',
                            ),
                        ),
                    ),
                    'wrapper'           => array(
                        'width' => '',
                        'class' => '',
                        'id'    => '',
                    ),
                    'choices'           => array(),
                    'default_value'     => array(),
                    'allow_null'        => 0,
                    'multiple'          => 0,
                    'ui'                => 0,
                    'ajax'              => 0,
                    'return_format'     => 'value',
                    'placeholder'       => '',
                ),
                array(
                    'key'               => 'field_form_email_recipient_custom',
                    'label'             => __( 'Custom recipient', 'fooorms' ),
                    'name'              => 'fooorms_recipient_custom',
                    'type'              => 'text',
                    'instructions'      => '',
                    'required'          => 0,
                    'conditional_logic' => array(
                        array(
                            array(
                                'field'    => 'field_form_email_recipient_type',
                                'operator' => '==',
                                'value'    => 'custom',
                            ),
                        ),
                    ),
                    'wrapper'           => array(
                        'width' => '',
                        'class' => '',
                        'id'    => '',
                    ),
                    'default_value'     => '',
                    'placeholder'       => '',
                    'prepend'           => '',
                    'append'            => '',
                    'maxlength'         => '',
                ),
                array(
                    'key'               => 'field_form_email_from',
                    'label'             => __( 'From', 'fooorms' ),
                    'name'              => 'fooorms_from',
                    'type'              => 'text',
                    'instructions'      => 'Must be either an email address or on the form "Name &#x3C;Email address&#x3E;".',
                    'required'          => 0,
                    'conditional_logic' => 0,
                    'wrapper'           => array(
                        'width' => '',
                        'class' => '',
                        'id'    => '',
                    ),
                    'default_value'     => '',
                    'placeholder'       => '',
                    'prepend'           => '',
                    'append'            => '',
                    'maxlength'         => '',
                ),
                array(
                    'key'               => 'field_form_email_subject',
                    'label'             => __( 'Subject', 'fooorms' ),
                    'name'              => 'fooorms_subject',
                    'type'              => 'text',
                    'instructions'      => '',
                    'required'          => 0,
                    'conditional_logic' => 0,
                    'wrapper'           => array(
                        'width' => '',
                        'class' => '',
                        'id'    => '',
                    ),
                    'default_value'     => '',
                    'placeholder'       => '',
                    'prepend'           => '',
                    'append'            => '',
                    'maxlength'         => '',
                ),
                array(
                    'key'               => 'field_form_email_content',
                    'label'             => __( 'Content', 'fooorms' ),
                    'name'              => 'fooorms_content',
                    'type'              => 'wysiwyg',
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
            ),
        );

        $field_group = apply_filters( 'fooorms/acf/form/notification_settings_fields', $field_group );

        return $field_group;
    }
}