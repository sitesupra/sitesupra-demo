<?php

namespace Supra\Cms\CrudManager;

/**
 * Crud controller
 */
final class CrudManagerEvents
{
	const PRE_INSERT = 'preInsertEvent',
		POST_INSERT = 'postInsertEvent',
		
		PRE_DELETE = 'preDeleteEvent',
		POST_DELETE = 'postDeleteEvent',
		
		PRE_MOVE = 'preMoveEvent',
		POST_MOVE = 'postMoveEvent',
		
		PRE_SAVE = 'preSaveEvent',
		POST_SAVE = 'postSaveEvent';
}