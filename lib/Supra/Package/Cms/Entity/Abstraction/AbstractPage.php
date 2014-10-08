<?php

namespace Supra\Package\Cms\Entity\Abstraction;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Supra\Package\Cms\Entity\BlockProperty;
use Supra\NestedSet;

/**
 * Page abstraction
 * @Entity(repositoryClass="Supra\Package\Cms\Repository\PageRepository")
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({
 * 		"template"		= "Supra\Package\Cms\Entity\Template",
 * 		"page"			= "Supra\Package\Cms\Entity\Page",
 * 		"application"	= "Supra\Package\Cms\Entity\ApplicationPage",
 * 		"group"			= "Supra\Package\Cms\Entity\GroupPage"
 * })
 * @Table(indexes={
 * 		@index(name="page_abstraction_lft_idx", columns={"lft"}),
 * 		@index(name="page_abstraction_rgt_idx", columns={"rgt"}),
 * 		@index(name="page_abstraction_lvl_idx", columns={"lvl"})
 * })
 * @method int getNumberChildren()
 * @method AbstractPage addChild(AbstractPage $child)
 * @method void delete()
 * @method boolean hasNextSibling()
 * @method boolean hasPrevSibling()
 * @method int getNumberDescendants()
 * @method boolean hasParent()
 * @method AbstractPage getParent()
 * @method string getPath(string $separator, boolean $includeNode)
 * @method array getAncestors(int $levelLimit, boolean $includeNode)
 * @method array getDescendants(int $levelLimit, boolean $includeNode)
 * @method AbstractPage getFirstChild()
 * @method AbstractPage getLastChild()
 * @method AbstractPage getNextSibling()
 * @method AbstractPage getPrevSibling()
 * @method array getChildren()
 * @method array getSiblings(boolean $includeNode)
 * @method boolean hasChildren()
 * @method AbstractPage moveAsNextSiblingOf(AbstractPage $afterNode)
 * @method AbstractPage moveAsPrevSiblingOf(AbstractPage $beforeNode)
 * @method AbstractPage moveAsFirstChildOf(AbstractPage $parentNode)
 * @method AbstractPage moveAsLastChildOf(AbstractPage $parentNode)
 * @method boolean isLeaf()
 * @method boolean isAncestorOf(AbstractPage $node)
 * @method boolean isDescendantOf(AbstractPage $node)
 * @method boolean isEqualTo(AbstractPage $node)
 */
abstract class AbstractPage extends Entity implements NestedSet\Node\EntityNodeInterface
{

    /**
     * Filled by NestedSetListener
     * @var NestedSet\Node\DoctrineNode
     */
    protected $nestedSetNode;

    /**
     * @OneToMany(targetEntity="Supra\Package\Cms\Entity\Abstraction\Localization", mappedBy="master", cascade={"persist", "remove"}, indexBy="locale")
     * @var Collection
     */
    protected $localizations;

    /**
     * @Column(type="integer", name="lft", nullable=true)
     * @var integer
     */
    protected $left;

    /**
     * @Column(type="integer", name="rgt", nullable=true)
     * @var integer
     */
    protected $right;

    /**
     * @Column(type="integer", name="lvl", nullable=true)
     * @var integer
     */
    protected $level;

    /**
     * @Column(type="boolean", name="global", nullable=true)
     * @var boolean
     */
    protected $global = true;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->localizations = new ArrayCollection();
    }

    /**
     * Don't serialize nested set node
     * @return array
     */
    public function __sleep()
    {
        $properties = get_object_vars($this);
        unset($properties['nestedSetNode']);
        $properties = array_keys($properties);

        return $properties;
    }

    /**
     * @return Collection
     */
    public function getLocalizations()
    {
        return $this->localizations;
    }

    /**
     * Get data item by locale
     * @param string $locale
     * @return Localization
     */
    public function getLocalization($locale)
    {
        $dataCollection = $this->getLocalizations();
        $data = $dataCollection->get($locale);

        return $data;
    }

    /**
     * @param string $locale
     * @param Localization $data
     */
    public function setLocalization(Localization $data)
    {
        if ($this->lock('localizations')) {
            $this->matchDiscriminator($data);
            if ($this->addUnique($this->localizations, $data, 'locale')) {
                $data->setMaster($this);
            }
            $this->unlock('localizations');
        }
    }

    /**
     * Get left value
     * @return int
     */
    public function getLeftValue()
    {
        return $this->left;
    }

    /**
     * Get right value
     * @return int
     */
    public function getRightValue()
    {
        return $this->right;
    }

    /**
     * Get depth level
     * @return int
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * Set left value
     * @param int $left
     * @return AbstractPage
     */
    public function setLeftValue($left)
    {
        $this->left = $left;
        if (isset($this->nestedSetNode)) {
            $this->nestedSetNode->setLeftValue($left);
        }
        return $this;
    }

    protected function getAuthorizationAncestorsDirect()
    {
        return $this->getAncestors(0, false);
    }

    /**
     * Set right value
     * @param int $right
     * @return AbstractPage
     */
    public function setRightValue($right)
    {
        $this->right = $right;
        if (isset($this->nestedSetNode)) {
            $this->nestedSetNode->setRightValue($right);
        }
        return $this;
    }

    /**
     * Set depth level
     * @param int $level
     * @return AbstractPage
     */
    public function setLevel($level)
    {
        $this->level = $level;

        if (isset($this->nestedSetNode)) {
            $this->nestedSetNode->setLevel($level);
        }
        return $this;
    }

    /**
     * Move left value by the difference
     * @param int $diff
     * @return AbstractPage
     */
    public function moveLeftValue($diff)
    {
        $this->left += $diff;
        if (isset($this->nestedSetNode)) {
            $this->nestedSetNode->moveLeftValue($diff);
        }
        return $this;
    }

    /**
     * Move right value by the difference
     * @param int $diff
     * @return AbstractPage
     */
    public function moveRightValue($diff)
    {
        $this->right += $diff;
        if (isset($this->nestedSetNode)) {
            $this->nestedSetNode->moveRightValue($diff);
        }
        return $this;
    }

    /**
     * Move depth level by the difference
     * @param int $diff
     * @return AbstractPage
     */
    public function moveLevel($diff)
    {
        $this->level += $diff;

        if (isset($this->nestedSetNode)) {
            $this->nestedSetNode->moveLevel($diff);
        }
        return $this;
    }

    /**
     * Nested node title
     * @return string
     */
    public function getNodeTitle()
    {
        return $this->__toString();
    }

    /**
     * Try the unknown method against the nested set node
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        $node = $this->nestedSetNode;
        if (is_null($this->nestedSetNode)) {
            throw new NestedSet\Exception\BadMethodCall("Method $method does not exist for class " . __CLASS__ . " and it's node object is not initialized.");
        }

        if ( ! method_exists($node, $method)) {
            throw new NestedSet\Exception\BadMethodCall("Method $method does not exist for class " . __CLASS__ . " and it's node object.");
        }
        $callable = array($node, $method);
        $result = call_user_func_array($callable, $arguments);

        // Compare the result with $node and return $this on match to keep method chaining
        if ($result === $node) {
            $result = $this;
        }

        return $result;
    }

    /**
     * Free the node unsetting the pointers to other objects.
     * MUST clear entity manager after doing this!
     */
    public function free()
    {
        if ( ! is_null($this->nestedSetNode)) {
            $this->nestedSetNode->free($this);
            $this->nestedSetNode = null;
        }
    }

    public function isBlockPropertyEditable(BlockProperty $blockProperty)
    {
        $page = $blockProperty->getLocalization()
                ->getMaster();

        $editable = $page->equals($this);

        return $editable;
    }

    /**
     * @return NestedSet\Node\DoctrineNode
     */
    public function getNestedSetNode()
    {
        return $this->nestedSetNode;
    }

    /**
     * {@inheritdoc}
     * @param NestedSet\Node\DoctrineNode $nestedSetNode
     */
    public function setNestedSetNode(NestedSet\Node\DoctrineNode $nestedSetNode)
    {
        $this->nestedSetNode = $nestedSetNode;
    }

    /**
     * Set global
     * @param boolean $global 
     */
    public function setGlobal($global)
    {
        $this->global = (bool) $global;
    }

    /**
     * Get global
     * @return boolean
     */
    public function getGlobal()
    {
        return $this->global;
    }

    /**
	 * Is page global or not.
	 *
     * @return boolean
     */
    public function isGlobal()
    {
		return $this->getLevel() !== 0
				? ($this->global === true)
				: true;
    }

    /**
     * Is local (not global)
     * @return boolean
     */
    public function isLocal()
    {
        return ! $this->global;
    }

    /**
     * Checks, weither page is root (level == 0) or not 
     * @return boolean
     */
    public function isRoot()
    {
        $isRoot = ($this->getLevel() == 0);
        return $isRoot;
    }

    /**
     * Need to unset the nested set node after clone
     */
    public function __clone()
    {
        if ( ! empty($this->id)) {
            $this->nestedSetNode = null;
			$this->localizations = new ArrayCollection();
            parent::__clone();
        }
    }
}
