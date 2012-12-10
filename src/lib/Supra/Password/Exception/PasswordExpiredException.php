<?php

namespace Supra\Password\Exception;

/**
 * Raised when password is expired, 
 * used by CMS password expiration feature
 */
class PasswordExpiredException extends \Supra\Authentication\Exception\AuthenticationFailure
{
	
}
