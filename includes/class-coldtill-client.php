<?php

defined('ABSPATH') || exit;

class Coldtill_Client
{
    public function __construct(private string $base_url, private string $api_key)
    {
        $this->base_url = rtrim($base_url, '/');
    }

    /** @return array{host:string,reference_id:string,url:string} */
    public function create_payment(array $req): array
    {
        return $this->request('POST', '/payment', $req);
    }

    public function get_payment(string $reference_id): array
    {
        return $this->request('GET', '/payment/reference/' . rawurlencode($reference_id));
    }

    private function request(string $method, string $path, ?array $body = null): array
    {
        $args = [
            'method'  => $method,
            'timeout' => 20,
            'headers' => ['API-Key' => $this->api_key, 'Accept' => 'application/json'],
        ];
        if ($body !== null) {
            $args['headers']['Content-Type'] = 'application/json';
            $args['body'] = wp_json_encode($body);
        }
        $res = wp_remote_request($this->base_url . '/api/v1' . $path, $args);
        if (is_wp_error($res)) {
            throw new RuntimeException('Coldtill is unreachable: ' . $res->get_error_message());
        }
        $code = wp_remote_retrieve_response_code($res);
        $data = json_decode(wp_remote_retrieve_body($res), true);
        if ($code < 200 || $code >= 300) {
            $msg = is_array($data) && isset($data['error']) ? $data['error'] : 'HTTP ' . $code;
            throw new RuntimeException('Coldtill: ' . $msg, $code);
        }
        return is_array($data) ? $data : [];
    }

    /** Verifies the timestamped X-Coldtill-Signature-V2 header and rejects deliveries older than five minutes. */
    public static function verify_signature_v2(string $raw_body, string $header, string $timestamp, string $secret): bool
    {
        if ($secret === '' || !str_starts_with($header, 'sha256=') || !ctype_digit($timestamp)) {
            return false;
        }
        if (abs(time() - (int) $timestamp) > 300) {
            return false;
        }
        $want = 'sha256=' . hash_hmac('sha256', $timestamp . '.' . $raw_body, $secret);
        return hash_equals($want, strtolower(trim($header)));
    }
}
