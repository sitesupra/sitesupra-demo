<?php

namespace Supra\Authentication\Exception;

/**
 * Thrown on existing session limitations, e.g. when only one active session is allowed
 */
class ExistingSessionLimitation extends AuthenticationFailure
{
	
}
