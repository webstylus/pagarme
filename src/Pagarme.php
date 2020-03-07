<?php

namespace Lojazone\Pagarme;

use PagarMe\Client;
use PagarMe\Exceptions\PagarMeException;

class Pagarme
{
    /**
     * @var \Illuminate\Config\Repository|mixed
     */
    private $environment;
    /**
     * @var Client
     */
    private $pagarme;
    /**
     * @var
     */
    private $customer;
    /**
     * @var
     */
    private $card;
    /**
     * @var
     */
    private $error;
    /**
     * @var array
     */
    private $transactionData;
    /**
     * @var
     */
    private $transaction;
    /**
     * @var
     */
    private $bank;
    /**
     * @var
     */
    private $recipient;
    /**
     * @var
     */
    private $plan;
    /**
     * @var
     */
    private $subscription;

    /**
     * @return string|null
     */
    public function getError(): ?string
    {
        $error = preg_replace('/.*MESSAGE\:\s/', '', $this->error);
        if (empty($error)) {
            $error = 'Não foi possível completar esta ação, verifique os dados e tente novamente.';
        }
        return $error;
    }

    /**
     * Pagarme constructor.
     */
    public function __construct()
    {
        $this->setEnvironment(config('pagarme.environment'));
        $this->pagarme = new Client($this->environment);
        $this->transactionData = [];
    }

    /**
     * @param \Illuminate\Config\Repository|mixed $environment
     */
    public function setEnvironment($environment): void
    {
        $this->environment = config('pagarme.key.' . $environment . '.api_key');
    }

    /**
     * @return \Illuminate\Config\Repository|mixed
     */
    public function getEnvironment()
    {
        return $this->environment;
    }

    /**
     * Criação do objeto customer dentro do sistema da Pagar.me
     *
     * @param string $id = ID do usuário na base local
     * @param string $name
     * @param string $email
     * @param string $document = Número do CPF contendo somente números
     * @param string $document_type = tipo de documento
     * @param string $mobile = Número de telefone sem o DDI
     * @param string $birthday
     * @return \ArrayObject|null
     */
    public function createCustomer(
        string $id,
        string $name,
        string $email,
        string $document_type,
        string $document,
        string $mobile,
        string $birthday
    ): ?\stdClass
    {

        try {
            $customer = $this->pagarme->customers()->create([
                'external_id' => $id,
                'name' => $name,
                'type' => 'individual',
                'country' => 'br',
                'email' => $email,
                'documents' => [
                    [
                        'type' => $document_type,
                        'number' => $this->clearField($document),
                    ],
                ],
                'phone_numbers' => [
                    '+55' . $this->clearField($mobile),
                ],
                'birthday' => $birthday,
            ]);

            $this->customer = $customer;
            $this->setCustomer();
            return $customer;
        } catch (PagarMeException $ex) {
            $this->error = $ex->getMessage();
            return null;
        }
    }

    /**
     * @param string $id = ID de cliente dentro do sistema da Pagar.me
     * @return \ArrayObject|null
     */
    public function getCustomer(string $id): ?\stdClass
    {
        try {
            $customer = $this->pagarme->customers()->get([
                'id' => $id,
            ]);

            $this->customer = $customer;
            $this->setCustomer();
            return $customer;
        } catch (PagarMeException $ex) {
            $this->error = $ex->getMessage();
            return null;
        }
    }

    public function getCustomerList()
    {
        $customers = $this->pagarme->customers()->getList();
        dd($customers);
    }

    /**
     *
     */
    private function setCustomer(): void
    {
        $this->transactionData += [
            'customer' => [
                'id' => $this->customer->id,
                'external_id' => $this->customer->external_id,
                'name' => $this->customer->name,
                'type' => 'individual',
                'country' => $this->customer->country,
                'document_number' => $this->customer->documents[0]->number,
                'documents' => [
                    [
                        'type' => 'cpf',
                        'number' => $this->customer->documents[0]->number,
                    ],
                ],
                'phone_numbers' => [$this->customer->phone_numbers[0]],
                'email' => $this->customer->email,
            ],
        ];
    }

    /**
     * @param string $holderName
     * @param string $cardNumber
     * @param string $expiration
     * @param string $cvv
     * @param string $idCustomer
     * @return \stdClass|null
     */
    public function createCreditCard(
        string $holderName,
        string $cardNumber,
        string $expiration,
        string $cvv,
        string $idCustomer
    ): ?\stdClass
    {
        try {
            $card = $this->pagarme->cards()->create([
                'holder_name' => strtoupper($holderName),
                'number' => $cardNumber,
                'expiration_date' => $this->clearField($expiration),
                'cvv' => $cvv,
                'customer_id' => $idCustomer,
            ]);

            if ($card->valid !== true) {
                return null;
            }

            $this->card = $card;
            $this->setCreditCard();
            return $card;
        } catch (PagarMeException $ex) {
            $this->error = $ex->getMessage();
            return null;
        }
    }

    /**
     * @param string $cardId
     * @return \stdClass|null
     */
    public function getCreditCard(string $cardId): ?\stdClass
    {
        try {
            $card = $this->pagarme->cards()->get([
                'id' => $cardId,
            ]);

            $this->card = $card;
            $this->setCreditCard();
            return $card;
        } catch (PagarMeException $ex) {
            $this->error = $ex->getMessage();
            return null;
        }
    }

    /**
     *
     */
    private function setCreditCard(): void
    {
        $this->transactionData += [
            'payment_method' => 'credit_card',
            'card_id' => $this->card->id,
        ];
    }

    /**
     * @param string $expirationDate
     * @param string $instruction
     */
    public function billet(string $expirationDate = '3', string $instruction): void
    {
        $this->transactionData += [
            'payment_method' => 'boleto',
            'boleto_expiration_date' => date('Y-m-d', strtotime('+' . $expirationDate . 'days')),
            'boleto_instructions' => substr($instruction, 0, 255),
        ];
    }

    /**
     * @param string $recipientId
     * @param string $amount
     * @param string $percentage
     * @param string $liable
     * @param string $chargeProcessingFee
     * @return bool
     */
    public function split(
        string $recipientId,
        string $amount,
        string $percentage,
        string $liable,
        string $chargeProcessingFee
    ): bool
    {
        if (!isset($this->transactionData['split_rules'])) {
            $this->transactionData['split_rules'] = [];
        }

        if (!empty($amount)) {
            array_push($this->transactionData['split_rules'], [
                'recipient_id' => $recipientId,
                'amount' => $amount,
                'liable' => $liable,
                'charge_processing_fee' => $chargeProcessingFee,
            ]);
            return true;
        } elseif (!empty($percentage)) {
            array_push($this->transactionData['split_rules'], [
                'recipient_id' => $recipientId,
                'percentage' => $percentage,
                'liable' => $liable,
                'charge_processing_fee' => $chargeProcessingFee,
            ]);
            return true;
        } else {
            $this->error = 'Type in split_rules is not defined';
            return false;
        }
    }

    /**
     * @param string $amount
     * @param bool $async
     * @param int $intallments
     * @return \stdClass|null
     */
    public function payRequest(string $amount, bool $async = false, int $intallments = 1): ?\stdClass
    {
        $this->transactionData += [
            'amount' => $amount,
            'async' => $async,
            'postback_url' => env('PAGARME_POSTBACK', 'https://www.webstylus.com.br/api/pagarme/post-back'),
            'installments' => $intallments
        ];

        try {
            $transaction = $this->pagarme->transactions()->create(
                $this->transactionData
            );

            $this->transaction = $transaction;
            return $transaction;
        } catch (PagarMeException $ex) {
            $this->error = $ex->getMessage();
            return null;
        }
    }

    /**
     * @param string $bankCode
     * @param string $agency
     * @param string $agencyDv
     * @param string $account
     * @param string $accountDv
     * @param string $document
     * @param string $legalName
     * @return \stdClass|null
     */
    public function createBank(
        string $bankCode,
        string $agency,
        string $agencyDv,
        string $account,
        string $accountDv,
        string $document,
        string $legalName
    ): ?\stdClass
    {
        try {
            $bankAccount = $this->pagarme->bankAccounts()->create([
                'bank_code' => $bankCode,
                'agencia' => $agency,
                'agencia_dv' => $agencyDv,
                'conta' => $account,
                'conta_dv' => $accountDv,
                'document_number' => $this->clearField($document),
                'legal_name' => $legalName,
            ]);

            $this->bank = $bankAccount;
            return $bankAccount;
        } catch (PagarMeException $ex) {
            $this->error = $ex->getMessage();
            return null;
        }
    }

    /**
     * @param string $id
     * @return \stdClass|null
     */
    public function getBank(string $id): ?\stdClass
    {
        try {
            $bankAccount = $this->pagarme->bankAccounts()->get([
                'id' => $id,
            ]);

            $this->bank = $bankAccount;
            return $bankAccount;
        } catch (PagarMeException $ex) {
            $this->error = $ex->getMessage();
            return null;
        }
    }

    /**
     * @param string $account
     * @param string $transferInterval
     * @param string $enable
     * @param string $transferDay
     * @param string $automaticAnticipationEnabled
     * @param string $anticipatableVolumePercentage
     * @return \stdClass|null
     */
    public function createRecipient(
        string $account,
        string $transferInterval,
        string $enable,
        string $transferDay,
        string $automaticAnticipationEnabled,
        string $anticipatableVolumePercentage
    ): ?\stdClass
    {
        try {
            $recipient = $this->pagarme->recipients()->create([
                'anticipatable_volume_percentage' => $anticipatableVolumePercentage,
                'automatic_anticipation_enabled' => $automaticAnticipationEnabled,
                'bank_account_id' => $account,
                'transfer_day' => $transferDay,
                'transfer_enabled' => $enable,
                'transfer_interval' => $transferInterval,
            ]);

            $this->recipient = $recipient;
            return $recipient;
        } catch (PagarMeException $ex) {
            $this->error = $ex->getMessage();
            return null;
        }
    }

    /**
     * @param string $id
     * @return \stdClass|null
     */
    public function getRecipient(string $id): ?\stdClass
    {
        try {
            $recipient = $this->pagarme->recipients()->get([
                'id' => $id,
            ]);

            $this->recipient = $recipient;
            return $recipient;
        } catch (PagarMeException $ex) {
            $this->error = $ex->getMessage();
            return null;
        }
    }

    /**
     * @param string $id
     * @param string|null $amount
     * @param string|null $bank
     * @return \stdClass|null
     */
    public function refund(string $id, ?string $amount = null, ?string $bank = null): ?\stdClass
    {
        if (!empty($amount)) {
            $data = [
                'id' => $id,
                'amount' => $amount,
                'bank_account_id' => $bank,
            ];
        } else {
            $data = [
                'id' => $id,
                'bank_account_id' => $bank,
            ];
        }
        try {
            $refund = $this->pagarme->transactions()->refund(
                $data
            );

            $this->transaction = $refund;
            return $refund;
        } catch (PagarMeException $ex) {
            $this->error = $ex->getMessage();
            return null;
        }
    }

    /**
     * @param string $amount
     * @param int $days
     * @param string $name
     * @param int $trial_days
     * @return \stdClass|null
     */
    public function createPlan(string $amount, int $days, string $name, int $trial_days = 0): ?\stdClass
    {
        try {
            $plan = $this->pagarme->plans()->create([
                'amount' => $amount,
                'days' => $days,
                'name' => $name,
                'trial_days' => $trial_days
            ]);

            $this->plan = $plan;
            return $plan;
        } catch (PagarMeException $ex) {
            $this->error = $ex->getMessage();
            return null;
        }
    }

    /**
     * @param string $id
     * @return \stdClass|null
     */
    public function getPlan(string $id): ?\stdClass
    {
        try {
            $plan = $this->pagarme->plans()->get([
                'id' => $id,
            ]);

            $this->plan = $plan;
            return $plan;
        } catch (PagarMeException $ex) {
            $this->error = $ex->getMessage();
            return null;
        }
    }

    /**
     * @param string $id
     * @param string $name
     * @param int $trial_days
     * @return \stdClass|null
     */
    public function updatePlan(string $id, string $name, int $trial_days): ?\stdClass
    {
        try {
            $plan = $this->pagarme->plans()->update([
                'id' => $id,
                'name' => $name,
                'trial_days' => $trial_days,
            ]);

            $this->plan = $plan;
            return $plan;
        } catch (PagarMeException $ex) {
            $this->error = $ex->getMessage();
            return null;
        }
    }

    /**
     * @param string $planId
     * @param array $metadata
     * @return \stdClass|null
     */
    public function createSubscription(string $planId, array $metadata = []): ?\stdClass
    {
        $data = array_merge($this->transactionData, [
            'plan_id' => $planId,
            'postback_url' => env('PAGARME_POSTBACK', 'https://www.webstylus.com.br/api/pagarme/post-back'),
            'metadata' => $metadata,
        ]);
        try {
            $subscription = $this->pagarme->subscriptions()->create($data);
            $this->subscription = $subscription;
            return $subscription;
        } catch (PagarMeException $ex) {
            $this->error = $ex->getMessage();
            return null;
        }
    }

    /**
     * @param string $id
     * @return \stdClass|null
     */
    public function getSubscription(string $id): ?\stdClass
    {
        try {
            $subscription = $this->pagarme->subscriptions()->get([
                'id' => $id,
            ]);

            $this->subscription = $subscription;
            return $subscription;
        } catch (PagarMeException $ex) {
            $this->error = $ex->getMessage();
            return null;
        }
    }

    /**
     * @param string $id
     * @return \stdClass|null
     */
    public function cancelSubscription(string $id): ?\stdClass
    {
        try {
            $canceledSubscription = $this->pagarme->subscriptions()->cancel([
                'id' => $id,
            ]);

            $this->subscription = $canceledSubscription;
            return $canceledSubscription;
        } catch (PagarMeException $ex) {
            $this->error = $ex->getMessage();
            return null;
        }
    }

    /**
     * @param string $param
     * @return mixed
     */
    private function clearField(string $param): ?string
    {
        return str_replace(['.', '/', '-', '(', ')', ','], '', $param);
    }
}
