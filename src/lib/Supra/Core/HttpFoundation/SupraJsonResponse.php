<?php

namespace Supra\Core\HttpFoundation;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class SupraJsonResponse extends JsonResponse
{
	//we use JsonResponse's data to sotre actual data
	protected $jsonStatus = 1;
	protected $jsonErrorMessage = null;
	protected $jsonWarningMessage = null;
	protected $jsonPermissions = null;

	public function __construct($data = true, $status = 200, $headers = array())
	{
		parent::__construct($data, $status, $headers);
	}

	public static function create($data = true, $status = 200, $headers = array())
	{
		return parent::create($data, $status, $headers);
	}

	public function sendContent()
	{
		echo $this->getContent();

		return $this;
	}

	public function getContent()
	{
		return json_encode(array(
				'status' => $this->jsonStatus,
				'data' => $this->data,
				'error_message' => $this->jsonErrorMessage,
				'warning_message' => $this->jsonWarningMessage,
				'permissions' => $this->jsonPermissions
			)
		);
	}

	/**
	 * @param $errorMessage
	 */
	public function setErrorMessage($errorMessage)
	{
		$this->jsonErrorMessage = $errorMessage;
	}

	/**
	 * @return null
	 */
	public function getErrorMessage()
	{
		return $this->jsonErrorMessage;
	}

	/**
	 * @param null $permissions
	 */
	public function setPermissions($permissions)
	{
		$this->jsonPermissions = $permissions;
	}

	/**
	 * @return null
	 */
	public function getPermissions()
	{
		return $this->jsonPermissions;
	}

	/**
	 * @param int $status
	 */
	public function setStatus($status)
	{
		$this->jsonStatus = $status;
	}

	/**
	 * @return int
	 */
	public function getStatus()
	{
		return $this->jsonStatus;
	}

	/**
	 * @param null $warningMessage
	 */
	public function setWarningMessage($warningMessage)
	{
		$this->jsonWarningMessage = $warningMessage;
	}

	/**
	 * @return null
	 */
	public function getWarningMessage()
	{
		return $this->jsonWarningMessage;
	}

}
