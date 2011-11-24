<?php

namespace Supra\Tests\Doctrine;

use Doctrine\ORM\UnitOfWork;

class DDC1509Test extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();
		
		$this->_em = \Supra\ObjectRepository\ObjectRepository::getEntityManager($this);
		$this->_schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->_em);
		
        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1509AbstractFile'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1509File'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1509Picture'),
            ));
        } catch(\Exception $ignored) {}
    }

    public function testFailingCase()
    {
		if (version_compare(\Doctrine\ORM\Version::VERSION, '2.1.4', 'lt')) {
			self::markTestSkipped("Is not working in Doctrine ORM 2.1.3");
		}
		
		// Remove all data so we can count in the end
		$this->_em->createQuery("DELETE FROM " . __NAMESPACE__ . '\DDC1509Picture')->execute();
		$this->_em->createQuery("DELETE FROM " . __NAMESPACE__ . '\DDC1509File')->execute();
		$this->_em->createQuery("DELETE FROM " . __NAMESPACE__ . '\DDC1509AbstractFile')->execute();
		
        $file = new DDC1509File;
        $thumbnail = new DDC1509File;

        $picture = new DDC1509Picture;
        $picture->setFile($file);
        $picture->setThumbnail($thumbnail);
		

		/* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->_em;
        $em->persist($picture);
        $em->flush();
        $em->clear();
		
		$id = $picture->getPictureId();

        $pic = $em->merge($picture);
		/* @var $pic DDC1509Picture */
		
		self::assertNotNull($pic->getThumbnail());
		self::assertNotNull($pic->getFile());
    }
}

/**
 * @Entity
 */
class DDC1509Picture
{
    /**
     * @Column(type="integer")
     * @Id
	 * @GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ManyToOne(targetEntity="DDC1509AbstractFile", cascade={"persist", "remove"})
     */
    private $thumbnail;
	
    /**
     * @ManyToOne(targetEntity="DDC1509AbstractFile", cascade={"persist", "remove"})
     */
    private $file;

    /**
     * Get pictureId
     */
    public function getPictureId()
    {
        return $this->id;
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
	
	public function getThumbnail()
	{
		return $this->thumbnail;
	}

	public function setThumbnail($thumbnail)
	{
		$this->thumbnail = $thumbnail;
	}
}

/**
 * @Entity
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({"file" = "DDC1509File"})
 */
class DDC1509AbstractFile
{
    /**
     * @Column(type="integer")
     * @Id
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * Get fileId
     */
    public function getFileId()
    {
        return $this->id;
    }
}

/**
 * @Entity
 */
class DDC1509File extends DDC1509AbstractFile
{
    
}