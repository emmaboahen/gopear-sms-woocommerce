<?php
/*
Plugin Name: GoPear SMS Integration for WooCommerce
Description: Send SMS alerts using GoPear SMS when customers place orders in WooCommerce.
Version: 1.0
Author: Emmanuel Boahen
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Hook to send SMS after order is placed
add_action('woocommerce_new_order', 'send_gopear_sms_after_order', 10, 1);

function send_gopear_sms_after_order($order_id) {
    $order = wc_get_order($order_id);
    $phone = $order->get_billing_phone();

    if (!empty($phone) && is_ghanaian_number($phone)) {
        $message = 'Thank you for your order. Your order ID is ' . $order_id . '.';
        send_gopear_sms($phone, $message);
    }
}

// Function to check if the phone number is from Ghana
function is_ghanaian_number($phone) {
    // Modify the regular expression based on Ghanaian phone number patterns
    $ghana_pattern = '/^\+233[0-9]{9}$/';

    return (bool) preg_match($ghana_pattern, $phone);
}

// Function to send GoPear SMS
function send_gopear_sms($to, $message) {
    $api_key = get_option('gopear_sms_api_key');
    $sender_id = get_option('gopear_sms_sender_id');

    if (empty($api_key) || empty($sender_id)) {
        error_log('GoPear SMS API Key or Sender ID is not configured.');
        return;
    }

    $url = 'https://sms.gopeartech.com/smsapi?key=' . $api_key . '&to=' . $to . '&msg=' . urlencode($message) . '&sender_id=' . $sender_id;

    // Use wp_remote_get to make the HTTP request
    $response = wp_remote_get($url);

    // Check if the request was successful
    if (is_wp_error($response)) {
        error_log('GoPear SMS API Request Error: ' . $response->get_error_message());
    } else {
        // Log the response (optional)
        $response_body = wp_remote_retrieve_body($response);
        error_log('GoPear SMS API Response: ' . $response_body);
    }
}

// Add custom settings page
add_action('admin_menu', 'gopear_sms_admin_menu');

function gopear_sms_admin_menu() {
    add_options_page('GoPear SMS Settings', 'GoPear SMS', 'manage_options', 'gopear-sms-settings', 'gopear_sms_settings_page');
}

// Register settings
add_action('admin_init', 'gopear_sms_register_settings');

function gopear_sms_register_settings() {
    register_setting('gopear_sms_settings_group', 'gopear_sms_api_key');
    register_setting('gopear_sms_settings_group', 'gopear_sms_sender_id');
}

// Create settings page content
function gopear_sms_settings_page() {
    ?>
    <div class="wrap">
        <h1>GoPear SMS Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields('gopear_sms_settings_group'); ?>
            <?php do_settings_sections('gopear_sms_settings_group'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">GoPear SMS API Key</th>
                    <td><input type="text" name="gopear_sms_api_key" value="<?php echo esc_attr(get_option('gopear_sms_api_key')); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">GoPear SMS Sender ID</th>
                    <td><input type="text" name="gopear_sms_sender_id" value="<?php echo esc_attr(get_option('gopear_sms_sender_id')); ?>" /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
