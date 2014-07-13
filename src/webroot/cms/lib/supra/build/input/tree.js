YUI.add('supra.input-tree', function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Shortcuts
	var Manager = Supra.Manager,
		DEFAULT_LABEL_SET = '{#form.set_tree#}';
	
	
	function Input (config) {
		Input.superclass.constructor.apply(this, arguments);
	}
	
	Input.NAME = 'input-tree';
	Input.CLASS_NAME = Y.ClassNameManager.getClassName(Input.NAME);
	Input.ATTRS = {
		'labelSet': {
			'value': DEFAULT_LABEL_SET,
			'validator': Y.Lang.isString
		},
		'mode': {
			'value': 'tree'
		},
		'groupsSelectable': {
			'value': false
		},
		
		'sourceId': {
			'value': ''
		}
	};
	
	// Input is inline
	Input.IS_INLINE = false;
	
	// Input is inside form
	Input.IS_CONTAINED = true;
	
	// Input supports notifications
	Input.SUPPORTS_NOTIFICATIONS = false;
	
	Input.HTML_PARSER = {};
	
	Y.extend(Input, Supra.Input.Link, {
		
		openLinkManager: function () {
			// Update request URI
			//var requestUri = Supra.Manager.Loader.getDynamicPath() + '/crud-manager/data/sourcedata?sourceId=' + this.get('sourceId');
			var requestUri = Supra.CRUD.getDataPath('sourcedata') + '?sourceId=' + this.get('sourceId');
			this.set('treeRequestURI', requestUri);
			
			// Open link manager
			return Input.superclass.openLinkManager.apply(this, arguments);
		}
		
	});
	
	Supra.Input.Tree = Input;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.input-link']});