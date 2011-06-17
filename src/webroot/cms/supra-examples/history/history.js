/*
 * Assume this is action with Panel + Grid which opens a form
 */
openRecord: function (item_id) {
    //Record may not exist
    if (this.grid.getRecord(item_id)) {

        //Add record ID to the history
        //during history restore this will do nothing?
        Supra.History.set('record-id', item_id);

        //Call form or something...
        Supra.Manager.executeAction('RecordForm', item_id);
    }
},

initialize: function () {
    //We have to wait until data is loaded to do something with history
    //Execute callback only after first 'data:rendered' event.
    this.grid.once('data:rendered', function () {
        
        Supra.History.register('record-id', this.openRecord, this);

    }, this);
	
	//Remove state from history after RecordForm closes
	//or should this be in RecordForm action?
	Supra.Manager.getAction('RecordForm').on('hide', function () {
		
		Supra.History.unset('record-id');	// == set('record-id', null)
		
	}, this);
}