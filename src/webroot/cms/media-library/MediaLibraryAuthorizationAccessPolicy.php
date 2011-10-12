<?

namespace Supra\Cms\MediaLibrary;

use Supra\Authorization\AccessPolicy\AuthorizationThreewayWithEntitiesAccessPolicy;
use Supra\FileStorage\Entity as FileEntity;
use Supra\Request\RequestInterface;
use Supra\ObjectRepository\ObjectRepository;
use Supra\User\Entity\Abstraction\User;

class MediaLibraryAuthorizationAccessPolicy extends AuthorizationThreewayWithEntitiesAccessPolicy
{

	function __construct()
	{
		parent::__construct('files', FileEntity\Abstraction\File::CN());
	}

	public function getEntityTree(RequestInterface $request) 
	{
		$em = ObjectRepository::getEntityManager($this);
		
		$fr = $em->getRepository(FileEntity\Abstraction\File::CN());

		$slash = new FileEntity\SlashFolder();
		
		$slashNode = array(
			'id' => $slash->getId(),
			'title' => $slash->getFileName(),
			'icon' => 'folder'
		);
		
		$entityTree = array();
				
		$rootNodes = $fr->getRootNodes();
		
		foreach ($rootNodes as $rootNode) {
			$entityTree[] = $this->buildMediaLibraryTreeArray($rootNode);
		}

		$slashNode['children'] = $entityTree;
		
		return array($slashNode);
	}
	
	private function buildMediaLibraryTreeArray(FileEntity\Abstraction\File $file) 
	{ 
		if( ! ($file instanceof FileEntity\Folder)) {
			return array();
		}
		
		$array = array(
			'id' => $file->getId(),
			'title' => $file->getFileName(),
			'icon' => 'folder'
		);

		$array['children'] = array();

		foreach ($file->getChildren() as $child) {
			
			$childArray = $this->buildMediaLibraryTreeArray($child);

			if ( ! empty($childArray)) {
				$array['children'][] = $childArray;
			}
		}

		if (count($array['children']) == 0) {
			unset($array['children']);
		} 
		
		return $array;
	}
	
	protected function getAllEntityPermissionStatuses(User $user) 
	{
		return parent::getAllEntityPermissionStatuses($user);
	}
	
}
