<?php

if (!function_exists('secret_encode')) {
    function secret_encode($data)
    {
        $iv = random_bytes(16);
        $key = hash('sha256', env('SECRET_KEY'), true);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . $encrypted);
    }
}

if (!function_exists('secret_decode')) {
    function secret_decode($data)
    {
        $rawData = base64_decode($data);
        $iv = substr($rawData, 0, 16);
        $cipherText = substr($rawData, 16);
        $key = hash('sha256', env('SECRET_KEY'), true);
        return openssl_decrypt($cipherText, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    }
}
