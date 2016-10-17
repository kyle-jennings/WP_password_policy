<?php
/*
    Plugin Name:Standard and Multisite Password Policy
    Description: provide password expiration, emergency mass resetting, and helpful notifications
    Author: Kyle Jennings
    Version: 1.0.0
    Author URI: kylejenningsdesign.com

*/


/**
 * Are we going to use the network settings or lets the individual sites to use their own?
 */
$use_network_settings = false;
if( is_multisite() ){
    $options = get_site_option('password_policy');
    if( !empty($options) && $options['use_network_settings'] )
        $use_network_settings = $options['use_network_settings'];
}

// get options from either the site or the network
if( $use_network_settings == 'yes' )
    $saved_options = get_site_option('password_policy');
else
    $saved_options = get_option('password_policy');


// do the thing
add_action('init', function(){

    if( !current_user_can('manage_options') )
        return;

    include('includes/class-PasswordPolicyAdminOptions.php');

    $admin = new PasswordPolicyAdminOptions;
    $admin->init();

});

// If the policy has not be activated then we dont need to do anything further
if( $saved_options['enforce_policy'] !== 'yes')
    return;



// are we enforcing strong password?
$force_strong_password = $saved_options['force_strong_password'];

// gather files
$includes = array(
    'CheckPasswordOnLogin',
    'UserPasswordReset',
    'LoginPageStyles',
);

// add check password strength file if option is set
if($force_strong_password == 'yes')
    $includes[] = 'CheckPasswordStrength';

// include ze files
foreach($includes as $include){
    include('includes/class-'.$include.'.php');
}

// add our actions and filters
// add_action( 'init', array(new UserPasswordReset, 'init') );

//  when a user is logging in, we need to check their password and reset it if needed
add_filter( 'authenticate', array(new CheckPasswordOnLogin,'init'), 30, 3 );

// display password expiration in profile
add_action( 'show_user_profile', array( new DisplayPasswordWarning, 'display_password_expiration') );
add_action( 'edit_user_profile', array( new DisplayPasswordWarning, 'display_password_expiration') );

// Check password strength
add_action( 'resetpass_form', array(new CheckPasswordStrength, 'validate_resetpass_form'), 10);
add_action( 'validate_password_reset', array(new CheckPasswordStrength, 'validate_password_reset'), 10, 2 );
add_action( 'user_profile_update_errors', array(new CheckPasswordStrength, 'validate_profile_update'), 10, 3 );


// when then user updates their profile and changes their password, set "last reset" time
if ( is_admin() )
    add_action( 'user_profile_update_errors', array(new UserPasswordReset, 'profile_update'), 15, 3 );
else // when a password is reset from the frontend, set "last reset" time
add_action( 'after_password_reset', array(new UserPasswordReset, 'user_reset_password'), 15, 2 );
