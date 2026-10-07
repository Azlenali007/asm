<?php
/**
 * ApexSMM - Enterprise SMM Panel Platform
 * Response & API Output Helpers
 */

if (!function_exists('json_response')) {
    function json_response(mixed $data, int $statusCode = 200): never {
        if (!headers_sent()) {
            http_response_code($statusCode);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!function_exists('api_error')) {
    function api_error(string $message, int $statusCode = 400): never {
        json_response(['error' => $message], $statusCode);
    }
}

if (!function_exists('api_success')) {
    function api_success(array $data = [], int $statusCode = 200): never {
        json_response(array_merge(['status' => 'success'], $data), $statusCode);
    }
}
