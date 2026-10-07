<?php
/**
 * ApexSMM - Enterprise SMM Panel Platform
 * Formatting & Presentation Helpers
 */

if (!function_exists('money')) {
    function money(float|int|string|null $amount, ?string $currency = null): string {
        $amount = (float)($amount ?? 0);
        $curr = $currency ?? app_config('app.currency', '$');
        return $curr . number_format($amount, 2, '.', ',');
    }
}

if (!function_exists('rate_per_k')) {
    function rate_per_k(float|int|string|null $rate): string {
        $rate = (float)($rate ?? 0);
        $curr = app_config('app.currency', '$');
        return $curr . number_format($rate, 3, '.', '');
    }
}

if (!function_exists('format_date')) {
    function format_date(?string $datetime, string $format = 'M d, Y - H:i'): string {
        if (!$datetime) return 'N/A';
        $timestamp = strtotime($datetime);
        return $timestamp ? date($format, $timestamp) : 'N/A';
    }
}

if (!function_exists('status_badge')) {
    function status_badge(string $status): string {
        $status = strtolower($status);
        $classes = [
            'pending'    => 'badge-pending',
            'processing' => 'badge-processing',
            'inprogress' => 'badge-inprogress',
            'completed'  => 'badge-completed',
            'partial'    => 'badge-partial',
            'canceled'   => 'badge-canceled',
            'fail'       => 'badge-fail',
            'active'     => 'badge-active',
            'suspended'  => 'badge-suspended',
            'banned'     => 'badge-banned',
            'answered'   => 'badge-answered',
            'closed'     => 'badge-closed',
        ];

        $class = $classes[$status] ?? 'badge-default';
        $label = ucfirst(str_replace('_', ' ', $status));
        return "<span class=\"badge {$class}\">{$label}</span>";
    }
}

if (!function_exists('number_short')) {
    function number_short(int|float $num): string {
        if ($num >= 1000000) {
            return round($num / 1000000, 1) . 'M';
        }
        if ($num >= 1000) {
            return round($num / 1000, 1) . 'K';
        }
        return (string)$num;
    }
}
