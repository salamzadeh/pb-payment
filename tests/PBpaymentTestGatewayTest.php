<?php

use Salamzadeh\PBPayment\Gateways\Sadad\Sadad;
use Salamzadeh\PBPayment\Gateways\Test\TestGateway;
use Salamzadeh\PBPayment\PBPayment;
use Salamzadeh\PBPayment\Models\PBPaymentTransaction;
use Orchestra\Testbench\TestCase;
use Tests\Models\ProductModel;

class PBpaymentTestGatewayTest extends TestCase
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
    }

    protected function getPackageProviders($app)
    {
        return [
            \Salamzadeh\PBPayment\PBPaymentServiceProvider::class,
        ];
    }

    public function testGateway()
    {
        $payment = PBPayment::create('test');
        $this->assertInstanceOf(TestGateway::class, $payment);
        $payment = $payment->setAmount(1000)
            ->setCallbackUrl(url('/test'))
            ->setPayableId(1)
            ->setPayableType(ProductModel::class)
            ->ready();

        $this->assertEquals(PBPaymentTransaction::T_PENDING, $payment->getTransaction()->status);
        $this->assertEquals(1000, $payment->getTransaction()->amount);
        $this->assertEquals(url('/test'), $payment->getCallbackUrl());
        $this->assertEquals(1, $payment->getPayableId());
        $this->assertEquals(ProductModel::class, $payment->getPayableType());

        //verify
        $tr = $payment->getTransaction();
        $payment = PBPayment::create('test');
        $payment->findTransaction($tr->code);
        $payment->confirm();
        $transaction = $payment->getTransaction();
        $this->assertEquals(PBPaymentTransaction::T_SUCCEED, $transaction->status);
    }
}