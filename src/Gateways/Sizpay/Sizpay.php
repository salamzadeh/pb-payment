<?php
/**
 * Api Version: ?
 * Api Document Date: 1401/04/01
 * Last Update: 2022/08/03
 */

namespace Salamzadeh\PBPayment\Gateways\Sizpay;

use Illuminate\Support\Facades\Http;
use Salamzadeh\PBPayment\Gateways\AbstractGateway;
use Salamzadeh\PBPayment\Gateways\GatewayInterface;

use Salamzadeh\PBPayment\Exceptions\GatewayException;
use Salamzadeh\PBPayment\Exceptions\InvalidDataException;
use Salamzadeh\PBPayment\Exceptions\PBPaymentException;

use Salamzadeh\PBPayment\Helpers\Currency;

use Exception;
use SoapFault;
use SoapClient;

class Sizpay extends AbstractGateway implements GatewayInterface
{
    private const TOKEN_URL = 'https://rt.sizpay.ir/KimiaIPGRouteService.asmx?WSDL';
    private const PAYMENT_URL = 'https://rt.sizpay.ir/Route/Payment';
    private const VERIFY_URL = 'https://rt.sizpay.ir/KimiaIPGRouteService.asmx?WSDL';
    private const CURRENCY = Currency::IRR;

    /**
     * Merchant ID variable
     *
     * @var string|null
     */
    protected ?string $merchant_id;

    /**
     * Terminal ID variable
     *
     * @var string|null
     */
    protected ?string $terminal_id;

    /**
     * Username variable
     *
     * @var string|null
     */
    protected ?string $username;

    /**
     * Password variable
     *
     * @var string|null
     */
    protected ?string $password;

    /**
     * SignData variable
     *
     * @var string|null
     */
    protected ?string $sign_data;

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
        return 'sizpay';
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
     * Set Terminal Id function
     *
     * @param string $terminal_id
     * @return $this
     */
    public function setTerminalId(string $terminal_id): self
    {
        $this->terminal_id = $terminal_id;

        return $this;
    }

    /**
     * Get Terminal Id function
     *
     * @return string|null
     */
    public function getTerminalId(): ?string
    {
        return $this->terminal_id;
    }

    /**
     * Set Username function
     *
     * @param string $username
     * @return $this
     */
    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Get Username function
     *
     * @return string|null
     */
    public function getUsername(): ?string
    {
        return $this->username;
    }

    /**
     * Set Password function
     *
     * @param string $password
     * @return $this
     */
    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get Password function
     *
     * @return string|null
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * Set Sign Data function
     *
     * @param string $sign_data
     * @return $this
     */
    public function setSignData(string $sign_data): self
    {
        $this->sign_data = $sign_data;

        return $this;
    }

    /**
     * Get Sign Data function
     *
     * @return string|null
     */
    public function getSignData(): ?string
    {
        return $this->sign_data;
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

        $this->setMerchantId(app('config')->get('pbpayment.sizpay.merchant_id'));
        $this->setTerminalId(app('config')->get('pbpayment.sizpay.terminal_id'));
        $this->setUsername(app('config')->get('pbpayment.sizpay.username'));
        $this->setPassword(app('config')->get('pbpayment.sizpay.password'));
        $this->setSignData(app('config')->get('pbpayment.sizpay.sign_data'));

        $this->setCallbackUrl($parameters['callback_url']
            ?? app('config')->get('pbpayment.sizpay.callback-url')
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

        if ($this->preparedAmount() < 10000 || $this->preparedAmount() > 500000000) {
            throw InvalidDataException::invalidAmount();
        }

        $this->setResNum($this->getTransactionCode());
    }

    /**
     * @throws GatewayException
     * @throws SizpayException
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

            $result = $soap->GetToken2(
                $parameters = array(
                    'MerchantID'  => $this->getMerchantId(),
                    'TerminalID'  => $this->getTerminalId(),
                    'UserName'    => $this->getUsername(),
                    'Password'    => $this->getPassword(),
                    'Amount'      => $this->preparedAmount(),
                    'OrderID'     => time(),
                    'ReturnURL'   => $this->getCallbackUrl(),
                    'InvoiceNo'   => time(),
                    'DocDate'     => '',
                    'ExtraInf'    => $this->getTransactionCode(),
                    'AppExtraInf' => '',
                    'SignData'    => $this->getSignData()

                )
            );
        } catch(SoapFault|Exception $ex) {
            throw GatewayException::connectionProblem($ex);
        }
        $result = json_decode($result->GetToken2Result);
        if (! isset($result->ResCod) || !in_array($result->ResCod, array('0', '00'))) {
            throw SizpayException::error($result->ResCod);
        }

        $this->setToken($result->Token);
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
        $response = Http::post(self::PAYMENT_URL, [
            'MerchantID' => $this->getMerchantId(),
            'TerminalID' => $this->getTerminalId(),
            'Token' => $this->getToken(),
        ]);
        return $response;
    }

    /**
     * Purchase View Params function
     *
     * @return array
     */
    protected function purchaseViewParams(): array
    {
        return [
            'title' => 'سیزپی',
            'image' => 'https://raw.githubusercontent.com/salamzadeh/pb-payment/master/resources/assets/img/sep.png',
        ];
    }

    /**
     * @throws PBPaymentException
     */
    public function preVerify(): void
    {
        parent::preVerify();
        if (
            (isset($this->request['ResCod']) && $this->request['ResCod'] !== '0') &&
            (isset($this->request['ResCod']) && $this->request['ResCod'] !== '00')
        ) {
            throw SizpayException::error($this->request['ResCod']);
        }

        if (isset($this->request['MerchantID']) && $this->request['MerchantID'] !== $this->getMerchantId()) {
            throw SizpayException::error(1003);
        }

        $this->transactionUpdate([
            'extra' => $this->request['AppExtraInf'] ?? null,
            'tracking_code' => $this->request['RefNo'] ?? null,
            'reference_number' => $this->request['Token'] ?? null,
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

            $result = $soap->Confirm2(
                $parameters = array(
                    'MerchantID'  => $this->getMerchantId(),
                    'TerminalID'  => $this->getTerminalId(),
                    'UserName'    => $this->getUsername(),
                    'Password'    => $this->getPassword(),
                    'Token'      => $this->getReferenceNumber(),
                    'SignData'    => ''

                )
            );

        } catch(SoapFault|Exception $ex) {
            throw GatewayException::connectionProblem($ex);
        }
        $result = json_decode($result->Confirm2Result);
        if (! isset($result->ResCod) || !in_array($result->ResCod, array('0', '00'))) {
            throw SizpayException::error($result->ResCod);
        }

        if ($result->Amount != $this->preparedAmount()) {
            throw SizpayException::error(7777);
        }
    }
}
