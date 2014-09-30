<?php

namespace Supra\Package\Cms\Entity;

use Supra\Database\Entity;

/**
 * @Entity
 * @Table(uniqueConstraints={@UniqueConstraint(name="unique_idx", columns={"blockId"})}))
 */
class BlockRelation extends Entity
{
    /**
     * @Column(type="supraId20", nullable=false)
     * @var string
     */
    protected $groupId;

    /**
     * @Column(type="supraId20", nullable=false)
     * @var string
     */
    protected $blockId;

    /**
     * @param string $blockId
     * @param string $groupId
     */
    public function __construct($blockId, $groupId = null)
    {
        if (is_null($groupId)) {
            $groupId = $blockId;
        }

        $this->groupId = $groupId;
        $this->blockId = $blockId;

        parent::__construct();
    }

    /**
     * @param string $blockId
     */
    public function setBlockId($blockId)
    {
        $this->blockId = $blockId;
    }

    /**
     * @return string
     */
    public function getBlockId()
    {
        return $this->blockId;
    }

    /**
     * @param string $groupId
     */
    public function setGroupId($groupId)
    {
        $this->groupId = $groupId;
    }

    /**
     * @return string
     */
    public function getGroupId()
    {
        return $this->groupId;
    }
}
