/**
 * Style dropdown
 */
YUI().add('supra.htmleditor-plugin-style', function (Y) {
	
	var defaultConfiguration = {
		/* Modes which plugin supports */
		modes: [SU.HTMLEditor.MODE_SIMPLE, SU.HTMLEditor.MODE_RICH]
	};
	
	/*
	 * Handle style dropdown
	 */
	SU.HTMLEditor.addPlugin('style', defaultConfiguration, {
		
		/**
		 * Selectors grouped by tags
		 * @type {Object}
		 */
		selectors: {},
		
		/**
		 * Toolbar dropdown element
		 * @type {Object}
		 */
		dropdown: null,
		
		/**
		 * Node which style is being changed
		 * @type {HTMLElement}
		 */
		targetNode: null,
		
		/**
		 * Returns selectors matching tag
		 * 
		 * @param {String} tagName Tag name to search for 
		 * @param {Boolean} excludeGlobal Selectors matching all tags should be excluded
		 * @return List of selectors
		 * @type {Array}
		 */
		getSelectors: function (tagName, excludeGlobal) {
			var selectors = this.selectors,
				tagName = tagName.toUpperCase(),
				result = [];
			
			if (tagName in selectors) {
				result = result.concat(selectors[tagName]);
			}
			
			if (!excludeGlobal && tagName != '' && selectors['']) {
				result = result.concat(selectors['']);
			}
			
			return result;
		},
		
		parseSelectorAttributes: function (match) {
			var attr = match[5],
				data,
				ret = {},
				trim = /^("|')|("|')$/g;
			
			if (attr) {
				attr = attr.split(',');
				for(var i=0,ii=attr.length; i<ii; i++) {
					data = attr[i].split('=');
					ret[data[0]] = data.length > 1 ? data[1].replace(trim, '') : '';
				}
			}
			
			if (!ret.title) {
				ret.title = (match[2] ? match[2] : '') + match[3];
			}
			
			return ret;
		},
		
		parseStyleSelectors: function (result) {
			var i = 0,
				imax = result.length,
				selector,
				match,
				list = [];
				
			for(; i < imax; i++) {
				selector = result[i].replace('#su-style-dropdown ', '');
				match = selector.match(/(.+\s)?([a-z0-9]+)?\.([a-z0-9\-\_]+)\s?(\[([^\]]+)\])?/i)
				
				if (match) {
					list.push({
					    'path': match[1] ? match[1].replace(/^\s+|\s+$/g, '') : null,
					    'tag': match[2] ? match[2].toUpperCase() : '',
					    'classname': match[3],
					    'attributes': this.parseSelectorAttributes(match)
					});
				}
			}
			
			return list;
		},
		
		/**
		 * Filter out selectors, which doesn't match current container
		 * 
		 * @param {Array} selectors List of selectors
		 */
		filterSelectors: function (selectors) {
			var container = this.htmleditor.get('srcNode');
			var result = {},
				i = 0,
				imax = selectors.length,
				selector;
				
			for(; i < imax; i++) {
				selector = selectors[i];
				if (!selector.path || container.test(selector.path) || container.test(selector.path + ' *')) {
					if (!result[selector.tag]) result[selector.tag] = [];
					result[selector.tag].push(selector);
				}
			}
			
			return result;
		},
		
		/**
		 * Traverse stylesheets and extract styles from "Style" dropdown box
		 */
		collectStyleSelectors: function () {
			var result = [],
				rules,
				doc = new SU.Y.Node(this.htmleditor.get('doc')),
				links = doc.all('link[rel="stylesheet"]');
			
			if (links) {
				for(var i=0,ii=links.size(); i<ii; i++) {
					rules = SU.Y.Node.getDOMNode(links.item(i)).sheet.cssRules;
					for(var k=0,kk=rules.length; k<kk; k++) {
					    if (rules[k].selectorText && rules[k].selectorText.indexOf('#su-style-dropdown') != -1) {
					        result.push(rules[k].selectorText);
					    }
					}
				}
			}
			
			var style = doc.all('style[type="text/css"]'),
				css,
				match;
			
			if (style) {
				for(var i=0,ii=style.size(); i<ii; i++) {
					css = style.item(i).get('innerHTML');
					match = css.match(/#su\-style\-dropdown [^,{]*/g);
					
					if (match) {
						result = result.concat(match);
					}
				}
			}
			
			result = this.parseStyleSelectors(result);
			return this.filterSelectors(result);
		},
		
		/**
		 * When node changes update button states
		 * @param {Object} event
		 */
		handleNodeChange: function (event) {
			var allowEditing = this.htmleditor.editingAllowed;
			
			var selectedNode = this.htmleditor.getSelectedElement(),
				node = selectedNode,
				srcNode = this.htmleditor.get('srcNode'),
				selectors = this.selectors,
				tagNames,
				i,
				ii;
			
			this.targetNode = null;
			
			//Traverse up the tree and find first tag which has selectors
			while(node && !srcNode.compareTo(node)) {
				tagNames = this.htmleditor.getNodeTagName(node);
				
				for(i=0,ii=tagNames.length; i<ii; i++) {
					if (tagNames[i] in selectors) {
						this.targetNode = node;
						this.fillOptions(node, tagNames[i]);
						
						return;
					}
				}
				node = node.parentNode;
			}
			
			//If there are no selectors for this or parent elements then show selectors which are for all tags
			if ('' in selectors && !srcNode.compareTo(selectedNode)) {
				tagNames = this.htmleditor.getNodeTagName(selectedNode);
				this.targetNode = selectedNode;
				this.fillOptions(selectedNode, tagNames[0]);
				
				return;
			}
			
			this.fillOptions(null, null);
		},
		
		/**
		 * When editing allowed changes update button states 
		 * @param {Object} event
		 */
		handleEditingAllowChange: function (event) {
			this.dropdown.disabled = !event.allowed;
		},
		
		/**
		 * Initialize plugin for editor,
		 * Called when editor instance is initialized
		 * 
		 * @param {Object} htmleditor HTMLEditor instance
		 * @constructor
		 */
		init: function (htmleditor) {
			//When un-editable node is selected disable toolbar button
			htmleditor.on('editingAllowedChange', this.handleEditingAllowChange, this);
			htmleditor.on('nodeChange', this.handleNodeChange, this);
			
			htmleditor.get('toolbar').on('disabledChange', function (evt) {
				this.dropdown.disabled = evt.newVal;
			}, this);
			htmleditor.on('disable', function () {
				this.dropdown.style.display = 'none';
			}, this);
			htmleditor.on('enable', function () {
				this.dropdown.style.display = 'inline';
			}, this);
			
			this.selectors = this.collectStyleSelectors();
			this.createDropdown();
		},
		
		/**
		 * Fill options
		 */
		fillOptions: function (node, tagName) {
			//Remove old options
			var option = this.dropdown.firstChild.nextSibling,
				nextOption;
			
			while(option) {
				nextOption = option.nextSibling;
				this.dropdown.removeChild(option);
				option = nextOption;
			}
			
			if (!node) {
				//If there is no node, then skip selector check
				//because there won't be any matching styles
				this.dropdown.disabled = true;
				return;
			}
			this.dropdown.disabled = false;
			node = new Y.Node(node);
			
			//Add new options
			var selectors = this.getSelectors(tagName),
				i = 0, imax = selectors.length,
				classnameFound = false;
			
			//If there are no items in dropdown then disable it
			if (!imax) {
				this.dropdown.disabled = true;
			}
			
			for(; i < imax; i++) {
				this.dropdown.appendChild(selectors[i].node);
				if (node.hasClass(selectors[i].classname)) {
					this.dropdown.value = selectors[i].classname;
					classnameFound = true;
				}
			}
			
			if (!classnameFound) {
				this.dropdown.value = '';
			}
		},
		
		/**
		 * Change element style
		 */
		changeStyle: function () {
			if (!this.targetNode) return;
			
			var targetNode = new Y.Node(this.targetNode);
			var tagName = targetNode.get('tagName');
			var selectors = this.getSelectors(tagName);
			var classname = this.dropdown.value;
			
			for(var i=0, ii=selectors.length; i<ii; i++) {
				if (selectors[i].classname != classname) {
					targetNode.removeClass(selectors[i].classname);
				} else {
					targetNode.addClass(classname);
				}
			}
			
			this.htmleditor.fire('selectionChange');
			this.htmleditor.fire('nodeChange');
			this.htmleditor._changed();
		},
		
		/**
		 * Create dropdown and options
		 */
		createDropdown: function () {
			var toolbar = this.htmleditor.get('toolbar'),
				dropdown = toolbar ? toolbar.getButton('style') : null;
			
			if (!dropdown) return;
			
			this.dropdown = Y.Node.create('<select></select>');
			dropdown.insert(this.dropdown, 'after');
			
			dropdown.addClass('hidden');
			this.dropdown = Y.Node.getDOMNode(this.dropdown);
			
			//Create options
			var selectors = this.selectors,
				tagName = null,
				i = 0,
				imax = 0,
				option = null,
				node = null;
			
			node = document.createElement('option');
			node.tag = '';
			node.value = '';
			node.innerHTML = '&nbsp;';
			this.dropdown.appendChild(node);
			
			for(tagName in selectors) {
				for(var i = 0, imax = selectors[tagName].length; i < imax; i++) {
					option = selectors[tagName][i];
					node = document.createElement('option');
					node.value = option.classname;
					node.innerHTML = option.attributes.title;
					option.node = node;
				}
			}
			
			dropdown = new Y.Node(this.dropdown);
			dropdown.on('change', this.changeStyle, this);
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
	
}, YUI.version, {'requires': ['supra.htmleditor-base']});