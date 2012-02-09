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
	
	const pagePreCreateEvent = 'pagePreCreateEvent';
	const pagePostCreateEvent = 'pagePostCreateEvent';
}