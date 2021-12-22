<?php

use Salamzadeh\PBPayment\Gateways\Sadad\Sadad;
use Salamzadeh\PBPayment\Gateways\Sadad\SadadException;
use Salamzadeh\PBPayment\Gateways\Test\TestGateway;
use Salamzadeh\PBPayment\PBPayment;
use Salamzadeh\PBPayment\Models\PBPaymentTransaction;
use Orchestra\Testbench\TestCase;
use Tests\Models\ProductModel;

class PBpaymentSadadGatewayTest extends TestCase
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
        $app['config']->set([
            'database.default' => 'testing',
            'app.env' => 'testing',
            'pbpayment.sadad.merchant_id' => app('config')->get('pbpayment.sadad.merchant_id', 1),
            'pbpayment.sadad.terminal_id' => app('config')->get('pbpayment.sadad.terminal_id', "1"),
            'pbpayment.sadad.terminal_key' => app('config')->get('pbpayment.sadad.terminal_key', "1"),
            'pbpayment.sadad.app_name' => app('config')->get('pbpayment.sadad.app_name', "TestApp"),
        ]);
    }

    protected function getPackageProviders($app)
    {
        return [
            \Salamzadeh\PBPayment\PBPaymentServiceProvider::class,
        ];
    }

    public function testSuccess()
    {
        $sadad = Mockery::mock(Sadad::class)->makePartial();
        $sadad->shouldReceive('getToken')->andReturn(1);
        $sadad->shouldAllowMockingProtectedMethods();
        $purchaseResponse = (object) [
            'Token' => 1,
            'ResCode' => 0,
            'Description' => ''
        ];
        $verifyResponse = (object) [
            'ResCode' => 0,
            'Amount' => 10000,
            'SystemTraceNo' => 1,
            'RetrivalRefNo' => 1,
            'Description' => ''
        ];
        $sadad->shouldReceive('httpRequest')->andReturn($purchaseResponse, $verifyResponse);
        // $sadad->shouldReceive('purchase')->andReturn(null);
        // $sadad->shouldReceive('verify')->andReturn(null);

        $payment = PBPayment::create($sadad);

        $payment = $payment
            ->setAmount(10000)
            ->setCallbackUrl(url('/test'))
            ->setPayableId(1)
            ->setPayableType(ProductModel::class);
        $this->assertInstanceOf(Sadad::class, $payment);
        $this->assertEquals(10000, $payment->getAmount());
        $this->assertEquals(url('/test'), $payment->getCallbackUrl());
        $this->assertEquals(1, $payment->getPayableId());
        $this->assertEquals(ProductModel::class, $payment->getPayableType());

        $payment = $payment->ready();
        $this->assertEquals(PBPaymentTransaction::T_PENDING, $payment->getTransaction()->status);
        $this->assertEquals(1, $payment->getToken());
        $this->assertEquals("https://sadad.shaparak.ir/VPG/Purchase?Token=1", $payment->purchaseUri());

        $tr = $payment->getTransaction();
        $payment = (new PBPayment($sadad))->build();
        $payment->findTransaction($tr->code);
        $payment->confirm();
        $transaction = $payment->getTransaction();
        $this->assertEquals(PBPaymentTransaction::T_SUCCEED, $transaction->status);
    }

    public function testError()
    {
        $sadad = Mockery::mock(Sadad::class)->makePartial();
        $sadad->shouldAllowMockingProtectedMethods();
        $purchaseResponse = (object) [
            'Token' => 1,
            'ResCode' => 1006,
            'Description' => ''
        ];
        $verifyResponse = (object) [
            'ResCode' => -1,
            'Amount' => 10000,
            'SystemTraceNo' => 1,
            'RetrivalRefNo' => 1,
            'Description' => ''
        ];
        $sadad->shouldReceive('httpRequest')->andReturn($purchaseResponse, $verifyResponse);

        $payment = PBPayment::create($sadad);

        $payment = $payment
            ->setAmount(10000)
            ->setCallbackUrl(url('/test'))
            ->setPayableId(1)
            ->setPayableType(ProductModel::class);
        $this->assertInstanceOf(Sadad::class, $payment);
        $this->assertEquals(10000, $payment->getAmount());
        $this->assertEquals(url('/test'), $payment->getCallbackUrl());
        $this->assertEquals(1, $payment->getPayableId());
        $this->assertEquals(ProductModel::class, $payment->getPayableType());

        try {
            $payment = $payment->ready();
        } catch (Throwable $e) {
            $this->assertInstanceOf(SadadException::class, $e);
            $this->assertEquals(SadadException::error(1006)->getMessage(), $e->getMessage());
        }

        $this->assertEquals(PBPaymentTransaction::T_FAILED, $payment->getTransaction()->status);
    }
}
