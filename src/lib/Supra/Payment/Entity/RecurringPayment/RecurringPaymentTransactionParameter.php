<?

namespace Supra\Payment\Entity\RecurringPayment;

use Supra\Database;
use Supra\Payment\Entity\Abstraction\PaymentEntityParameter;

/**
 * @Entity
 * @Table(indexes={
 * 		@index(name="phaseNameIdx", columns={"phaseName"})
 * })
 */
class RecurringPaymentTransactionParameter extends PaymentEntityParameter
{
	/**
	 * @return RecurringPaymentTransaction
	 */
	public function getRecurringPaymentTransaction()
	{
		return $this->paymentEntity;
	}

	/**
	 * @param RecurringPaymentTransaction $recurringPaymentTransaction 
	 */
	public function setRecurringPaymentTransaction(RecurringPaymentTransaction $recurringPaymentTransaction)
	{
		$this->paymentEntity = $recurringPaymentTransaction;
	}

}