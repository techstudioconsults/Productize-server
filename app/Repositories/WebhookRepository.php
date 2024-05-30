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
        protected EarningRepository $earningRepository
    ) {
    }
    public function paystack(string $type, $data)
    {
        try {
            switch ($type) {
                case 'subscription.create':

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
                    break;

                case 'charge.success':

                    // Handle if isPurchase is present in metadata
                    /**
                     * This is a product purchase charge success webhook
                     */
                    if ($data['metadata'] && isset($data['metadata']['isPurchase']) && $data['metadata']['isPurchase']) {
                        $metadata = $data['metadata'];
                        $buyer_id = $metadata['buyer_id'];
                        $products = $metadata['products'];

                        // Delete Cart
                        $cart = $this->cartRepository->findOne(['user_id' => $buyer_id]);

                        if ($cart) {
                            $this->cartRepository->deleteOne($cart);
                        }

                        try {
                            foreach ($products as $product) {
                                $product_saved = $this->productRepository->findById($product['product_id']);
                                $user = $product_saved->user;

                                $buildOrder = [
                                    'reference_no' => $data['reference'],
                                    'user_id' => $buyer_id,
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

                    break;

                case 'subscription.not_renew':
                    // email customer
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
                    $email = $data['customer']['email'];

                    $this->userRepository->guardedUpdate($email, 'account_type', 'free');
                    break;

                case 'subscription.expiring_cards':
                    /**
                     * Might want to reach out to customers
                     * https://paystack.com/docs/payments/subscriptions/#handling-subscription-payment-issues
                     */
                    break;

                case 'transfer.success':

                    //     $reference = $data['reference'];

                    //     try {
                    //         $payout = $this->payoutRepository->findByReference($reference);

                    //         $payout->status = 'completed';

                    //         $payout->save();

                    //         $user_id = $payout->payoutAccount->user->id;

                    //         $this->paymentRepository->updateWithdraws($user_id, $data['amount']);

                    //         // Email User
                    //     } catch (\Throwable $th) {
                    //         Log::channel('webhook')->error('Updating Payout', ['data' => $th->getMessage()]);
                    //     }

                    //     break;

                    // case 'transfer.failed':

                    //     $reference = $data['reference'];

                    //     try {
                    //         $payout = $this->payoutRepository->findByReference($reference);

                    //         $payout->status = 'failed';

                    //         $payout->save();

                    //         // Email User
                    //     } catch (\Throwable $th) {
                    //         Log::channel('webhook')->error('Updating Payout', ['data' => $th->getMessage()]);
                    //     }
                    break;

                case 'transfer.reversed':

                    // $reference = $data['reference'];

                    // try {
                    //     $payout = $this->payoutRepository->findByReference($reference);

                    //     $payout->status = 'reversed';

                    //     $payout->save();

                    //     // Email User
                    // } catch (\Throwable $th) {
                    //     Log::channel('webhook')->error('Updating Payout', ['data' => $th->getMessage()]);
                    // }
                    break;
            }
        } catch (\Throwable $th) {
            // Log::critical('paystack webhook error', ['error_message' => $th->getMessage()]);
        }
    }
}
