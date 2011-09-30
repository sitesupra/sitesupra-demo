<?

namespace Supra\Cms\MediaLibrary;

use Supra\Authorization\AccessPolicy\AuthorizationThreewayAccessPolicy;

use Supra\FileStorage\Entity\Abstraction\File;


class MediaLibraryAuthorizationAccessPolicy extends AuthorizationThreewayAccessPolicy 
{

	function __construct() 
	{
		parent::__construct('files', File::CN());
	}
}
