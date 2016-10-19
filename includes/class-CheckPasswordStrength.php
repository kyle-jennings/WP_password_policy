<?php


class CheckPasswordStrength{
    public $user = '';
    public $new_pass = '';
    private $force_strong_password = 'yes';
    private $force_extra_strong_pw = 'no';
    private $errors;

    public function __construct(){
        $this->force_strong_password = 'yes';
    }


    // Examine a value
    public function examine($val){
        if( empty($val) )
            return;
        echo "<pre>";
        print_r($val);
        die;
    }

    // Check user profile update and throw an error if the password isn't strong
    public function validate_profile_update( $errors, $update, $user_data ) {
        // $errors = $this->validate_password_reset( $errors, $user_data );
    	return $this->validate_password_reset( $errors, $user_data );
    }

    // Check password reset form and throw an error if the password isn't strong
    public function validate_resetpass_form( $user_data ) {

    	return $this->validate_password_reset( false, $user_data );
    }


    private function set_error($error){
        if(is_wp_error($errors))
            $this->errors->add( 'pass', $error, 'mbm_bad_password' );
        else
            $this->errors = new WP_Error( 'pass', $error, 'mbm_bad_password' );

        return $this->errors;
    }


    // validate_password_reset!
    public function validate_password_reset( $errors, $user ){

        // // if no user data has been sent
        if( !isset( $_POST[ 'pass1' ]) || empty($_POST[ 'pass1' ]) )
            return;

        // clear the errors so we can override the wordpress strength checking
        // $errors = new WP_Error();
        // collect
        $password_ok = false;
        $password    = ( isset( $_POST[ 'pass1' ] ) && trim( $_POST[ 'pass1' ] ) ) ? sanitize_text_field( $_POST[ 'pass1' ] ) : false;
        $role        = isset( $_POST[ 'role' ] ) ? sanitize_text_field( $_POST[ 'role' ] ) : false;
        $user_id     = isset( $user->ID ) ? sanitize_text_field( $user->ID ) : false;
        $username    = isset( $_POST["user_login"] ) ? sanitize_text_field( $_POST["user_login"] ) : $user->user_login ;
        $email       = isset( $user->user_email ) ? sanitize_text_field( $user->user_email ) : false;


        $last_reset = get_user_meta($user_id, 'password_reset', true);

        // password must be 1 day old
        // if( ! mbm_is_older_than(2, $last_reset) ){
        //     $error = 'You password must be more than 1 day old before you can reset it';
        //     if(is_wp_error( $errors ))
        //         $errors->add( 'pass', $error, 'mbm_bad_password' );
        //     else
        //         $errors = new WP_Error( 'pass', $error, 'mbm_bad_password' );
        //
        //     return $errors;
        // }


        // Password cannot match any of hte previous 10
        $prev_passwords = get_user_meta($user_id, 'previous_10_passwords', true);

        if( in_array($password, $prev_passwords ) ){
            $error = 'You cannot use any of your previous 10 passwords';
            return $this->set_error($error);
        }

        // if a password was not supplied then FAIL
        if($password == false){
            $error = 'You did not enter a password';
            return $this->set_error($error);
        }

        // Already got a password error?
    	if ( ( false === $password ) || ( is_wp_error( $errors ) && $errors->get_error_data( 'pass' ) ) ) {
    		return $this->errors;
    	}

        // calculate password strength
        $results = $this->calc_strength_score($password, $username, $email);

        // get pass and errors
        $password_ok = $results['acceptable'];
        $strength_errors = $results['errors'];

        // if we have errors and password is not ok then we set the WP_Errors
        if(!empty($strength_errors) && $password_ok != true){
            foreach($strength_errors as $error){
                if(is_wp_error($errors))
                    $errors->add( 'pass', $error, 'mbm_bad_password' );
                else
                    $errors = new WP_Error( 'pass', $error, 'mbm_bad_password' );

            }
        }

        // return the errors
        if(is_wp_error($errors || $password_ok != true))
            return $errors;

    }


    function calc_strength_score( $password, $username, $email ) {

        if($this->force_strong_password != 'yes')
            return;

        $results = array(
            'errors'=>array(),
            'acceptable'=>false
        );

        $short = 1; // too short
        $weak = 2; // weak
        $medium = 3; // medium
        $strong = 4; // strong

        // used to add the strength level
        $strength_meter = 0;

        // math s tuff
        $g = null;
        $c = null;


        // if the password is too short, return
        if ( strlen( $password ) < 8 )
            $results['errors'][] = 'Your password is too short, it needs to be
                at least 8 characters long';

        // if the password is the same as the username, return
        if ( strtolower( $password ) == strtolower( $username ) )
            $results['errors'][] = 'Your password cannot match your username';

        // the password cannot be your email
        if( strtolower( $password ) == strtolower( $email ) )
            $results['errors'][] = 'Your password cannot match your email';

        // if the password contains at least 1 numeric charcter
        if ( preg_match( "/[0-9]/", $password ) )
            $strength_meter += 10;
        else
            $results['errors'][] = 'Your password needs at least 1 number';

        // if the password contains at least 1 lowercase alpha character
        if ( preg_match( "/[a-z]/", $password ) )
            $strength_meter += 26;
        else
            $results['errors'][] = 'Your password needs at least 1 lowercase letter';

        // if the password contains at least 1 uppercase alpha character
        if ( preg_match( "/[A-Z]/", $password ) )
            $strength_meter += 26;
        else
            $results['errors'][] = 'Your password needs at least 1 uppercase letter';


        // if the password contains a symbol
        if ( preg_match( "/[^a-zA-Z0-9]/", $password ) )
            $strength_meter += 31;
        else
            $results['errors'][] = 'Your password needs at least 1 symbol';


        // At this point, if there are any errors the password is not acceptable
        if( !empty($results['errors']) ){
            return $results;
        }


        if($this->force_extra_strong_pw != 'yes' )
            return $results['acceptable'] = true;


        // math stuff
        $g = log( pow( $strength_meter, strlen( $password ) ) );

        // more math stuff
        $c = $g / log( 2 );

        // if password strength is less than 40
        if ( $c < 40 )
            $results['errors'][] = 'Your password is too weak';
        elseif ( $c < 70 )
            $results['errors'][] = 'Your password is only medium strength';

        // its a strong password
        if ( $c > 70)
            $results['acceptable'] = true;

        return $results;
    }
}
