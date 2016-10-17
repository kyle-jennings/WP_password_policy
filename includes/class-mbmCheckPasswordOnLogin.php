<?php



/**
 * This class is used to force users to reset their password after attempting to login
 */
class CheckPasswordOnLogin {

    public $user = null;
    public $timestamp = null;
    public $user_id = null;
    public $reset_needed = false;

    public $lifespan = 0;
    public $expired;
    public $password_hint = null;


    public function use_network_settings(){

        // if we aren't in a multisite install, or the option and setting are
        if( !is_multisite())
            return false;

        $options = get_site_option('password_policy');


        return false;
    }


    // get the options
    public function get_settings_values(){

        $options = get_site_option('gsa_site_settings');

        $lifespan = $options['password_lifespan'];
        $this->lifespan = $lifespan;
        $this->expired = 60 * 60 * 24 * $this->lifespan;
    }



    /**
     * Init hook
     * Checks to see whether or not we need to reset the password.
     *
     * if we do, we reset the user's password to something random, set the new reset timer,
     * and display a message
     */
    function init($user, $username, $password){

        $this->get_settings_values();


        if($this->lifespan <= 0 ){
            return $user;
        }


        //the next two checks ensure the user is attempting to sign on
        //the authenticate filter is run in different places to this
        //ensures we dont break something

        // Check if an error has already been set
        if ( is_wp_error( $user ) )
            return $user;

        // Check we're dealing with a WP_User object
        if ( ! is_a( $user, 'WP_User' ) )
            return $user;

        // Set the instance attributes
        $this->user = $user;
        $this->user_id = $this->user->data->ID;
        $this->timestamp = time();
        $this->reset_needed = 'false';


        // reset flag
        $reset_needed = 'false';

        // record the last login time
        $this->update_last_login($this->user_id, $this->timestamp);

        // check to see if the password needs to be reset
        $reset_needed = $this->check_password_age($this->timestamp);


        //reset the password and reset the timeout
        if( $reset_needed == 'true'){
            $this->reset_password($this->user_id, $this->timestamp);
            $this->set_password_transient($this->user_id, $this->timestamp);

            $user = new WP_Error(
                'authentication_failed',
                sprintf(
                        __( '<strong>ERROR</strong>: You must <a href="%s">reset your password</a>.', 'mbm' ),
                       site_url( 'wp-login.php?action=lostpassword', 'login' )
                    )
            );
        }



        return $user;
    }


    /**
     * [update_last_login description]
     * @return [type] [description]
     */
    function update_last_login($user_id = null, $timestamp = null){

        // we want to use the timestamp from the login time, but if thats missing
        // then do something. right now im just resetting it
        if($timestamp == null)
            $timestamp = time();

        update_user_meta( $this->user_id, 'last_login', $timestamp );
    }


    /**
     * Check for the {user}_password_reset transient
     * Transiest delete themselves when they expire so if we cant find one
     * we assume the password needs to be reset
     *
     * @return [type] [description]
     */
    function check_password_age($timestamp = null){

        // we want to use the timestamp from the login time, but if thats missing
        // then do something. right now im just resetting it
        if($timestamp == null)
            $timestamp = time();

        // Do we have this information in our transients already?
        $password_reset = get_user_meta($this->user_id, 'password_reset', true);

        // yes? cool that means the password has not yet expired, otherwise we need to reset it
        if( !isset($password_reset) || $this->is_expired($password_reset, $timestamp) )
            return 'true';

        return 'false';
    }


    // Check to see if the timestamp is expired
    function is_expired($password_reset, $timestamp){

        $time_since = $timestamp - $age;

        $one_day = (24 * 60 * 60);

        $expires_in_days = (($password_reset + $this->expired) - time() ) / $one_day;
        $expires_in_days = round($expires_in_days);
        error_log( $expires_in_days );


        if( $expires_in_days <= 0){
            return true;
        }

        return false;
    }


    /**
     * Set the new transient
     * @param  [type] $user_id [description]
     * @return [type]          [description]
     */
    function set_password_transient($user_id, $timestamp = null){

        // we want to use the timestamp from the login time, but if thats missing
        // then do something. right now im just resetting it
        if($timestamp == null)
            $timestamp = time();
        update_user_meta( $this->user_id, 'password_reset', $timestamp );
    }


    /**
     * This is where we actually reset the password
     * an email notification is NOT sent
     */
    function reset_password($user_id = null, $timestamp = null) {

        $new_password = $this->randomPassword();
        wp_set_password($new_password,$this->user_id);

    }

    // reset the expired password
    function randomPassword() {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890!@#$%^&*()-=+';
        $pass = array(); //remember to declare $pass as an array
        $alpha_length = strlen($characters) - 1; //put the length -1 in cache
        for ($i = 0; $i < 12; $i++) {
            $n = rand(0, $alpha_length);
            $pass[] = $characters[$n];
        }
        return implode($pass); //turn the array into a string
    }


}
