<?php

namespace Supra\Console\Output;

interface CommandOutputWithData
{

	public function setData($data);

	public function getData();
}
