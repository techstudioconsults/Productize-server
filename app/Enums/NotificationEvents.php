<?php

namespace App\Enums;

enum NotificationEvents: string
{
    case FIRST_PRODUCT_CREATED = 'first.product.created';
    case FREE_TRIAL_ENDED = 'free.trial.ended';
    case ORDER_CREATED = 'order.created';
    case PAYOUT_CARD_ADDED = 'payout.card.added';
    case PRODUCT_CREATED = 'product.created';
    case PRODUCT_PUBLISHED = 'product.published';
    case PRODUCT_PURCHASED = 'product.purchased';
    case SUBSCRIPTION_CANCELLED = 'subscription.cancelled';
    case SUBSCRIPTION_PAYMENT_FAILED = 'subscription.payment.failed';
    case WITHDRAW_REVERSED = 'withdraw.reversed';
    case WITHDRAW_SUCCESS = 'withdraw.successful';
}
