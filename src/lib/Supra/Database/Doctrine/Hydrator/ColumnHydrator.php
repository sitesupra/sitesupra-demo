<?php

namespace Supra\Database\Doctrine\Hydrator;

use Doctrine\ORM\Internal\Hydration\AbstractHydrator;

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
}
