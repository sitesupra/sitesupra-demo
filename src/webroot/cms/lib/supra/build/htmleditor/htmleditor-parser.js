//Invoke strict mode
"use strict";

YUI().add('supra.htmleditor-parser', function (Y) {
	
	/* Tag white list, all other tags will be removed. <font> tag is added if "fonts" plugin is enabled */
	Supra.HTMLEditor.WHITE_LIST_TAGS = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'b', 'em', 'small', 'sub', 'sup', 'a', 'img', 'br', 'b', 'strong', 's', 'strike', 'u', 'blockquote', 'q', 'big', 'table', 'tbody', 'tr', 'td', 'thead', 'th', 'ul', 'ol', 'li', 'div', 'dl', 'dt', 'dd', 'col', 'colgroup', 'caption', 'object', 'param', 'embed', 'article', 'aside', 'details', 'embed', 'figcaption', 'figure', 'footer', 'header', 'hgroup', 'nav', 'section', '_span'];
	
	/* List of inline elements */
	Supra.HTMLEditor.ELEMENTS_INLINE = {'b': 'b', 'i': 'i', 'span': 'span', 'em': 'em', 'sub': 'sub', 'sup': 'sup', 'small': 'small', 'strong': 'strong', 's': 's', 'strike': 'strike', 'a': 'a', 'u': 'u', 'img': 'img', 'br': 'br', 'q': 'q', 'big': 'big', 'mark': 'mark', 'rp': 'rp', 'rt': 'rt', 'ruby': 'ruby', 'summary': 'summary', 'time': 'time'};
	
	/* List of tags which doesn't need to be closed */
	Supra.HTMLEditor.NOT_CLOSED_TAGS = {'img': 'img', 'br': 'br', 'param': 'param', 'col': 'col', 'embed': 'embed'};
	
	/* Elements which should be checked for inline style */
	Supra.HTMLEditor.STYLED_INLINE   = {'span': 'span', 'b': 'b', 'i': 'i', 'em': 'em', 'sub': 'sub', 'sup': 'sup', 'small': 'small', 'strong':'strong', 's':'s', 'strike': 'strike', 'a': 'a', 'u': 'u', 'q': 'q', 'big': 'big'};
	
	/* Find all tags */
	var REGEXP_FIND_TAGS = /<\/?([a-z][a-z0-9\:]*)\b[^>]*>/gi;
	
	var REGEXP_FIND_CLASS = /class=(([a-z0-9\_\-]+)|"([^"]+)")/i;
	
	/* List of style properties which should be converted into tags */
	var STYLE_TO_TAG = [
		//[TAG, REGEX, KEEP STYLE ATTRIBUTE]
		['b', /font-weight:\s?bold/i],
		['em', /font-style:\s?italic/i],
		['u', /text-decoration:[^"']*underline/i],
		['s', /text-decoration:[^"']*line-through/i],
		['font', /background-color:[^;"']+/i, true] // we keep style, because we want to change only tag
	];
	
	/* List of tagNames and matching regular expressions to find correct tag name */
	var STYLE_TO_TAG_NAME = [
		['B', /font-weight:\s?bold/i],
		['EM', /font-style:\s?italic/i],
		['U', /text-decoration:[^"']*underline/i],
		['S', /text-decoration:[^"']*line-through/i]
	];
	
	Y.mix(Supra.HTMLEditor.prototype, {
		/**
		 * Converts html into browser compatible format
		 * @param {Object} html
		 */
		uncleanHTML: function (html) {
			//Convert <i> into <em>
			html = html.replace(/<(\/?)i((\s[^>]+)?)>/ig, '<$1em$2>');
			
			if (Y.UA.ie) {
				//IE uses STRONG, EM, U, STRIKE instead of SPAN
				html = html.replace(/<(\/?)b((\s[^>]+)?)>/ig, '<$1strong$2>');
				html = html.replace(/<(\/?)s((\s[^>]+)?)>/ig, '<$1strike$2>');
			} else {
				//Convert <strong> into <b>
				html = html.replace(/<(\/?)strong([^>]*)>/g, '<$1b$2>');
				
				//Convert B, EM, U, S into SPAN
				var tagToSpan = [
						['b',  'font-weight: bold'],
						['em', 'font-style: italic'],
						['u',  'text-decoration: underline'],
						['s',  'text-decoration: line-through']
					],
					tag,
					expression;
				
				for(var i=0,ii=tagToSpan.length; i<ii; i++) {
					tag = tagToSpan[i][0];
					
					expression = new RegExp("<" + tag + "(\s[^>]*)?(\\sclass=\"[^\"]+\")?(\\s[^>]*)?>", "ig");
					html = html.replace(expression, '<span style="' + tagToSpan[i][1] + ';" $2>');
					
					expression = new RegExp("<\/" + tag + "(\\s[^>]*)?>", "ig");
					html = html.replace(expression, '</span>');
				}
			}
			
			var event = {'html': html};
			this.fire('uncleanHTML', {}, event);
			
			return event.html;
		},
		
		/**
		 * Converts browser generated markup into valid html
		 * Handles cases like:
		 * 		Input:  <a style="font-weight: bold;">text</a>
		 * 		Output: <a><b>text</b></a>
		 * 
		 * 		Input:  <span style="font-weight: bold; font-style: italic;">text</span>
		 * 		Output: <b><i>text</i></b>
		 *
		 * 		Input:  <span style="text-decoration: underline line-through;">text</span>
		 * 		Output: <u><s>text</s></u>
		 *  
		 * @param {Object} html
		 */
		cleanHTML: function (html) {
			var mode = this.get('mode');
			
			if (mode == Supra.HTMLEditor.MODE_STRING) {
				//In string mode there is nothing to clean up
			} else {
				//IE creates STRONG, EM, U, STRIKE instead of SPAN
				if (Y.UA.ie) {
					html = html.replace(/<(\/?)strong/ig, '<$1b');
					html = html.replace(/<(\/?)strike/ig, '<$1s');
				}
				
				//Remove YUI ids from nodes
				html = html.replace(/\s+id="yui_[^"]*"/gi, '');
				
				//Remove un-editable classnames
				html = html.replace(/su\-(un)?editable/gi, '');
				
				//Replace styles with tags
				var regexTag = /<(\/?)([a-z]+)/,
					tagOpenIndex = html.indexOf('<'),
					tagCloseIndex = -1,
					tag = null,
					tagName = '',
					tagClosing = false,
					tagStack = [];
				
				while(tagOpenIndex != -1) {
					tagCloseIndex = html.indexOf('>', tagOpenIndex);
					if (tagCloseIndex != -1) {
						tag = this.cleanTag(html.substring(tagOpenIndex + 1, tagCloseIndex));
						if (tag) {
							if (typeof tag === 'string') {
								//Closing tag
								if (tagStack.length && tagStack[0][0] == tag) {
									//Get item from stack
									tag = tagStack.shift();
									
									if (tag[3]) {
										//Remove existing tag
										html = html.substr(0, tagOpenIndex) + tag[2] + html.substr(tagCloseIndex + 1);
										
										//Update index
										tagOpenIndex += tag[2].length - 1;
									} else {
										//Keep existing tag
										html = html.substr(0, tagOpenIndex) + tag[2] + html.substr(tagOpenIndex);
										
										//Update index
										tagOpenIndex = tagCloseIndex + tag[2].length - 1;
									}
								}
							} else {
								//Add item to stack
								tagStack.unshift(tag);
								
								if (tag[3]) {
									//Remove existing tag
									html = html.substr(0, tagOpenIndex) + tag[1] + html.substr(tagCloseIndex + 1);
									
									//Update index
									tagOpenIndex += tag[1].length - 1;
								} else {
									//Keep existing tag
									var tmp = html.substr(tagOpenIndex, tagCloseIndex + 1 - tagOpenIndex).replace(/style=("[^"]*"|'[^']*')/, '');
									
									html =  html.substr(0, tagOpenIndex) +
											tmp +
											tag[1] +
											html.substr(tagCloseIndex + 1);
									
									//Update index
									tagOpenIndex += tmp.length + tag[1].length - 1;
								}
							}
						}
					}
					
					tagOpenIndex = html.indexOf('<', tagOpenIndex + 1);
				}
				
				//Convert <strong> into <b>
				html = html.replace(/<(\/?)strong([^>]*)>/g, '<$1b$2>');
				
				//Convert <i> into <em>
				html = html.replace(/<(\/?)i((\s[^>]+)?)>/g, '<$1em$2>');
				
				//Moves whitespaces outside <A> tags
				html = html.replace(/(<a [^>]+>)\s/g, ' $1');
				html = html.replace(/\s(<\/a[^>]*>)/g, '$1 ');
				
				//Moves <BR> outside <A> tags
				html = html.replace(/<br\s*\/?>\s*(<\/a[^>]*>)/gi, ' $1<br />');
				
				//Remove tags, which are not white-listed (SPAN is also removed)
				var white_list_tags = Supra.HTMLEditor.WHITE_LIST_TAGS;
				if (this.getPlugin("fonts")) {
					white_list_tags.push("font")
				}
				html = this.stripTags(html, white_list_tags);
				
				//Convert <_ into <
				html = html.replace(/<(\/?)_/g, '<$1');
				
				//Remove empty UL and OL tags
				html = html.replace(/<(ul|ol)>[\s\r\n]*?<\/(ul|ol)>/gi, '');
				
				//Remove style attribute, except background-color
				html = html.replace(/\s+style=["']([^'"]*)["']/gi, function (all, styles) {
					styles = styles.match(/background-color:[^;]+/);
					if (styles && styles.length) {
						return ' style="' + styles[0] + '"';
					}
					return '';
				});
				
				//Remove empty class attributes
				html = html.replace(/class="\s*"/g, '');
				
				//Remove YUI classnames
				html = html.replace(/(yui3\-table\-selected|yui3\-cell\-selected)/g, '');
			}
			
			//Fire event to allow plugins to clean up after themselves
			var event = {'html': html};
			this.fire('cleanHTML', {}, event);
			
			return event.html || '';
		},
		
		/**
		 * Convert <span> and other tag style="" attributes into tags
		 * 
		 * @return Tag name, opening and closing HTML
		 * @private 
		 */
		cleanTag: function (html) {
			var tagName = html.match(/^(\/?)([a-z]+)/i);
			if (!tagName) return null;
			
			var styleToTag = STYLE_TO_TAG,
				k = 0,
				kk = styleToTag.length,
				
				styleTags = Supra.HTMLEditor.STYLED_INLINE,
				tagsAdd = [],
				classAdd = '',
				styleAdd = '',
				tagClosing = false,
				match = null,
				remove = false;
			
			tagClosing = !!tagName[1];
			tagName = tagName[2].toLowerCase();
			
			if (!(tagName in styleTags)) return null;
			if (tagClosing) return tagName;
			
			if (tagName == 'span') {
				//Remove existing tag
				remove = true;
			}
			
			for(k=0; k<kk; k++) {
				match = html.match(styleToTag[k][1]);
				if (match) {
					tagsAdd[tagsAdd.length] = styleToTag[k][0];
					
					//If keep style
					if (styleToTag[k][2]) {
						styleAdd = styleAdd + (styleAdd ? ' ': '') + match[0] + ';';
					}
				}
			}
			
			classAdd = html.match(REGEXP_FIND_CLASS);
			classAdd = classAdd ? ' class="' + (classAdd[2] || classAdd[3]) + '"' : '';
			
			styleAdd = styleAdd ? ' style="' + styleAdd + '"' : '';
			
			return [
				tagName,
				tagsAdd.length ? '<' + tagsAdd.join(classAdd + styleAdd + '><') + classAdd + styleAdd + '>' : '',
				tagsAdd.length ? '</' + tagsAdd.reverse().join('></') + '>' : '',
				remove
			];
		},
		
		/**
		 * Beautify HTML
		 * 
		 * @param {String} html HTML
		 * @return Beutified HTML code
		 * @type {String}
		 */
		beautifyHTML: function (html) {
			var i = 0,
				len = html.length,
				chr = '',
				indent = 0,
				out = '',
				tags = [],
				tag_name = '',
				tag_inline = false,
				inline = Supra.HTMLEditor.ELEMENTS_INLINE,
				not_closed = Supra.HTMLEditor.NOT_CLOSED_TAGS;
			
			function insertNewLine () {
				var str = '';
				for(var i=0; i<indent; i++) str += '    ';
				out += '\n' + str;
			}
			
			for(; i<len; i++) {
				chr = html.charAt(i);
				if (chr == '<') {
					tag_name = html.substr(i+1).match(/\/?([a-z]+)/i);
					tag_name = tag_name ? tag_name[1] : '';
					tag_inline = !!inline[tag_name] || !!not_closed[tag_name];
					tags.push(tag_inline);
					
					if (!tag_inline) {
						if (html.charAt(i+1) == '/') {
							indent--;	//Closing tag </a>
							insertNewLine();
						} else {
							insertNewLine();
							indent++;	//Opening tag <a>
						}
					}
					
					out+= chr;
				} else if (chr == '>') {
					out+= chr;
					tag_inline = tags.pop();
					if (!tag_inline) {
						insertNewLine();
					}
				} else {
					out+= chr;
				}
			}
			
			out = out.replace(/\r/g, '');
			out = out.replace(/[ \n\t]*(\n[ \t]*)/g, '$1');
			
			return out;
		},
		
		/**
		 * Strip tags from HTML leaving only whiteList tags
		 * 
		 * @param {String} html HTML from which tags should be striped
		 * @param {Array} whiteList List of allowed tags. Array or comma separated list
		 * @return Striped html
		 * @type {String}
		 */
		stripTags: function (html, whiteList) {
			whiteList = Supra.Y.Lang.isArray(whiteList) ? whiteList.join(',') : typeof whiteList == 'string' ? whiteList : '';
			whiteList = whiteList.toLowerCase();
			
			return html.replace(REGEXP_FIND_TAGS, function(match, tagName){
				return whiteList.indexOf(tagName.toLowerCase()) != -1 ? match : '';
			});
		},
		
		/**
		 * Parse tag attributes HTML and return object with key, value pairs
		 * 
		 * @param {String} html HTML which should be parsed
		 * @return Object with key,value pairs of attributes
		 * @type {Object}
		 */
		parseTagAttributes: function (html) {
			var parts = {};
			html.replace(/([a-z0-9\-]+)=("[^"]*"|'[^']*'|[^\s]*)/ig, function (all, key, val) {
				parts[key] = decodeURIComponent(val.replace(/(^'|^"|"$|'$)/g, ''));
				return '';
			});
			return parts;
		},
		
		/**
		 * Returns array with correct tag names (B, EM, U or S) for SPAN element, for all other
		 * elements returns array with single value, which is actual tagName
		 * 
		 * @param {Object} node HTML element
		 * @return Array of tag names
		 * @type {Array}
		 */
		getNodeTagName: function (node) {
			var tagName = (node instanceof Y.Node ? node.get('tagName') : node.tagName);
			if (tagName != 'SPAN') return [tagName];
			
			//Convert style attribute into B, EM, U, S
			var style = node.getAttribute('style'),
				tagNames = [],
				styleToTag = STYLE_TO_TAG_NAME;
			
			for(var i=0,ii=styleToTag.length; i<ii; i++) {
				if (styleToTag[i][1].test(style)) {
					tagNames.push(styleToTag[i][0]);
				}
			}
			
			return (tagNames.length ? tagNames : ['SPAN']);
		}
	});
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['supra.htmleditor-base']});