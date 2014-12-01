/**
 * Block level element formatting (UL, OL)
 */
YUI().add('supra.htmleditor-plugin-lists', function (Y) {
	
	var HTMLEditor = Supra.HTMLEditor,
		Nodes = HTMLEditor.Nodes;
	
	var defaultConfiguration = {
		/* Modes which plugin supports */
		modes: [HTMLEditor.MODE_SIMPLE, HTMLEditor.MODE_RICH],
		
		/* List types */
		lists: ['ul', 'ol']
	};
	
	HTMLEditor.addPlugin('lists', defaultConfiguration, {
		
		lists:  null,
		commands: {'ul': 'insertunorderedlist', 'ol': 'insertorderedlist'},
		
		buttons: {},
		
		/**
		 * Execute command
		 * 
		 * @param {Object} data
		 * @param {String} command
		 */
		exec: function (data, command) {
			var htmleditor = this.htmleditor,
				history = htmleditor.getPlugin('history'),
				doc = htmleditor.get('doc'),
				win = htmleditor.get('win'),
				selection = htmleditor.getSelection(),
				
				res = false;
			
			history.pushTextState();
			
			if (command in this.commands) {
				var selected_li = htmleditor.getSelectedNodes(),
					selected_list = selected_li.closest('ul, ol'),
					is_sublist = selected_list.parent().closest('ul, ol').length;
				
				if (selected_list.length) {
					if (selected_list.first(true).tagName.toLowerCase() === command) {
						// Remove list
						// List content must be either inserted into P tags or
						// separated by BR tags
						var items = selected_list.children();
						
						items.each(function (li) {
							var children = Y.Lang.toArray(li.childNodes),
								node = selected_list.prev(),
								i = 0,
								ii = children.length,
								tag,
								type,
								
								previous_p = null,
								
								insert_break = false,
								container = htmleditor.get('srcNode').getDOMNode(),
								
								ELEMENTS_BLOCK = HTMLEditor.ELEMENTS_BLOCK,
								ELEMENTS_INLINE = HTMLEditor.ELEMENTS_INLINE;
							
							if (is_sublist) {
								// Elements inside lists are separated by BR
								node = selected_list.prev().last(true);
								insert_break = false;
								
								while (node && node !== container) {
									if (node.nodeType === 3 && node.textContent.trim()) {
										// Non-empty text, will need to insert <br /> tag
										insert_break = true;
										break;
									} else if (node.nodeType === 1) {
										tag = node.tagName.toLowerCase();
										if (tag === 'br' || (tag in ELEMENTS_BLOCK)) {
											// Block level element found, no need for <br />
											break;
										} else {
											node = node.lastChild ? node.lastChild : (node.previousSibling ? node.previousSibling : (node.parentNode ? node.parentNode.previousSibling : null));
										}
									} else {
										node = node.previousSibling ? node.previousSibling : (node.parentNode ? node.parentNode.previousSibling : null);
									}
								}
								
								if (insert_break) {
									selected_list.insert(doc.createElement('BR'), 'before');
								}
								selected_list.insert(children, 'before');
							} else {
								// Elements inside main content are placed inside P
								for (; i<ii; i++) {
									node = children[i];
									type = node.nodeType;
									tag  = type === 1 ? node.tagName.toLowerCase() : null;
									
									if (type === 1 || (type === 3 && node.textContent.trim())) {
										// Text or node
										if (type === 3 || tag in ELEMENTS_INLINE) {
											// Text or inline element, copy inside P tag
											if (!previous_p) {
												previous_p = Nodes('P', htmleditor);
												selected_list.insert(previous_p, 'before');
											}
											
											previous_p.insert(node, 'append');
										} else {
											// Block element, copy as is
											previous_p = null;
											selected_list.insert(node, 'before');
										}
									}
								}
							}
							
						});
						
						selected_list.remove();
						res = true;
					} else {
						// Change list
						var node = Nodes(command, htmleditor); // creates element
						node.insert(selected_list.children(), 'append');
						selected_list.insert(node, 'before');
						selected_list.remove();
						res = true;
					}
				} else {
					// Create list
					var res = htmleditor.get('doc').execCommand(this.commands[command], false, null);
					
					// Remove wrapping element if it's P, H1, H2, etc.
					Supra.immediate(this, function () {
						var element = htmleditor.getSelectedElement('UL, OL'),
							parent, tag;
						
						if (element) {
							parent = element.parentNode;
							tag = parent.tagName.toUpperCase();
								
							if (tag == 'H1' || tag == 'H2' || tag == 'H3' || tag == 'H4' || tag == 'H5' || tag == 'H6' || tag == 'P') {
								if (element && !htmleditor.previousSibling(element) && !htmleditor.nextSibling(element)) {
									// Only child
									htmleditor.unwrapNode(parent);
								}
							}
							
							htmleditor.selectNode(element);
						}
					});
				}
				
				if (res) {
					htmleditor._changed();
					history.pushState();
					
					htmleditor.setSelection(selection);
				}
				
				return true;
			} else if (command == 'indent') {
				var groups = htmleditor.getSelectedNodes().closest('li').getGroupedByParents(),
					i = 0,
					ii = groups.length,
					group,
					li, list, sublist;
				
				for (; i<ii; i++) {
					group = groups[i];
					li = group.first().prev(':element');
					list = group.parent();
					
					if (li.length) {
						// There is a previous list item
						sublist = li.children().last(':element');
						
						if (sublist.length && sublist.is(list.first(true).tagName)) {
							// Append to the existing list
							sublist.insert(group, 'append');
						} else {
							// Create a new list inside previous item
							li.insert(
								list.clone().insert(group, 'append'),
								'append'
							);
						}
						
						res = true;
					}
				}
				
				if (res) {
					htmleditor._changed();
					history.pushState();
					
					htmleditor.setSelection(selection);
				}
				
				return true;
			} else if (command == 'outdent') {
				var selected_li = htmleditor.getSelectedNodes().closest('li'),
					groups = selected_li.getGroupedByParents(),
					list = null,
					i = 0,
					ii = groups.length,
					group,
					sublist;
				
				for (; i<ii; i++) {
					group = groups[i];
					
					if (selected_li.contains(group.first().parent().closest('li'))) {
						// All items in this group are children of other selected item,
						// don't do anything
						continue;
					}
					
					list = group.first().parent().closest('ol,ul');
					
					if (list.parent().closest('ol,ul').length) {
						// All following list items added as sub-list to the last list item
						var next = group.nextAll(':element'),
							first = group.is(':first-child');
						
						if (next.length) {
							group.last().insert(
								group.parent().clone().insert(next, 'append'),
								'append'
							);
						}
						
						// Move items to the parent list
						list.closest('li').insert(group, 'after');
						
						if (first) {
							// There are no list items before selection, remove empty list
							list.remove();
						}
					}
					
					res = true;
				}
				
				if (res) {
					htmleditor._changed();
					history.pushState();
					
					htmleditor.setSelection(selection);
				}
				
				return true;
			} else {
				return false;
			}
		},
		
		bindButton: function (format) {
			var htmleditor = this.htmleditor;
			var toolbar = htmleditor.get('toolbar');
			var button = toolbar ? toolbar.getButton(format) : null;
			if (button) {
				this.buttons[format.toUpperCase()] = button;
			}
		},
		
		/**
		 * When node changes update button states
		 * @param {Object} event
		 */
		handleNodeChange: function (event) {
			var allowEditing = this.htmleditor.editingAllowed;
			
			var node = this.htmleditor.getSelectedElement(),
				rootNode = this.htmleditor.get('srcNode').getDOMNode(),
				down = false,
				buttons = this.buttons,
				selected = null,
				i = null;
			
			while (node) {
				if (node.tagName == 'IMG') {
					// Image is special element, while image is selected
					// don't allow editing anything
					allowEditing = false;
					break;
				}
				if (node.tagName in buttons) {
					selected = node.tagName;
					break;
				}
				if (node === rootNode) break;
				node = node.parentNode;
			}
			
			for(i in buttons) {
				buttons[i].set('down', i == selected);
				buttons[i].set('disabled', !allowEditing);
			}
			
			buttons.INDENT.set('visible', !!selected);
			buttons.OUTDENT.set('visible', !!selected);
		},
		
		/**
		 * When editing allowed changes update button states 
		 * @param {Object} event
		 */
		handleEditingAllowChange: function (event) {
			var i,
				disabled = !event.allowed,
				buttons = this.buttons;
			
			for(i in buttons) {
				buttons[i].set('disabled', disabled);
			}
		},
		
		/**
		 * Initialize plugin for editor,
		 * Called when editor instance is initialized
		 * 
		 * @param {Object} htmleditor HTMLEditor instance
		 * @constructor
		 */
		init: function (htmleditor, configuration) {
			if (!configuration) return;
			
			this.lists = (Y.Lang.isArray(configuration.lists) ? configuration.lists : []);
			this.buttons = {};
			
			// Add command
			var lists = ['indent', 'outdent'].concat(this.lists),
				i = 0,
				imax = lists.length,
				execCallback = Y.bind(this.exec, this),
				button;
			
			for(; i < imax; i++) {
				this.htmleditor.addCommand(lists[i], execCallback);
				this.bindButton(lists[i]);
			}
			
			// Show buttons
			lists = this.lists;
			for (i=0, imax=lists.length; i<imax; i++) {
				button = this.buttons[lists[i].toUpperCase()];
				if (button) {
					button.set('visible', true);
				}
			}
			
			//When un-editable node is selected disable toolbar button
			this.htmleditor.on('editingAllowedChange', this.handleEditingAllowChange, this);
			this.htmleditor.on('nodeChange', this.handleNodeChange, this);
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