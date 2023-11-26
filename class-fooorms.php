<?php

namespace Fooorms;

use Fooorms\ACF;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Fooorms Class
 */
final class Fooorms {

    public $version = '1.1.0';

    protected static $_instance = null;

    /**
     * Throw error on object clone
     *
     * The whole idea of the singleton design pattern is that there is a single
     * object therefore, we don't want the object to be cloned.
     *
     * @return void
     * @since 1.0.0
     */
    public function __clone() {
        _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'fooorms' ), '1.0.0' );
    }

    /**
     * Disable unserializing of the class
     *
     * @return void
     * @since 1.0.0
     */
    public function __wakeup() {
        _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'fooorms' ), '1.0.0' );
    }

    /**
     * Main Pay_Guide Instance
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * Pay_Guide Constructor.
     */
    public function __construct() {
        include_once($this->plugin_path() . '/vendor/autoload.php');
        include_once($this->plugin_path() . '/includes/Mail.php');
        include_once($this->plugin_path() . '/includes/functions.php');
        include_once($this->plugin_path() . '/includes/api.php');

        if ( $this->has_acf() ) {
            include_once($this->plugin_path() . '/includes/acf/fields/input/class-acf-field-input.php');
            include_once($this->plugin_path() . '/includes/acf/functions.php');
            include_once($this->plugin_path() . '/includes/acf/class-extension.php');
            include_once($this->plugin_path() . '/includes/acf/class-admin-forms.php');
            include_once($this->plugin_path() . '/includes/acf/class-admin-entries.php');
            include_once($this->plugin_path() . '/includes/acf/class-admin-emails.php');

            new ACF\Extension();
            new ACF\Admin_Forms();
            new ACF\Admin_Entries();
            new ACF\Admin_Emails();
        }
        $this->init_hooks();
    }

    /**
     *  Init hooks
     */
    private function init_hooks() {
        add_action( 'plugins_loaded', [$this, 'load_plugin_textdomain'], 1, 0 );
        add_action( 'init', [$this, 'register_post_types'], 10, 0 );
        add_action( 'init', [$this, 'register_acf_fields'], 10, 0 );

        if ( is_admin() ) {
            add_action( 'admin_enqueue_scripts', [$this, 'enqueue_admin_styles'], 10, 0 );
        }

        // rest endpoint
        add_action( 'rest_api_init', 'fooorms_register_route' );

        add_filter( 'post_row_actions', [$this, 'ext_row_actions'], 10, 2 );
        add_filter( 'bulk_actions-edit-fooorms_entry', [$this, 'ext_bulk_actions'], 10, 1 );

        // link to Forms page from plugin item
        add_filter( 'plugin_action_links', array($this, 'plugin_add_settings_link'), 10, 5 );
    }

    /**
     * @return void
     */
    function enqueue_admin_styles() {
        wp_enqueue_style( 'fooorms-admin-style', $this->plugin_url() . '/assets/css/admin.css' );
    }

    /**
     * @param $name
     * @param $id
     * @return mixed|null
     */
    function get_meta( $name, $id ) {
        if ( function_exists( 'get_field' ) ) {
            return get_field( $name, $id );
        } else {
            return get_post_meta( $id, $name, true );
        }
    }

    /**
     * @return bool
     */
    function has_acf() {
        return class_exists( 'acf_pro' );
    }

    /**
     * @return void
     */
    function register_post_types() {
        $labels = array(
            'name'                  => _x( 'Forms', 'Post Type General Name', 'fooorms' ),
            'singular_name'         => _x( 'Form', 'Post Type Singular Name', 'fooorms' ),
            'menu_name'             => __( 'Fooorms', 'fooorms' ),
            'name_admin_bar'        => __( 'Form', 'fooorms' ),
            'archives'              => __( 'Form Archives', 'fooorms' ),
            'parent_item_colon'     => __( 'Parent Form:', 'fooorms' ),
            'all_items'             => __( 'Forms', 'fooorms' ),
            'add_new_item'          => __( 'Add New Form', 'fooorms' ),
            'add_new'               => __( 'Add New', 'fooorms' ),
            'new_item'              => __( 'New Form', 'fooorms' ),
            'edit_item'             => __( 'Edit Form', 'fooorms' ),
            'update_item'           => __( 'Update Form', 'fooorms' ),
            'view_item'             => __( 'View Form', 'fooorms' ),
            'search_items'          => __( 'Search Form', 'fooorms' ),
            'not_found'             => __( 'Not found', 'fooorms' ),
            'not_found_in_trash'    => __( 'Not found in Trash', 'fooorms' ),
            'featured_image'        => __( 'Featured Image', 'fooorms' ),
            'set_featured_image'    => __( 'Set featured image', 'fooorms' ),
            'remove_featured_image' => __( 'Remove featured image', 'fooorms' ),
            'use_featured_image'    => __( 'Use as featured image', 'fooorms' ),
            'insert_into_item'      => __( 'Insert into form', 'fooorms' ),
            'uploaded_to_this_item' => __( 'Uploaded to this form', 'fooorms' ),
            'items_list'            => __( 'Forms list', 'fooorms' ),
            'items_list_navigation' => __( 'Forms list navigation', 'fooorms' ),
            'filter_items_list'     => __( 'Filter forms list', 'fooorms' ),
        );
        $args   = array(
            'label'             => __( 'Form', 'fooorms' ),
            'description'       => __( 'Form', 'fooorms' ),
            'labels'            => $labels,
            'supports'          => array('title'),
            'hierarchical'      => false,
            'public'            => false,
            'show_ui'           => true,
            'show_in_menu'      => true,
            'menu_icon'         => 'dashicons-welcome-widgets-menus',
            'menu_position'     => 80,
            'show_in_admin_bar' => false,
            'can_export'        => true,
            'rewrite'           => false,
            'capability_type'   => 'page',
            'query_var'         => false,
        );
        register_post_type( 'fooorms_form', $args );

        // Entry post type
        $labels = array(
            'name'                  => _x( 'Entries', 'Post Type General Name', 'fooorms' ),
            'singular_name'         => _x( 'Entry', 'Post Type Singular Name', 'fooorms' ),
            'menu_name'             => __( 'Entries', 'fooorms' ),
            'name_admin_bar'        => __( 'Entry', 'fooorms' ),
            'archives'              => __( 'Entry Archives', 'fooorms' ),
            'parent_item_colon'     => __( 'Parent Entry:', 'fooorms' ),
            'all_items'             => __( 'Entries', 'fooorms' ),
            'add_new_item'          => __( 'Add New Entry', 'fooorms' ),
            'add_new'               => __( 'Add New', 'fooorms' ),
            'new_item'              => __( 'New Entry', 'fooorms' ),
            'edit_item'             => __( 'Edit Entry', 'fooorms' ),
            'update_item'           => __( 'Update Entry', 'fooorms' ),
            'view_item'             => __( 'View Entry', 'fooorms' ),
            'search_items'          => __( 'Search Entry', 'fooorms' ),
            'not_found'             => __( 'Not found', 'fooorms' ),
            'not_found_in_trash'    => __( 'Not found in Trash', 'fooorms' ),
            'featured_image'        => __( 'Featured Image', 'fooorms' ),
            'set_featured_image'    => __( 'Set featured image', 'fooorms' ),
            'remove_featured_image' => __( 'Remove featured image', 'fooorms' ),
            'use_featured_image'    => __( 'Use as featured image', 'fooorms' ),
            'insert_into_item'      => __( 'Insert into entry', 'fooorms' ),
            'uploaded_to_this_item' => __( 'Uploaded to this entry', 'fooorms' ),
            'items_list'            => __( 'Entries list', 'fooorms' ),
            'items_list_navigation' => __( 'Entries list navigation', 'fooorms' ),
            'filter_items_list'     => __( 'Filter entries list', 'fooorms' ),
        );
        $args   = array(
            'label'             => __( 'Entry', 'fooorms' ),
            'description'       => __( 'Entry', 'fooorms' ),
            'labels'            => $labels,
            'supports'          => array('title',),
            'hierarchical'      => false,
            'public'            => false,
            'show_ui'           => true,
            'show_in_menu'      => 'edit.php?post_type=fooorms_form',
            'menu_icon'         => 'dashicons-welcome-widgets-menus',
            'menu_position'     => 80,
            'show_in_admin_bar' => false,
            'can_export'        => true,
            'rewrite'           => false,
            'capability_type'   => 'page',
            'capabilities'      => array(
                'create_posts' => 'do_not_allow'
            ),
            'map_meta_cap'      => true,
            'query_var'         => false,
        );
        register_post_type( 'fooorms_entry', $args );
    }

    public function ext_row_actions( $actions, $post ) {
        if ( 'fooorms_entry' === $post->post_type ) {
            $actions['edit'] = sprintf(
                '<a href="%s" aria-label="%s">%s</a>',
                admin_url( add_query_arg( array(
                    'post'   => $post->ID,
                    'action' => 'edit'
                ), 'post.php' ) ),
                esc_attr( __( 'View entry', 'fooorms' ) ),
                __( 'View', 'fooorms' )
            );
            // Removes the "Quick Edit" action.
            unset( $actions['inline hide-if-no-js'] );
        }
        return $actions;
    }

    public function ext_bulk_actions( $actions ) {
        unset( $actions['edit'] );
        return $actions;
    }

    public function register_acf_fields() {
        if ( !function_exists( 'acf_register_field_type' ) ) {
            return;
        }

        acf_register_field_type( 'class_fooorms_acf_field_field' );
    }

    public function get_form_field_types() {
        // in smart-form: number, integer, boolean
        return [
            'input_text'    => [
                'type' => 'string',
                'label' => __('Text input (text, email, hidden etc)', 'fooorms')
            ],
            'input_integer' => [
                'type' => 'integer',
                'label' => __('Input type "number" for integers', 'fooorms')
            ],
            'input_number'  => [
                'type' => 'number',
                'label' => __('Input type "number" for decimals', 'fooorms')
            ],
            'textarea'      => [
                'type' => 'string',
                'label' => __('Textarea', 'fooorms')
            ],
            'select'        => [
                'type' => 'string',
                'label' => __('Select', 'fooorms')
            ],
            'input_radio'   => [
                'type' => 'string',
                'label' => __('Radio input', 'fooorms')
            ],
            'checkboxes'    => [
                'type' => 'array',
                'label' => __('Checkboxes', 'fooorms')
            ]
        ];
    }

    /**
     * load_plugin_textdomain()
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'fooorms',
            false,
            plugin_basename( dirname( __FILE__ ) ) . "/languages"
        );
    }

    /**
     * @param $links
     * @return mixed
     */
    public function plugin_add_settings_link( $actions, $plugin_file ) {
        if ( 'fooorms/index.php' === $plugin_file ) {
            array_unshift(
                $actions,
                '<a href="edit.php?post_type=fooorms_form">' . __( 'Forms', 'fooorms' ) . '</a>',
                '<a href="edit.php?post_type=fooorms_entry">' . __( 'Entries', 'fooorms' ) . '</a>'
            );
        }
        return $actions;
    }

    /**
     * plugin_url()
     */
    public function plugin_url() {
        return untrailingslashit( plugins_url( '/', __FILE__ ) );
    }

    /**
     * plugin_path()
     */
    public function plugin_path() {
        return untrailingslashit( plugin_dir_path( __FILE__ ) );
    }

    /**
     * Get Ajax URL.
     * @return string
     */
    public function ajax_url() {
        return admin_url( 'admin-ajax.php', 'relative' );
    }
}
