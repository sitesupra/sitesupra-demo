<?php

namespace Supra\Package\Framework\Controller;

use Supra\Core\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class ComboController extends Controller
{
	public function comboAction(Request $request)
	{
		die('xxx');
		var_dump($request->query->all());
	}

}
