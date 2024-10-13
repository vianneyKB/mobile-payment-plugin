<?php
/*
 * Plugin Name: Multi-Vendor Payment Gateway
 * Description: A plugin to integrate M-Pesa, Airtel Money, and Orange Money into a multi-vendor WordPress site.
 * Version: 1.0.0
 * Author: Vianney Kondo Bongo
 */

// Security check to prevent direct access to this file
if (!defined('ABSPATH')) {
    exit;
}

// Register activation hook to run code when the plugin is activated
function mvpg_activate() {
    // Code to run on plugin activation (e.g., create custom database tables if needed)
}
register_activation_hook(__FILE__, 'mvpg_activate');

// Register deactivation hook
function mvpg_deactivate() {
    // Code to run on plugin deactivation
}
register_deactivation_hook(__FILE__, 'mvpg_deactivate');

// Load gateway class files
require_once plugin_dir_path(__FILE__) . 'includes/class-mvpg-gateway.php';

// Register the payment gateways
add_action('plugins_loaded', 'mvpg_init_payment_gateways');
function mvpg_init_payment_gateways() {
    if (class_exists('WC_Payment_Gateway')) {
        include_once 'includes/class-mvpg-mpesa.php';
        include_once 'includes/class-mvpg-airtel.php';
        include_once 'includes/class-mvpg-orange.php';

        // Add the gateways to WooCommerce
        add_filter('woocommerce_payment_gateways', 'mvpg_add_gateways');
    }
}

// Enqueue scripts for frontend
add_action('wp_enqueue_scripts', 'mvpg_enqueue_scripts');
function mvpg_enqueue_scripts() {
    wp_enqueue_script('mvpg-payment-script', plugin_dir_url(__FILE__) . 'assets/js/payment-handler.js', array('jquery'), '1.0.0', true);

    // Pass data from PHP to JS
    wp_localize_script('mvpg-payment-script', 'mvpg_vars', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        // 'mpesa_api_url' => 'https://api.safaricom.co.ke/mpesa/',
        // 'airtel_api_url' => 'https://openapi.airtel.africa/',
        // 'orange_api_url' => 'https://api.orange.com/'
        'mpesa_nonce' => wp_create_nonce('mpesa_payment_nonce'),
        'airtel_nonce' => wp_create_nonce('airtel_payment_nonce'),
        'orange_nonce' => wp_create_nonce('orange_payment_nonce'),
    ));
}

// Add custom gateways to WooCommerce
function mvpg_add_gateways($methods) {
    $methods[] = 'MVPG_Mpesa_Gateway';
    $methods[] = 'MVPG_Airtel_Gateway';
    $methods[] = 'MVPG_Orange_Gateway';
    return $methods;
}

// Handle M-Pesa payment AJAX request
add_action('wp_ajax_process_mpesa_payment', 'mvpg_process_mpesa_payment');
add_action('wp_ajax_nopriv_process_mpesa_payment', 'mvpg_process_mpesa_payment');

function mvpg_process_mpesa_payment() {
    check_ajax_referer('mpesa_payment_nonce', 'security'); // Validate nonce

    $order_id = intval($_POST['order_id']);
    $order = wc_get_order($order_id);

    // Get M-Pesa API credentials from the gateway settings
    $mpesa_api_key = get_option('mpesa_api_key');
    $mpesa_api_secret = get_option('mpesa_api_secret');

    // Step 1: Get Access Token
    // $auth_response = wp_remote_post('https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials', array(
    //     'headers' => array(
    //         'Authorization' => 'Basic ' . base64_encode("$mpesa_api_key:$mpesa_api_secret")
    //     )
    // ));

    // Make API request to M-Pesa (using cURL)
    $response = wp_remote_post('https://api.safaricom.co.ke/mpesa/v1/payment', array(
        'body' => json_encode(array(
            'amount' => $order->get_total(),
            'currency' => 'USD',
            'Timestamp' => date('YmdHis'),
            'TransactionType' => 'CustomerPayBillOnline',
            'phoneNumber' => $order->get_billing_phone(),
            'CallBackURL' => site_url('/wc-api/mpesa_callback'),
            'transactionDesc' => 'Payment for Order ' . $order_id
        )),
        'headers' => array(
            'Authorization' => 'Bearer ' . base64_encode($mpesa_api_key . ':' . $mpesa_api_secret),
            'Content-Type' => 'application/json'
        )
    ));
    
    $auth_body = json_decode(wp_remote_retrieve_body($auth_response), true);
    $access_token = $auth_body['access_token'] ?? '';

    if (is_wp_error($response)) {
        wp_send_json_error(array('message' => 'Payment Failed. Unable to get M-Pesa token.'));
    }

    // Parse and handle the API response
    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (isset($body['status']) && $body['status'] === 'success') {
        // Mark order as paid
        $order->payment_complete();
        $order->add_order_note('M-Pesa payment successful.');
        wp_send_json_success(array(
            'redirect_url' => wc_get_order($order_id)->get_checkout_order_received_url()
        ));
    } else {
        $order->add_order_note('M-Pesa payment failed.');
        wp_send_json_error(array('message' => 'M-Pesa payment failed.'));
    }
}

// Handle Airtel payment AJAX request
add_action('wp_ajax_process_airtel_payment', 'mvpg_process_airtel_payment');
add_action('wp_ajax_nopriv_process_airtel_payment', 'mvpg_process_airtel_payment');

function mvpg_process_airtel_payment() {
    check_ajax_referer('airtel_payment_nonce', 'security'); // Validate nonce

    $order_id = intval($_POST['order_id']);
    $order = wc_get_order($order_id);

    $api_key = get_option('airtel_api_key');
    $api_secret = get_option('airtel_api_secret');

    // Get access token
    $response = wp_remote_post('https://openapi.airtel.africa/auth/oauth2/token', array(
        'body' => json_encode(array(
            'client_id' => $api_key,
            'client_secret' => $api_secret,
            'grant_type' => 'client_credentials'
        )),
        'headers' => array('Content-Type' => 'application/json')
    ));

    $body = json_decode(wp_remote_retrieve_body($response), true);
    $access_token = $body['access_token'] ?? '';

    if (!$access_token) {
        wp_send_json_error(array('message' => 'Failed to get Airtel token.'));
    }

    // Initiate payment
    $payment_response = wp_remote_post('https://openapi.airtel.africa/merchant/v1/payments/', array(
        'body' => json_encode(array(
            'amount' => $order->get_total(),
            'currency' => 'USD',
            'msisdn' => $order->get_billing_phone(),
            'reference' => $order_id
        )),
        'headers' => array(
            'Authorization' => 'Bearer ' . $access_token,
            'Content-Type' => 'application/json'
        )
    ));

    $payment_body = json_decode(wp_remote_retrieve_body($payment_response), true);

    if ($payment_body['status'] == 'SUCCESS') {
        $order->payment_complete();
        $order->add_order_note('Airtel Money payment successful.');
        wp_send_json_success(array('redirect_url' => $order->get_checkout_order_received_url()));
    } else {
        $order->add_order_note('Airtel Money payment failed.');
        wp_send_json_error(array('message' => 'Airtel payment failed.'));
    }
}

// Handle Orange payment AJAX request
add_action('wp_ajax_process_orange_payment', 'mvpg_process_orange_payment');
add_action('wp_ajax_nopriv_process_orange_payment', 'mvpg_process_orange_payment');

function mvpg_process_orange_payment() {
    check_ajax_referer('orange_payment_nonce', 'security'); // Nonce verification

    $order_id = intval($_POST['order_id']);
    $order = wc_get_order($order_id);

    $api_key = get_option('orange_api_key');
    $api_secret = get_option('orange_api_secret');

    // Get access token
    $response = wp_remote_post('https://api.orange.com/oauth/v3/token', array(
        'body' => http_build_query(array(
            'grant_type' => 'client_credentials'
        )),
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode("$api_key:$api_secret"),
            'Content-Type' => 'application/x-www-form-urlencoded'
        )
    ));

    $body = json_decode(wp_remote_retrieve_body($response), true);
    $access_token = $body['access_token'] ?? '';

    if (!$access_token) {
        wp_send_json_error(array('message' => 'Failed to get Orange token.'));
    }

    // Initiate payment
    $payment_response = wp_remote_post('https://api.orange.com/orange-money-webpay/', array(
        'body' => json_encode(array(
            'amount' => $order->get_total(),
            'currency' => 'USD',
            'msisdn' => $order->get_billing_phone(),
            'order_id' => $order_id
        )),
        'headers' => array(
            'Authorization' => 'Bearer ' . $access_token,
            'Content-Type' => 'application/json'
        )
    ));

    $payment_body = json_decode(wp_remote_retrieve_body($payment_response), true);

    if ($payment_body['status'] == 'SUCCESS') {
        $order->payment_complete();
        $order->add_order_note('Orange Money payment successful.');
        wp_send_json_success(array('redirect_url' => $order->get_checkout_order_received_url()));
    } else {
        $order->add_order_note('Orange Money payment failed.');
        wp_send_json_error(array('message' => 'Orange payment failed.'));
    }
}
?>