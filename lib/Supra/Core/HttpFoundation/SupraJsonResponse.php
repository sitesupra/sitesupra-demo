<?php

namespace Supra\Core\HttpFoundation;

use Symfony\Component\HttpFoundation\JsonResponse;

class SupraJsonResponse extends JsonResponse
{
	protected $jsonData = null;
	protected $jsonStatus = 1;
	protected $jsonErrorMessage = null;
	protected $jsonWarningMessage = null;
	protected $jsonPermissions = null;

	public function __construct($data = true, $status = 200, $headers = array())
	{
		$this->jsonData = $data;

		parent::__construct($data, $status, $headers);
	}

	public function setData($data = array())
	{
		$this->jsonData = $data;

		return parent::setData($this->compactJson());
	}

	/**
	 * @param $errorMessage
	 * @return JsonResponse
	 */
	public function setErrorMessage($errorMessage)
	{
		$this->jsonErrorMessage = $errorMessage;

		return parent::setData($this->compactJson());
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
	 * @return JsonResponse
	 */
	public function setPermissions($permissions)
	{
		$this->jsonPermissions = $permissions;

		return parent::setData($this->compactJson());
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
	 * @return JsonResponse
	 */
	public function setStatus($status)
	{
		$this->jsonStatus = $status;

		return parent::setData($this->compactJson());
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
	 * @return JsonResponse
	 */
	public function setWarningMessage($warningMessage)
	{
		$this->jsonWarningMessage = $warningMessage;

		return parent::setData($this->compactJson());
	}

	/**
	 * @return null
	 */
	public function getWarningMessage()
	{
		return $this->jsonWarningMessage;
	}

	protected function compactJson()
	{
		return array(
			'status' => $this->jsonStatus,
			'data' => $this->jsonData,
			'error_message' => $this->jsonErrorMessage,
			'warning_message' => $this->jsonWarningMessage,
			'permissions' => $this->jsonPermissions
		);
	}

}
