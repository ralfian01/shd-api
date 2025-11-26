<?php

if (!function_exists('format_phone_number')) {
    function format_phone_number($number)
    {
        $nomor = preg_replace('/\D/', '', $number);

        if (strpos($nomor, '0') === 0) {
            $nomor = '62' . substr($nomor, 1);
        } else if (strpos($nomor, '62') === 0) {
            // nothing
        } else {
            $nomor = '62' . $nomor;
        }

        return $nomor;
    }
}
