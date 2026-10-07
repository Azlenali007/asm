<?php
/**
 * ApexSMM - Enterprise SMM Panel Platform
 * SMM Provider Interface
 */

namespace Providers;

interface ProviderInterface
{
    /**
     * Fetch all services from provider
     */
    public function services(): array;

    /**
     * Add new order to provider
     */
    public function add(array $params): array;

    /**
     * Query order status
     */
    public function status(string|int $orderId): array;

    /**
     * Query multiple order statuses
     */
    public function multiStatus(array $orderIds): array;

    /**
     * Query account balance
     */
    public function balance(): array;

    /**
     * Request order refill
     */
    public function refill(string|int $orderId): array;
}
