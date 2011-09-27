<?php

namespace Supra\Cms\ContentManager;

use Supra\Authorization\AccessPolicy\AuthorizationThreewayAccessPolicy;

class ContentManagerAuthorizationAccessPolicy extends AuthorizationThreewayAccessPolicy {
	
	function __construct() {
		parent::__construct('pages', array('page_edit', 'page_publish'));
	}
}
