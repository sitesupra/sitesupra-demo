<?php
namespace Supra\Controller\Pages\Event;

final class AuditEvents
{
    const pagePublishEvent = 'pagePublishEvent';
	
	const pagePreDeleteEvent = 'pagePreDeleteEvent';
	const pagePostDeleteEvent = 'pagePostDeleteEvent';
	
	const pagePreRestoreEvent = 'pagePreRestoreEvent';
	const pagePostRestoreEvent = 'pagePostRestoreEvent';
	
	const pagePreEditEvent = 'pagePreEditEvent';
	
	/**
	 * "Content edit event" is used to pass additional info to listener
	 * about what element was edited, which action it was (block settings change
	 * block move action etc.). Additional info will be added to page revision data
	 */
	const pageContentEditEvent = 'pageContentEditEvent';
	
	const pagePreCreateEvent = 'pagePreCreateEvent';
	const pagePostCreateEvent = 'pagePostCreateEvent';
}