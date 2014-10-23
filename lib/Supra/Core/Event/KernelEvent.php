<?php

namespace Supra\Core\Event;

class KernelEvent
{
	const REQUEST = 'kernel.request';
	const RESPONSE = 'kernel.response';
	const ERROR404 = 'kernel.404';
	const CONTROLLER_START = 'kernel.controller_start';
	const CONTROLLER_END = 'kernel.controller_end';
	const EXCEPTION = 'kernel.exception';
}
