/**
 * Style dropdown
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
		},
		{
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
		}
	];
	
	var TEMPLATE_STYLES = Supra.Template.compile('\
			<div class="yui3-input-select-item {% if main %}current{% endif %} style-main {{ tag }}" data-tag="{{ tag }}" data-id="">\
				{% set title = "htmleditor.tags." + tag %}\
				{{ title|intl }}\
			</div>\
			{% for key, match in matches %}\
				<div class="yui3-input-select-item {% if classname == match.classname %}current{% endif %} style-class" data-tag="{{ tag }}" data-id="{{ match.classname }}" style="{{ match.style }}">\
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
		 * Toolbar types dropdown element
		 * @type {Object}
		 */
		dropdownTypes: null,
		
		/**
		 * Toolbar types dropdown option elements
		 * @type {Object}
		 */
		dropdownTypesNodes: null,
		
		/**
		 * Toolbar styles dropdown element
		 * @type {Object}
		 */
		dropdownStyles: null,
		
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
		
		/**
		 * Returns group ID by tag
		 * 
		 * @param {String} tag Tag name
		 * @return Group ID
		 * @type {String}
		 */
		getGroupByTag: function (tag) {
			var groups = GROUPS,
				i = 0,
				ii = groups.length;
			
			for(; i<ii; i++) {
				if (groups[i].tagsObject[tag]) return groups[i].id;
			}
			
			return null;
		},
		
		
		/* -------------------------------------- CSS parsing ------------------------------------------ */
		
		
		/**
		 * Parse and return CSS selector attribute values
		 * 
		 * @param {Array} match Selector match
		 * @return Object with attribute names and values
		 * @type {Object}
		 */
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
		
		/**
		 * Parses style selectors and extract dropdown values
		 * 
		 * @param {Array} result List of selectors
		 * @return List of dropdown values
		 * @type {Object}
		 */
		parseStyleSelectors: function (result) {
			var i = 0,
				imax = result.length,
				selector,
				match,
				list = [],
				tmp = null,
				regex_normal = /(.+\s)?([a-z0-9]+)?\.([a-z0-9\-\_]+)\s?(\[([^\]]+)\])?\s?\{([^\}]*)\}/i,
				regex_reverse = /(.+\s)?([a-z0-9]+)?(\[([^\]]+)\])?\.([a-z0-9\-\_]+)\s?\{([^\}]*)\}/i;
			
			for(; i < imax; i++) {
				selector = result[i].replace('#su-style-dropdown ', '');
				
				//Format is .selector tag.classname[attribute]{css}
				match = selector.match(regex_normal);
				
				//Need to support also: .selector tag[attribute].classname{css}
				if (match && !match[1] && !match[2] && !match[4] && !match[5]) {
					match = selector.match(regex_reverse);
					
					//Fix incorrect indexes
					tmp = match[4]; match[4] = match[5]; match[5] = tmp;
					tmp = match[3]; match[3] = match[4]; match[4] = tmp;
				}
				
				if (match) {
					tmp = match[2] ? match[2].toUpperCase() : '';
					list.push({
						'path': match[1] ? match[1].replace(/^\s+|\s+$/g, '') : null,
					    'tag': tmp,
					    'group': this.getGroupByTag(tmp),
					    'classname': match[3],
					    'attributes': this.parseSelectorAttributes(match),
					    'style': match[6]
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
		 * Traverse stylesheets and extract styles for "Style" dropdown box
		 * 
		 * @return List of selectors
		 * @type {Object}
		 */
		collectStyleSelectors: function () {
			var result = [],
				rules,
				doc = new Y.Node(this.htmleditor.get('doc')),
				links = doc.all('link[rel="stylesheet"]'),
				link = null;
			
			if (links) {
				for(var i=0,ii=links.size(); i<ii; i++) {
					link = links.item(i).getDOMNode();
					if (link.sheet) {
						rules = link.sheet.cssRules;
						for(var k=0,kk=rules.length; k<kk; k++) {
						    if (rules[k].selectorText && rules[k].cssText.indexOf('#su-style-dropdown') != -1) {
						        result.push(rules[k].cssText);
						    }
						}
					}
				}
			}
			
			var style = doc.all('style[type="text/css"]'),
				regex = /#su\-style\-dropdown [^}]*}/g,
				css,
				match;
			
			if (style) {
				for(var i=0,ii=style.size(); i<ii; i++) {
					css = style.item(i).get('innerHTML');
					match = css.match(regex);
					
					if (match) {
						result = result.concat(match);
					}
				}
			}
			
			result = this.parseStyleSelectors(result);
			return this.filterSelectors(result);
		},
		
		
		
		
		/**
		 * Update style dropdown values
		 */
		updateStylesDropdown: function () {
			var group = this.dropdownTypes.get('value'),
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
			
			this.renderStylesDropdown(matches);
		},
		
		/**
		 * Fill style dropdown
		 * 
		 * @param {Array} matches Tags and matching selectors
		 */
		renderStylesDropdown: function (matches) {
			var tag = null,
				i = 0,
				ii = matches.length,
				html = '',
				node = this.dropdownStyles.get('contentNode');
			
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
			var allowEditing = this.htmleditor.editingAllowed;
			
			var selectedNode = this.htmleditor.getSelectedElement(),
				node = selectedNode,
				srcNode = this.htmleditor.get('srcNode'),
				selectors = this.selectors,
				tagNames = null,
				targetNodes = this.targetNodes = [],
				i = 0,
				ii = 0,
				groups = {'text': true},	/* Text group is always available */
				includedTags = {};			/* List of tags already included in the list */
			
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
			
			//Close dropdowns
			if (this.dropdownTypes.get('opened')) this.dropdownTypes.set('opened', false);
			if (this.dropdownStyles.get('opened')) this.dropdownStyles.set('opened', false);
		},
		
		/**
		 * When editing allowed changes update dropdown state 
		 * @param {Object} event
		 */
		handleEditingAllowChange: function (event) {
			this.dropdownTypes.set('disabled', !event.allowed);
			this.dropdownStyles.set('disabled', !event.allowed);
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
					this.highlightNode = Y.Node.create('<div class="yui3-element-overlay"></div>');
					
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
			var item		= e.target.closest('.yui3-input-select-item'),
				tag			= item.getAttribute('data-tag');
			
			//While dropdown is closing it's possible to hover one of the items
			//but in this case we don't want to highlight anything
			if (e.type == 'mouseenter' && this.dropdownStyles.get('opened')) {
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
			
			var item		= e.target.closest('.yui3-input-select-item'),
				tag			= item.getAttribute('data-tag'),
				classname	= item.getAttribute('data-id'),
				
				match		= this.getMatchingTarget(tag),
				target 		= match ? match.target : null,
				changeTag	= match ? !match.exact : true;
			
			//Remove highlight
			this.highlightElement(null);
			
			if (!target) {
				//No matching elements were found, user has selected simple text
				//Create P element
				this.htmleditor.exec('p');
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
			
			//Close dropdown
			this.dropdownStyles.set('opened', false);
		},
		
		
		/* -------------------------------------- Plugin ---------------------------------------- */
		
		/**
		 * Returns type dropdown nodes
		 * 
		 * @return Dropdown nodes
		 * @type {Object}
		 */
		getTypeDropdownNodes: function () {
			var dropdownTypes = this.dropdownTypes,
				dropdownStyles = this.dropdownStyles,
				content = dropdownTypes.get('contentNode'),
				nodes = null;
			
			if (!dropdownTypes.get('values').length) {
				dropdownTypes.set('values', GROUPS);
				dropdownStyles.set('values', [{
					'id': '',
					'title': Supra.Intl.get(['htmleditor', 'style'])
				}]);
			}
			
			
			return dropdownTypes.getValueNodes();
		},
		
		/**
		 * Initialize plugin for editor,
		 * Called when editor instance is initialized
		 * 
		 * @param {Object} htmleditor HTMLEditor instance
		 * @constructor
		 */
		init: function (htmleditor) {
			var toolbar = htmleditor.get('toolbar');
			
			this.pluginFormats = htmleditor.getPlugin('formats');
			this.excludeList = {};
			this.targetNodes = [];
			this.selectors = this.collectStyleSelectors();
			this.dropdownTypes = toolbar.getControl('type');
			this.dropdownStyles = toolbar.getControl('style');
			this.dropdownTypesNodes = this.getTypeDropdownNodes();
			this.listeners = [];
			
			//When style dropdown is shown update style list
			this.listeners.push(
				this.dropdownStyles.on('openedChange', function (e) {
					if (!this.htmleditor.get('disabled') && e.prevVal != e.newVal && e.newVal) {
						this.updateStylesDropdown();
					}
				}, this)
			);
			
			//When user selects a value, update content
			this.listeners.push(
				this.dropdownStyles.get('contentNode').delegate('click', this.updateStyle, '.yui3-input-select-item', this)
			);
			this.listeners.push(
				this.dropdownStyles.get('contentNode').delegate('mouseenter', this.highlightElementByEvent, '.yui3-input-select-item', this)
			);
			this.listeners.push(
				this.dropdownStyles.get('contentNode').delegate('mouseleave', this.highlightElementByEvent, '.yui3-input-select-item', this)
			);
			
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
			this.dropdownTypes = null;
			this.dropdownStyles = null;
			this.dropdownTypesNodes = null;
			
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