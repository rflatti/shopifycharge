<?php

namespace RFlatti\ShopifyPlans\Models\Subscriptions;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use RFlatti\ShopifyPlans\Models\Subscriptions as SubscriptionModel;
use RFlatti\ShopifyApp\Services\StorageService;
use RFlatti\ShopifyPlans\Models\Plans\Plans;
use RFlatti\ShopifyApp\Models\Store;

class Subscriptions
{
    public function __construct(
        protected StorageService $storageService,
        protected Plans $plans,
    ){}
    public function getStoreSubscription($store_id = null){
        if($store_id == null){
            $current_store_id = $this->storageService->getIds()[0];
        }
        return SubscriptionModel::where('store_id', $store_id);
    }

    public function createSubscription(int $plan_id, $store_id = null){
        $selected_plan = $this->plans->get($plan_id)->first();

        $redirectUrl = route('charge_redirect_uri', ['plan_id' => $plan_id]);

        $payload = [
            'recurring_application_charge' => [
                'name' => $selected_plan['plan_name'],
                'price' => $selected_plan['plan_price'],
                'return_url' => $redirectUrl,
                'test' => env('SHOPIFY_APPLICATION_CHARGE_TEST', true),
                'trial_days' => 3
            ]
        ];
        //charge with the created payload
        return $this->charge($payload);
    }

    private function charge($payload){
        $store = Store::where('id', $this->storageService->getIds()[0])->first();

        $endpoint =  'https://'.$store['myshopify_domain'].'/admin/api/2024-07/recurring_application_charges.json';
        $data = json_encode($payload);
        $accessToken = $store['access_token'];

        $ch = curl_init($endpoint);

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "X-Shopify-Access-Token: $accessToken",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        $response = curl_exec($ch);

        curl_close($ch);

        $responseArray = json_decode($response, true);

        if (isset($responseArray['recurring_application_charge']['confirmation_url'])) {
            return $responseArray['recurring_application_charge']['confirmation_url'];
        }
        Log::info("There was an error with the carge request");
        Log::info(json_encode($response));
    }
}
