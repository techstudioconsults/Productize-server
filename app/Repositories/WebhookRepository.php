<?php

namespace App\Repositories;

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
        protected PayoutRepository $payoutRepository
    ) {
    }
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
                    # code...
                    break;

                case 'invoice.update':
                    # code...
                    break;

                    /**
                     * Cancelling a subscription will also trigger the following events:
                     */

                case 'invoice.payment_failed':
                    # code...
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
        $buyer_id = $metadata['buyer_id'];

        // Retrieve the products info from the cart metadata
        $products = $metadata['products'];

        // Retrieve gift user id
        $gift_user_id = $metadata['gift_user_id'];

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
                    'user_id' => $gift_user_id ?? $buyer_id,
                    'total_amount' => $product_saved->price * $product['quantity'],
                    'quantity' => $product['quantity'],
                    'product_id' => $product_saved->id
                ];

                $order = $this->orderRepository->create($buildOrder);

                $this->customerRepository->create([
                    'user_id' => $order->user->id,
                    'merchant_id' => $order->product->user->id,
                    'order_id' => $order->id
                ]);

                // Update earnings
                $this->earningRepository->create([
                    'user_id' => $user->id,
                    'amount' => $product['amount']
                ]);
            }
        } catch (\Throwable $th) {
            Log::channel('webhook')->critical('ERROR OCCURED', ['error' => $th->getMessage()]);
        }
    }

    private function handleCreateSubscription(array $data): void
    {
        // update subscription code
        $customer = $data['customer'];

        $subscription = $this->subscriptionRepository->findOne([
            'customer_code' => $customer['customer_code']
        ]);

        $this->subscriptionRepository->update($subscription, [
            'subscription_code' => $data['subscription_code'],
            'status' => $data['status']
        ]);

        // update user to premium
        $this->userRepository->guardedUpdate($customer['email'], 'account_type', 'premium');
    }

    private function handleSubscriptionRenewEvent(array $data): void
    {
        $subscription_code = $data['subscription_code'];

        $subscription = $this->subscriptionRepository->findOne([
            'subscription_code' => $subscription_code
        ]);

        // update the status
        $this->subscriptionRepository->update($subscription, [
            'status' => $data['status']
        ]);
    }

    private function handleSubscriptionDisableEvent(array $data): void
    {
        $email = $data['customer']['email'];

        $subscription_code = $data['subscription_code'];

        $subscription = $this->subscriptionRepository->findOne([
            'subscription_code' => $subscription_code
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

            $payout = $this->payoutRepository->update($payout, ['status' => 'completed']);

            $user_id = $payout->account->user->id;

            $earnings = $this->earningRepository->findOne(['user_id' => $user_id]);

            Log::channel('webhook')->error('Updating Payout', ['data' => $earnings]);

            $new_withdrawn_earnings = $earnings->withdrawn_earnings + $data['amount'];

            $this->earningRepository->update($earnings, [
                'withdrawn_earnings' => $new_withdrawn_earnings,
                'pending' => 0
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
                'pending' => 0
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
                'pending' => 0
            ]);

            // Email User
        } catch (\Throwable $th) {
            Log::channel('webhook')->error('Updating Payout', ['data' => $th->getMessage()]);
        }
    }

    private function isGiftPurchase(array $gift): bool
    {
        return isset($gift['email']) && isset($gift['full_name']);
    }
}
