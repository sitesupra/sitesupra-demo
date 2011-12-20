<?php

namespace Supra\Tests\Doctrine;

use Doctrine\ORM\UnitOfWork;

class DDC408Test extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();
		
		$this->_em = \Supra\ObjectRepository\ObjectRepository::getEntityManager($this);
		$this->_schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->_em);
		
        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC408Picture'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC408PictureSize'),
            ));
        } catch(\Exception $ignored) {
			//throw $ignored;
		}
    }

    public function testFailingCase()
    {
		// Remove all data so we can count in the end
		$this->_em->createQuery("DELETE FROM " . __NAMESPACE__ . '\DDC408PictureSize')->execute();
		$this->_em->createQuery("DELETE FROM " . __NAMESPACE__ . '\DDC408Picture')->execute();
		
        $pic = new DDC408Picture;
        $size = new DDC408PictureSize;

        
		$pic->setId(1);
		$size->setId(1);
		
		$size->setPicture($pic);
		
        $em = $this->_em;
		/* @var $em \Doctrine\ORM\EntityManager */
        $em->persist($pic);
        $em->persist($size);
        $em->flush();
        $em->clear();
		
		$repo = $em->getRepository(__NAMESPACE__ . '\DDC408PictureSize');
		
		// Works
		$results = $repo->findBy(array('picture' => 1));
		self::assertEquals(1, count($results));
		
		// Does not work
		$results = $repo->findBy(array('picture' => array(1)));
		self::assertEquals(1, count($results));
    }
}

/**
 * @Entity
 */
class DDC408Picture
{
    /**
     * @Column(type="integer")
     * @Id
     */
    private $id;

	public function getId()
	{
		return $this->id;
	}

	public function setId($id)
	{
		$this->id = $id;
	}
}

/**
 * @Entity
 */
class DDC408PictureSize
{
    /**
     * @Column(type="integer")
     * @Id
     */
    private $id;
	
	/**
	 * @ManyToOne(targetEntity="DDC408Picture", inversedBy="sizes")
	 */
	private $picture;
	
	public function getId()
	{
		return $this->id;
	}

	public function setId($id)
	{
		$this->id = $id;
	}

	public function getPicture()
	{
		return $this->picture;
	}

	public function setPicture($picture)
	{
		$this->picture = $picture;
	}

}
