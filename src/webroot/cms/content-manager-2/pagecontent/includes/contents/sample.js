YUI.add('supra.page-content-sample', function (Y) {
	
	//Shortcut
	var Action = SU.Manager.PageContent;
	
	function ContentSample () {
		this.editor = null;
		ContentSample.superclass.constructor.apply(this, arguments);
	}
	
	ContentSample.NAME = 'page-content-sample';
	ContentSample.CLASS_NAME = Y.ClassNameManager.getClassName(ContentSample.NAME);
	
	ContentSample.ATTRS = {
		'editable': {
			value: true,
			readOnly: true
		},
		'title': 'Sample'
	};
	
	Y.extend(ContentSample, Action.Proto, {
		editor: null,
		
		bindUI: function () {
			ContentSample.superclass.bindUI.apply(this, arguments);
			
			//On properties save / cancel trigger block save / cancel 
			this.on('properties:save', function () {
				this.fire('block:save');
			});
			this.on('properties:cancel', function () {
				this.fire('block:cancel');
			});
			
			//Handle block save / cancel
			this.on('block:save', function () {
				/* @TODO Save data */
			});
			this.on('block:cancel', function () {
				/* @TODO Revert data changes */
			});
		},
		
		renderUI: function () {
			ContentSample.superclass.renderUI.apply(this, arguments);
			this.renderOverlay();
			
			//Properties form plugin
			this.plug(Action.PluginProperties, {
				'data': this.get('data'),
				'showOnEdit': true
			});
		}
	});
	
	Action.Sample = ContentSample;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.page-content-proto', 'supra.htmleditor', 'supra.page-content-properties']});