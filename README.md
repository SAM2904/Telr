# Pay online using telr payment gateway

## Installation

You can install the package via composer:

``` bash
composer require sudhanshu-mittal/telr-laravel
```


In Laravel starting from 5.5 the service provider will automatically get registered. In older versions of the framework just add the service provider in `config/app.php` file:

```php
'providers' => [
    // ...
    SudhanshuMittal\TelrGateway\TelrServiceProvider::class,
];
```

You can publish using the following provider

```bash
php artisan vendor:publish --provider="SudhanshuMittal\TelrGateway\TelrServiceProvider"
```
After that you can create the telr transaction table by running the migrations command:

```bash
php artisan migrate
```

When published, [the `config/telr.php` config file](#) contains:
```php
return [
    // The current mode is live|production or test
    'test_mode' => env('TELR_TEST_MODE', true),

    // The currency of store

    'currency' => 'SAR',

    // The sale endpoint that receive the params
    // @see https://telr.com/support/knowledge-base/hosted-payment-page-integration-guide
    'sale' => [
        'endpoint' => 'https://secure.telr.com/gateway/order.json',
    ],

    // The hosted payment page use the following params as it explained in the integration guide
    // @see https://telr.com/support/knowledge-base/hosted-payment-page-integration-guide/#request-method-and-format
    'create' => [
        'ivp_method' => "create",
        'ivp_store' => env('TELR_STORE_ID', null),
        'ivp_authkey' => env('TELR_STORE_AUTH_KEY', null),
        'return_auth' => '/handle-payment/success',
        'return_can' => '/handle-payment/cancel',
        'return_decl' => '/handle-payment/declined',
    ]
];
```

## Usage

After creating the route place the following code to redirect to bank page

```php
$telrManager = new \SudhanshuMittal\TelrGateway\TelrManager();

$billingParams = [
        'first_name' => 'Robert',
        'sur_name' => 'Evans',
        'address_1' => 'Gnaklis',
        'address_2' => 'Gnaklis 2',
        'city' => 'Alexandria',
        'region' => 'San Stefano',
        'zip' => '11231',
        'country' => 'EG',
        'email' => 'example@company.com',
    ];

$req_params = [
    'user_id' => 5,
    'product_id' => 2,
    'amount' => 500,
    'short_desc' => 'cart items request payload',
];

return $telrManager->pay('ORDER_ID_GOES_HERE', 'TOTAL_AMOUNT', 'DESCRIPTION ...', $billingParams, $req_params)->redirect();

```
> - note that if you want to avoid sending billing params while creating token to process the payment it's applicable and the `Telr hosted payment page` will require it and will get the customer information on**check**request.

> - Also, if you want to avoid sending request params while creating token to process the payment it's applicable and the `Telr hosted payment page` will not require it. The req_params helps you to know the request payload for which you initiate the transaction.

And on telr callback **(Success|Cancel|Declined)** to handle response put the following code:
```php
$telrManager = new \SudhanshuMittal\TelrGateway\TelrManager();
$telrManager->handleTransactionResponse($request);
```