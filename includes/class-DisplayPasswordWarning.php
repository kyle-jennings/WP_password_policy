<?php

class DisplayPasswordWarning{


  public function calculate_password_expiration_times($user){

    $options = get_site_option('gsa_site_settings');

    $lifespan = $options['password_lifespan'];
    $lifespan_timestamp = 60 * 60 * 24 * $lifespan;
    $password_reset = get_user_meta( $user->ID, 'password_reset', true );

    $timestamp = time();
    $one_day = (24 * 60 * 60);

    $expires_in_days = (($password_reset + $lifespan_timestamp) - $timestamp) / $one_day;
    $expires_in_days = round($expires_in_days);

    $expiration_date = gmdate('m/d/Y', $password_reset + $lifespan_timestamp);

    $day = ($expires_in_days > 1) ? 'days' : 'day';
    $warning_label = '';

    return array(
      'password_reset' => $password_reset,
      'expires_in_days' => $expires_in_days,
      'expiration_date'=> $expiration_date,
      'day' => $day
    );
  }

  public function display_password_expiration( $user ) {

      $vars = $this->calculate_password_expiration_times($user);
      extract($vars);
      $output = '';

      if($password_reset):
        $output .= '<h3 style="font-weight: normal; ">Your password expires in ';
            $output .= '<b>'.$expires_in_days . ' ' .$day .'</b> on ';
            $output .= '<b>'.$expiration_date.'</b>';
        $output .= '<h3>';
      else:
        $output .= '<h3 style="font-weight: normal; ">';
          $output .= 'You must reset your password on the next login';
        $output .= '</h3>';
      endif;

      echo $output;
  }


}
