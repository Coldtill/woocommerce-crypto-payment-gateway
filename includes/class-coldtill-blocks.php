<?php

defined('ABSPATH') || exit;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class Coldtill_Blocks_Integration extends AbstractPaymentMethodType
{
    protected $name = 'coldtill';

    public function initialize(): void
    {
        $this->settings = get_option('woocommerce_coldtill_settings', []);
    }

    public function is_active(): bool
    {
        $gateways = WC()->payment_gateways()->payment_gateways();
        return isset($gateways['coldtill']) && $gateways['coldtill']->is_available();
    }

    public function get_payment_method_script_handles(): array
    {
        wp_register_script(
            'coldtill-blocks',
            plugins_url('assets/blocks.js', COLDTILL_WC_FILE),
            ['wc-blocks-registry', 'wc-settings', 'wp-element', 'wp-html-entities'],
            COLDTILL_WC_VERSION,
            true
        );
        return ['coldtill-blocks'];
    }

    public function get_payment_method_data(): array
    {
        return [
            'title'       => $this->settings['title'] ?? 'Pay with crypto',
            'description' => $this->settings['description'] ?? '',
        ];
    }
}
