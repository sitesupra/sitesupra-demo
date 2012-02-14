<?

namespace Supra\User\Entity;

use Supra\Database;
use Supra\Authorization\AuthorizedEntityInterface;
use Supra\Authorization\AuthorizationProvider;
use Supra\Database\Doctrine\Listener\Timestampable;
use Supra\FileStorage\Entity\Image as ImageFile;
use \DateTime;

/**
 * @Entity
 * @Table(indexes={
 * 		@index(name="userId_idx", columns={"userId"}),
 * 		@index(name="isRead_idx", columns={"isRead"}),
 * 		@index(name="isVisible_idx", columns={"isVisible"})
 * })
 * @HasLifecycleCallbacks
 */
class UserNotification extends Database\Entity
{

	/**
	 * @Column(type="string", nullable=false)
	 * @var string
	 */
	protected $userId;

	/**
	 * @Column(type="boolean", nullable=false)
	 * @var boolean
	 */
	protected $isRead;

	/**
	 * @Column(type="boolean", nullable=false)
	 * @var boolean
	 */
	protected $isVisible;

	/**
	 * @Column(type="integer", nullable=false)
	 * @var integer
	 */
	protected $type;

	/**
	 * @ManyToOne(targetEntity="Supra\FileStorage\Entity\Image")
	 * @JoinColumn(name="image_file_id", referencedColumnName="id")
	 * @var ImageFile
	 */
	protected $image;

	/**
	 * @Column(type="string", nullable=false)
	 * @var string
	 */
	protected $message;

	/**
	 * @Column(type="string", nullable=false)
	 * @var string
	 */
	protected $link;

	/**
	 * @Column(type="string", nullable=false)
	 * @var string
	 */
	protected $sender;

	/**
	 * @Column(type="datetime", nullable="false")
	 * @var DateTime
	 */
	protected $creationTime;

	/**
	 * @Column(type="datetime", nullable="false")
	 * @var DateTime
	 */
	protected $modificationTime;

	/**
	 * @Column(type="datetime", nullable="false")
	 * @var DateTime
	 */
	protected $readTime;

	/**
	 * @return string
	 */
	public function getUserId()
	{
		return $this->userId;
	}

	/**
	 * @param string $userId 
	 */
	public function setUserId($userId)
	{
		$this->userId = $userId;
	}

	/**
	 * @return string
	 */
	public function getSender()
	{
		return $this->sender;
	}

	/**
	 * @param string $sender 
	 */
	public function setSender($sender)
	{
		$this->sender = $sender;
	}

	/**
	 * @return boolean
	 */
	public function getIsVisible()
	{
		return $this->isVisible;
	}

	/**
	 * @param boolean $isVisible 
	 */
	public function setIsVisible($isVisible)
	{
		$this->isVisible = $isVisible;
	}

	public function getIsRead()
	{
		return $this->isRead;
	}

	public function setIsRead($isRead)
	{
		if ( ! $this->isRead) {
			$this->readTime = new DateTime('now');
		}

		$this->isRead = $isRead;
	}

	public function getLink()
	{
		return $this->link;
	}

	public function setLink($link)
	{
		$this->link = $link;
	}

	/**
	 * @return DateTime
	 */
	public function getCreationTime()
	{
		return $this->creationTime;
	}

	/**
	 * @return DateTime
	 */
	public function getModificationTime()
	{
		return $this->modificationTime;
	}

	/**

	 * @return DateTime
	 */
	public function getReadTime()
	{
		return $this->readTime;
	}

	public function setReadTime($readTime)
	{
		$this->readTime = $readTime;
	}

	public function getType()
	{
		return $this->type;
	}

	public function setType($type)
	{
		$this->type = $type;
	}

	public function getImage()
	{
		return $this->image;
	}

	public function setImage($image)
	{
		$this->image = $image;
	}

	public function getMessage()
	{
		return $this->message;
	}

	public function setMessage($message)
	{
		$this->message = $message;
	}

	/**
	 * @preUpdate
	 * @prePersist
	 */
	public function autoModificationTime()
	{
		$this->modificationTime = new DateTime('now');
	}

	/**
	 * @prePersist
	 */
	public function autoCreationTime()
	{
		$this->creationTime = new DateTime('now');
	}

}
