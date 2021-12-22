<?php

namespace Salamzadeh\PBPayment\Traits;

trait Payable
{
    /**
     * PBPayment Amount variable
     *
     * @var int|null
     */
    private ?int $pbpayment_amount;

    /**
     * PBPayment Amount Model Field Name variable
     *
     * There is no need to call setPBPaymentAmount function
     * if this variable has been set in model.
     *
     * @var string|null
     */
     // protected ?string $pbpayment_amount_field;

    /**
     * Get all of the payment's transactions.
     */
    public function transactions()
    {
        return $this->morphMany(\Salamzadeh\PBPayment\Models\PBPaymentTransaction::class, 'payable');
    }

    /**
     * Set PBPayment Amount function
     *
     * @param int $amount
     * @return $this
     */
    protected function setPBPaymentAmount(int $amount): self
    {
        $this->pbpayment_amount = $amount;

        return $this;
    }

    /**
     * Call PBPayment Purchase Method
     *
     * @param null $gateway
     * @return mixed
     * @throws \Salamzadeh\PBPayment\Exceptions\PBPaymentException
     */
    public function pay($gateway = null)
    {
        if (!isset($this->pbpayment_amount) && isset($this->pbpayment_amount_field)) {
            $this->pbpayment_amount = intval($this->{$this->pbpayment_amount_field});
        }

        return \Salamzadeh\PBPayment\PBPayment::create($gateway)
            ->setAmount($this->pbpayment_amount)
            ->setPayable($this)
            ->ready();
    }
}
