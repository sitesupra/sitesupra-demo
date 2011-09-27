<?

namespace Supra\Cms\MediaLibrary;

use Supra\Authorization\AccessPolicy\AuthorizationThreewayAccessPolicy;

class MediaLibraryAuthorizationAccessPolicy extends AuthorizationThreewayAccessPolicy {
	
	function __construct() {
		parent::__construct('pages', array('file_edit', 'file_upload', 'file_delete'));
	}
}
