YUI.add('supra.page-content-html', function (Y) {
	
	//Shortcut
	var Action = SU.Manager.PageContent;
	
	function ContentHTML () {
		this.editor = null;
		ContentHTML.superclass.constructor.apply(this, arguments);
	}
	
	ContentHTML.NAME = 'page-content-html';
	ContentHTML.CLASS_NAME = Y.ClassNameManager.getClassName(ContentHTML.NAME);
	ContentHTML.ATTRS = {
		'editable': {
			value: true,
			readOnly: true
		},
		'title': 'Free text'
	};
	
	Y.extend(ContentHTML, Action.Proto, {
		editor: null,
		bindUI: function () {
			ContentHTML.superclass.bindUI.apply(this, arguments);
			
			this.on('editing-start', function () {
				if (!this.editor) {
					this.editor = new Supra.HTMLEditor({
						'doc': this.get('doc'),
						'win': this.get('win'),
						'srcNode': this.getNode(),
						'toolbar': SU.Manager.EditorToolbar.getToolbar()
					});
					this.editor.render();
				}
				
				SU.Manager.Page.showEditorToolbar();
				this.editor.set('disabled', false);
			}, this);
			
			this.on('editing-end', function () {
				if (this.editor) {
					this.editor.set('disabled', true);
					SU.Manager.Page.hideEditorToolbar();
				}
			});
			
			this.bindUISettings();
			
			
			//Handle block save / cancel
			this.on('block:save', function () {
				/* @TODO Save data */
			});
			this.on('block:cancel', function () {
				/* @TODO Revert data changes */
			});
		},
		
		/**
		 * Bind Settings form
		 */
		bindUISettings: function () {
			this.once('editing-start', function () {
				this.plug(Action.PluginProperties, {
					'data': this.get('data'),
					'showOnEdit': false			// Properties form is opened using 'Settings' button
				});
				
				//Bind to editor instead of toolbar, because toolbar is shared between editors
				this.editor.addCommand('settings', Y.bind(function () {
					if (this.editor.get('toolbar').getButton('settings').get('down')) {
						this.properties.showPropertiesForm();
					} else {
						this.properties.hidePropertiesForm();
					}
				}, this));
				
				//When properties form is hidden, unset "Settings" button down state
				this.properties.get('form').on('visibleChange', function (evt) {
					if (evt.newVal != evt.prevVal && !evt.newVal) {
						this.editor.get('toolbar').getButton('settings').set('down', false);
					}
				}, this);
			}, this);
			this.on('editing-end', function () {
				if (this.editor) this.editor.get('toolbar').getButton('settings').set('down', false);
			});
		},
		
		renderUI: function () {
			ContentHTML.superclass.renderUI.apply(this, arguments);
			
			this.renderOverlay();
		}
	});
	
	Action.Html = ContentHTML;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.page-content-proto', 'supra.htmleditor', 'supra.page-content-properties']});