YUI.add('supra.page-content-html', function (Y) {
	
	//Shortcut
	var Action = SU.Manager.PageContent;
	
	function Content () {
		this.editor = null;
		Content.superclass.constructor.apply(this, arguments);
	}
	
	Content.NAME = 'page-content-html';
	Content.CLASS_NAME = Y.ClassNameManager.getClassName(Content.NAME);
	Content.ATTRS = {
		'editable': {
			value: true,
			readOnly: true
		},
		'title': {
			value: 'Free text'
		}
	};
	
	Y.extend(Content, Action.Proto, {
		editor: null,
		bindUI: function () {
			Content.superclass.bindUI.apply(this, arguments);
			
			this.on('editing-start', function () {
				
				if (!this.editor) {
					this.editor = new Supra.Editor({
						'doc': this.get('doc'),
						'win': this.get('win'),
						'srcNode': this.getNode(),
						'toolbar': SU.Manager.EditorToolbar.getToolbar()
					});
					this.editor.render();
					this.editor.on('exec', function (e) {
						if (e.action == 'image') {
							SU.Manager.Page.showMediaLibrary();
							return false;
						}
					});
				}
				
				this.editor.set('disabled', false);
				
			}, this);
			
			this.on('editing-end', function () {
				
				if (this.editor) {
					this.editor.set('disabled', true);
				}
				
			});
		},
		renderUI: function () {
			Content.superclass.renderUI.apply(this, arguments);
			this.renderOverlay();
			
			this.plug(Action.PluginControls, {
				data: this.get('data')
			});
		}
	});
	
	Action.Html = Content;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.page-content-proto', 'supra.editor', 'supra.page-content-controls']});