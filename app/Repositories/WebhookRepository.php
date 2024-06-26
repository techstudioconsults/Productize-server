<?php

namespace App\Repositories;

use App\Enums\PayoutStatus;
use App\Enums\RevenueActivity;
use App\Events\OrderCreated;
use Log;

class WebhookRepository
{
    public function __construct(
        protected SubscriptionRepository $subscriptionRepository,
        protected UserRepository $userRepository,
        protected CartRepository $cartRepository,
        protected ProductRepository $productRepository,
        protected OrderRepository $orderRepository,
        protected CustomerRepository $customerRepository,
        protected EarningRepository $earningRepository,
        protected PayoutRepository $payoutRepository,
        protected RevenueRepository $revenueRepository,
    ) {}

    public function paystack(string $type, $data)
    {
        try {
            switch ($type) {
                case 'subscription.create':
                    $this->handleCreateSubscription($data);

                    break;

                case 'charge.success':

                    // Handle if isPurchase is present in metadata
                    /**
                     * This is a product purchase charge success webhook
                     */
                    if ($this->isPurchaseCharge($data)) {
                        $this->handlePurchaseCharge($data);
                    }

                    break;

                case 'subscription.not_renew':
                    $this->handleSubscriptionRenewEvent($data);
                    break;

                case 'invoice.create':
                    // code...
                    break;

                case 'invoice.update':
                    // code...
                    break;

                    /**
                     * Cancelling a subscription will also trigger the following events:
                     */
                case 'invoice.payment_failed':
                    // code...
                    break;

                case 'subscription.disable':
                    $this->handleSubscriptionDisableEvent($data);
                    break;

                case 'subscription.expiring_cards':
                    /**
                     * Might want to reach out to customers
                     * https://paystack.com/docs/payments/subscriptions/#handling-subscription-payment-issues
                     */
                    break;

                case 'transfer.success':

                    $this->handleTransferSuccessEvent($data);
                    break;

                case 'transfer.failed':

                    $this->handleTransferFailedEvent($data);
                    break;

                case 'transfer.reversed':

                    $this->handleTransferReversedEvent($data);
                    break;
            }
        } catch (\Throwable $th) {
            Log::critical('paystack webhook error', ['error_message' => $th->getMessage()]);
        }
    }

    private function isPurchaseCharge(array $data): bool
    {
        return $data['metadata'] && isset($data['metadata']['isPurchase']) && $data['metadata']['isPurchase'];
    }

    private function handlePurchaseCharge(array $data): void
    {
        // Retrieve saved metadat
        $metadata = $data['metadata'];

        // Retrieve the buyer's ID - The paying user's id.
        $buyer_id = $metadata['buyer_id'] ?? null;

        // Retrieve the products info from the cart metadata
        $products = $metadata['products'] ?? [];

        // If it is a gift, retrieve the user id of the recipient
        $recipient_id = $metadata['recipient_id'];

        // Find Cart
        $cart = $this->cartRepository->findOne(['user_id' => $buyer_id]);

        if ($cart) {
            // Delete the cart
            $this->cartRepository->deleteOne($cart);
        }

        try {
            foreach ($products as $product) {

                // Retrieve the product
                $product_saved = $this->productRepository->findById($product['product_id']);

                // Retrieve the user product
                $user = $product_saved->user;

                $buildOrder = [
                    'reference_no' => $data['reference'],
                    'user_id' => $recipient_id ? $recipient_id : $buyer_id,
                    'total_amount' => $product['price'] * $product['quantity'],
                    'quantity' => $product['quantity'],
                    'product_id' => $product_saved->id,
                ];

                $order = $this->orderRepository->create($buildOrder);

                // Trigger Order created Event
                OrderCreated::dispatch($user, $order);

                $this->customerRepository->create([
                    'user_id' => $order->user->id,
                    'merchant_id' => $order->product->user->id,
                    'order_id' => $order->id,
                ]);

                // Update earnings
                $this->earningRepository->create([
                    'user_id' => $user->id,
                    'amount' => $product['amount'],
                ]);
            }

            // Update productize's revenue
            $this->revenueRepository->create([
                'user_id' => $recipient_id ? $recipient_id : $buyer_id,
                'activity' => RevenueActivity::PURCHASE->value,
                'product' => 'Purchase',
                'amount' => $data['amount'],
                'commission' => RevenueRepository::SALE_COMMISSION,
            ]);

        } catch (\Throwable $th) {
            Log::channel('webhook')->critical('ERROR OCCURED', ['error' => $th->getMessage()]);
        }
    }

    private function handleCreateSubscription(array $data): void
    {
        // update subscription code
        $customer = $data['customer'];

        $subscription = $this->subscriptionRepository->findOne([
            'customer_code' => $customer['customer_code'],
        ]);

        $this->subscriptionRepository->update($subscription, [
            'subscription_code' => $data['subscription_code'],
            'status' => $data['status'],
        ]);

        // update user to premium
        $user = $this->userRepository->guardedUpdate($customer['email'], 'account_type', 'premium');

        // Update productize's revenue
        $this->revenueRepository->create([
            'user_id' => $user->id,
            'activity' => RevenueActivity::SUBSCRIPTION->value,
            'product' => 'Subscription',
            'amount' => SubscriptionRepository::PRICE,
        ]);
    }

    private function handleSubscriptionRenewEvent(array $data): void
    {
        $subscription_code = $data['subscription_code'];

        $subscription = $this->subscriptionRepository->findOne([
            'subscription_code' => $subscription_code,
        ]);

        // update the status
        $user = $this->subscriptionRepository->update($subscription, [
            'status' => $data['status'],
        ]);

        // Update productize's revenue
        $this->revenueRepository->create([
            'user_id' => $user->id,
            'activity' => RevenueActivity::SUBSCRIPTION_RENEW->value,
            'product' => 'Subscription',
            'amount' => SubscriptionRepository::PRICE,
        ]);
    }

    private function handleSubscriptionDisableEvent(array $data): void
    {
        $email = $data['customer']['email'];

        $subscription_code = $data['subscription_code'];

        $subscription = $this->subscriptionRepository->findOne([
            'subscription_code' => $subscription_code,
        ]);

        // delete the subscription
        $this->subscriptionRepository->deleteOne($subscription);

        $this->userRepository->guardedUpdate($email, 'account_type', 'free');
    }

    private function handleTransferSuccessEvent(array $data): void
    {
        $reference = $data['reference'];

        try {
            // update payout history status
            $payout = $this->payoutRepository->findOne(['reference' => $reference]);

            $payout = $this->payoutRepository->update($payout, ['status' => PayoutStatus::Completed->value]);

            $user_id = $payout->account->user->id;

            $earnings = $this->earningRepository->findOne(['user_id' => $user_id]);

            Log::channel('webhook')->error('Updating Payout', ['data' => $earnings]);

            $new_withdrawn_earnings = $earnings->withdrawn_earnings + $data['amount'];

            $this->earningRepository->update($earnings, [
                'withdrawn_earnings' => $new_withdrawn_earnings,
                'pending' => 0,
            ]);

            // Email User
        } catch (\Throwable $th) {
            Log::channel('webhook')->error('Updating Payout', ['data' => $th->getMessage()]);
        }
    }

    private function handleTransferFailedEvent(array $data): void
    {
        $reference = $data['reference'];

        try {
            $payout = $this->payoutRepository->findOne(['reference' => $reference]);

            $payout = $this->payoutRepository->update($payout, ['status' => 'failed']);

            $user_id = $payout->account->user->id;

            $earnings = $this->earningRepository->findOne(['user_id' => $user_id]);

            $this->earningRepository->update($earnings, [
                'pending' => 0,
            ]);

            // Email User
        } catch (\Throwable $th) {
            Log::channel('webhook')->error('Updating Payout', ['data' => $th->getMessage()]);
        }
    }

    private function handleTransferReversedEvent(array $data): void
    {
        $reference = $data['reference'];

        try {
            $payout = $this->payoutRepository->findOne(['reference' => $reference]);

            $payout = $this->payoutRepository->update($payout, ['status' => 'reversed']);

            $user_id = $payout->account->user->id;

            $earnings = $this->earningRepository->findOne(['user_id' => $user_id]);

            $this->earningRepository->update($earnings, [
                'pending' => 0,
            ]);

            // Email User
        } catch (\Throwable $th) {
            Log::channel('webhook')->error('Updating Payout', ['data' => $th->getMessage()]);
        }
    }
}
