<?php


/**
 * This class adds some custom styles to the password reset/login page
 */
class LoginPageStyles {
    function init() {

        $css =  plugins_url('css/styles.css',dirname(__FILE__) );
        wp_enqueue_style( 'custom-login', $css );
    }
}
