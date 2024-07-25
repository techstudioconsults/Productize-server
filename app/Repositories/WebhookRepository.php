<?php

namespace App\Repositories;

use App\Enums\PayoutStatus;
use App\Enums\RevenueActivity;
use App\Enums\RevenueActivityStatus;
use App\Exceptions\ServerErrorException;
use App\Mail\GiftAlert;
use App\Models\User;
use App\Notifications\OrderCreated;
use App\Notifications\ProductPurchased;
use App\Notifications\SubscriptionCancelled;
use App\Notifications\SubscriptionPaymentFailed;
use App\Notifications\WithdrawReversed;
use App\Notifications\WithdrawSuccessful;
use Log;
use Mail;

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
        protected PaystackRepository $paystackRepository
    ) {}

    public function paystack(string $type, $data)
    {
        try {
            switch ($type) {
                case 'subscription.create':
                    // $this->handleCreateSubscription($data);

                    break;

                case 'charge.success':

                    // Handle if isPurchase is present in metadata
                    /**
                     * This is a product purchase charge success webhook
                     */
                    if ($this->isPurchaseCharge($data)) {
                        $this->handlePurchaseCharge($data);
                    } else {
                        // Subscription charge success
                        $this->handleSubscriptionRenewEvent($data);
                    }

                    break;

                case 'subscription.not_renew':

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
                    $this->handleSubscriptionPaymentFailed($data);
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

        // Retrieve the revenue
        $revenue_id = $metadata['revenue_id'];

        // Product will be available in this user's download
        // If it is a gift, owner will be the recipient
        // Else, the buyer
        $owner = $this->getPurchaseOwner($buyer_id, $recipient_id);

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
                $user->notify(new OrderCreated($order));

                // Notify owner of this product availability in download
                $owner->notify(new ProductPurchased($product_saved));

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

            if ($recipient_id) {
                $recipient = $this->userRepository->findById($recipient_id);
                $buyer = $this->userRepository->findById($buyer_id);

                Mail::send(new GiftAlert($recipient, $buyer));
            }

            // Update productize's revenue

             // Fetch the revenue model
             $revenue = $this->revenueRepository->findById($revenue_id);

            $this->revenueRepository->update($revenue, [
               'status' => RevenueActivityStatus::COMPLETED->value,
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
        $this->userRepository->guardedUpdate($customer['email'], 'account_type', 'premium');

         // create revenue record
         $this->revenueRepository->create([
            'user_id' => $subscription->user->id,
            'activity' => RevenueActivity::PURCHASE->value,
            'product' => 'Purchase',
            'amount' => SubscriptionRepository::PRICE,
            'status' => RevenueActivityStatus::COMPLETED->value,
            'commission' => RevenueRepository::SALE_COMMISSION,
        ]);

    }

    private function handleSubscriptionRenewEvent(array $data): void
    {
        $subscription_code = $data['subscription_code'];

        $subscription = $this->subscriptionRepository->findOne([
            'subscription_code' => $subscription_code,
        ]);

        // update the status
        $this->subscriptionRepository->update($subscription, [
            'status' => $data['status'],
        ]);

        // Update productize's revenue
        $this->revenueRepository->create([
            'user_id' => $subscription->user->id,
            'activity' => RevenueActivity::SUBSCRIPTION_RENEW->value,
            'product' => 'Subscription',
            'amount' => SubscriptionRepository::PRICE,
            'status' => RevenueActivityStatus::COMPLETED->value,
        ]);
    }

    private function handleSubscriptionPaymentFailed(array $data)
    {
        $subscription_code = $data['subscription']['subscription_code'];

        $subscriptionDto = $this->paystackRepository->fetchSubscription($subscription_code);

        $description = $subscriptionDto->getMostRecentInvoice()->getDescription();

        $subscription = $this->subscriptionRepository->findOne([
            'subscription_code' => $subscription_code,
        ]);

        // update the status
        $this->subscriptionRepository->update($subscription, [
            'status' => $data['status'],
        ]);

        $subscription->user->notify(new SubscriptionPaymentFailed($description));
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

        $user = $this->userRepository->guardedUpdate($email, 'account_type', 'free');

        $user->notify(new SubscriptionCancelled);
    }

    private function handleTransferSuccessEvent(array $data): void
    {
        $reference = $data['reference'];

        try {
            // update payout history status
            $payout = $this->payoutRepository->findOne(['reference' => $reference]);

            $payout = $this->payoutRepository->update($payout, ['status' => PayoutStatus::Completed->value]);

            $user = $payout->account->user;

            $earnings = $this->earningRepository->findOne(['user_id' => $user->id]);

            $new_withdrawn_earnings = $earnings->withdrawn_earnings + $data['amount'];

            $this->earningRepository->update($earnings, [
                'withdrawn_earnings' => $new_withdrawn_earnings,
                'pending' => 0,
            ]);

            $user->notify(new WithdrawSuccessful);

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

            $user = $payout->account->user;

            $earnings = $this->earningRepository->findOne(['user_id' => $user->id]);

            $this->earningRepository->update($earnings, [
                'pending' => 0,
            ]);

            $user->notify(new WithdrawReversed);

            // Email User
        } catch (\Throwable $th) {
            Log::channel('webhook')->error('Updating Payout', ['data' => $th->getMessage()]);
        }
    }

    private function getPurchaseOwner($buyer_id, $recipient_id): User
    {
        if (! $buyer_id && ! $recipient_id) {
            throw new ServerErrorException('No Buyer or Recipient In Purchase');
        }

        if ($recipient_id) {
            return $this->userRepository->findById($recipient_id);
        }

        return $this->userRepository->findById($buyer_id);
    }
}
