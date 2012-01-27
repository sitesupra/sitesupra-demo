<?

namespace Supra\Payment\Entity\RecurringPayment;

use Supra\Database;
use Supra\Payment\Entity\Abstraction\PaymentEntityParameter;

/**
 * @Entity
 * @Table(indexes={
 * 		@index(name="recurringPaymentIdIdx", columns={"recurringPaymentId"}),
 * 		@index(name="phaseNameIdx", columns={"phaseName"})
 * })
 */
class RecurringPaymentParameter extends PaymentEntityParameter
{
	/**
	 * @return RecurringPayment
	 */
	public function getRecurringPayment()
	{
		return $this->paymentEntity;
	}

	/**
	 * @param RecurringPayment $recurringPayment 
	 */
	public function setRecurringPayment($recurringPayment)
	{
		$this->paymentEntity = $recurringPayment;
	}

}