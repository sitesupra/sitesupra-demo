<?php

namespace Supra\Log;

use Supra\ObjectRepository\ObjectRepository;
use Supra\Configuration\ComponentConfiguration;
use Supra\User\Entity\User as UserEntity;

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
	 * Log event constructor
	 * @param array $data
	 * @param string $level
	 * @param string $component
	 * @param string $action
	 * @param string $logger
	 */
	public function __construct($data, $level, $component, $action, $user, $logger)
	{
		$this->data = $data;
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
		
		if ($user instanceof UserEntity) {
			$this->user = $user->getLogin();
		} else {
			$this->user = (string) $user;
		}
		
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
			'subject' => $this->getSubject(),
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
