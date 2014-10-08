//Invoke strict mode
"use strict";

YUI().add('website.sitemap-plugin-page-global', function (Y) {
	
	//Shortcuts
	var Manager = Supra.Manager,
		Action = Manager.getAction('SiteMap');
	
	var KEY_RETURN = 13;
	
	/**
	 * Page edit settings form
	 */
	function Plugin () {
		Plugin.superclass.constructor.apply(this, arguments);
	};
	
	Plugin.NAME = 'PluginSitemapPageGlobal';
	Plugin.NS = 'page_global';
	
	Y.extend(Plugin, Y.Plugin.Base, {
		/**
		 * TreeNode or DataGridRow which currently user has selected
		 * @type {Object}
		 * @private
		 */
		'_node': null,
		
		/**
		 * TreeNode which currently user has selected or parent of DataGridRow
		 * @type {Object}
		 * @private
		 */
		'_treeNode': null,
		
		/**
		 * Children widget list
		 * 
		 * @type {Object}
		 * @private
		 */
		'_widgets': {
			/* Form */
			'form': null,
			/* Form container panel */
			'panel': null,
			/* Create page with text button */
			'buttonCreateText': null
		},
		
		
		
		/**
		 * ------------------------------ PRIVATE ------------------------------
		 */
		
		
		
		/**
		 * Attach to event listeners, etc.
		 * 
		 * @constructor
		 * @private
		 */
		'initializer': function () {
			this._widgets = {};
			this.get('host').on('page:select', this._show, this);
		},
		
		/**
		 * Create edit form popup
		 * 
		 * @private
		 */
		'_createPanel': function () {
			var container = this.get('host').get('boundingBox').closest('.su-sitemap').one('.su-sitemap-global'),
				widgets = this._widgets,
				panel = null;
			
			widgets.panel = panel = new Supra.Panel({
				'srcNode': container,
				'autoClose': true,
				'arrowVisible': true,
				'zIndex': 2,
				'visible': false,
				'closeOnEscapeKey': true
			});
			
			//Bind event listeners
			panel.on('visibleChange', this._onVisibleChange, this);
			
			//On return key close form and save
			panel.get('contentBox').on('keydown', this._onKeyDown, this);
			
			//Create form
			this._createForm(container);
			
			//Render all widgets
			for(var i in widgets) {
				widgets[i].render();
			}
			
			//Listeners
			this._bindEventListeners();
		},
		
		/**
		 * Create form
		 * 
		 * @param {Object} container Container element
		 * @private
		 */
		'_createForm': function (container) {
			var widgets = this._widgets,
				buttons = container.all('button');
			
			//Buttons
			widgets.buttonCreateText = new Supra.Button({'srcNode': buttons.item(0), 'style': 'small-blue'});
			
			widgets.buttonCreateText.on('click', this.createPagePrepopulated, this);
			
			//Form
			widgets.form = new Supra.Form({
				'srcNode': container.one('form')
			});
		},
		
		/**
		 * Bind all event listeners
		 * 
		 * @private
		 */
		'_bindEventListeners': function () {
			//Inputs
			var widgets = this._widgets,
				inputs = widgets.form.getInputs();
			
			inputs['locale'].on('valueChange', this._fillLocaleData, this);
		},
		
		/**
		 * When panel is hidden unset target
		 * 
		 * @param {Event} e Event facade object
		 * @private
		 */
		'_onVisibleChange': function (e) {
			if (e.newVal != e.prevVal && !e.newVal) {
				if (this._widgets.panel.get('alignTarget')) {
					this._widgets.panel.set('alignTarget', null);
					
					if (this._node) {
						var view = this.get('host').get('view');
						
						if (this._node.isInstanceOf('TreeNode')) {
							if (!this._node.get('root')) {
								//Center parent
								if (this._node.get('parent').size() > 1) {
									view.set('spacingBottom', 0);
									view.center(this._node.get('parent'));
								} else {
									//Parent has only 1 child, which is this temporary node
									view.set('spacingBottom', 0);
									view.center(this._node.get('parent').get('parent'));
								}
							}
							
							this._node.set('highlighted', false);
							this._node.set('dndLocked', false);
							this._node.getWidget('buttonEdit').set('disabled', false);
							this._node.getWidget('buttonOpen').set('disabled', false);
						} else if (this._node.isInstanceOf('DataGridRow')) {
							this._node.get('parent').get('parent').set('highlighted', false);
						}
						
						view.set('disabled', false);
					}
				}
			}
		},
		
		/**
		 * Returns align position which prevents panel from going out of screen
		 * 
		 * @param {Object} target Target element
		 * @return Align position
		 * @type {String}
		 * @private
		 */
		'_getAlignPosition': function (target) {
			var winHeight = Y.DOM.winHeight(),
				region = target.get('region'),
				space = winHeight - region.top - region.height;
			
			if (space < 300) {
				return 'L';
			} else {
				return 'T';
			}
		},
		
		/**
		 * Show popup for creating new page from another locale
		 * 
		 * @param {Event} e Event facade object
		 * @private
		 */
		'_show': function (e) {
			var node = e.node,
				data = node.get('data'),
				view = this.get('host').get('view');
			
			if ( ! data.localized) {
				this._node = node;
				
				if (node.isInstanceOf('TreeNode')) {
					this._treeNode = node;
				} else if (node.isInstanceOf('DataGridRow')) {
					this._treeNode = node.get('parent').get('parent');
				} else {
					return;
				}
				
				if (!this._widgets.panel) {
					this._createPanel();
				}
				
				//Set form values
				this.setFormValues(data);
				
				//Center item and disable draging
				if (node.isInstanceOf('TreeNode')) {
					//Show popup over TreeNode
					view.set('spacingBottom', 300);
					view.center(node, this.__showOverNode, this);
				} else {
					//Show popup over DataGrid row
					this.__showOverRow();
				}
				
				view.set('disabled', true);
			}
		},
		
		/**
		 * Highlight node, show popup, disable other interactions
		 * 
		 * @private
		 */
		'__showOverNode': function () {
			var node = this._node,
				widgets = this._widgets,
				panel = widgets.panel;
			
			//Change node style
			node.set('highlighted', true);
			node.set('dndLocked', false);
			node.getWidget('buttonEdit').set('disabled', true);
			node.getWidget('buttonOpen').set('disabled', true);
			
			//Panel position and style
			var target = node.get('contentBox');
			
			if (target === panel.get('alignTarget')) {
				panel.show();
			} else {
				panel.set('alignTarget', target);
				panel.set('alignPosition', this._getAlignPosition(target));
				
				panel.fadeIn();
			}
		},
		
		/**
		 * Show popup, disable other interactions
		 * 
		 * @private
		 */
		'__showOverRow': function () {
			var node = this._node,
				widgets = this._widgets,
				panel = widgets.panel;
			
			//Change node style
			this._node.get('parent').get('parent').set('highlighted', true);
			
			//Panel position and style
			var target = node.getNode();
			
			if (target === panel.get('alignTarget')) {
				panel.show();
			} else {
				panel.set('alignTarget', target);
				panel.set('alignPosition', this._getAlignPosition(target));
				
				panel.fadeIn();
			}
		},
		
		/**
		 * On create request complete open page
		 * 
		 * @param {Object} data Page data
		 * @param {Boolean} status Request status
		 * @private
		 */
		'_onCreatePageComplete': function (data, status) {
			//Enable form
			this._widgets.form.set('disabled', false);
			this._widgets.buttonCreateText.set('disabled', false);
			this._widgets.panel.set('autoClose', true);
				
			if (status && data) {
				var node = this._node,
					node_data = node.get('data'),
					tree = this._treeNode.get('tree');
				
				//Update data
				node.set('localized', true);
				Supra.mix(node_data, data, {
					'localized': true,
					'redirect': false,
					'redirect_page_id': null
				});
				
				var params = {
					'data': node_data,
					'node': node
				};
				
				//Hide panel
				this.hide();
				
				//Open page
				tree.fire('page:select', params);
			}
		},
		
		/**
		 * On return key create page
		 * 
		 * @param {Event} event Event facade object
		 * @private
		 */
		'_onKeyDown': function (event) {
			if (event.keyCode == KEY_RETURN) {
				this.createPagePrepopulated();
			}
		},
		
		
		/**
		 * ------------------------------ API ------------------------------
		 */
		
		
		
		/**
		 * Create page
		 * 
		 * @param {Object} newData Contains page locale to copy from and title, path
		 */
		'createPage': function (sourceLocale, newData) {
			var mode    = this._treeNode.get('tree').get('mode'),
				fn      = 'createPageLocalization',
				context = Supra.Manager.getAction('Page'),
				data    = this._node.get('data');
				
			if (mode == 'templates') {
				fn = 'createTemplateLocalization';
				context = Supra.Manager.getAction('Template');
			}
			
			//Disable form
			this._widgets.form.set('disabled', true);
			this._widgets.buttonCreateText.set('disabled', true);
			this._widgets.panel.set('autoClose', false);
			
			newData['locale'] = this.get('host').get('locale');
			
			//Call duplicate request
			context[fn](data.id, newData, sourceLocale, this._onCreatePageComplete, this);
		},
		
		/**
		 * Create page prepopulated with data from another locale
		 */
		'createPagePrepopulated': function () {
			var form = this._widgets.form,
				sourceLocale = form.getInput('locale').get('value'),
				title = form.getInput('title').get('value'),
				path = form.getInput('path').get('value');
				
			var newData = {
				'title': title,
				'path': path
			};
			
			this.createPage(sourceLocale, newData);
		},
		
		/**
		 * Set form values
		 * 
		 * @param {Object} data Form values
		 */
		'setFormValues': function (data) {
			var form = this._widgets.form,
				input = form.getInput('locale'),
				node = this._node,
				values = [],
				default_value = '',
				
				locales = data.localizations,
				contexts = Supra.data.get('contexts'),
				l = 0, ll = contexts.length,
				
				languages = null,
				k = 0, kk = 0;
			
			// Reset data
			form.getInput('title').set('value', '');
			form.getInput('title').set('originalValue', '');
			form.getInput('path').set('value', '');
			form.getInput('path').set('originalValue', '');
			
			//Find titles for locales
			for(l=0; l<ll; l++) {
				languages = contexts[l].languages;
				for(k=0,kk=languages.length; k<kk; k++) {
					if (languages[k].id in locales) {
						values.push({
							'id': languages[k].id,
							'title': languages[k].title
						});
						if (!default_value) {
							default_value = languages[k].id;
						}
					}
				}
			}
			
			//data.localizations
			input.set('values', values);
			input.set('value', default_value);

			//Toggle fields
			if (this.get('host').get('mode') == 'pages') {
				form.getInput('title').set('label', Supra.Intl.get(['sitemap', 'new_page_label_title']));
				
				if (node.get('root')) {
					//Root page doesn't have a path
					form.getInput('path').set('visible', false);
				} else {
					form.getInput('path').set('visible', true);
				}
			} else {
				form.getInput('title').set('label', Supra.Intl.get(['sitemap', 'new_template_label_title']));
				form.getInput('path').set('visible', false);
			}
		},
		
		/**
		 * Fills in title/path from the chosen locale if the input values has not been changed by user
		 * @param {Object} evt Event data, only newVal is used
		 * @private
		 */
		'_fillLocaleData': function(evt) {
			var form = this._widgets.form,
				locale = evt.newVal,
				node = this._node,
				localizations = node.get('data').localizations,
				titleInput = form.getInput('title'),
				pathInput = form.getInput('path');
			
			if (locale in localizations) {
				var title = localizations[locale].title,
					path = localizations[locale].path,
					originalTitle = titleInput.get('originalValue'),
					originalPath = pathInput.get('originalValue');
				
				if ( ! originalTitle || (originalTitle == titleInput.get('value'))) {
					titleInput.set('value', title);
					titleInput.set('originalValue', title);
				}
				if ( ! originalPath || (originalPath == pathInput.get('value'))) {
					pathInput.set('value', path);
					pathInput.set('originalValue', path);
				}
			}
		},
		
		/**
		 * Hide edit panel
		 */
		'hide': function () {
			if (this._widgets.panel && this._node) {
				this._widgets.panel.hide();
			}
		}
		
	});
	
	Action.PluginPageGlobal = Plugin;
	
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['supra.input']});