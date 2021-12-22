<?php

namespace Salamzadeh\PBPayment\Exceptions;

use Salamzadeh\PBPayment\Exceptions\PBPaymentException;

class InvalidRequestException extends PBPaymentException
{
    public static function notFound()
    {
        return new self('درخواست مورد نظر یافت نشد');
    }

    public static function unProcessableVerify()
    {
        return new self('امکان انجام عملیات تایید بر روی این تراکنش وجود ندارد');
    }
}