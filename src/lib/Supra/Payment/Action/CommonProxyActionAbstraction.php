<?php

namespace Supra\Payment\Action;

abstract class CommonProxyActionAbstraction extends ProxyActionAbstraction
{

	const PHASE_NAME_PROXY_FORM = 'proxy-form';
	const PHASE_NAME_PROXY_REDIRECT = 'proxy-redirect';

	/**
	 * @var boolean
	 */
	protected $formAutosubmit;

	/**
	 * @var string
	 */
	protected $formMethod;

	/**
	 * @var array
	 */
	protected $proxyData;

	/**
	 * @var string
	 */
	protected $redirectUrl;

	/**
	 * @return array
	 */
	abstract protected function getRedirectUrl();

	/**
	 * @return array
	 */
	protected function getPaymentProviderFormElements()
	{
		$formElements = array();

		foreach ($this->proxyData as $name => $value) {

			$input = new HtmlTag('input');

			$input->setAttribute('name', $name);
			$input->setAttribute('value', $value);

			if ($this->autosubmit) {
				$input->setAttribute('type', 'hidden');
			} else {
				$input->setAttribute('type', 'text');
			}

			$formElements[] = $input;
		}

		return $formElements;
	}

	/**
	 * Creates form to be submitted to payment provider.
	 */
	protected function submitFormToPaymentProvider()
	{
		$response = new TwigResponse($this);

		$formElements = $this->getPaymentProviderFormElements();

		$response->assign('formElements', $formElements);

		$redirectUrl = $this->getRedirectUrl();

		$response->assign('action', $redirectUrl);
		$response->assign('method', $this->formMethod);

		$response->assign('autosubmit', $this->formAutosubmit);

		$response->outputTemplate('proxyform.html.twig');

		$response->getOutputString();

		$this->response = $response;

		$this->fireProxyEvent();
	}

	/**
	 * Sends HTTP redirect header to client.
	 */
	protected function redirectToPaymentProvider()
	{
		$redirectUrl = $this->getRedirectUrl();

		$this->response->header('Location', $redirectUrl);
		$this->response->flush();

		$this->fireProxyEvent();
	}

}
