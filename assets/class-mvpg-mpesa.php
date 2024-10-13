<?php
if (!defined('ABSPATH')) {
    exit;
}

class MVPG_Mpesa_Gateway extends WC_Payment_Gateway {

    public function __construct() {
        $this->id = 'mpesa';
        $this->method_title = __('M-Pesa', 'mvpg');
        $this->method_description = __('Allows payments through M-Pesa.', 'mvpg');

        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
    }

    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'mvpg'),
                'type' => 'checkbox',
                'label' => __('Enable M-Pesa Payments', 'mvpg'),
                'default' => 'yes'
            ),
            'title' => array(
                'title' => __('Title', 'mvpg'),
                'type' => 'text',
                'default' => __('M-Pesa', 'mvpg')
            ),
            'description' => array(
                'title' => __('Description', 'mvpg'),
                'type' => 'textarea',
                'default' => __('Pay with M-Pesa.', 'mvpg')
            ),
            'api_key' => array(
                'title' => __('API Key', 'mvpg'),
                'type' => 'text',
                'description' => __('Enter your M-Pesa API Key.', 'mvpg')
            ),
            'api_secret' => array(
                'title' => __('API Secret', 'mvpg'),
                'type' => 'password',
                'description' => __('Enter your M-Pesa API Secret.', 'mvpg')
            )
        );
    }

    public function process_payment($order_id) {
        $order = wc_get_order($order_id);
        // Handle payment process here by calling M-Pesa API

        // Mark order as complete
        $order->payment_complete();

        return array(
            'result' => 'success',
            'redirect' => $this->get_return_url($order)
        );
    }
}
?>
