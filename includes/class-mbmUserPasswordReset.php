<?php

/**
 * This class is used when a user resets their own password from their profile page
 */
class UserPasswordReset{

    public $password_hint = null;
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
        $this->password_hint = $options['password_hint'] ? $options['password_hint'] : null ;

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


        $this->user_id = $user->ID;
        if ( $errors->get_error_data( 'pass' ) || empty( $_POST['pass1'] ) || empty( $_POST['pass2'] ) )
            return;

        if($timestamp == null)
            $timestamp = time();

        $new_pass = $_POST['pass1'];
        $this->user_save_last_10_passwords($new_pass);

        // Store timestamp
        update_user_meta( $this->user_id, 'password_reset', $timestamp );
    }


    /**
     * When user successfully resets their own password, re-set the timestamp.
     */
    function user_reset_password( $user, $new_pass ) {

        if($timestamp == null)
            $timestamp = time();

        $this->user_id = $user->ID;
        $this->user_save_last_10_passwords($new_pass);

        update_user_meta( $this->user_id, 'password_reset', $timestamp );
    }


    function user_save_last_10_passwords(){
        $passwords = get_user_meta($this->user_id, 'previous_10_passwords', true);
        $passwords = $this->count_previous_passwords($passwords);
        $passwords[] = $new_pass;


        update_user_meta($this->user_id, 'previous_10_passwords', $passwords);
    }


    /**
     * Set the password hint if set
     * @param  [type] $string [description]
     * @return [type]         [description]
     */
    function password_hint($string){

        if($this->password_hint)
            $string = $this->password_hint;

        $title = '<label for="hint">&nbsp;</label>';
        $string = $title . $string;
        return $string;
    }

    function count_previous_passwords($passwords){
        $prev_passwords = count($passwords);

        error_log('# of passwords: '. $prev_passwords);
        if($prev_passwords > 9)
            array_shift($passwords);

        return $passwords;
    }
}
