<?php

namespace Supra\Tests\Doctrine;

use Doctrine\ORM\UnitOfWork;

class DDC1454Test extends \PHPUnit_Framework_TestCase
{
	protected function setUp()
    {
        parent::setUp();
		
		$this->_em = \Supra\ObjectRepository\ObjectRepository::getEntityManager($this);
		$this->_schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->_em);
		
        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1454File'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1454Picture'),
            ));
        } catch(\Exception $ignored) {}
    }

    public function testFailingCase()
    {
		if ( ! version_compare(\Doctrine\ORM\Version::VERSION, '2.1.2', '>')) {
			self::markTestSkipped("This is a known bug in ORM 2.1.2");
		}
		
		$pic = new DDC1454Picture();
		$this->_em->getUnitOfWork()->getEntityState($pic);
	}
}

/**
 * @Entity
 */
class DDC1454Picture extends DDC1454File
{
    
}

/**
 * @Entity
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({"picture" = "DDC1454Picture"})
 */
class DDC1454File
{
    /**
     * @Column(name="file_id", type="integer")
     * @Id
     */
    public $fileId;

	public function __construct()
	{
		$this->fileId = rand();
	}
	
    /**
     * Get fileId
     */
    public function getFileId()
    {
        return $this->fileId;
    }
}