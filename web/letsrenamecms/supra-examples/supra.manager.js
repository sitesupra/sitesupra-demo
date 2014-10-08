Supra('supra.manager', function (Y) {
	
	/*
	 * Supra.Manager
	 */
	
	//Returns action object by name. If action isn't loaded yet, then returns temporary action object
	//which allows to set configuration, etc. which is applied to real action when it's loaded
	var action = Supra.Manager.getAction('Foo');
	
	//Load action without executing it
	Supra.Manager.loadAction('Bar');
	
	//Load and execute action,
	//all arguments after action_name will be passed to action 'execute' method
	Supra.Manager.executeAction('Calendar', {'date': '2011-06-01'})
	
	
	//Bind to action events
	Supra.Manager.on('Bar:load', function () { /* ... */ });
	Supra.Manager.on('Bar:ready', function () { /* ... */ });
	Supra.Manager.on('Bar:initialize', function () { /* ... */ });
	Supra.Manager.on('Bar:render', function () { /* ... */ });
	Supra.Manager.on('Bar:execute', function () { /* ... */ });
	
	
	/*
	 * Supra.Manager.Loader
	 */
	
	//Returns if action is loaded / loading
	var isIt = Supra.Manager.Loader.isLoaded('Foo');
	var isIt = Supra.Manager.Loader.isLoading('Bar');
	
	
	//Returns information about action: 
	//'folder', 'path_data', 'path_script', 'path_stylesheet', 'path_template'
	Supra.Manager.Loader.getActionInfo('Bar');
	
	//Returns action folder path
	Supra.Manager.Loader.getActionFolder('Bar');
	
	
	//Set manager base path where all action should be loaded from
	Supra.Manager.Loader.setBasePath('../supra-examples');
	
	//Get manager base path
	var path = Supra.Manager.Loader.getBasePath();
	
	
	//Set path to action which is not in base path
	Supra.Manager.Loader.setActionBasePath('ActionFromOtherManagerNr1', '../content-manager');
	Supra.Manager.Loader.setActionBasePaths({
		'ActionFromOtherManagerNr2': '../content-manager',
		'ActionFromOtherManagerNr3': '../content-manager'
	});
	
});
