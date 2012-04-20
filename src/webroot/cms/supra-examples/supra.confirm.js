/*
 * Show confirmation popup
 */
Supra.Manager.executeAction('Confirmation', {
	//Take locale string from lang.json
	'message': Supra.Intl.get(['sample', 'confirmation']),
	
	//Don't block all other content, default value is "true"
	'useMask': false,
	
	//Buttons
	'buttons': [
		{
			'id': 'yes',
			
			//Custom label, default would be "Yes"
			'label': 'Sure',
			
			//Callback context
			'context': this,
			//Callback function
			'click': function () {
				alert('You clicked "Yes"');
			}
		},
		
		//On "No" click confirmation will just close without calling any callback
		//with default label "buttons.no" from lang.json
		{
			'id': 'no'
		}
	]
});