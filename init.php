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

// password resetting
add_action( 'password_reset', array(new UserPasswordReset, 'user_reset_password') );
// when then user updates their profile and changes their password
if ( is_admin() )
    add_action( 'user_profile_update_errors', array(new UserPasswordReset, 'profile_update'), 11, 3 );
add_filter('password_hint', array(new UserPasswordReset, 'password_hint'));


add_filter( 'authenticate', array(new CheckPasswordOnLogin,'init'), 30, 3 );
add_action( 'login_enqueue_scripts', array(new LoginPageStyles, 'init') );

// add password stength action if option is set
if($force_strong_password == 'yes')
    add_action( 'validate_password_reset', array(new CheckPasswordStrength, 'init'), 10, 2 );
