//Invoke strict mode
"use strict";

YUI().add('website.sitemap-plugin-page-global', function (Y) {
	
	//Shortcuts
	var Manager = Supra.Manager,
		Action = Manager.getAction('SiteMap');
	
	
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
			'buttonCreateText': null,
			/* Create blank page button */
			'buttonCreateBlank': null
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
				'zIndex': 1,
				'visible': false
			});
			
			//Bind event listeners
			panel.on('visibleChange', this._onVisibleChange, this);
			
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
			widgets.buttonCreateBlank = new Supra.Button({'srcNode': buttons.item(1), 'style': 'small-blue'});
			
			widgets.buttonCreateText.on('click', this.createPagePrepopulated, this);
			widgets.buttonCreateBlank.on('click', this.createPageBlank, this);
			
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
				inputs = widgets.form.getInputs(),
				id = null;
			
			//@TODO ???
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
				return 'B';
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
			this._widgets.buttonCreateBlank.set('disabled', false);
			this._widgets.panel.set('autoClose', true);
				
			if (status && data) {
				var node = this._node,
					node_data = node.get('data'),
					tree = this._treeNode.get('tree');
				
				//Update data
				node.set('localized', true);
				Supra.mix(node_data, data, {
					'localized': true
				})
				
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
		 * ------------------------------ API ------------------------------
		 */
		
		
		
		/**
		 * Create page
		 * 
		 * @param {String} locale Page locale to copy from
		 */
		'createPage': function (locale) {
			var mode    = this._treeNode.get('tree').get('mode'),
				fn      = 'duplicateGlobalPage',
				context = Supra.Manager.getAction('Page'),
				data    = this._node.get('data');
				
			if (mode == 'templates') {
				fn = 'duplicateGlobalTemplate';
				context = Supra.Manager.getAction('Template');
			}
			
			//Disable form
			this._widgets.form.set('disabled', true);
			this._widgets.buttonCreateText.set('disabled', true);
			this._widgets.buttonCreateBlank.set('disabled', true);
			this._widgets.panel.set('autoClose', false);
			
			//Call duplicate request
			context[fn](data.id, this.get('host').get('locale'), locale, this._onCreatePageComplete, this);
		},
		
		/**
		 * Create blank page
		 */
		'createPageBlank': function () {
			this.createPage('');
		},
		
		/**
		 * Create page prepopulated with data from another locale
		 */
		'createPagePrepopulated': function () {
			var locale  = this._widgets.form.getInput('locale').get('value');
			this.createPage(locale);
		},
		
		/**
		 * Set form values
		 * 
		 * @param {Object} data Form values
		 */
		'setFormValues': function (data) {
			var input = this._widgets.form.getInput('locale'),
				values = [],
				default_value = '',
				
				locales = data.localizations,
				contexts = Supra.data.get('contexts'),
				l = 0, ll = contexts.length,
				
				languages = null,
				k = 0, kk = 0;
			
			//Find titles for locales
			for(var i=0,ii=locales.length; i<ii; i++) {
				for(l=0; l<ll; l++) {
					languages = contexts[l].languages;
					for(k=0,kk=languages.length; k<kk; k++) {
						if (languages[k].id == locales[i]) {
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
			}
			
			//data.localizations
			input.set('values', values);
			input.set('value', default_value);
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