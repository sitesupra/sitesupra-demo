//Invoke strict mode
"use strict";

YUI.add('supra.manager-loader-actions', function (Y) {
	
	/**
	 * Set common action base paths
	 * 
	 * Define here all actions which are reusable between 'managers' to allow them loading
	 * without specifying path each time
	 */
	Supra.Manager.Loader.setActionBasePaths({
		'Header': '/content-manager',
		'SiteMap': '/content-manager',
		'MediaLibrary': '/content-manager',
		'PageToolbar': '/content-manager',
		'PageButtons': '/content-manager',
		'LayoutContainers': '/content-manager',
		'Confirmation': '/content-manager'
	});

	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['supra.manager-loader']});