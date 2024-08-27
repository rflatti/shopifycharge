<?php

namespace RFlatti\ShopifyPlans\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Response;
use RFlatti\ShopifyApp\Models\Store;
use RFlatti\ShopifyPlans\Models\Subscriptions\Subscriptions;
use RFlatti\ShopifyPlans\Models\Subscriptions as SubscriptionModel;
use RFlatti\ShopifyApp\Services\StorageService;

class ChargeController extends Controller
{
    public function __construct(
        protected Subscriptions $subscriptions,
        protected StorageService $storageService,
    ){}

    public function charge($plan_id){
        $redirect_url = $this->subscriptions->createSubscription($plan_id);

        return Response::json(['redirect_url' => $redirect_url]);
    }

    public function redirectAfterCharge(Request $request)
    {
        Log::info('Charge seems to have been made successfully');

        // Get the store information
        $store = Store::where('id', $this->storageService->getIds()[0])->first();

        // Get the charge_id from the request
        $charge_id = $request->input('charge_id');

        // API endpoint to verify the charge_id
        $endpoint = 'https://' . $store['myshopify_domain'] . '/admin/api/2024-07/recurring_application_charges/' . $charge_id . '.json';
        $accessToken = $store['access_token'];

        // Initialize cURL
        $ch = curl_init($endpoint);

        // Set cURL options for a GET request
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "X-Shopify-Access-Token: $accessToken",
            "Content-Type: application/json"
        ]);

        // Execute the request and get the response
        $response = curl_exec($ch);

        // Check for cURL errors
        if (curl_errno($ch)) {
            Log::error('cURL error: ' . curl_error($ch));
            curl_close($ch);
            return response()->json(['error' => 'Could not verify charge ID'], 500);
        }

        curl_close($ch);

        // Decode the JSON response
        $responseArray = json_decode($response, true);

        // Check if the charge is valid
        if (isset($responseArray['recurring_application_charge']) && $responseArray['recurring_application_charge']['id'] == $charge_id) {
            // Check if the charge_id already exists in the database
            $existingSubscription = SubscriptionModel::where('charge_id', $charge_id)->first();

            if ($existingSubscription) {
                Log::info('Charge ID ' . $charge_id . ' already exists in the database.');
                return response()->json(['error' => 'Charge ID already exists'], 400);
            }

            // Save it in the subscriptions table
            SubscriptionModel::create([
                'store_id' => $store['id'],
                'charge_id' => $charge_id,
                'plan_id' => $request->input('plan_id')
            ]);

            Log::info('Charge ID ' . $charge_id . ' saved successfully.');
            $storePath = explode('.', $store['myshopify_domain'])[0];
            return Redirect::to("https://admin.shopify.com/store/$storePath/apps/".config('shopify.handle'));
        } else {
            Log::error('Invalid charge ID: ' . $charge_id);
            return response()->json(['error' => 'Invalid charge ID'], 400);
        }
    }
}
