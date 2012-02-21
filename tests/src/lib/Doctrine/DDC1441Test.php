<?php

namespace Supra\Tests\Doctrine;

use Doctrine\ORM\UnitOfWork;

class DDC1441Test extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();
		
		$this->_em = \Supra\ObjectRepository\ObjectRepository::getEntityManager($this);
		$this->_schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->_em);
		
        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1441File'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1441Picture'),
            ));
        } catch(\Exception $ignored) {}
    }

    public function testFailingCase()
    {
		if (version_compare(\Doctrine\ORM\Version::VERSION, '2.2.1', 'lt')) {
			self::markTestSkipped("Is not working in Doctrine ORM " . \Doctrine\ORM\Version::VERSION);
		}
		
		// Remove all data so we can count in the end
		$this->_em->createQuery("DELETE FROM " . __NAMESPACE__ . '\DDC1441Picture')->execute();
		$this->_em->createQuery("DELETE FROM " . __NAMESPACE__ . '\DDC1441File')->execute();
		
		// Persist new objects
        $file = new DDC1441File;
        $picture = new DDC1441Picture;
        $picture->setFile($file);
		
        $this->_em->persist($picture);
		$this->_em->flush();
		$this->_em->clear();

		// Load picture with unloaded file proxy object
        $picture = $this->_em->find(__NAMESPACE__ . '\DDC1441Picture', $picture->getPictureId());
		
		// Unset the metadata for proxy object, partially simulates clean environment
		// e.g. when serialized $pic is unwrapped in another request
		$file = $picture->getFile();
		$proxyClassName = get_class($file);
		$this->_em->getMetadataFactory()
				->setMetadataFor($proxyClassName, null);
		
		$this->_em->merge($picture);
    }
}

/**
 * @Entity
 */
class DDC1441Picture
{
    /**
     * @Column(name="picture_id", type="integer")
     * @Id @GeneratedValue
     */
    private $pictureId;

    /**
     * @ManyToOne(targetEntity="DDC1441File", cascade={"persist", "remove", "merge"})
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
class DDC1441File
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