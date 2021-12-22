<?php

namespace Salamzadeh\PBPayment\Gateways\Test;

use Salamzadeh\PBPayment\Exceptions\InvalidDataException;
use Salamzadeh\PBPayment\Gateways\AbstractGateway;
use Salamzadeh\PBPayment\Gateways\GatewayInterface;
use Salamzadeh\PBPayment\Helpers\Currency;
use Exception;

class TestGateway extends AbstractGateway implements GatewayInterface
{
    public function getName(): string
    {
        return 'test';
    }

    public function initialize(array $parameters = []): self
    {
        parent::initialize($parameters);

        $this->setGatewayCurrency(Currency::IRR);

        $this->setCallbackUrl($parameters['callback_url']
            ?? app('config')->get('pbpayment.test.callback-url')
            ?? app('config')->get('pbpayment.callback-url')
        );

        return $this;
    }

	protected function prePurchase(): void
	{
        parent::prePurchase();

        if ($this->preparedAmount() < 1000 || $this->preparedAmount() > 500000000) {
            throw InvalidDataException::invalidAmount();
        }
    }
    
    public function preVerify(): void
    {
        parent::preVerify();
    }

	public function verify(): void
    {
        $code = rand(1, 10000);
		$this->transactionSucceed(['tracking_code' => $code]);
    }

    /**
     * @throws Exception
     */
	public function redirect()
    {
        $this->addExtra($this->getCallbackUrl(), 'callback_url');
        return view('pbpayment::pages.test')->with([
            'transaction_code' => $this->getTransactionCode(),
            'reference_number' => $this->getReferenceNumber(),
        ]);
    }

	public function purchase(): void
    {
        $this->transactionUpdate([
            'reference_number'	=> uniqid(),
		]);
    }

	public function purchaseView(array $arr = [])
    {
        return view('pbpayment::pages.test', [
            'reference_number'	=> uniqid(),
			'transaction_code'	=> $this->getTransactionCode(),
		]);
    }

    public function purchaseUri(): string
    {
        return route('pbpayment.test.pay', $this->getReferenceNumber());
    }
}
