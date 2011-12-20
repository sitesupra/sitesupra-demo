<?php

namespace Supra\AuditLog;

use Supra\ObjectRepository\ObjectRepository;
use Supra\Configuration\ComponentConfiguration;
use Supra\User\Entity\User as UserEntity;
use Supra\Log\LogEvent;

/**
 * Audit log event
 *
 */
class AuditLogEvent extends LogEvent
{

	/**
	 * Component name
	 * @var string
	 */
	public $component;
	
	/**
	 * Action name
	 * @var string
	 */
	public $action;

	/**
	 * User name
	 * @var string
	 */
	public $user;

	/**
	 * Text message
	 *
	 * @var string
	 */
	public $message;

	/**
	 * Log event constructor
	 * @param string $level
	 * @param string $component
	 * @param string $action
	 * @param string $message
	 * @param string $logger
	 * @param string $user
	 * @param array $data
	 */
	public function __construct($level, $component, $action, $message, $logger, $user = null, $data = array())
	{
		$this->level = $level;

		if (is_object($component)) {
			$componentConfig = ObjectRepository::getComponentConfiguration($component);
			if ($componentConfig instanceof ComponentConfiguration
				&& ! empty($componentConfig->title)
			) {
				$this->component = $componentConfig->title;
			} else {
				$this->component = get_class($component);
			}
		} else {		
			$this->component = (string) $component;
		}
		
		$this->action = (string) $action;
		$this->message = (string) $message;

		if ($user instanceof UserEntity) {
			$this->user = $user->getLogin();
		} else if ( ! empty($user)) {
			$this->user = (string) $user;
		} else {
			$this->user = '';
		}
		
		$this->data = $data;
		$this->timestamp = time();
		$this->microtime = (string) microtime(true);
		$this->logger = $logger;
	}

	/**
	 * Object cast to array
	 * @return array
	 */
	public function toArray()
	{
		return array(
			'timestamp' => $this->timestamp,
			'microtime' => $this->microtime,
//			TODO: decide on data field
//			'subject' => $this->getSubject(),
			'subject' => $this->message,
			'message' => $this->message,
			'level' => $this->level,
			'levelPriority' => $this->getLevelPriority(),
			'component' => $this->component,
			'action' => $this->action,
			'user' => $this->user,
			'logger' => $this->logger,
			'thread' => getmypid(),
		);
	}
}
