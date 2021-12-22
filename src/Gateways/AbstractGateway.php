<?php

namespace Salamzadeh\PBPayment\Gateways;

use Salamzadeh\PBPayment\Traits\UserData;
use Salamzadeh\PBPayment\Traits\PaymentData;
use Salamzadeh\PBPayment\Traits\TransactionData;

use Exception;
use Salamzadeh\PBPayment\Exceptions\InvalidDataException;
use Salamzadeh\PBPayment\Exceptions\PBPaymentException;
use Salamzadeh\PBPayment\Exceptions\SucceedRetryException;
use Salamzadeh\PBPayment\Exceptions\InvalidRequestException;
use Salamzadeh\PBPayment\Exceptions\TransactionFailedException;
use Salamzadeh\PBPayment\Exceptions\TransactionNotFoundException;

use Salamzadeh\PBPayment\Models\PBPaymentTransaction;

use Salamzadeh\PBPayment\Helpers\Currency;

/**
 * @method getName()
 * @method purchase()
 * @method purchaseUri()
 * @method verify()
 */
abstract class AbstractGateway
{
	use UserData,
        PaymentData,
        TransactionData;

    /**
     * Request variable
     *
     * @var array
     */
	protected array $request;

    /**
     * Gateway Request Options variable
     *
     * @var array
     */
    protected array $gateway_request_options = [];

    /**
     * Initialize Gateway function
     *
     * @param array $parameters
     * @return $this
     * @throws InvalidDataException
     */
    public function initialize(array $parameters = []): self
    {
        $this->setRequest($parameters['request'] ?? app('request')->all());

        $this->setCurrency($parameters['currency'] ?? app('config')->get('pbpayment.currency', Currency::IRR));

        $this->setCallbackUrl($parameters['callback_url'] ?? app('config')->get('pbpayment.callback-url'));

        $this->setGatewayRequestOptions(array_merge(
            [
                'timeout' => app('config')->get('pbpayment.timeout', 30),
                'connection_timeout' => app('config')->get('pbpayment.connection_timeout', 60),
            ],
            $parameters['gateway_request_options'] ?? [],
        ));

        return $this;
    }

	/**
	 * Set Request function
	 *
	 * @param array $request
	 * @return $this
	 */
	public function setRequest(array $request): self
	{
		$this->request = $request;

		return $this;
	}

    /**
     * Get Request function
     *
     * @return array
     */
    public function getRequest(): array
    {
        return $this->request;
    }

    /**
     * Set Gateway Request Options function
     *
     * @param array $options
     * @return $this
     */
    public function setGatewayRequestOptions(array $options): self
    {
        $this->gateway_request_options = $options;

        return $this;
    }

    /**
     * Get Gateway Request Options function
     *
     * @return array
     */
    public function getGatewayRequestOptions(): array
    {
        return $this->gateway_request_options;
    }

    /**
     * @throws InvalidDataException
     */
    protected function prePurchase(): void
    {
        if ($this->preparedAmount() <= 0) {
            throw InvalidDataException::invalidAmount();
        }

        if (!in_array($this->getCurrency(), [Currency::IRR, Currency::IRT])) {
            throw InvalidDataException::invalidCurrency();
        }

        if (filter_var($this->preparedCallbackUrl(), FILTER_VALIDATE_URL) === false) {
            throw InvalidDataException::invalidCallbackUrl();
        }

        $this->newTransaction([
            'full_name' => $this->getFullname(),
            'email' => $this->getEmail(),
            'mobile' => $this->getMobile(),
            'description' => $this->getDescription(),
        ]);
    }

    protected function postPurchase(): void
    {
        $this->transactionPending();
    }

    /**
     * Pay function
     *
     * @return $this
     * @throws PBPaymentException
     */
    public function ready(): self
	{
		try {
            $this->prePurchase();

            $this->purchase();

            $this->postPurchase();
		} catch (Exception $ex) {
            if ($this->getTransaction() !== null) {
                $this->transactionFailed($ex->getMessage());
            }

		    if (!$ex instanceof PBPaymentException) {
                $ex = PBPaymentException::unknown($ex);
            }

		    throw $ex;
		}

		return $this;
	}

    /**
     * Alias for Purchase Uri function
     *
     * @return string
     * @throws PBPaymentException
     */
    public function uri(): string
    {
        try {
            return $this->purchaseUri();
        } catch (PBPaymentException $ex) {
            throw $ex;
        } catch (Exception $ex) {
            throw PBPaymentException::unknown($ex);
        }
    }

    /**
     * Redirect to Purchase Uri function
     *
     * @throws PBPaymentException
     */
    public function redirect()
    {
        try {
            return response()->redirectTo($this->purchaseUri());
        } catch (PBPaymentException $ex) {
            throw $ex;
        } catch (Exception $ex) {
            throw PBPaymentException::unknown($ex);
        }
    }

    /**
     * Purchase View Params function
     *
     * @return array
     */
    protected function purchaseViewParams(): array
    {
        return [];
    }

    /**
     * @param array $data
     * @return mixed
     */
	public function purchaseView(array $data = [])
	{
        $parameters = array_merge(
            [
                'view' => 'pbpayment::pages.redirect',
                'title' => null,
                'image' => null,
                'method' => 'GET',
                'form_data' => [],
            ],
            $this->purchaseViewParams(),
            $data
        );

		return response()->view($parameters['view'], array_merge(
            [
                'transaction_code' => $parameters['transaction_code'] ?? $this->getTransactionCode(),
                'bank_url' => $parameters['bank_url'] ?? $this->purchaseUri(),
		    ],
            $parameters
        ));
	}

    /**
     * Alias for Purchase View function
     *
     * @param array $data
     * @return mixed
     */
    public function view(array $data = [])
    {
        return $this->purchaseView($data);
    }

    /**
     * @throws PBPaymentException
     */
    protected function preVerify(): void
    {
        if(!isset($this->transaction)) {
            $transaction_code_field = app('config')->get('pbpayment.transaction_query_param', 'tc');
            if (isset($this->request[$transaction_code_field])) {
                $this->findTransaction($this->request[$transaction_code_field]);
            } else {
                throw new TransactionNotFoundException();
            }
        }

        if ($this->transaction->status == PBPaymentTransaction::T_SUCCEED) {
            throw new SucceedRetryException;
        } elseif (!in_array($this->transaction->status, [
            PBPaymentTransaction::T_PENDING,
            PBPaymentTransaction::T_VERIFY_PENDING,
        ])) {
            throw InvalidRequestException::unProcessableVerify();
        }

        $this->setCurrency($this->transaction->currency);
        $this->setAmount($this->transaction->amount);

        $this->transactionVerifyPending();
    }

    protected function postVerify(): void
    {
        $this->transactionSucceed();
    }

    /**
     * Confirm function
     *
     * @param PBPaymentTransaction|null $transaction
     * @return self
     * @throws PBPaymentException
     */
	public function confirm(PBPaymentTransaction $transaction = null)
	{
		if(isset($transaction)) {
			$this->setTransaction($transaction);
		}

		try {
            $this->preVerify();

            $this->verify();

            $this->postVerify();
        } catch (TransactionFailedException $ex) {
            $this->transactionFailed($ex->getMessage());

            throw $ex;
        } catch (PBPaymentException $ex) {
            throw $ex;
        } catch (Exception $ex) {
            throw PBPaymentException::unknown($ex);
        }

		return $this;
	}
}
