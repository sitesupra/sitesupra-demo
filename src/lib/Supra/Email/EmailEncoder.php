<?php

namespace Supra\Email;

class EmailEncoder
{

	public function encode($email)
	{
		return str_rot13($email);
	}
}