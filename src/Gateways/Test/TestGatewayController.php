<?php

namespace Salamzadeh\PBPayment\Gateways\Test;

use Salamzadeh\PBPayment\PBPayment;
use Salamzadeh\PBPayment\Models\PBPaymentTransaction;

use Illuminate\Http\Request;

class TestGatewayController {

    public function paymentView($code)
    {
        $payment = PBPayment::create('test');
        $transaction = PBPaymentTransaction::where('reference_number', $code)->first();
        $payment->setTransaction($transaction);

        return $payment->view();
    }

    public function verify(Request $request, $code)
    {
        $transaction = PBPaymentTransaction::where('code', $code)->first();

        $queryParams = http_build_query([
            app('config')->get('pbpayment.transaction_query_param') => $code
        ]);

        $callback = $transaction->extra['callback_url'];;

        $question_mark = strpos($callback, '?');
        if($question_mark) {
            return redirect($callback.'&'.$queryParams);
        }

        return redirect($callback.'?'.$queryParams);
    }
}
