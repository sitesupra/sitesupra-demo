<?php

namespace Supra\Form;

use Supra\Controller\Pages\BlockController;
use Symfony\Component\Form;
use Symfony\Component\HttpFoundation\Request;

abstract class FormBlockController extends BlockController
{

	/**
	 * @var Form\Form
	 */
	protected $form;

	/**
	 * @return Form\Form
	 */
	public function getForm()
	{
		return $this->form;
	}

	protected function doExecute()
	{
		$request = new Request($_GET, $_POST, array(), $_COOKIE, $_FILES, $_SERVER);

		$conf = $this->getConfiguration();
		$this->form = $conf->form;

		if ($request->isMethod('POST')) {

			$this->form->bindRequest($request);

			if ($this->form->isValid()
					&& $this->validate($this->form->getClientData())) {
				
				$this->success();
				return;
			} else {
				$this->failure();
				return;
			}
		}

		$this->render();
	}

	abstract protected function render();

	abstract protected function success();

	abstract protected function failure();

	/**
	 * Custom validation
	 * @return boolean 
	 */
	protected function validate(array $data = array())
	{
		return true;
	}

}