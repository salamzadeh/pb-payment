<?php
/**
 * Api Version: ?
 * Api Document Date: 1398/04/01
 * Last Update: 2020/08/03
 */

namespace Salamzadeh\PBPayment\Gateways\Saman;

use Salamzadeh\PBPayment\Gateways\AbstractGateway;
use Salamzadeh\PBPayment\Gateways\GatewayInterface;

use Salamzadeh\PBPayment\Exceptions\GatewayException;
use Salamzadeh\PBPayment\Exceptions\InvalidDataException;
use Salamzadeh\PBPayment\Exceptions\PBPaymentException;

use Salamzadeh\PBPayment\Helpers\Currency;

use Exception;
use SoapFault;
use SoapClient;

class Saman extends AbstractGateway implements GatewayInterface
{
    private const TOKEN_URL = 'https://sep.shaparak.ir/Payments/InitPayment.asmx?wsdl';
    private const PAYMENT_URL = 'https://sep.shaparak.ir/Payment.aspx';
    private const VERIFY_URL = 'https://verify.sep.ir/Payments/ReferencePayment.asmx?wsdl';
    private const CURRENCY = Currency::IRR;

    /**
     * Merchant ID variable
     *
     * @var string|null
     */
    protected ?string $merchant_id;

    /**
     * ResNum variable
     *
     * @var string|null
     */
    protected ?string $res_num;

    /**
     * Token variable
     *
     * @var string|null
     */
    protected ?string $token;

    /**
     * Gateway Name function
     *
     * @return string
     */
    public function getName(): string
    {
        return 'saman';
    }

    /**
     * Set Merchant Id function
     *
     * @param string $merchant_id
     * @return $this
     */
    public function setMerchantId(string $merchant_id): self
    {
        $this->merchant_id = $merchant_id;

        return $this;
    }

    /**
     * Get Merchant Id function
     *
     * @return string|null
     */
    public function getMerchantId(): ?string
    {
        return $this->merchant_id;
    }

    /**
     * Set ResNum function
     *
     * @param string|null $res_num
     * @return $this
     */
    public function setResNum(string $res_num): self
    {
        $this->res_num = $res_num;

        return $this;
    }

    /**
     * Get ResNum function
     *
     * @return string|null
     */
    public function getResNum(): ?string
    {
        return $this->res_num;
    }

    /**
     * Set Token function
     *
     * @param string|null $token
     * @return $this
     */
    public function setToken(string $token): self
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Get Token function
     *
     * @return string|null
     */
    public function getToken(): ?string
    {
        return $this->token;
    }

    /**
     * Initialize function
     *
     * @param array $parameters
     * @return $this
     * @throws InvalidDataException
     */
    public function initialize(array $parameters = []): self
    {
        parent::initialize($parameters);

        $this->setGatewayCurrency(self::CURRENCY);

        $this->setMerchantId(app('config')->get('pbpayment.saman.merchant-id'));

        $this->setCallbackUrl($parameters['callback_url']
            ?? app('config')->get('pbpayment.saman.callback-url')
            ?? app('config')->get('pbpayment.callback-url')
        );

        return $this;
    }

    /**
     * @throws InvalidDataException
     */
    protected function prePurchase(): void
    {
        parent::prePurchase();

        if ($this->preparedAmount() < 100) {
            throw InvalidDataException::invalidAmount();
        }

        $this->setResNum($this->getTransactionCode());
    }

    /**
     * @throws GatewayException
     * @throws SamanException
     */
    public function purchase(): void
    {
        try{
            $soap = new SoapClient(self::TOKEN_URL, [
                'encoding' => 'UTF-8',
                'trace' => 1,
                'exceptions' => 1,
                'connection_timeout' => $this->getGatewayRequestOptions()['connection_timeout'] ?? 60,
            ]);

            $result = $soap->RequestToken(
                $this->getMerchantId(),
                $this->getTransactionCode(),
                $this->preparedAmount()
            );
        } catch(SoapFault|Exception $ex) {
            throw GatewayException::connectionProblem($ex);
        }

        if (is_numeric($result)) {
            throw SamanException::error($result);
        }

        $this->setToken($result);
    }

    protected function postPurchase(): void
    {
        $this->transactionUpdate([
            'reference_number' => $this->getToken(),
        ]);

        parent::postPurchase();
    }

    /**
     * @return string
     * @throws GatewayException
     */
    public function purchaseUri(): string
    {
        throw GatewayException::notSupportedMethod();
    }

    /**
     * Purchase View Params function
     *
     * @return array
     */
    protected function purchaseViewParams(): array
    {
        return [
            'title' => 'بانک سامان',
            'image' => 'https://raw.githubusercontent.com/salamzadeh/pb-payment/master/resources/assets/img/sep.png',
            'bank_url' => self::PAYMENT_URL,
            'method' => 'POST',
            'form_data' => [
                'Token' => $this->getToken(),
                'RedirectURL' => $this->getCallbackUrl(),
            ],
        ];
    }

    /**
     * @throws PBPaymentException
     */
    public function preVerify(): void
    {
        parent::preVerify();

        if (
            (isset($this->request['State']) && $this->request['State'] !== 'OK') ||
            (isset($this->request['StateCode']) && $this->request['StateCode'] !== '0')
        ) {
            switch ($this->request['StateCode']) {
                case '-1':
                    throw SamanException::error(-101);
                case '51':
                    throw SamanException::error(51);
                default:
                    throw SamanException::error(-100);
            }
        }

        if (isset($this->request['MID']) && $this->request['MID'] !== $this->getMerchantId()) {
            throw SamanException::error(-4);
        }

        $this->transactionUpdate([
            'card_number' => $this->request['SecurePan'] ?? null,
            'tracking_code' => $this->request['TRACENO'] ?? null,
            'reference_number' => $this->request['RefNum'] ?? null,
        ]);
    }

    /**
     * @throws GatewayException
     */
    public function verify(): void
    {
        try{
            $soap = new SoapClient(self::VERIFY_URL, [
                'encoding' => 'UTF-8',
                'trace' => 1,
                'exceptions' => 1,
                'connection_timeout' => $this->getGatewayRequestOptions()['connection_timeout'] ?? 60,
            ]);

            $result = $soap->verifyTransaction(
                $this->getReferenceNumber(),
                $this->getMerchantId()
            );
        } catch(SoapFault|Exception $ex) {
            throw GatewayException::connectionProblem($ex);
        }

        if ($result <= 0) {
            throw SamanException::error($result);
        }

        if ($result != $this->preparedAmount()) {
            throw SamanException::error(-102);
        }
    }
}
