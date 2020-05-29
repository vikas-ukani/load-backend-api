<?php

namespace App\Http\Controllers\API\v1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use PayPal\Api\CreditCard;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;

class PaypalController extends Controller
{
    public function __construct()
    {
        // Detect if we are running in live mode or sandbox
        if (config('paypal.settings.mode') == 'live') {
            $this->client_id = config('paypal.live_client_id');
            $this->secret = config('paypal.live_secret');
        } else {
            $this->client_id = config('paypal.sandbox_client_id');
            $this->secret = config('paypal.sandbox_secret');
        }

        // Set the Paypal API Context/Credentials
        $this->apiContext = new \PayPal\Rest\ApiContext(new \PayPal\Auth\OAuthTokenCredential($this->client_id, $this->secret));
        $this->apiContext->setConfig(config('paypal.settings'));
    }


    // public function create_plan()
    // {

    //     // Create a new billing plan
    //     $plan = new Plan();
    //     $plan->setName('App Name Monthly Billing')
    //         ->setDescription('Monthly Subscription to the App Name')
    //         ->setType('infinite');

    //     // Set billing plan definitions
    //     $paymentDefinition = new PaymentDefinition();
    //     $paymentDefinition->setName('Regular Payments')
    //         ->setType('REGULAR')
    //         ->setFrequency('Month')
    //         ->setFrequencyInterval('1')
    //         ->setCycles('0')
    //         ->setAmount(new Currency(array('value' => 9, 'currency' => 'USD')));

    //     // Set merchant preferences
    //     $merchantPreferences = new MerchantPreferences();
    //     $merchantPreferences->setReturnUrl('https://website.dev/subscribe/paypal/return')
    //         ->setCancelUrl('https://website.dev/subscribe/paypal/return')
    //         ->setAutoBillAmount('yes')
    //         ->setInitialFailAmountAction('CONTINUE')
    //         ->setMaxFailAttempts('0');

    //     $plan->setPaymentDefinitions(array($paymentDefinition));
    //     $plan->setMerchantPreferences($merchantPreferences);

    //     //create the plan
    //     try {
    //         $createdPlan = $plan->create($this->apiContext);

    //         try {
    //             $patch = new Patch();
    //             $value = new PayPalModel('{"state":"ACTIVE"}');
    //             $patch->setOp('replace')
    //                 ->setPath('/')
    //                 ->setValue($value);
    //             $patchRequest = new PatchRequest();
    //             $patchRequest->addPatch($patch);
    //             $createdPlan->update($patchRequest, $this->apiContext);
    //             $plan = Plan::get($createdPlan->getId(), $this->apiContext);

    //             // Output plan id
    //             echo 'Plan ID:' . $plan->getId();
    //         } catch (PayPal\Exception\PayPalConnectionException $ex) {
    //             echo $ex->getCode();
    //             echo $ex->getData();
    //             die($ex);
    //         } catch (Exception $ex) {
    //             die($ex);
    //         }
    //     } catch (PayPal\Exception\PayPalConnectionException $ex) {
    //         echo $ex->getCode();
    //         echo $ex->getData();
    //         die($ex);
    //     } catch (Exception $ex) {
    //         die($ex);
    //     }
    // }

    /**
     * storeCardDetailsPaypal => store card details to paypal account
     *
     * @param  mixed $input
     *
     * @return void
     */
    public function storeCardDetailsPaypal($input = null, $paypalLoginDetail = null)
    {
        $response = null;
        if (!isset($input) || !isset($paypalLoginDetail)) {
            return $this->makeError(null, __('validation.common.details_not_found', ['module' => "Card"]));
        }


        //  $item_1 = new Item();
        // $item_1->setName('Item 1')
        //     /** item name **/
        //     ->setCurrency('USD')
        //     ->setQuantity(1)
        //     ->setPrice($request->get('amount'));
        /** unit price **/

        /** store credit Card Details */
        $creditCard = new CreditCard();
        $creditCard->setNumber($input['number'])
            ->setType($input['type'])
            ->setExpireMonth($input['expire_month'])
            ->setExpireYear($input['expire_year'])
            ->setCvv2($input['cvv'])
            ->setFirstName($input['first_name'])
            ->setLastName($input['last_name'])
            ->setBillingAddress($input['billing_address']);





        try {
            $client = new \GuzzleHttp\Client();
            // https://api.sandbox.paypal.com/v1/vault/credit-cards/ 
            $response = $client->request(
                'POST',
                'https://api.sandbox.paypal.com/v1/vault/credit-cards/',
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => $paypalLoginDetail['token_type'] . ' ' . $paypalLoginDetail['access_token']
                    ],
                    'json' => [
                        $input
                    ]
                ]
            );
            $response = json_decode($response->getBody()->getContents(), true);


            // dd('check req', $response->getStatusCode(),  json_decode($response->getBody()->getContents(), true));
        } catch (\Exception $exception) {
            \Log::error($exception->getMessage());
            // dd('check exception', $exception->getMessage());
        }
        return $response;
    }
}
