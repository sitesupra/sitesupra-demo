<?php

namespace Supra\Tests\Doctrine;

use Doctrine\ORM\UnitOfWork;

class DDC1392Test extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();
		
		$this->_em = \Supra\ObjectRepository\ObjectRepository::getEntityManager($this);
		$this->_schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->_em);
		
        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1392File'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1392Picture'),
            ));
        } catch(\Exception $ignored) {}
    }

    public function testFailingCase()
    {
		// Remove all data so we can count in the end
		$this->_em->createQuery("DELETE FROM " . __NAMESPACE__ . '\DDC1392Picture')->execute();
		$this->_em->createQuery("DELETE FROM " . __NAMESPACE__ . '\DDC1392File')->execute();
		
        $file = new DDC1392File;

        $picture = new DDC1392Picture;
        $picture->setFile($file);

        $em = $this->_em;
        $em->persist($picture);
        $em->flush();
        $em->clear();

        $fileId = $file->getFileId();
        $pictureId = $picture->getPictureId();
        
        $this->assertTrue($fileId > 0);

        $picture = $em->find(__NAMESPACE__ . '\DDC1392Picture', $pictureId);
        $this->assertEquals(UnitOfWork::STATE_MANAGED, $em->getUnitOfWork()->getEntityState($picture->getFile()), "Lazy Proxy should be marked MANAGED.");
		
		$file = $picture->getFile();
		
		// With this activated there will be no problem
//		$file->__load();
		
		$picture->setFile(null);
		
		$em->clear();

        $em->merge($file);
		
        $em->flush();
		
		$q = $this->_em->createQuery("SELECT COUNT(e) FROM " . __NAMESPACE__ . '\DDC1392File e');
		$result = $q->getSingleScalarResult();
		
		self::assertEquals(1, $result, "Will be fixed in Doctrine ORM 2.1.2 release");
    }
}

/**
 * @Entity
 */
class DDC1392Picture
{
    /**
     * @Column(name="picture_id", type="integer")
     * @Id @GeneratedValue
     */
    private $pictureId;

    /**
     * @ManyToOne(targetEntity="DDC1392File", cascade={"persist", "remove"})
     * @JoinColumns({
     *   @JoinColumn(name="file_id", referencedColumnName="file_id")
     * })
     */
    private $file;

    /**
     * Get pictureId
     */
    public function getPictureId()
    {
        return $this->pictureId;
    }

    /**
     * Set file
     */
    public function setFile($value = null)
    {
        $this->file = $value;
    }

    /**
     * Get file
     */
    public function getFile()
    {
        return $this->file;
    }
}

/**
 * @Entity
 */
class DDC1392File
{
    /**
     * @Column(name="file_id", type="integer")
     * @Id
     * @GeneratedValue(strategy="AUTO")
     */
    public $fileId;

    /**
     * Get fileId
     */
    public function getFileId()
    {
        return $this->fileId;
    }
}