<?php

namespace Supra\Controller\Pages\Event;

/**
 * Page events raised in different CMS actions
 */
final class PageCmsEvents
{
	const pagePrePersist = 'pagePrePersist';
	const pagePostPersist = 'pagePostPersist';

	const pagePreRemove = 'pagePreRemove';
	const pagePostRemove = 'pagePostRemove';
	
	const pagePreRestore = 'pagePreRestore';
	const pagePostRestore = 'pagePostRestore';
	
	const pagePrePublish = 'pagePrePublish';
	const pagePostPublish = 'pagePostPublish';
	
	const pagePreLock = 'pagePreLock';
	const pagePostLock = 'pagePostLock';
	
	const pagePreUnlock = 'pagePreUnlock';
	const pagePostUnlock = 'pagePostUnlock';
	
	const pageContentPostSave = 'pageContentPostSave';
}