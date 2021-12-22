<?php

use Salamzadeh\PBPayment\Gateways\PayIr\PayIr;
use Salamzadeh\PBPayment\Gateways\Saman\Saman;
use Salamzadeh\PBPayment\PBPayment;
use Salamzadeh\PBPayment\Models\PBPaymentTransaction;
use Orchestra\Testbench\TestCase;
use Tests\Models\ProductModel;

class PBpaymentSamanGatewayTest extends TestCase
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
            'pbpayment.saman.merchant-id',
            app('config')->get('pbpayment.saman.merchant-id', 1)
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
        $gateway = Mockery::mock(Saman::class)->makePartial();
        $gateway->shouldReceive('getToken')->andReturn(1);
        $gateway->shouldReceive('purchase')->andReturn(null);
        $gateway->shouldReceive('verify')->andReturn(null);

        $product = (new ProductModel(['title' => 'product']));
        $product->save();
        $payment = PBPayment::create($gateway);

        $payment = $payment
            ->setAmount(10000)
            ->setCallbackUrl(url('/test'))
            ->setPayable($product);
        $this->assertInstanceOf(Saman::class, $payment);
        $this->assertEquals(10000, $payment->getAmount());
        $this->assertEquals(url('/test'), $payment->getCallbackUrl());
        $this->assertEquals(1, $payment->getPayable()->id);
        $this->assertEquals(ProductModel::class, get_class($payment->getPayable()));

        $payment = $payment->ready();
        $this->assertEquals(PBPaymentTransaction::T_PENDING, $payment->getTransaction()->status);
        $this->assertEquals(1, $payment->getToken());

        $tr = $payment->getTransaction();
        $payment = (new PBPayment($gateway))->build();
        $payment->findTransaction($tr->code);
        $payment->confirm();
        $transaction = $payment->getTransaction();
        $this->assertEquals(PBPaymentTransaction::T_SUCCEED, $transaction->status);
    }
}