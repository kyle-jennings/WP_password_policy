<?php

/**
 * This class creates the admin forms to set the policy settings
 */

class PasswordPolicyAdminOptions {

    public $lifespan = null;

    public function use_network_settings(){

        // if we aren't in a multisite install, or the option and setting are
        if( !is_multisite())
            return false;

        $options = get_site_option('password_policy');
        if( $options['use_network_settings'] )
            return $options['use_network_settings'];

        return false;
    }


    // Examine a value
    public function examine($val){
        if( empty($val) )
            return;

        echo "<pre>";
        print_r($val);
        die;

    }

    function init(){

        // set up the menus
        if( is_network_admin() )
            add_action('network_admin_menu', array($this, 'add_top_level_menus'));
        elseif( !is_network_admin() && $this->use_network_settings() !== 'yes' )
            add_action('admin_menu', array($this, 'add_top_level_menus'));

        // the multisite options saving is hacky
        if(is_network_admin()){
            add_action(
              'network_admin_edit_update_network_password_policy',
              array($this,'update_network_password_policy')
            );
        }

        // register the settings
        add_action('admin_init', array($this, 'setup_settings_section'));
        add_action('admin_init', array($this, 'setup_settings_fields'));
        add_action('admin_init', array($this, 'register_settings'));

    }


    /**
     * Set up the menu and the page
     */

    // The top menu item (appears on the left side menu in dashboard)
    function add_top_level_menus(){

        $cap = 'administrator';
        // if we are only using network settings, set the cap to a network admin cap
        if( $this->use_network_settings() == 'yes' )
            $cap = 'manage_network_options';

        add_menu_page(
            'Password Policy', // page title
            'Password Policy', // menu title
            $cap, // cap
            'password_policy', // slug
            array($this, 'display_password_admin_page'), // callback
            'dashicons-welcome-view-site'
        );
    }

    // the landing page
    function display_password_admin_page(){

        $action = is_network_admin() ? 'edit.php?action=update_network_password_policy' : 'options.php';

        echo '<div class="wrap">';
            echo '<h2> Password Policy </h2>';
           echo '<form action="'.$action.'" method="post">';

                do_settings_sections( 'password_policy' );
                settings_fields( 'password_policy' );
                submit_button();

            echo '</form>';

        echo '</div>';

    }


    /**
     * These be the fields and section settings
     */
    // set up the field sections
    public function setup_settings_section(){

        // we need to set up sections to organize our fields first
        add_settings_section(
            'password_policy_section',
            '',
            null,
            'password_policy'
        );


    }


    // Set up the fields
    public function setup_settings_fields(){

        if( is_network_admin() )
            $saved_options = get_site_option('password_policy');
        else
            $saved_options = get_option('password_policy');

        add_settings_field(
            'password_lifespan',
            'Password Lifespan',
            array($this, 'password_lifespan_field'),
            'password_policy',
            'password_policy_section',
            $saved_options['password_lifespan']
        );

        add_settings_field(
            'password_hint',
            'Password Hint',
            array($this, 'settings_password_hint_field'),
            'password_policy',
            'password_policy_section',
            $saved_options['password_hint']
        );


        // add_settings_field(
        //     'settings_reset_page_styles',
        //     'Password Reset Page Styles',
        //     array($this, 'settings_reset_page_styles_field'),
        //     'password_policy',
        //     'password_policy_section',
        //     $saved_options['settings_reset_page_styles']
        // );

        add_settings_field(
            'force_strong_password',
            'Force Strong Passwords',
            array($this, 'settings_force_strong_password_field'),
            'password_policy',
            'password_policy_section',
            $saved_options['force_strong_password']
        );

        add_settings_field(
            'enforce_policy',
            'Enforce Policy',
            array($this, 'settings_enforce_policy_field'),
            'password_policy',
            'password_policy_section',
            $saved_options['enforce_policy']
        );

        if( is_network_admin() )
            add_settings_field(
                'use_network_settings',
                'Use Network Settings',
                array($this, 'settings_use_network_settings_field'),
                'password_policy',
                'password_policy_section',
                $saved_options['use_network_settings']
            );

        // // password strength settings
        // add_settings_field(
        //     'password_length_settings',
        //     'Force Strong Passwords',
        //     array($this, 'settings_password_length_settings_field'),
        //     'password_policy',
        //     'password_policy_section',
        //     $saved_options['password_length_settings']
        // );

        // // amount of numerals in password
        // add_settings_field(
        //     'password_numeral_count_settings',
        //     'Force Strong Passwords',
        //     array($this, 'settings_password_numeral_count_settings_field'),
        //     'password_policy',
        //     'password_policy_section',
        //     $saved_options['password_numeral_count_settings']
        // );

        // // amount of capital characters
        // add_settings_field(
        //     'password_capitals_count_settings',
        //     'Force Strong Passwords',
        //     array($this, 'settings_password_capitals_count_settings_field'),
        //     'password_policy',
        //     'password_policy_section',
        //     $saved_options['password_capitals_count_settings']
        // );

        // // amount of lowers characters
        // add_settings_field(
        //     'password_lowers_count_settings',
        //     'Force Strong Passwords',
        //     array($this, 'settings_password_lowers_count_settings_field'),
        //     'password_policy',
        //     'password_policy_section',
        //     $saved_options['password_lowers_count_settings']
        // );


    }

    // register the settings pages
    public function register_settings(){
        register_setting(
            'password_policy',
            'password_policy'
        );
    }

    /**
     * The fields
     */
    public function settings_enforce_policy_field( $args = array() ){
        $value  = isset($args) ? $args : 'no';

        $options = array('yes','no');

        $output = '';
        // Note the ID and the name attribute of the element match that of the ID in the call to add_settings_field
        $output .= '<select name="password_policy[enforce_policy]">';
            foreach($options as $option):
                $selected = selected( $value, $option, false);

                $output .= '<option value="'.$option.'" '.$selected.'>';
                    $output .= $option;
                $output .= '</option>';
            endforeach;

        $output .= '</select>';

        echo $output;
    }



    // Use the network settings over the individual site settings
    public function settings_use_network_settings_field( $args = array() ){
        $value  = isset($args) ? $args : 'no';

        $options = array('yes','no');

        $output = '';
        // Note the ID and the name attribute of the element match that of the ID in the call to add_settings_field
        $output .= '<select name="password_policy[use_network_settings]">';
            foreach($options as $option):
                $selected = selected( $value, $option, false);

                $output .= '<option value="'.$option.'" '.$selected.'>';
                    $output .= $option;
                $output .= '</option>';
            endforeach;

        $output .= '</select>';

        echo $output;
    }


    // toggle password strenth enforcment
    public function settings_force_strong_password_field( $args = array() ){
        $value  = isset($args) ? $args : 'no';

        $options = array('yes','no');

        $output = '';
        // Note the ID and the name attribute of the element match that of the ID in the call to add_settings_field
        $output .= '<select name="password_policy[force_strong_password]">';
            foreach($options as $option):
                $selected = selected( $value, $option, false);

                $output .= '<option value="'.$option.'" '.$selected.'>';
                    $output .= $option;
                $output .= '</option>';
            endforeach;

        $output .= '</select>';

        echo $output;
    }


    // setup the lifespan field
    public function password_lifespan_field($args = array()){

        $value  = (intval($args) && isset($args) && !empty($args) ) ? $args : '0';

        $output = '';
        // Note the ID and the name attribute of the element match that of the ID in the call to add_settings_field
        $output .= '<input type="number" name="password_policy[password_lifespan]" min="0" max="180" value="'.$value.'">';

        echo $output;
    }

    // the password hint field - uses wp editor
    public function settings_password_hint_field($args = array()){

        $value = ( is_string($args) && !empty($args) && isset($args) ) ? $args : '' ;
        $settings = array(
            'media_buttons' => false,
            'textarea_rows' => 5,
            'textarea_name' => 'password_policy[password_hint]'
        );

        wp_editor( $value, 'password_hint', $settings );
    }

    // the styles field
    public function settings_reset_page_styles_field($args = array()){

        $value = ( is_string($args) && !empty($args) && isset($args) ) ? $args : '' ;
        $output = '';

        $output .= '<textarea class="full-width" name="password_policy[settings_reset_page_styles]" rows="10" style="width: 100%;">';
            $output .= $value;
        $output .= '</textarea>';
        echo $output;
    }




    // hacky shit to save the network options

    function update_network_password_policy() {
        check_admin_referer('password_policy-options');

        // This is the list of registered options.
        global $new_whitelist_options;
        $options = $new_whitelist_options['password_policy'];

        // Go through the posted data and save only our options. This is a generic
        // way to do this, but you may want to address the saving of each option
        // individually.
        foreach ($options as $option) {
            if (isset($_POST[$option])) {
                $values = $_POST[$option];
                update_site_option($option, $values);
            } else {
              delete_site_option($option);
            }
        }

        // At last we redirect back to our options page.
        wp_redirect(add_query_arg(array('page' => 'password_policy',
          'updated' => 'true'), network_admin_url('admin.php')));
        exit;
    }
}
