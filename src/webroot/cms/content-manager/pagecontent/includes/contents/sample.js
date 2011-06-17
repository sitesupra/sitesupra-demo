YUI.add('supra.page-content-sample', function (Y) {
	
	//Shortcut
	var Action = SU.Manager.PageContent;
	
	function Content () {
		this.editor = null;
		Content.superclass.constructor.apply(this, arguments);
	}
	
	Content.NAME = 'page-content-sample';
	Content.CLASS_NAME = Y.ClassNameManager.getClassName(Content.NAME);
	
	Content.ATTRS = {
		'editable': {
			value: true,
			readOnly: true
		},
		'title': {
			value: 'Sample'
		}
	};
	
	Y.extend(Content, Action.Proto, {
		editor: null,
		bindUI: function () {
			Content.superclass.bindUI.apply(this, arguments);
		},
		renderUI: function () {
			Content.superclass.renderUI.apply(this, arguments);
			this.renderOverlay();
			
			this.plug(Action.PluginControls);
			this.plug(Action.PluginProperties, {
				data: this.get('data')
			});
		}
	});
	
	Action.Sample = Content;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.page-content-proto', 'supra.editor', 'supra.page-content-controls']});