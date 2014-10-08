<?php

namespace Supra\Core\Event;

class KernelEvent
{
	const REQUEST = 'kernel.request';
	const RESPONSE = 'kernel.response';
	const ERROR404 = 'kernel.404';
}