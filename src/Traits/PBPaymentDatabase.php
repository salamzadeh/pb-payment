<?php

namespace Salamzadeh\PBPayment\Traits;

trait PBPaymentDatabase
{
    /**
     * PBPayment Table Name variable
     *
     * @var string
     */
    private string $pbpayment_table = 'pbpayment_transactions';

    /**
     * Get PBPayment Table Name function
     *
     * @return string
     */
    public function getTable(): string
    {
        return $this->table = app('config')->get('pbpayment.table', $this->pbpayment_table);
    }
}
