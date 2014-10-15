<?php

namespace Supra\Core\Doctrine\Hydrator;

use Doctrine\ORM\Internal\Hydration\AbstractHydrator;
use PDO;

/**
 * Hydrator used for one column array fetching
 */
class ColumnHydrator extends AbstractHydrator
{
	const HYDRATOR_ID = 'ColumnHydrator';
	
	/**
	 * @override
	 * @return array
	 */
    protected function _hydrateAll()
    {
        return $this->_stmt->fetchAll(\PDO::FETCH_COLUMN);
    }
	
	protected function hydrateAllData()
	{
		 return $this->_hydrateAll();
	}
}
