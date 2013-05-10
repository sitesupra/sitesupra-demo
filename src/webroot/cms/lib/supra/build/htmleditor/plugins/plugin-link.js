YUI().add('supra.htmleditor-plugin-link', function (Y) {
	
	var defaultConfiguration = {
		/* Modes which plugin supports */
		modes: [Supra.HTMLEditor.MODE_SIMPLE, Supra.HTMLEditor.MODE_RICH],
		
		/* String replacements */
		replacements: [
			[
				/[a-z]+:\/\/[a-z0-9\-\.@:]+[a-z0-9](\/[a-z0-9\?#&%\-\_=\(\)\\\/\$\!:,]*)?/ig,
				function (url) { return '<a href="' + url + '" target="_blank">' + url + '</a>'; }
			],
			[
				/([a-z0-9]([a-z0-9\.\-\_]*[a-z0-9])?@[a-z0-9][a-z0-9\-\_]*([\.]([a-z0-9][a-z0-9\-\_]?)?[a-z0-9])*)/ig,
				function (email) { return '<a href="mailto:' + email + '">' + email + '</a>'; }
			]
		]
	};
	
	Supra.HTMLEditor.addPlugin('link', defaultConfiguration, {
		
		/**
		 * Link editor is visible
		 * @type {Boolean}
		 */
		visible: false,
		
		
		/**
		 * Insert link around current selection
		 */
		insertLink: function () {
			if (!this.htmleditor.editingAllowed) return;
			
			var htmleditor = this.htmleditor,
				selection = htmleditor.getSelection();
			
			//If in current selection is a link then edit it instead of creating new
			var nodes = htmleditor.findNodesInSelection(selection, 'a');
			
			if (nodes && nodes.size())
			{
				//Edit selected link
				this.editLink({
					'currentTarget': nodes.item(0)
				});
				
				//Prevent default 
				return false;
			}
			else if (selection.collapsed)
			{
				//Cancel if no text is selected
				return false;
			}
			else if (htmleditor.isSelectionEditable(selection))
			{
				//Show link manager
				this.showLinkManager(null, function (data) {
					this.insertLinkConfirmed(data, selection);
				}, this);
				
				//Prevent default
				return false;
			}
			
			//Nothing was done
			return true;
		},
		
		/**
		 * After user entered value in prompt insert link
		 * 
		 * @param {Object} event
		 */
		insertLinkConfirmed: function (data, selection) {
			if (data && data.href) {
				var htmleditor = this.htmleditor;
				
				//Restore selection
				htmleditor.setSelection(selection);
				
				//Insert link
				var uid = htmleditor.generateDataUID(),
					text = this.htmleditor.getSelectionText(),
					html = '<a id="' + uid + '"' + (data.classname ? ' class="' + data.classname + '"' : '') + (data.target ? ' target="' + data.target + '"' : '') + ' title="' + Y.Escape.html(data.title || '') + '">' + text + '</a>';
				
				data.type = this.NAME;
				htmleditor.setData(uid, data)
				htmleditor.replaceSelection(html, null);
			}
			
			//Trigger selection change event
			this.htmleditor._changed();
			this.visible = false;
			this.htmleditor.refresh(true);
			
			var button = this.htmleditor.get('toolbar').getButton('insertlink');
			if (button) button.set('down', false).set('disabled', true);
		},
		
		/**
		 * Double clicking link must open prompt to enter new link url
		 * 
		 * @param {Object} event Event
		 */
		editLink: function (event) {
			var target = event.currentTarget;
			if (!this.htmleditor.editingAllowed || !this.htmleditor.isEditable(target)) return;
			
			//Get current value
			var data = this.htmleditor.getData(target);
			if (!data) {
				data = {
					'type': this.NAME,
					'title': target.getAttribute('title'),
					'target': target.getAttribute('target'),
					'href': this.normalizeHref(target.getAttribute('href')),
					'resource': 'link',
					'classname': target.getAttribute('class')
				}
			}
			
			this.showLinkManager(data, function (data) {
				this.editLinkConfirmed(data, target);
			}, this);
		},
		
		/**
		 * After user changed link save data into htmleditor and update html
		 * 
		 * @param {Object} event
		 */
		editLinkConfirmed: function (data, target) {
			if (data && data.href) {
				data.type = this.NAME;
				this.htmleditor.setData(target, data);
				
				//Title attribute
				target.setAttribute('title', data.title || '');
				
				//Target attribute
				if (data.target) {
					target.setAttribute('target', data.target);
				} else {
					target.removeAttribute('target');
				}
			} else {
				//Insert all link children nodes before link and remove <A>
				target.insert(target.get('childNodes'), 'before').remove();
			}
			
			//Trigger selection change event
			this.htmleditor._changed();
			this.visible = false;
			this.htmleditor.refresh(true);
			
			var button = this.htmleditor.get('toolbar').getButton('insertlink');
			if (button) button.set('down', false).set('disabled', true);
		},
		
		/**
		 * Normalize link by removing domain
		 * 
		 * @param {String} href
		 * @return Normalized domain
		 * @type {String}
		 */
		normalizeHref: function (href) {
			var domain = document.location.protocol + '//' + document.location.host;
			return String(href || '').replace(domain, '') || '/';
		},
		
		/**
		 * Show link manager
		 * 
		 * @param {String} href
		 * @param {Object} target
		 * @param {Function} callback
		 */
		showLinkManager: function (data, callback, context) {
			if (!callback) return;
			
			Supra.Manager.getAction('LinkManager').once('execute', function () {
				this.visible = true;
			}, this);
			
			Supra.Manager.getAction('LinkManager').execute(data, {
				'mode': 'link',
				'hideToolbar': true
			}, callback, context || this);
		},
		
		/**
		 * Hide link manager
		 */
		hideLinkManager: function () {
			if (this.visible) {
				Supra.Manager.getAction('LinkManager').hide();
				this.visible = false;
				this.htmleditor.refresh();
			}
		},
		
		/**
		 * Show or hide link manager based on toolbar button state
		 */
		toggleLinkManager: function () {
			if (!this.visible) {
				this.insertLink();
			} else {
				this.hideLinkManager();
			}
			
			//return true;
		},
		
		/**
		 * Initialize plugin for editor,
		 * Called when editor instance is initialized
		 * 
		 * @param {Object} htmleditor HTMLEditor instance
		 * @constructor
		 */
		init: function (htmleditor) {
			// Add command
			htmleditor.addCommand('insertlink', Y.bind(this.toggleLinkManager, this));
			
			// When double clicking on link show popup
			var container = htmleditor.get('srcNode');
			container.delegate('click', Y.bind(this.editLink, this), 'a');
			
			var self = this;
			var toolbar = htmleditor.get('toolbar');
			var button = toolbar ? toolbar.getButton('insertlink') : null;
			if (button) {
				button.show();
				
				//When un-editable node is selected disable toolbar button
				htmleditor.on('editingAllowedChange', function (event) {
					button.set('disabled', !event.allowed);
				});
				
				//If there is no text selection disable toolbar button
				htmleditor.on('selectionChange', function (event) {
					var allowEditing = false,
						down = false;
					
					//Check if cursor is inside link
					var node = this.getSelectedElement();
					if (node && node.tagName == 'A') {
						if (this.editingAllowed) {
							allowEditing = true;
							down = self.visible;
						}
					} else if (this.editingAllowed) {
						//Check if there is text selection
						if (!this.selection.collapsed) {
							allowEditing = true;
							down = self.visible;
						}
					}
					
					button.set('disabled', !allowEditing);
					button.set('down', down);
				});
			}
			
			this.visible = false;
			
			//After paste replace links with tags
			htmleditor.on('pasteHTML', this.tagPastedHTML, this);
			
			//When selection changes hide link manager
			htmleditor.on('selectionChange', this.hideLinkManager, this);
			
			//Hide link manager when editor is closed
			htmleditor.on('disable', this.hideLinkManager, this);
		},
		
		/**
		 * Clean up after plugin
		 * Called when editor instance is destroyed
		 */
		destroy: function () {},
		
		
		/**
		 * Parse HTML and replace all email addresses with links
		 */
		parseStrings: function (html) {
			var replacements = this.configuration.replacements,
				k = 0,
				kk = replacements.length,
			
				regex = null,
				replacement = null,
				
				addresses = [],
				match = null,
				pos_match = 0,
				pos_tag_end = 0,
				pos_tag_start = 0,
				tag = null,
				ok = false,
				search = true,
				
				i = 0;
			
			for (; k<kk; k++) {
				regex = replacements[k][0];
				replacement = replacements[k][1];
				
				addresses = [];
				
				while(match = regex.exec(html)) {
					ok = true;
					search = true;
					pos_match = (match.index || regex.lastIndex);
					pos_tag_start = pos_match + 1;
					
					while (search && pos_tag_start > 0) {
						pos_tag_end = html.lastIndexOf('>', pos_tag_start - 1);
						pos_tag_start = html.lastIndexOf('<', pos_tag_start - 1);
						
						if (pos_tag_start != -1) {
							if (pos_tag_end == -1 || pos_tag_end < pos_tag_start) {
								// email address is an attribute
								ok = false;
								search = false;
							} else {
								// check if it is inside <a> tag
								tag = html.substr(pos_tag_start + 1, 2);
								if (tag == '/a') {
									// Found closing a tag, there is no open link tag before email address
									search = false;
								} else if (tag == 'a ') {
									// There is open a before email address, skip
									ok = false;
									search = false;
								} else {
									// continue searching
								}
							}
						} else {
							// No opening tags found
							search = false;
						}
					}
					
					if (ok) {
						addresses.push([pos_match, pos_match + match[0].length, match[0]]);
					}
				}
				
				// Reset, is this even needed?
				regex.lastIndex = 0;
				
				// Replace with <a> tags
				for (i = addresses.length - 1; i >= 0; i--) {
					html = html.substr(0, addresses[i][0]) +
						   replacement(addresses[i][2]) +
						   html.substr(addresses[i][1]);
				}
			}
			
			return html;
		},
		
		/**
		 * Process HTML and replace all nodes with supra tags {supra.link id="..."}
		 * Called before HTML is saved
		 * 
		 * @param {String} html
		 * @return Processed HTML
		 * @type {HTML}
		 */
		tagHTML: function (html) {
			var htmleditor = this.htmleditor,
				NAME = this.NAME;
			
			//Add links to email addresses
			html = this.parseStrings(html);
			
			//Opening tag
			html = html.replace(/<a [^>]*id="([^"]+)"[^>]*>/gi, function (html, id) {
				if (!id) return html;
				var data = htmleditor.getData(id);
				
				if (data && data.type == NAME) {
					//Extract classname
					var classname = html.match(/class="([^"]+)"/);
					data.classname = classname ? classname[1] : '';
					
					return '{supra.' + NAME + ' id="' + id + '"}';
				} else {
					return html;
				}
			});
			
			//Closing tag
			html = html.replace(/<\/a[^>]*>/g, '{/supra.' + NAME + '}');
			
			return html;
		},
		
		/**
		 * Process pasted HTML and add links to data object
		 * Called after paste plugin cleanPastedHTML
		 * 
		 * @param {Object} event Event
		 */
		tagPastedHTML: function (event, data) {
			var htmleditor = this.htmleditor,
				NAME = this.NAME;
			
			//Extract email addresses
			if (event) {
				data.html = this.parseStrings(data.html);
			}
			
			//Opening tag
			data.html = data.html.replace(/<a([^>]*)>/gi, function (html, attrs_html) {
				var attrs = htmleditor.parseTagAttributes(attrs_html),
					id = attrs.id || htmleditor.generateDataUID(),
					data = null;
				
				if (!id || !htmleditor.getData(id)) {
					// Only if there isn't already data
					data = {
						'href': attrs.href || '',
						'resource': 'link',
						'target': attrs.target || '',
						'title': attrs.title || '',
						'classname': attrs['class'] || '',
						'type': 'link'
					};
					htmleditor.setData(id, data, true);
				}
				
				// Remove 'href' because it prevents entering another symbol after last/before first in Chrome
				if ('href' in attrs) {
					attrs_html = attrs_html.replace(/href="?'?[^\s"'>]+'?"?/i, '');
				}
				
				if (attrs.id) {
					return '<a' + attrs_html.replace(/id="?'?[a-z0-9\_]+'?"?/i, 'id="' + id + '"') + '>';
				} else {
					return '<a' + attrs_html + ' id="' + id + '">';
				}
			});
		},
		
		/**
		 * Process HTML and replace all supra tags with nodes
		 * Called before HTML is set
		 * 
		 * @param {String} html HTML
		 * @param {Object} data Data
		 * @return Processed HTML
		 * @type {String}
		 */
		untagHTML: function (html, data) {
			var htmleditor = this.htmleditor,
				NAME = this.NAME,
				self = this,
				tmp  = {'html': html};
			
			//Save data and process normal <a> tags
			this.tagPastedHTML(null, tmp);
			html = tmp.html;
			
			//Opening tags
			html = html.replace(/{supra\.link id="([^"]+)"}/ig, function (tag, id) {
				if (!id || !data[id] || data[id].type != NAME) return '';
				
				var href = self.normalizeHref(data[id].href);
				return '<a id="' + id + '"' + (data[id].classname ? ' class="' + data[id].classname + '"' : '') + (data[id].target ? ' target="' + data[id].target + '"' : '') + ' title="' + Y.Escape.html(data[id].title || '') + '">';
			});
			
			//Closing tags
			html = html.replace(/{\/supra\.link}/g, '</a>');
			
			//Process email addresses
			html = this.parseStrings(html);
			
			return html;
		},
		
		/**
		 * Process data and remove all unneeded before it's sent to server
		 * Called before save
		 * 
		 * @param {String} id Data ID
		 * @param {Object} data Data
		 * @return Processed data
		 * @type {Object}
		 */
		processData: function (id, data) {
			//Remove unneeded data
			delete(data.file_path);
			
			//HREF is needed for external links
			if (data.resource != 'link') delete(data.href);
			
			return data;
		}
		
	});
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['supra.htmleditor-base']});