<?php
/*
Plugin Name: Cloudflare Cache Manager
Plugin URI: https://github.com/VishalTaj
description: Wordpress plugin to purge cloudflare page cache
Version: 1.0
Author: Vishal Taj PM
Author URI: https://github.com/VishalTaj
License: GPL2
*/

function cloudflare_cache_manager() {

  /* Purge button in post page */
  add_action( 'post_submitbox_misc_actions', 'purge_me' );
  function purge_me(){
    ?>
      <div id="major-purge-actions" >
        <div id="purge-action">
          <input type="hidden" name="cfm_auth_nonce" value="<?= esc_attr( wp_create_nonce( 'wp_rest' ) ) ?>">
          <button type="button" accesskey="pr" tabindex="5" class="button button-danger" post-url="<?= get_permalink() ?>" id="cf-manager-purge-btn">Purge</button>
        </div>
      </div>
    <?php
  }


  // adding script to wp admin pages
  function cfm_manager_styles() {
    wp_enqueue_style( 'cfm-main-css',  plugin_dir_url( __FILE__ ) . '/cfm-main.css' );
    wp_enqueue_script( 'cfm-main-js',  plugin_dir_url( __FILE__ ) . '/cfm-main.js' );
  }
  add_action( 'admin_enqueue_scripts', 'cfm_manager_styles' );

  // Settings page for keeping CF details
  function cfm_settings_page() {
    ?>
    <h2>Cloudflare Manager</h2>
    <form action="options.php" method="post">
      <?php 
      settings_fields( 'cfm_plugin_options' );
      do_settings_sections( 'cfm_plugin_options' ); ?>
      <input name="submit" class="button button-primary" type="submit" value="<?php esc_attr_e( 'Save' ); ?>" />
    </form>
    <?php
  }

  // Adding setting page path to WP Settings list
  function cfm_add_settings_page() {
    add_options_page( 'Cloudflare Manager', 'Cloudflare Manager', 'manage_options', 'cloudflare-manager', 'cfm_settings_page' );
  }
  add_action( 'admin_menu', 'cfm_add_settings_page' );

  function cfm_register_settings() {
    register_setting( 'cfm_plugin_options', 'cfm_plugin_options');
    add_settings_section( 'api_settings', '', '', 'cfm_plugin_options' );

    add_settings_field( 'cfm_plugin_setting_api_token', 'API Token', 'cfm_plugin_setting_api_token', 'cfm_plugin_options', 'api_settings' );
    add_settings_field( 'cfm_plugin_setting_zone', 'Zone', 'cfm_plugin_setting_zone', 'cfm_plugin_options', 'api_settings' );
  }
  add_action( 'admin_init', 'cfm_register_settings' );

  function cfm_plugin_setting_api_token() {
    $options = get_option( 'cfm_plugin_options' );
    echo "<input id='cfm_plugin_setting_api_token' class='cfm_settings_input' name='cfm_plugin_options[api_token]' type='text' value='" . esc_attr($options['api_token'] ?? '') . "' />";
  }
  function cfm_plugin_setting_zone() {
    $options = get_option( 'cfm_plugin_options' );
    echo "<input id='cfm_plugin_setting_zone' class='cfm_settings_input' name='cfm_plugin_options[zone]' type='text' value='" . esc_attr($options['zone'] ?? '') . "' />";
  }
}

function purge_cache(WP_REST_Request $request) {
  $params = $request->get_params();
  $base_url = "https://api.cloudflare.com/client/v4/";
  $options = get_option( 'cfm_plugin_options' );
  $uri = $base_url . 'zones/' . $options['zone'] . '/purge_cache';
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $uri);
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array('files' => array($params['url']))));
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: Bearer " . $options['api_token']));

  $server_output = json_decode(curl_exec($ch), true);
  curl_close ($ch);

  if ($server_output["success"] == true) { 
    return new WP_REST_Response(array(), 204);
  } else { 
    return new WP_REST_Response($server_output["messages"], 500);
  }
}

add_action( 'rest_api_init', 'init_cf_routes' );

function init_cf_routes() {
  register_rest_route( 'cf-manager/v1', "/purge", array(
    'methods' => 'POST',
    'callback' => 'purge_cache',
    'permission_callback' => function($request){
      return current_user_can( 'edit_others_posts' );
    }
  ));
}

add_action( 'init', 'cloudflare_cache_manager' );
?>