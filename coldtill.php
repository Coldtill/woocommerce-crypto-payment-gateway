<?php
/**
 * Plugin Name: Coldtill for WooCommerce
 * Plugin URI: https://github.com/Coldtill/woocommerce-crypto-payment-gateway
 * Description: Accept USDT, USDC, BTC, ETH, SOL, TRX and 200+ coins straight to your own wallets, through your self-hosted Coldtill crypto payment gateway.
 * Version: 1.0.0
 * Author: Coldtill
 * Author URI: https://coldtill.com
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * WC requires at least: 8.0
 * License: MIT
 * License URI: https://opensource.org/license/mit
 * Text Domain: coldtill
 */

defined('ABSPATH') || exit;

define('COLDTILL_WC_VERSION', '1.0.0');
define('COLDTILL_WC_FILE', __FILE__);

add_action('before_woocommerce_init', function () {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
});

// Carry settings over from the Keyhold plugin this one replaces.
register_activation_hook(__FILE__, function () {
    $old = get_option('woocommerce_keyhold_settings');
    if (is_array($old) && !get_option('woocommerce_coldtill_settings')) {
        update_option('woocommerce_coldtill_settings', $old);
    }
});

add_action('plugins_loaded', function () {
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }
    require_once __DIR__ . '/includes/class-coldtill-client.php';
    require_once __DIR__ . '/includes/class-wc-gateway-coldtill.php';

    add_filter('woocommerce_payment_gateways', function ($gateways) {
        $gateways[] = 'WC_Gateway_Coldtill';
        return $gateways;
    });
});

add_action('woocommerce_blocks_loaded', function () {
    if (!class_exists(\Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType::class)) {
        return;
    }
    require_once __DIR__ . '/includes/class-coldtill-blocks.php';
    add_action('woocommerce_blocks_payment_method_type_registration', function ($registry) {
        $registry->register(new Coldtill_Blocks_Integration());
    });
});
