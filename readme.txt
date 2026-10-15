=== Coldtill for WooCommerce: Crypto Payment Gateway ===
Contributors: coldtill
Tags: crypto, cryptocurrency, usdt, bitcoin, payment gateway
Requires at least: 6.4
Requires PHP: 8.1
WC requires at least: 8.0
Stable tag: 1.0.0
License: MIT
License URI: https://opensource.org/license/mit

Accept USDT, USDC, BTC, ETH, SOL, TRX and 200+ coins in WooCommerce, straight to your own wallets. No sign-up, no KYB, no custody.

== Description ==

Coldtill is a self-hosted crypto payment gateway. This plugin connects your WooCommerce store to your own Coldtill
install, so customers can pay in crypto and the money goes straight to wallets you control.

* **200+ coins on 35+ chains.** BTC, ETH, SOL, TRX, BNB, POL, USDT and USDC directly, and many more through
  auto-convert, settled to you as USDC or USDT.
* **Self-custody.** On Ethereum, Base, Polygon, BNB Chain, Tron and Solana every customer pays into a deposit
  contract that can only send to your cold wallet. Bitcoin goes to your own wallet's addresses.
* **No account to freeze.** No sign-up, no KYC or KYB, no processor holding your money.
* **Classic and block checkout**, and WooCommerce's high-performance order storage.
* **Signed webhooks** with replay protection. Orders update automatically, and are re-checked when the customer
  returns to your store if a webhook is missed.

You need a running Coldtill server. The Free edition installs with one command:
`curl -fsSL https://coldtill.com/install.sh | sudo bash -s -- --edition free`. See https://coldtill.com/download/.

== Installation ==

1. Download `coldtill-woocommerce.zip` from the latest release on GitHub, then in WordPress go to Plugins → Add New →
   Upload Plugin, choose the zip and activate it.
2. In Coldtill, create a project API key (API keys) and copy it.
3. In WooCommerce → Settings → Payments → Coldtill, enter your Coldtill URL and API key.
4. In Coldtill → Webhooks, set the URL shown under "Webhook secret" (it ends in `/wc-api/coldtill/`) and paste the
   signing secret into the plugin settings.

== Frequently Asked Questions ==

= Which store currency does it support? =

USD. Coldtill prices payments in US dollars, so the gateway only appears when the store currency is USD.

= How do order statuses change? =

Orders move to Processing when the payment is filled, to On hold while partially paid, and to Cancelled if the
Coldtill payment is cancelled or replaced. If the amount Coldtill reports doesn't match the order total, the plugin
adds a note and leaves the order alone.

= Where does the money go? =

To your own wallets, never through Coldtill or this plugin. The plugin only creates payments and reads their status.

== Changelog ==

= 1.0.0 =
* First public release.
