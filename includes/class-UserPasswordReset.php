<?php

/**
 * This class is used when a user resets their own password from their profile page
 */
class UserPasswordReset{

    public $lifespan = 90;
    public $user_id;
    public $reset_date;

    public function examine($val){
        if( empty($val) )
            return;

        echo "<pre>";
        print_r($val);
        die;

    }


    public function use_network_settings(){

        // if we aren't in a multisite install, or the option and setting are
        if( !is_multisite())
            return false;

        $options = get_site_option('password_policy');
        if( $options['use_network_settings'] )
            return $options['use_network_settings'];

        return false;
    }


    public function get_settings_values(){

        if( $this->use_network_settings() == 'yes' )
            $options = get_site_option('password_policy');
        else
            $options = get_option('password_policy');

        $this->lifespan = $options['password_lifespan'] ? intval($options['password_lifespan']) : 90 ;

    }


    function __construct(){
        $this->get_settings_values();
        $this->timestamp = time();
    }

    //add hooks
    function init(){

    }

    /**
     * When user successfully changes their password, set the timestamp in user meta.
     */
    function profile_update( $errors, $update, $user ) {

        error_log('before error checking');
        $this->user_id = $user->ID;
        if ( $errors->get_error_data( 'pass' ) || empty( $_POST['pass1'] ) || empty( $_POST['pass2'] ) )
            return;

        if($timestamp == null)
            $timestamp = time();

        // Store timestamp
        update_user_meta( $this->user_id, 'password_reset', $timestamp );
        $new_pass = $_POST['pass1'];
        $this->set_previous_passwords($new_pass);

        error_log('password reset in profile');
    }


    /**
     * When user successfully resets their own password, re-set the timestamp.
     */
     function user_reset_password( $user, $new_pass ) {

        error_log('password reset in form');
        if($timestamp == null)
            $timestamp = time();

        $this->user_id = $user->ID;
        update_user_meta( $this->user_id, 'password_reset', $timestamp );

        $this->set_previous_passwords($new_pass);

    }

    function count_previous_passwords($passwords){
        $prev_passwords = count($passwords);

        error_log('# of passwords: '. $prev_passwords);
        if($prev_passwords > 9)
            array_shift($passwords);

        return $passwords;
    }

    function set_previous_passwords($new_pass){
        error_log($new_pass);
        $passwords = get_user_meta($this->user_id, 'previous_10_passwords', true);
        $passwords = $this->count_previous_passwords($passwords);
        $passwords[] = $new_pass;

        update_user_meta($this->user_id, 'previous_10_passwords', $passwords);
    }

}
