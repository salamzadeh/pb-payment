<?php

use Salamzadeh\PBPayment\Gateways\PayIr\PayIr;
use Salamzadeh\PBPayment\Gateways\Zarinpal\Zarinpal;
use Salamzadeh\PBPayment\PBPayment;
use Salamzadeh\PBPayment\Models\PBPaymentTransaction;
use Orchestra\Testbench\TestCase;
use Tests\Models\ProductModel;

class PBpaymentZarinpalGatewayTest extends TestCase
{
    /**
     * Setup the test environment.
     */
    protected function setUp() : void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadMigrationsFrom(__DIR__ . '/migrations');
    }
    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('app.env', 'testing');

        $app['config']->set(
            'pbpayment.zarinpal.merchant-id',
            app('config')->get('pbpayment.zarinpal.merchant-id', 1)
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            \Salamzadeh\PBPayment\PBPaymentServiceProvider::class,
        ];
    }

    public function testSuccess()
    {
        $gateway = Mockery::mock(Zarinpal::class)->makePartial();
        $gateway->shouldReceive('getAuthority')->andReturn(1);
        $gateway->shouldReceive('purchase')->andReturn(null);
        $gateway->shouldReceive('verify')->andReturn(null);
        $gateway->shouldReceive('getRefId')->andReturn(1);

        $product = (new ProductModel(['title' => 'product']));
        $product->save();
        $payment = PBPayment::create($gateway);

        $payment = $payment
            ->setAmount(10000)
            ->setCallbackUrl(url('/test'))
            ->setPayable($product);
        $this->assertInstanceOf(Zarinpal::class, $payment);
        $this->assertEquals(10000, $payment->getAmount());
        $this->assertEquals(url('/test'), $payment->getCallbackUrl());
        $this->assertEquals(1, $payment->getPayable()->id);
        $this->assertEquals(ProductModel::class, get_class($payment->getPayable()));

        $payment = $payment->ready();
        $this->assertEquals(PBPaymentTransaction::T_PENDING, $payment->getTransaction()->status);
        $this->assertEquals(1, $payment->getAuthority());
        $this->assertEquals("https://www.zarinpal.com/pg/StartPay/1", $payment->purchaseUri());

        $tr = $payment->getTransaction();
        $payment = PBPayment::create($gateway);
        $payment->findTransaction($tr->code);
        $payment->confirm();
        $transaction = $payment->getTransaction();
        $this->assertEquals(PBPaymentTransaction::T_SUCCEED, $transaction->status);
    }
}