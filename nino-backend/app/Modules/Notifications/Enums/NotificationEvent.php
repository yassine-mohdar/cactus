<?php

namespace App\Modules\Notifications\Enums;

/**
 * All notification-triggering events in the system.
 * Each event maps to one or more channel templates.
 */
enum NotificationEvent: string
{
    // Order lifecycle
    case ORDER_PLACED = 'order_placed';
    case ORDER_CANCELLED = 'order_cancelled';

    // Payment lifecycle
    case PAYMENT_SUCCESS = 'payment_success';
    case PAYMENT_FAILED = 'payment_failed';

    // Shipping lifecycle
    case ORDER_SHIPPED = 'order_shipped';
    case ORDER_DELIVERED = 'order_delivered';

    // Account lifecycle
    case WELCOME = 'welcome';
    case PASSWORD_SETUP = 'password_setup';

    // Marketing / Recovery
    case ABANDONED_CART = 'abandoned_cart';
    case PROMOTIONAL_OFFER = 'promotional_offer';

    public function label(): string
    {
        return match($this) {
            self::ORDER_PLACED => 'Order Placed',
            self::ORDER_CANCELLED => 'Order Cancelled',
            self::PAYMENT_SUCCESS => 'Payment Successful',
            self::PAYMENT_FAILED => 'Payment Failed',
            self::ORDER_SHIPPED => 'Order Shipped',
            self::ORDER_DELIVERED => 'Order Delivered',
            self::WELCOME => 'Welcome',
            self::PASSWORD_SETUP => 'Password Setup / Login Link',
            self::ABANDONED_CART => 'Abandoned Cart',
            self::PROMOTIONAL_OFFER => 'Promotional Offer',
        };
    }

    /**
     * Group label for admin UI categorisation.
     */
    public function group(): string
    {
        return match($this) {
            self::ORDER_PLACED, self::ORDER_CANCELLED => 'Orders',
            self::PAYMENT_SUCCESS, self::PAYMENT_FAILED => 'Payments',
            self::ORDER_SHIPPED, self::ORDER_DELIVERED => 'Shipping',
            self::WELCOME, self::PASSWORD_SETUP => 'Account',
            self::ABANDONED_CART, self::PROMOTIONAL_OFFER => 'Marketing',
        };
    }

    /**
     * Default placeholder variables available per event.
     */
    public function availableVariables(): array
    {
        $base = ['customer_name', 'customer_email', 'store_name', 'store_url'];

        return match($this) {
            self::ORDER_PLACED, self::ORDER_CANCELLED => array_merge($base, ['order_reference', 'order_total', 'order_currency', 'order_items_count', 'order_date']),
            self::PAYMENT_SUCCESS, self::PAYMENT_FAILED => array_merge($base, ['order_reference', 'order_total', 'order_currency', 'payment_method', 'transaction_id']),
            self::ORDER_SHIPPED => array_merge($base, ['order_reference', 'tracking_number', 'tracking_url', 'carrier_name', 'estimated_delivery']),
            self::ORDER_DELIVERED => array_merge($base, ['order_reference', 'tracking_number', 'delivery_date']),
            self::WELCOME => array_merge($base, ['login_url']),
            self::PASSWORD_SETUP => array_merge($base, ['setup_url', 'expiry_hours']),
            self::ABANDONED_CART => array_merge($base, ['cart_items_count', 'cart_total', 'cart_url']),
            self::PROMOTIONAL_OFFER => array_merge($base, ['promo_title', 'promo_code', 'promo_discount', 'promo_expiry', 'promo_url']),
        };
    }
}
