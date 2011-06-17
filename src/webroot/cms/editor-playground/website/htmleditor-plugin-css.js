/**
 * Plugin for modifying paddings
 */
YUI().add('website.htmleditor-plugin-css', function (Y) {
	
	var defaultConfiguration = {
		elements: ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div', 'li']
	};
	
	SU.HTMLEditor.addPlugin('css', defaultConfiguration, {
		
		elements: '',
		
		selectedElement: null,
		
		toolbarTabs: null,
		
		inputPadding: null,
		
		tabId: 'css',
		
		/**
		 * Initialize plugin for editor,
		 * Called when editor instance is initialized
		 * 
		 * @param {Object} htmleditor HTMLEditor instance
		 * @constructor
		 */
		init: function (htmleditor, configuration) {
			
			// Get elements to which plugin should be applied
			if (configuration && Y.Lang.isArray(configuration.elements)) {
				this.elements = configuration.elements.join(',').toUpperCase();
			} else {
				//If there are no formats, then plugin is useless
				return false;
			}
			
			//This plugin can be used only if there is a tab in toolbar
			if (!this.addToolbarTab()) return;
			
			htmleditor.on('nodeChange', function (event) {
				//Only block level elements can be modified
				var element = htmleditor.getSelectedElement(this.elements);
				this.selectedElement = element;
				
				if (element) {
					this.toolbarTabs.showTab(this.tabId);
					this.updatePaddingValues();
				} else {
					this.toolbarTabs.hideTab(this.tabId);
				}
				
			}, this);
		},
		
		/**
		 * Create toolbar tab if possible and add inputs
		 * to tab content
		 */
		addToolbarTab: function () {
			
			var toolbar = this.htmleditor.get('toolbar');
			if (!toolbar) return false;
			
			this.toolbarTabs = toolbar.tabs;
			
			//Create new tab only if it doesn't exist already
			if (!this.toolbarTabs.hasTab(this.tabId)) {
				this.renderUI();
			}
			
			this.bindUI();
			
			return true
		},
		
		/**
		 * Create "CSS adjustments" tab
		 */
		renderUI: function () {
			var tabs = this.toolbarTabs,
				tabId = this.tabId,
				tabContent;
			
			//When creating tab, only required attributes are 'id' and 'title'
			tabContent = tabs.addTab({
				'id': tabId,
				'title': 'CSS adjustments'
			});
			
			//Add class to change input positions using external css
			tabContent.addClass('toolbar-plugin-css-panel');
			
			//Initially tabs are visible, but since this tab will be used for
			//properties it doesn't need to be visible until element is selected
			tabs.hideTab(tabId);
			
			//Create inputs
			var paddings = ['Left', 'Top', 'Right', 'Bottom'],
				i = 0,
				imax = paddings.length;
			
				//Store inputs in tab properties because multiple editor
				//instances may use same toolbar
				tabs.tabs[tabId].inputs = [];
			
			for(; i<imax; i++) {
				var inputPadding = new Supra.Input.String({
					'id': paddings[i],
					'label': i == 0 ? 'Padding:' : '',
					'value': ''
				});
				
				inputPadding.render(tabContent);
				inputPadding.addClass('toolbar-plugin-css-' + paddings[i].toLowerCase());
				
				tabs.tabs[tabId].inputs.push(inputPadding);
			}
		},
		
		/**
		 * Add event listeners to inputs
		 */
		bindUI: function () {
			var tabs = this.toolbarTabs,
				tabId = this.tabId,
				inputs = tabs.tabs[tabId].inputs,
				i = 0,
				imax = inputs.length,
				self = this;
			
			//Change event calback
			function onPaddingChange () {
				if (self.htmleditor.get('disabled') || !self.htmleditor.editingAllowed) return;
				if (!self.selectedElement) return;
				
				//'id' is 'Left', 'Right', 'Top' or 'Bottom'
				var id = this.get('id'),
					padding = parseInt(this.get('value'), 10);
					padding = isNaN(padding) ? 'inherit' : padding + 'px';
				
				self.selectedElement.style['padding' + id] = padding;
			}
			
			
			for(; i<imax; i++) {
				inputs[i].on('change', onPaddingChange);
				inputs[i].on('keyup', onPaddingChange);
			}
		},
		
		/**
		 * Update input values from element paddings
		 */
		updatePaddingValues: function () {
			var paddings = ['Left', 'Top', 'Right', 'Bottom'],
				i = 0,
				imax = paddings.length,
				inputs = this.toolbarTabs.tabs[this.tabId].inputs,
				padding;
				
			for(; i<imax; i++) {
				padding = parseInt(this.selectedElement.style['padding' + paddings[i]], 10);
				if (isNaN(padding)) padding = '';
				inputs[i].set('value', padding);
			}
			
		},
		
		/**
		 * Clean up after plugin
		 * Called when editor instance is destroyed
		 */
		destroy: function () {}
		
	});
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['supra.htmleditor-base', 'supra.input-string']});