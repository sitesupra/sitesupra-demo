<?php

namespace Supra\Tests\Doctrine;

use Doctrine\ORM\UnitOfWork;

class DDC1757Test extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();
		
		$this->_em = \Supra\ObjectRepository\ObjectRepository::getEntityManager($this);
		$this->_schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->_em);
		
        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1757A'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1757B'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1757C'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1757D'),
            ));
        } catch(\Exception $ignored) {}
    }

    public function testFailingCase()
    {
//		if (version_compare(\Doctrine\ORM\Version::VERSION, '2.1.4', 'lt')) {
//			self::markTestSkipped("Is not working in Doctrine ORM 2.1.3");
//		}
		
		$qb = $this->_em->createQueryBuilder();
		/* @var $qb \Doctrine\ORM\QueryBuilder */
		
		$qb->select('_a')
				->from(__NAMESPACE__ . '\DDC1757A', '_a')
				->from(__NAMESPACE__ . '\DDC1757B', '_b')
				->join('_b.c', '_c')
				->join('_c.d', '_d');
		
		$q = $qb->getQuery();
		$dql = $q->getDQL();
		$q->getResult();
    }
}

/**
 * @Entity
 */
class DDC1757A
{
    /**
     * @Column(type="integer")
     * @Id
	 * @GeneratedValue(strategy="AUTO")
     */
    private $id;
}

/**
 * @Entity
 */
class DDC1757B
{
    /**
     * @Column(type="integer")
     * @Id
	 * @GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @OneToOne(targetEntity="DDC1757C")
     */
    private $c;
}

/**
 * @Entity
 */
class DDC1757C
{
    /**
     * @Column(type="integer")
     * @Id
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

     /**
     * @OneToOne(targetEntity="DDC1757D")
     */
    private $d;
}

/**
 * @Entity
 */
class DDC1757D
{
    /**
     * @Column(type="integer")
     * @Id
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;
}