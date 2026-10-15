<?php

defined('ABSPATH') || exit;

class WC_Gateway_Coldtill extends WC_Payment_Gateway
{
    public const META_REF = '_coldtill_reference_id';

    public function __construct()
    {
        $this->id                 = 'coldtill';
        $this->method_title       = 'Coldtill';
        $this->method_description = 'Self-custody crypto payments through your own Coldtill install. Funds go straight to your wallets.';
        $this->has_fields         = false;
        $this->supports           = ['products'];

        $this->init_form_fields();
        $this->init_settings();
        $this->title       = $this->get_option('title');
        $this->description = $this->get_option('description');

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
        add_action('woocommerce_api_coldtill', [$this, 'handle_webhook']);
        add_action('woocommerce_thankyou_' . $this->id, [$this, 'sync_on_thankyou']);
    }

    public function init_form_fields(): void
    {
        $this->form_fields = [
            'enabled'        => ['title' => 'Enable', 'type' => 'checkbox', 'label' => 'Enable Coldtill', 'default' => 'no'],
            'title'          => ['title' => 'Title', 'type' => 'text', 'default' => 'Pay with crypto'],
            'description'    => ['title' => 'Description', 'type' => 'textarea', 'default' => 'Pay with USDT, USDC, BTC, ETH, SOL, TRX or 200+ other coins. You will be taken to a secure checkout page.'],
            'base_url'       => ['title' => 'Coldtill URL', 'type' => 'text', 'description' => 'For example https://pay.example.com', 'default' => ''],
            'api_key'        => ['title' => 'API key', 'type' => 'password', 'description' => 'Project API key from Coldtill → API keys.', 'default' => ''],
            'webhook_secret' => [
                'title'       => 'Webhook secret',
                'type'        => 'password',
                'description' => sprintf('In Coldtill → Webhooks, set the URL to <code>%s</code> and paste the signing secret here.', esc_html(WC()->api_request_url('coldtill'))),
                'default'     => '',
            ],
        ];
    }

    public function is_available(): bool
    {
        return parent::is_available()
            && get_woocommerce_currency() === 'USD'
            && $this->get_option('base_url') !== ''
            && $this->get_option('api_key') !== '';
    }

    private function client(): Coldtill_Client
    {
        return new Coldtill_Client($this->get_option('base_url'), $this->get_option('api_key'));
    }

    /** Coldtill allows one open payment per customer, so guests get a per-order id. */
    private function customer_id(WC_Order $order): string
    {
        $uid = $order->get_customer_id();
        return $uid ? 'wc-user-' . $uid : 'wc-order-' . $order->get_id();
    }

    public function process_payment($order_id): array
    {
        $order = wc_get_order($order_id);
        // Multi-currency plugins can switch the order currency after is_available() passed; Coldtill prices in USD.
        if (!$order || $order->get_currency() !== 'USD') {
            wc_add_notice('Crypto payment is only available for orders in USD.', 'error');
            return ['result' => 'failure'];
        }
        try {
            $res = $this->client()->create_payment([
                'customerID'    => $this->customer_id($order),
                'customerEmail' => $order->get_billing_email(),
                'amountInUSD'   => wc_format_decimal($order->get_total(), 2),
                'invoiceID'     => 'WC-' . $order->get_order_number(),
            ]);
        } catch (RuntimeException $e) {
            wc_get_logger()->error($e->getMessage(), ['source' => 'coldtill']);
            wc_add_notice('Crypto payment is unavailable right now. Please try again or choose another method.', 'error');
            return ['result' => 'failure'];
        }

        $order->update_meta_data(self::META_REF, $res['reference_id']);
        $order->update_status('pending', 'Awaiting crypto payment (Coldtill reference ' . $res['reference_id'] . ').');
        $order->save();
        WC()->cart?->empty_cart();

        return ['result' => 'success', 'redirect' => $res['url']];
    }

    public function handle_webhook(): void
    {
        $raw = file_get_contents('php://input') ?: '';
        // Servers send each header as X-Coldtill-* and as the pre-rename X-Keyhold-*.
        $sig = $_SERVER['HTTP_X_COLDTILL_SIGNATURE_V2'] ?? $_SERVER['HTTP_X_KEYHOLD_SIGNATURE_V2'] ?? '';
        $ts  = $_SERVER['HTTP_X_COLDTILL_TIMESTAMP'] ?? $_SERVER['HTTP_X_KEYHOLD_TIMESTAMP'] ?? '';
        if (!Coldtill_Client::verify_signature_v2($raw, $sig, $ts, $this->get_option('webhook_secret'))) {
            status_header(401);
            exit;
        }
        $event = sanitize_text_field($_SERVER['HTTP_X_COLDTILL_EVENT'] ?? $_SERVER['HTTP_X_KEYHOLD_EVENT'] ?? '');
        $data  = json_decode($raw, true);
        if (str_starts_with($event, 'payment.') && is_array($data) && !empty($data['reference_id'])) {
            $this->apply($data['reference_id'], (string) ($data['status'] ?? ''), (string) ($data['amount'] ?? ''), $data['payment_info'][0]['transaction_hash'] ?? '');
        }
        status_header(200);
        exit;
    }

    /** Fallback for missed webhooks: re-check the payment when the customer lands on the thank-you page. */
    public function sync_on_thankyou($order_id): void
    {
        $order = wc_get_order($order_id);
        $ref   = $order ? (string) $order->get_meta(self::META_REF) : '';
        if ($ref === '' || $order->is_paid()) {
            return;
        }
        try {
            $p = $this->client()->get_payment($ref);
            $this->apply($ref, (string) $p['paymentState'], (string) $p['amountInUSD'], $p['deposits'][0]['transactionHash'] ?? '');
        } catch (RuntimeException $e) {
            wc_get_logger()->warning($e->getMessage(), ['source' => 'coldtill']);
        }
    }

    private function apply(string $ref, string $status, string $amount, string $tx): void
    {
        $orders = wc_get_orders(['limit' => 1, 'meta_key' => self::META_REF, 'meta_value' => $ref, 'payment_method' => $this->id]);
        $order  = $orders[0] ?? null;
        // A retried checkout gets a new reference; events for the old one must not touch the order.
        if (!$order || $order->get_meta(self::META_REF) !== $ref || $order->is_paid()) {
            return;
        }
        if ($order->get_currency() !== 'USD') {
            $order->add_order_note('Coldtill payment ignored: the order currency is not USD.');
            return;
        }
        if ($amount !== '' && abs((float) $amount - (float) $order->get_total()) > 0.01) {
            $order->add_order_note(sprintf('Coldtill amount %s USD does not match the order total; not changing status.', esc_html($amount)));
            return;
        }
        switch ($status) {
            case 'FILLED':
            case 'OVER_FILLED':
                $order->payment_complete($tx);
                $order->add_order_note($status === 'OVER_FILLED' ? 'Crypto payment received (overpaid; refund the difference from your wallet if needed).' : 'Crypto payment received.');
                break;
            case 'PARTIALLY_FILLED':
                if ($order->get_status() !== 'on-hold') {
                    $order->update_status('on-hold', 'Partial crypto payment received; waiting for the rest.');
                }
                break;
            case 'CANCELLED':
            case 'REPLACED':
                $order->update_status('cancelled', 'Coldtill payment ' . strtolower($status) . '.');
                break;
        }
    }
}
