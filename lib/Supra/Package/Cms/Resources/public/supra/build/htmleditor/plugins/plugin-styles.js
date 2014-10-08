/**
 * Style sidebar
 */
YUI().add('supra.htmleditor-plugin-styles', function (Y) {
	
	var defaultConfiguration = {
		/* Modes which plugin supports */
		modes: [Supra.HTMLEditor.MODE_SIMPLE, Supra.HTMLEditor.MODE_RICH]
	};
	
	//Convert arrays to object, because object lookup is much faster
	var toObject = function (arr) { var o={},i=0,ii=arr.length; for(;i<ii;i++) o[arr[i]]=true; return o; };
	
	//Style groups
	var tmp = null;
	var GROUPS = [
		{
			'id': 'text',
			'title': '{# htmleditor.group_text #}',
			'tags': (tmp = ['H1', 'H2', 'H3', 'H4', 'H5', 'P', 'B', 'EM', 'U', 'S', 'A']),
			'tagsObject': toObject(tmp)
		},
		{
			'id': 'list',
			'title': '{# htmleditor.group_list #}',
			'tags': (tmp = ['UL', 'OL', 'LI']),
			'tagsObject': toObject(tmp)
		}
		/*{
			'id': 'table',
			'title': '{# htmleditor.group_table #}',
			'tags': (tmp = ['TABLE', 'TR', 'TD', 'TH']),
			'tagsObject': toObject(tmp)
		},
		{
			'id': 'image',
			'title': '{# htmleditor.group_image #}',
			'tags': (tmp = ['IMG']),
			'tagsObject': toObject(tmp)
		}*/
	];
	
	//Shortcuts
	var Manager = Supra.Manager;
	
	var TEMPLATE_STYLES = Supra.Template.compile('\
			<div class="style-item {% if main %}current{% endif %} style-main {{ tag }}" data-tag="{{ tag }}" data-id="">\
				{% set title = "htmleditor.tags." + tag %}\
				{{ title|intl }}\
			</div>\
			{% for key, match in matches %}\
				<div class="style-item {% if classname == match.classname %}current{% endif %} style-class" data-tag="{{ tag }}" data-id="{{ match.classname }}" style="{{ match.style }}">\
					{{ match.attributes.title }}\
				</div>\
			{% endfor %}\
		');
	
	
	/*
	 * Style plugin handles P, H1-H5 tag change and tag styling using
	 * dropdown menus
	 */
	Supra.HTMLEditor.addPlugin('styles', defaultConfiguration, {
		
		/**
		 * Formats plugin instance
		 * @type {Object}
		 */
		pluginFormats: null,
		
		/**
		 * Selectors grouped by tags
		 * @type {Object}
		 */
		selectors: {},
		
		/**
		 * Sidebar styles element
		 * @type {Object}
		 */
		sidebarElement: null,
		
		/**
		 * Nodes which styles are being changed
		 * @type {Array}
		 */
		targetNodes: [],
		
		/**
		 * List of tags, which can't be styled
		 * @type {Object}
		 */
		excludeList: {},
		
		/**
		 * Event listeners
		 * @type {Array}
		 */
		listeners: [],
		
		/**
		 * Node which is used for highlighting
		 * @type {Object}
		 */
		highlightNode: null,
		
		
		/**
		 * Add tag names to list of tags which will not be in the list
		 * 
		 * @param {Array} tagNames Tag names
		 */
		excludeTags: function (tagNames) {
			if (!Y.Lang.isArray(tagNames)) tagNames = [tagNames];
			for(var i=0,ii=tagNames.length; i<ii; i++) {
				this.excludeList[tagNames[i].toUpperCase()] = true;
			}
		},
		
		
		/* -------------------------------------- CSS parsing ------------------------------------------ */
		
		
		/**
		 * Filter out selectors, which doesn't match current container
		 */
		getSelectors: function () {
			var container = this.htmleditor.get('srcNode'),
				result = this.htmleditor.get("stylesheetParser").getSelectorsByNodeMatch(container);
			
			//Text noeds can changed be changed between P, H1, H2, H3 and H4
			//add these to the selector list to allow them changing even if there are
			//no styles in the list
			if (!result['P'])  result['P'] = [];
			if (!result['H1']) result['H1'] = [];
			if (!result['H2']) result['H2'] = [];
			if (!result['H3']) result['H3'] = [];
			if (!result['H4']) result['H4'] = [];
			
			return result;
		},
		
		
		
		
		/**
		 * Update style dropdown values
		 */
		updateStylesList: function () {
			var group = "text", //this.dropdownTypes.get('value'),
				groups = GROUPS,
				tags = null,
				tagsArr = null,
				i = 0,
				ii = groups.length;
			
			//Find tags
			for(var i=0,ii=groups.length; i<ii; i++) {
				if (groups[i].id == group) {
					tags = groups[i].tagsObject;
					tagsArr = groups[i].tags;
					break;
				}
			}
			
			//Check nodes
			var targetNodes = this.targetNodes,
				node = null,
				selectors = this.selectors,
				tag = null,
				matchesObject = {},
				matches = [],
				classnames = [],
				classname = '',
				c = 0,
				cc = 0;
			
			for(i=0,ii=targetNodes.length; i<ii; i++) {
				node = new Y.Node(targetNodes[i].node);
				tag = targetNodes[i].tag;
				
				//Check if tag is in this group
				if (tags[tag] && selectors[tag]) {
					//Find matching classname
					classname = '';
					classnames = selectors[tag];
					c = 0;
					cc = classnames.length;
					
					for(; c<cc; c++) {
						if (node.hasClass(classnames[c].classname)) {
							classname = classnames[c].classname;
							break;
						}
					}
					
					matchesObject[tag] = true;
					matches.push({'tag': tag, 'main': !classname, 'classname': classname, 'matches': selectors[tag]});
				}
			}
			
			//Add styles for P, H1-H4
			if (group == 'text') {
				if (!matchesObject['P'])  matches.push({'tag': 'P',  'main': false, 'classname': '', 'matches': selectors['P']  || []});
				if (!matchesObject['H1']) matches.push({'tag': 'H1', 'main': false, 'classname': '', 'matches': selectors['H1'] || []});
				if (!matchesObject['H2']) matches.push({'tag': 'H2', 'main': false, 'classname': '', 'matches': selectors['H2'] || []});
				if (!matchesObject['H3']) matches.push({'tag': 'H3', 'main': false, 'classname': '', 'matches': selectors['H3'] || []});
				if (!matchesObject['H4']) matches.push({'tag': 'H4', 'main': false, 'classname': '', 'matches': selectors['H4'] || []});
			}
			
			//Sort array
			matches.sort(function (a, b) {
				var a_i = Y.Array.indexOf(tagsArr, a.tag),
					b_i = Y.Array.indexOf(tagsArr, b.tag);
				
				return (a_i == b_i ? 0 : (a_i > b_i ? 1 : -1));
			});
			
			this.renderStylesList(matches);
		},
		
		/**
		 * Render style list
		 * 
		 * @param {Array} matches Tags and matching selectors
		 */
		renderStylesList: function (matches) {
			var tag = null,
				i = 0,
				ii = matches.length,
				html = '',
				node = this.sidebarElement;
			
			if (!node) return;
			
			for(; i<ii; i++) {
				html += TEMPLATE_STYLES(matches[i]);
			}
			
			node.set('innerHTML', html);
		},
		
		
		/* -------------------------------------- HTML editor ---------------------------------------- */
		
		
		/**
		 * When node changes update type dropdown values
		 * @param {Object} event
		 */
		handleNodeChange: function (event) {
			var allowEditing = this.htmleditor.editingAllowed,
				
				selectedNode = this.htmleditor.getSelectedElement(),
				node = selectedNode,
				srcNode = this.htmleditor.get('srcNode'),
				selectors = this.selectors,
				tagNames = null,
				targetNodes = this.targetNodes = [],
				i = 0,
				ii = 0,
				groups = {'text': true},	/* Text group is always available */
				includedTags = {};			/* List of tags already included in the list */
			
			if (this.htmleditor.getSelectedElement('svg,img')) {
				//Image & icon are special elements, we don't want to allow changing style while
				//one of them is selected
				allowEditing = false;
			} else {
				//Traverse up the tree and find tags which has selectors
				while(node && !srcNode.compareTo(node)) {
					//All tagnames for this node, SPAN may have more than 1 tag name
					//because its style may match B, U, I, S
					tagNames = this.htmleditor.getNodeTagName(node);
					
					for(i=0,ii=tagNames.length; i<ii; i++) {
						//If such tag is not in the list already and there are
						//selectors for this tag
						if (!includedTags[tagNames[i]] && selectors[tagNames[i]]) {
							
							if (selectors[tagNames[i]].length) {
								groups[selectors[tagNames[i]][0].group] = true;
							}
							
							targetNodes.push({'node': node, 'tag': tagNames[i]});
							includedTags[tagNames[i]] = true;
							
							break;
						}
					}
					node = node.parentNode;
				}
			}
			
			/*
			//If there are no selectors for this or parent elements then show selectors which are for all tags
			if ('' in selectors && !srcNode.compareTo(selectedNode)) {
				tagNames = this.htmleditor.getNodeTagName(selectedNode);
				this.targetNode = selectedNode;
				this.fillOptions(selectedNode, tagNames[0]);
				
				return;
			}
			*/
			
			// Show / hide groups
			/*
			var nodes = this.dropdownTypesNodes,
				prevValue = this.dropdownTypes.get('value'),
				value = null;
			
			for(i in nodes) {
				if (groups[i]) {
					nodes[i].removeClass('hidden');
					if (value === null || prevValue == i) {
						value = i;
					}
				} else {
					nodes[i].addClass('hidden');
				}
			}
			
			this.dropdownTypes.set('value', value);
			*/
			this.htmleditor.get('toolbar').getButton('style').set('disabled', !allowEditing);
			
			this.updateStylesList();
		},
		
		/**
		 * When editing allowed changes update sidebar visibility 
		 * @param {Object} event
		 */
		handleEditingAllowChange: function (event) {
			if (!event.allowed) {
				this.hideStylesSidebar();
			}
			
			this.htmleditor.get('toolbar').getButton('style').set('disabled', !event.allowed);
		},
		
		/**
		 * Highlight content element
		 * 
		 * @param {Object} element Element which needs to be highlighted
		 */
		highlightElement: function (element) {
			var node = element ? Y.Node(element) : null;
			if (node) {
				if (!this.highlightNode) {
					this.highlightNode = Y.Node.create('<div class="su-element-overlay"></div>');
					
					var doc = Y.Node(this.htmleditor.get('doc'));
					doc.one('body').append(this.highlightNode);
				}
				
				var offset = node.get('region');
				this.highlightNode.setStyles({
					'left': offset.left - 2,		//2px border
					'top': offset.top - 2,			//2px border
					'width': offset.width,
					'height': offset.height,
					'display': 'block'
				});
			} else {
				if (this.highlightNode) {
					this.highlightNode.setStyle('display', 'none');
				}
			}
		},
		
		/**
		 * Highlight element closest to selection which matches tag
		 * 
		 * @param {String} tag Tag name
		 */
		highlightElementByTag: function (tag) {
			if (tag) {
				var match = this.getMatchingTarget(tag);
				if (match) {
					this.highlightElement(match.target.node);
				}
			} else {
				this.highlightElement(null);
			}
		},
		
		highlightElementByEvent: function (e) {
			var item		= e.target.closest('.style-item'),
				tag			= item.getAttribute('data-tag');
			
			//While dropdown is closing it's possible to hover one of the items
			//but in this case we don't want to highlight anything
			if (e.type == 'mouseenter' && this.settings_form && this.settings_form.get('visible')) {
				this.highlightElementByTag(tag);
			} else if (e.type == 'mouseleave') {
				this.highlightElementByTag(null);
			}
		},
		
		
		/* -------------------------------------- Style change ---------------------------------------- */
		
		/**
		 * Returns object with 'target' and 'exact' values
		 * Exact is true if exact match was found or false if similar type node is returned
		 * Searching for P, H1 - H5 any of P, H1 - H5 can be returned
		 * 
		 * @param {String} tag Tag name
		 * @return Object with 'target' and 'exact'
		 * @type {Object}
		 */
		getMatchingTarget: function (tag) {
			var targets = this.targetNodes,
				target = null,
				t = 0,
				tt = targets.length,
				exact = true;
			
			//Find item in target list which classname needs to be changed
			for(; t<tt; t++) {
				if (targets[t].tag == tag) {
					target = targets[t];
					break;
				}
			}
			
			//Element with exact tag not found
			//Find similar element
			if (!target) {
				for(t = 0; t<tt; t++) {
					if (targets[t].tag == 'P' || targets[t].tag == 'H1' || targets[t].tag == 'H2' || targets[t].tag == 'H3' || targets[t].tag == 'H4' || targets[t].tag == 'H5') {
						target = targets[t];
						break;
					}
				}
				
				if (!target) {
					return null;
				} else {
					exact = false;
				}
			}
			
			return {
				'target': target,
				'exact': exact
			};
		},
		
		/**
		 * Update element style
		 * 
		 * @param {Event} e Event facade object
		 */
		updateStyle: function (e) {
			if (this.htmleditor.get('disabled')) return;
			
			var item		= e.target.closest('.style-item'),
				tag			= item.getAttribute('data-tag'),
				classname	= item.getAttribute('data-id'),
				
				match		= this.getMatchingTarget(tag),
				target 		= match ? match.target : null,
				changeTag	= match ? !match.exact : true;
			
			//Remove highlight
			this.highlightElement(null);
			
			if (!target) {
				target = this.htmleditor.getSelectedElement();
				target = target ? this.htmleditor.closest(target, 'H1, H2, H3, H4, H5, P, LI') : null;
				
				if (target && target.tagName == 'LI') {
					//List item is selected, wrap all inner nodes
					this.wrapContents(Y.Lang.toArray(target.childNodes), tag, classname);
					this.htmleditor.refresh(true);
				} else {
					//No matching elements were found, user has selected simple text
					//Create P element
					this.htmleditor.exec((tag || 'p').toLowerCase());
					this.htmleditor.refresh(true);
					
					//Style newly created element
					if (classname) {
						var node = this.htmleditor.getSelectedElement();
						if (node) {
							node = Y.Node(node).closest('H1, H2, H3, H4, H5, P');
							
							//Set new class
							if (node) {
								node.addClass(classname);
							}
						}
					}
				}
			} else {
				
				var node = Y.Node(target.node),
					classnames = this.selectors[target.tag],
					c = 0,
					cc = classnames.length;
				
				//Remove previous class
				for(; c<cc; c++) {
					node.removeClass(classnames[c].classname);
				}
				
				//Change tag
				if (changeTag) {
					//Commands are case-sensitive and are lower case
					this.htmleditor.exec(tag.toLowerCase());
					this.htmleditor.refresh(true);
					
					//Find newly created element
					node = this.htmleditor.getSelectedElement();
					if (node) {
						node = Y.Node(node).closest('H1, H2, H3, H4, H5, P');
					}
				}
				
				//Set new class
				if (node && classname) {
					node.addClass(classname);
				}
			}
		},
		
		/**
		 * Wrap 'node' inside a node
		 * Block level elements are traversed and their inline children and text are wrapped
		 * Heading and paragraph tags are replaced with 'tag'
		 * 
		 * @param {HTMLElement|Text} node Node to wrap
		 * @param {String} tag Tag name to wrap content inside
		 * @param {String} className Optional class to add to wrapped tag
		 * @private
		 */
		wrapContents: function (node, tag, className) {
			var replace    = {'h1': true, 'h2': true, 'h3': true, 'h4': true, 'h5': true, 'p': true},
				inline     = Supra.HTMLEditor.ELEMENTS_INLINE,
				htmleditor = this.htmleditor,
				selected   = null,
				
				createNode = function (content, replace) {
					var node = document.createElement(tag),
						old_node = null;
					
					if (className) {
						node.className = className;
					}
					if (content) {
						if (replace) {
							htmleditor.insertBefore(node, content);
							old_node = content;
							content = content.childNodes ? Y.Lang.toArray(content.childNodes) : [content];
						}
						if (Y.Lang.isArray(content)) {
							if (content.length) {
								if (!replace) {
									htmleditor.insertBefore(node, content[0]);
								}
								for (var i=0,ii=content.length; i<ii; i++) {
									node.appendChild(content[i]);
								}
							}
						} else {
							htmleditor.insertBefore(node, content);
							node.appendChild(content);
						}
						if (replace) {
							old_node.parentNode.removeChild(old_node);
						}
					}
					
					return node;
				},
				
				traverse = function (nodes) {
					var i = 0,
						ii = nodes.length,
						first = null,
						node = null,
						tagName = null;
					
					for (; i<ii; i++) {
						if (nodes[i].nodeType == 1) {
							tagName = nodes[i].tagName.toLowerCase();
							if (tagName in replace) {
								// Replace tag
								node = createNode(nodes[i], true);
								first = first || node;
							} else if (!(tagName in inline)) {
								// Traverse children, if tag is not inline
								traverse(Y.Lang.toArray(nodes[i].childNodes));
								// Reset node, so that it's created for next matching item
								// to preserve correct tag order
								node = null;
							} else {
								// Inline node, wrap inside a tag
								if (node) {
									// We already have a tag, append content to it
									node.appendChild(nodes[i]);
								} else {
									// Create a tag
									node = createNode(nodes[i]);
									first = first || node;
								}
							}
						} else if (nodes[i].nodeType == 3 && htmleditor.getNodeLength(nodes[i])){
							// Non empty text node
							if (node) {
								// We already have a tag, append content to it
								node.appendChild(nodes[i]);
							} else {
								// Create a tag
								node = createNode(nodes[i]);
								first = first || node;
							}
						}
					}
					
					return node;
				};
			
			if (Y.Lang.isArray(node)) {
				selected = traverse(node);
			} else {
				selected = traverse([node]);
			}
			
			if (selected) {
				htmleditor.selectNode(selected);
			}
		},
		
		
		/* -------------------------------------- Sidebar ---------------------------------------- */
		
		
		/**
		 * Create styles sidebar
		 */
		createStylesSidebar: function () {
			//Get form placeholder
			var content = Manager.getAction('PageContentSettings').get('contentInnerNode');
			if (!content) return;
			
			//Properties form
			var form_config = {
				'inputs': [],
				'style': 'vertical'
			};
			
			var form = new Supra.Form(form_config);
				form.render(content);
				form.hide();
			
			var node = this.sidebarElement = Y.Node.create('<div class="style-list"></div>');
			form.get('contentBox').append(node);
			
			//When user selects a value, update content
			this.listeners.push(
				node.delegate('click', this.updateStyle, '.style-item', this)
			);
			this.listeners.push(
				node.delegate('mouseenter', this.highlightElementByEvent, '.style-item', this)
			);
			this.listeners.push(
				node.delegate('mouseleave', this.highlightElementByEvent, '.style-item', this)
			);
			
			this.settings_form = form;
			return form;
		},
		
		/**
		 * Show styles sidebar
		 */
		showStylesSidebar: function () {
			//Make sure PageContentSettings is rendered
			var form = this.settings_form || this.createStylesSidebar(),
				action = Manager.getAction('PageContentSettings');
			
			if (!form) {
				if (action.get('loaded')) {
					if (!action.get('created')) {
						action.renderAction();
						this.showStylesSidebar();
					}
				} else {
					action.once('loaded', function () {
						this.showStylesSidebar();
					}, this);
					action.load();
				}
				return false;
			}
			
			var node = this.node = Y.Node.create('<div></div>');
			form.get('contentBox').append(node);
			
			if (!Manager.getAction('PageToolbar').hasActionButtons("htmleditor-plugin")) {
				Manager.getAction('PageToolbar').addActionButtons("htmleditor-plugin", []);
				Manager.getAction('PageButtons').addActionButtons("htmleditor-plugin", []);
			}
			
			action.execute(form, {
				'doneCallback': Y.bind(this.hideStylesSidebar, this),
				'hideCallback': Y.bind(this.onStyleSidebarHide, this),
				
				'title': Supra.Intl.get(['htmleditor', 'styles']),
				'scrollable': true,
				'toolbarActionName': 'htmleditor-plugin'
			});
			
			//Render list
			this.updateStylesList();
			
			//Style toolbar button
			this.htmleditor.get('toolbar').getButton('style').set('down', true);
		},
		
		/**
		 * Hide styles sidebar
		 */
		hideStylesSidebar: function () {
			if (this.settings_form && this.settings_form.get('visible')) {
				Manager.PageContentSettings.hide();
			}
		},
		
		/**
		 * When styles sidebar is hidden update toolbar button to reflect that
		 * 
		 * @private
		 */
		onStyleSidebarHide: function () {
			//Unstyle toolbar button
			this.htmleditor.get('toolbar').getButton('style').set('down', false);
		},
		
		
		/* -------------------------------------- Plugin ---------------------------------------- */
		
		
		/**
		 * Initialize plugin for editor,
		 * Called when editor instance is initialized
		 * 
		 * @param {Object} htmleditor HTMLEditor instance
		 * @constructor
		 */
		init: function (htmleditor) {
			var toolbar = htmleditor.get('toolbar');
			
			toolbar.getButton('style').set('visible', true);
			
			this.pluginFormats = htmleditor.getPlugin('formats');
			this.excludeList = {};
			this.targetNodes = [];
			this.selectors = this.getSelectors();
			this.listeners = [];
			
			htmleditor.addCommand('style', Y.bind(this.showStylesSidebar, this));
			
			//When un-editable node is selected disable toolbar button
			this.listeners.push(
				htmleditor.on('editingAllowedChange', this.handleEditingAllowChange, this)
			);
			this.listeners.push(
				htmleditor.on('nodeChange', this.handleNodeChange, this)
			);
		},
		
		/**
		 * Clean up after plugin
		 * Called when editor instance is destroyed
		 */
		destroy: function () {
			this.pluginFormats = null;
			this.excludeList = null;
			this.targetNodes = null;
			this.selectors = null;
			this.sidebarElement = null;
			
			if(this.settings_form) {
				this.settings_form.destroy();
			}
			
			for(var i=0,ii=this.listeners.length; i<ii; i++) {
				this.listeners[i].detach();
			}
			
			this.listeners = null;
		}
		
	});
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['supra.htmleditor-base', 'supra.template']});