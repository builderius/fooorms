<?php

namespace Fooorms\ACF;

class Extension {

    function __construct() {
        add_filter( 'acf/location/rule_types', [$this, 'add_form_location_type'], 10, 1 );
        add_filter( 'acf/location/rule_values/fooorms_form', [$this, 'form_location_rule_values'], 10, 1 );
        add_filter( 'acf/location/rule_match/fooorms_form', [$this, 'form_location_rule_match'], 10, 3 );
        add_filter( 'acf/load_field', [$this, 'forms_select'], 10, 1 );
    }

    /**
     * @param $choices
     * @return array
     */
    function add_form_location_type( $choices ) {
        $choices['"Fooorms" Forms']['fooorms_form'] = 'Form';

        return $choices;
    }


    /**
     * @param $choices
     * @return mixed
     */
    function form_location_rule_values( $choices ) {
        $forms = fooorms_acf_get_forms();

        foreach ( $forms as $form ) {
            $choices[$form['key']] = $form['title'];
        }

        return $choices;
    }

    /**
     * @param $match
     * @param $rule
     * @param $options
     * @return bool|mixed
     */
    function form_location_rule_match( $match, $rule, $options ) {
        // Match with form object
        if ( 'fooorms_form' == $rule['param'] && isset( $options['fooorms_form'] ) ) {
            if ( isset( $rule['value'] ) && $rule['value'] == $options['fooorms_form'] ) {
                $match = true;
            }

        }

        return $match;
    }

    /**
     * @param $field
     * @return mixed
     */
    function forms_select( $field ) {
        if ( 'fooorms_form' === $field['name'] ) {
            $all_forms = fooorms_acf_get_forms();
            $all_keys = wp_list_pluck($all_forms, 'key');
            $all_titles = wp_list_pluck($all_forms, 'title');

            if (!empty($all_forms)) {
                $field['choices'] = array_combine( $all_keys, $all_titles );
            } else {
                $field['choices'] = [];
            }
        }

        return $field;
    }
}