<?php

namespace Supra\Cms\BlogManager\Root;

use Supra\Cms\CmsAction;
use Supra\Response\TwigResponse;
use Supra\Request;

/**
 * @method TwigResponse getResponse()
 */
class RootAction extends CmsAction
{
    /**
     * @param Request\RequestInterface $request
     * @return TwigResponse
     */
    public function createResponse(Request\RequestInterface $request)
    {
        return $this->createTwigResponse();
    }
    
    public function indexAction()
    {
        $this->getResponse()
                ->outputTemplate('blog-manager/root/root.html.twig');
    }

}
