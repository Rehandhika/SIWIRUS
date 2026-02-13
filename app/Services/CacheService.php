<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class CacheService
{
    /**
     * Clear all dashboard caches
     */
    public static function clearDashboard(): void
    {
        Cache::forget('dashboard.admin_stats.'.today()->format('Y-m-d-H'));

        // Clear all user stats for today
        $pattern = 'dashboard.user_stats.*.'.today()->format('Y-m-d');
        // Note: Laravel doesn't support wildcard delete by default
        // You may need to track keys or use Redis tags
    }

    /**
     * Clear user-specific dashboard cache
     */
    public static function clearUserDashboard(int $userId): void
    {
        Cache::forget("dashboard.user_stats.{$userId}.".today()->format('Y-m-d'));
    }

    /**
     * Clear product list cache
     */
    public static function clearProducts(): void
    {
        Cache::forget('products.list');
        Cache::forget('products.low_stock');
        Cache::forget('products:categories');

        // Clear paginated catalog cache (clear first 10 pages)
        for ($page = 1; $page <= 10; $page++) {
            Cache::forget("products:public:page:{$page}:search::category:");
        }
    }

    /**
     * Clear catalog cache for public pages
     */
    public static function clearCatalog(): void
    {
        Cache::forget('products:categories');

        // Clear paginated catalog cache (clear first 20 pages)
        for ($page = 1; $page <= 20; $page++) {
            Cache::forget("products:public:page:{$page}:search::category:");
        }
    }

    /**
     * Clear attendance cache
     */
    public static function clearAttendance(): void
    {
        Cache::forget('attendance.today_summary');
        Cache::forget('attendance.stats.'.today()->format('Y-m-d'));
    }

    /**
     * Clear sales cache
     */
    public static function clearSales(): void
    {
        Cache::forget('sales.today');
        Cache::forget('sales.stats.'.today()->format('Y-m-d'));
    }

    /**
     * Clear all application caches
     */
    public static function clearAll(): void
    {
        Cache::flush();
    }

    /**
     * Remember with default TTL from config
     */
    public static function rememberWithConfig(string $key, string $configKey, callable $callback): mixed
    {
        $ttl = now()->addMinutes(config("siwirus.cache.{$configKey}", 10));

        return Cache::remember($key, $ttl, $callback);
    }
}
