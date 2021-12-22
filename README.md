<p align="center">
  <img src="https://raw.githubusercontent.com/salamzadeh/pb-payment/master/images/logo.png" height="128"/>
</p>
<h1 align="center">Payment Gateways for Laravel</h1>

<p align="center"><a href="https://github.com/salamzadeh/pb-payment" target="_blank"><img width="650" src="https://raw.githubusercontent.com/salamzadeh/pb-payment/master/images/screen.png"></a></p>

**a Laravel package to handle Internet Payment Gateways (IPGs) for Iran Banking System**

Accepting [Sadad (Melli)](https://sadadpsp.ir/), [Pay.ir](https://pay.ir/), [Zarinpal](https://zarinpal.com/) and more iranian payment gateways. Just use the PBPayment to receive payments directly on your website.

[![Latest Stable Version](https://poser.pugx.org/salamzadeh/pb-payment/v)](https://packagist.org/packages/salamzadeh/pb-payment)
[![Total Downloads](https://poser.pugx.org/salamzadeh/pb-payment/downloads)](https://packagist.org/packages/salamzadeh/pb-payment)
[![Latest Unstable Version](https://poser.pugx.org/salamzadeh/pb-payment/v/unstable)](https://packagist.org/packages/salamzadeh/pb-payment)
[![License](https://poser.pugx.org/salamzadeh/pb-payment/license)](https://packagist.org/packages/salamzadeh/pb-payment)

## Gateways

Gateway | Description  | Available | Tested | Last Update
--- | --- | --- | --- | ---
[Sadad (Melli)](https://sadadpsp.ir/) | بانک ملی (سداد) | ✓ | ✓ | 2021/12/22
[Pay.ir](https://pay.ir/) | پرداخت پی | ✓ | ✓ | 2021/12/22
[Zarinpal](https://zarinpal.com/) | زرین پال | ✓ | ✓ | 2021/12/22
[Payping](https://www.payping.ir/) | پی پینگ | ✓ | - | 2021/12/22
[Qeroun](https://qeroun.com/) | قرون - خرید امن با ایجاد توافق‌نامه | - | - | -
[Saman (Sep)](https://www.sep.ir/) | (سپ) بانک سامان | ✓ | - | 2021/12/22
[Mellat (Behpardakht)](http://www.behpardakht.com/) | (به پرداخت) بانک ملت | - | - | -
[Parsian (Pec)](https://www.pec.ir/) | (پک) بانک پارسیان | - | - | -
[Pasargad (Pep)](https://www.pep.co.ir/) | (پپ) بانک پاسارگاد | - | - | -
[Zibal](https://zibal.ir/) | زیبال | - | - | -

## Requirements

* PHP >= 7.4
* PHP ext-curl
* PHP ext-json
* PHP ext-soap
* [Laravel](https://www.laravel.com) (or [Lumen](https://lumen.laravel.com)) >= 5.7

## Installation
1. Add the package to your composer file via the `composer require` command:
   
   ```bash
   $ composer require salamzadeh/pb-payment:^1.0
   ```
   
   Or add it to `composer.json` manually:
   
   ```json
   "require": {
       "salamzadeh/pb-payment": "^1.0"
   }
   ```

2. PBPayment's service providers will be automatically registered using Laravel's auto-discovery feature.

    > Note: For Lumen you have to add the PBPayment service provider manually to: `bootstrap/app.php` :

    ```php
   $app->register( Salamzadeh\PBPayment\PBPaymentServiceProvider::class);
    ```

3. Publish the config-file and migration with:
    ```bash
    php artisan vendor:publish --provider="Salamzadeh\PBPayment\PBPaymentServiceProvider"
    ```
4. After the migration has been published you can create the transactions-tables by running the migrations:
    ```bash
    php artisan migrate
    ```

## Usage

### New Payment:
```php
use Salamzadeh\PBPayment\PBPayment;

// Default gateway
$payment = PBPayment::create();
// Select one of available gateways
$payment = PBPayment::create('sadad');
// Test gateway (Would not work on production environment)
$payment = PBPayment::create('test');
// Or use your own gateway
$payment = PBPayment::create(NewGateway::class);

$payment->setUserId($user->id)
        ->setAmount($data['amount'])
        ->setCallbackUrl(route('bank.callback'))
        ->ready();

return $payment->redirect();
```

### Verify Payment:

```php
use Salamzadeh\PBPayment\PBPayment;
use Salamzadeh\PBPayment\Exceptions\PBPaymentException;

try {
    $payment = PBPayment::detect()->confirm();
    $trackingCode = $payment->getTrackingCode();
    $statusText = $payment->getTransactionStatusText();
} catch (Salamzadeh\PBPayment\Exceptions\PBPaymentException $ex) {
    throw $ex;
}
```
### Create your own payment gateway class
```php
use Salamzadeh\PBPayment\Gateways\AbstractGateway;
use Salamzadeh\PBPayment\Gateways\GatewayInterface;

class NewGateway extends AbstractGateway implements GatewayInterface
{
    public function getName(): string
    {
        return 'new-gateway';
    }

    public function initialize(array $parameters = []): self
    {
        parent::initialize($parameters);
    
        return $this;
    }
    
    public function purchase(): void
    {
        // Send Purchase Request

        $reference_number = 'xxxx';

        $this->transactionUpdate([
            'reference_number' => $reference_number,
        ]);
    }

    
    public function purchaseUri(): string
    {
        return 'http://new-gateway.com/token/xxxx';
    }
    
    public function verify(): void
    {
        $this->transactionVerifyPending();
            
        // Send Payment Verify Request

        $tracking_code = 'yyyy';

        $this->transactionSucceed([
            'tracking_code' => $tracking_code
        ]);
    }
}
```

## Upgrading from v1.x

TODO:

## Contribute

Contributions are always welcome!

## Support

If you believe you have found a bug, please report it using the [GitHub issue tracker](https://github.com/salamzadeh/pb-payment/issues),
or better yet, fork the library and submit a pull request.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
