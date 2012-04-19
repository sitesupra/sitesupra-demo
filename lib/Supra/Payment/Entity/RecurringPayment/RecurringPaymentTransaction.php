<?php

namespace Supra\Payment\Entity\RecurringPayment;

use Supra\Payment\Entity\Abstraction\PaymentEntity;
use Supra\Payment\Entity\RecurringPayment\RecurringPaymentTransactionParameter;
use Supra\Payment\Transaction\TransactionStatus;

/**
 * @Entity
 * @HasLifecycleCallbacks
 */
class RecurringPaymentTransaction extends PaymentEntity
{

    /**
     * @ManyToOne(targetEntity="RecurringPayment", inversedBy="transactions")
     * @JoinColumn(name="paymentEntityId", referencedColumnName="id")
     */
    protected $recurringPayment;

    /**
     * @Column(type="string", nullable=false)
     * @var float
     */
    protected $amount;

    /**
     * @Column(type="string", nullable=false)
     * @var string
     */
    protected $currencyId;

    /**
     * @Column(type="string", nullable=false)
     * @var string
     */
    protected $description;

    /**
     * @OneToMany(targetEntity="TransactionParameter", mappedBy="transaction")
     * @var ArrayCollection
     */
    protected $parameters;

    public function getAmount()
    {
        return $this->amount;
    }

    public function setAmount($amount)
    {
        $this->amount = $amount;
    }

    public function getCurrencyId()
    {
        return $this->currencyId;
    }

    public function setCurrencyId($currencyId)
    {
        $this->currencyId = $currencyId;
    }

    public function getParameters()
    {
        return $this->parameters;
    }

    public function setParameters($parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * @return RecurringPayment
     */
    public function getRecurringPayment()
    {
        return $this->recurringPayment;
    }

    /**
     * @param RecurringPayment $recurringPayment 
     */
    public function setRecurringPayment(RecurringPayment $recurringPayment)
    {
        $this->recurringPayment = $recurringPayment;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description 
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return RecurringPaymentTransactionParameter 
     */
    public function createParameter()
    {
        $parameter = new RecurringPaymentTransactionParameter();
        $parameter->setRecurringPaymentTransaction($this);

        return $parameter;
    }

}
