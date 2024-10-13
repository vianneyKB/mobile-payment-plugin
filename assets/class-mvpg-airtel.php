<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class MVPG_Airtel_Gateway extends WC_Payment_Gateway {

    public function __construct() {
        $this->id = 'airtel_money';
        $this->method_title = __('Airtel Money', 'mvpg');
        $this->method_description = __('Allows payments through Airtel Money.', 'mvpg');

        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');

        // Save admin settings
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
    }

    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'mvpg'),
                'type' => 'checkbox',
                'label' => __('Enable Airtel Money Payments', 'mvpg'),
                'default' => 'yes'
            ),
            'title' => array(
                'title' => __('Title', 'mvpg'),
                'type' => 'text',
                'default' => __('Airtel Money', 'mvpg')
            ),
            'description' => array(
                'title' => __('Description', 'mvpg'),
                'type' => 'textarea',
                'default' => __('Pay using Airtel Money.', 'mvpg')
            ),
            'api_key' => array(
                'title' => __('API Key', 'mvpg'),
                'type' => 'text',
                'description' => __('Enter your Airtel API Key.', 'mvpg')
            ),
            'api_secret' => array(
                'title' => __('API Secret', 'mvpg'),
                'type' => 'password',
                'description' => __('Enter your Airtel API Secret.', 'mvpg')
            )
        );
    }

    public function process_payment($order_id) {
        $order = wc_get_order($order_id);
        // Call Airtel Money API here

        // Mark order as complete (or handle response logic)
        $order->payment_complete();

        return array(
            'result' => 'success',
            'redirect' => $this->get_return_url($order)
        );
    }
}
?>
