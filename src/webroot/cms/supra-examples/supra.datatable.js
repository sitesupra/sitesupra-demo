Supra('supra.datatable', function (Y) {

	//Create data table inside existing node
	var datatable = new SU.DataTable({
		'srcNode': Y.one('#myDataTablePlaceHolder'),
		'requestURI': '/sample/data.json'
	});
	
	//URI column formatter function
	var formatUriColumn = function (column_id, value, row_data) {
		var uri = row_data.title;
		return uri.replace(/[^a-z0-9\-]/g, '-')	//Replace all unneeded symbols
				  .replace(/(^-*|-*$)/g, '')	//Removed leading and trailing dashes
				  .replace(/-{2,}/, '-');		//Remove repeated dashed
	};
	
	//Set columns
	datatable.addColumns([
		{'id': 'id', 'title': '#'},
		{'id': 'title', 'title': 'Title'},
		//Data for this column is not received from server,
		//formatter will populate this column using modified title
		{'id': 'uri', 'title': 'URI', 'hasData': false, 'formatter': formatUriColumn}
	]);
	
	
	
	//Set request params
	datatable.requestParams.set('user_id', 'john');
	datatable.requestParams.set('limit', '10');
	
	//Render data table
	datatable.render();

});