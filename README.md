# WooCommerce crypto payment gateway: Coldtill for WooCommerce

**Accept USDT, USDC, Bitcoin, Ethereum, Solana, Tron and 200+ coins in WooCommerce, straight to your own wallets.**
No sign-up, no KYB, no custody and no processor that can freeze your money.

This plugin connects your WooCommerce store to [Coldtill](https://coldtill.com), a
[self-hosted crypto payment gateway](https://github.com/Coldtill/self-hosted-crypto-payment-gateway) that runs on
your own server.

[Download the plugin](https://github.com/Coldtill/woocommerce-crypto-payment-gateway/releases/latest/download/coldtill-woocommerce.zip) ·
[Coldtill](https://coldtill.com) · [Docs](https://coldtill.com/docs/) · [Live demo dashboard](https://demo.coldtill.com/)

![Coldtill checkout: choose a network and coin, then scan the QR code](https://coldtill.com/img/checkout-pay.webp)

## Features

- **200+ coins on 35+ chains.** BTC, ETH, SOL, TRX, BNB, POL, USDT and USDC directly, and XRP, TON, Sui, Cardano,
  Dogecoin and more through auto-convert, settled to you as USDC or USDT.
- **Self-custody.** On Ethereum, Base, Polygon, BNB Chain, Tron and Solana each customer pays into a deposit contract
  that can only send to your cold wallet. Bitcoin goes to your own wallet's addresses.
- **No account to freeze.** No sign-up, no KYC or KYB, no reserves and no payout schedule.
- **Classic and block checkout**, plus WooCommerce high-performance order storage (HPOS).
- **Signed webhooks** (HMAC-SHA256 with a timestamp and replay window). Orders update automatically and are re-checked
  when the customer returns to your store if a webhook is missed.
- **Safe by default.** The order only changes when the reference and amount match; overpayments and partial payments
  are noted on the order.

## Requirements

- WordPress 6.4+, WooCommerce 8.0+, PHP 8.1+
- Store currency: USD
- A running Coldtill server. The Free edition installs with one command:

```bash
curl -fsSL https://coldtill.com/install.sh | sudo bash -s -- --edition free
```

## Install

1. Download [`coldtill-woocommerce.zip`](https://github.com/Coldtill/woocommerce-crypto-payment-gateway/releases/latest/download/coldtill-woocommerce.zip).
2. In WordPress, go to **Plugins → Add New → Upload Plugin**, choose the zip and activate it.
3. In Coldtill, create a project API key (**API keys**) and copy it.
4. In **WooCommerce → Settings → Payments → Coldtill**, enter your Coldtill URL and API key.
5. In Coldtill → **Webhooks**, set the URL shown under "Webhook secret" (it ends in `/wc-api/coldtill/`) and paste
   the signing secret into the plugin settings.

## Order statuses

| Coldtill payment | WooCommerce order |
|---|---|
| Filled or overpaid | Processing (payment complete) |
| Partially paid | On hold |
| Cancelled or replaced | Cancelled |

## Security

Report vulnerabilities to [security@coldtill.com](mailto:security@coldtill.com). See
[coldtill.com/security](https://coldtill.com/security).

## Licence

MIT. See [LICENSE](LICENSE).
