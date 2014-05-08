YUI().add('supra.htmleditor-parser', function (Y) {
	//Invoke strict mode
	"use strict";
	
	/* Tag white list, all other tags will be removed. <font> tag is added if "fonts" plugin is enabled */
	Supra.HTMLEditor.WHITE_LIST_TAGS = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'hr', 'p', 'b', 'em', 'small', 'sub', 'sup', 'a', 'img', 'br', 'strong', 's', 'strike', 'u', 'blockquote', 'q', 'big', 'table', 'tbody', 'tr', 'td', 'thead', 'th', 'ul', 'ol', 'li', 'div', 'dl', 'dt', 'dd', 'col', 'colgroup', 'caption', 'object', 'param', 'embed', 'article', 'aside', 'details', 'figcaption', 'figure', 'footer', 'header', 'hgroup', 'nav', 'section', '_span', 'svg', 'pre', 'code'];
	
	/* Attribute black list */
	Supra.HTMLEditor.BLACK_LIST_ATTRS = ['contenteditable'];
	
	/* List of block elements */
	Supra.HTMLEditor.ELEMENTS_BLOCK = {'h1': 'h1', 'h2': 'h2', 'h3': 'h3', 'h4': 'h4', 'h5': 'h5', 'h6': 'h6', 'p': 'p', 'blockquote': 'blockquote', 'q': 'q', 'table': 'table', 'tbody': 'tbody', 'tr': 'tr', 'td': 'td', 'thead': 'thead', 'th': 'th', 'ul': 'ul', 'ol': 'ol', 'li': 'li', 'div': 'div', 'dl': 'dl', 'dt': 'dt', 'dd': 'dd', 'col': 'col', 'colgroup': 'colgroup', 'caption': 'caption', 'object': 'object', 'param': 'param', 'embed': 'embed', 'article': 'article', 'aside': 'aside', 'details': 'details', 'figcaption': 'figcaption', 'figure': 'figure', 'footer': 'footer', 'header': 'header', 'hgroup': 'hgroup', 'nav': 'nav', 'section': 'section', 'pre': 'pre', 'code': 'code'};
	Supra.HTMLEditor.ELEMENTS_BLOCK_ARR = Y.Lang.toArray(Supra.HTMLEditor.ELEMENTS_BLOCK);
	
	/* List of inline elements */
	Supra.HTMLEditor.ELEMENTS_INLINE = {'b': 'b', 'i': 'i', 'span': 'span', 'em': 'em', 'sub': 'sub', 'sup': 'sup', 'small': 'small', 'strong': 'strong', 's': 's', 'strike': 'strike', 'a': 'a', 'u': 'u', 'img': 'img', 'br': 'br', 'q': 'q', 'big': 'big', 'mark': 'mark', 'rp': 'rp', 'rt': 'rt', 'ruby': 'ruby', 'summary': 'summary', 'time': 'time', 'svg': 'svg', 'g': 'g', 'path': 'path'};
	Supra.HTMLEditor.ELEMENTS_INLINE_ARR = Y.Lang.toArray(Supra.HTMLEditor.ELEMENTS_INLINE);
	
	/* List of tags which doesn't need to be closed */
	Supra.HTMLEditor.NOT_CLOSED_TAGS = {'img': 'img', 'br': 'br', 'param': 'param', 'col': 'col', 'embed': 'embed', 'hr': 'hr'};
	
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
	
	/* */
	var REGEX_FIND_I = /<(\/?)i((\s[^>]+)?)>/ig,
		REGEX_FIND_B = /<(\/?)b((\s[^>]+)?)>/ig,
		REGEX_FIND_S = /<(\/?)s((\s[^>]+)?)>/ig,
		REGEX_FIND_STRONG = /<(\/?)strong([^>]*)>/g,
		
		REGEX_FIND_A_START = /(<a [^>]+>)\s/g,
		REGEX_FIND_A_END   = /\s(<\/a[^>]*>)/g,
		REGEX_A_BR         = /<br\s*\/?>\s*(<\/a[^>]*>)/gi,
		
		REGEX_SVG          = /<svg [^>]+>/g,
		REGEX_ATTR         = / ([a-zA-Z0-9\-\:]+)=("[^"]+")/g,
		
		REGEX_TAG_START    = /<(\/?)([a-z]+)/,
		REGEX_STRONG_START = /<(\/?)strong/ig,
		REGEX_STRIKE_START = /<(\/?)strike/ig,
		REGEX_NODE_ID_ATTR = /\s+id="yui_[^"]*"/gi,
		REGEX_NODE_UNEDITABLE = /su\-(un)?editable/gi,
		
		REGEX_FIND_STYLE  = /style=("[^"]*"|'[^']*')/,
		
		REGEX_EMPTY_UL_OL = /<(ul|ol)>[\s\r\n]*?<\/(ul|ol)>/gi,
		REGEX_ATTR_STYLE  = /\s+style=("[^"]*"|'[^']*')/gi,
		REGEX_STYLE_BG    = /(fill|background-color):[^;]+/,
		REGEX_EMPTY_CLASS = /class="\s*"/g,
		REGEX_YUI_CLASS   = /(yui3\-table\-selected|yui3\-cell\-selected)/g,
		
		REGEX_LT           = /<(\/?)_/g,
		
		REGEX_TAG_ATTRIBUTES = /([a-z0-9\-]+)=("[^"]*"|'[^']*'|[^\s]*)/ig,
		REGEX_STRIP_QUOTES   = /(^'|^"|"$|'$)/g,
		
		TAG_TO_SPAN = [
			['b',  'font-weight: bold', /<b(\s[^>]*)?(\sclass="[^"]+")?(\s[^>]*)?>/ig, /<\/b(\s[^>]*)?>/ig],
			['em', 'font-style: italic', /<em(\s[^>]*)?(\sclass="[^"]+")?(\s[^>]*)?>/ig, /<\/em(\s[^>]*)?>/ig],
			['u',  'text-decoration: underline', /<u(\s[^>]*)?(\sclass="[^"]+")?(\s[^>]*)?>/ig, /<\/u(\s[^>]*)?>/ig],
			['s',  'text-decoration: line-through', /<s(\s[^>]*)?(\sclass="[^"]+")?(\s[^>]*)?>/ig, /<\/s(\s[^>]*)?>/ig]
		];
	
	Y.mix(Supra.HTMLEditor.prototype, {
		/**
		 * Converts html into browser compatible format
		 * @param {Object} html
		 */
		uncleanHTML: function (html) {
			//Convert <i> into <em>
			html = html.replace(REGEX_FIND_I, '<$1em$2>');
			
			if (Y.UA.ie) {
				//IE uses STRONG, EM, U, STRIKE instead of SPAN
				html = html.replace(REGEX_FIND_I, '<$1strong$2>');
				html = html.replace(REGEX_FIND_S, '<$1strike$2>');
			} else {
				//Convert <strong> into <b>
				html = html.replace(REGEX_FIND_STRONG, '<$1b$2>');
				
				//Convert B, EM, U, S into SPAN
				var tagToSpan = TAG_TO_SPAN,
					tag,
					expression;
				
				for(var i=0,ii=tagToSpan.length; i<ii; i++) {
					tag = tagToSpan[i][0];
					
					html = html.replace(tagToSpan[i][2], '<span style="' + tagToSpan[i][1] + ';" $2>');
					html = html.replace(tagToSpan[i][3], '</span>');
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
			
			if (mode == Supra.HTMLEditor.MODE_STRING || mode == Supra.HTMLEditor.MODE_TEXT) {
				//In string mode there is nothing to clean up
			} else {
				//IE creates STRONG, EM, U, STRIKE instead of SPAN
				if (Y.UA.ie) {
					html = html.replace(REGEX_STRONG_START, '<$1b');
					html = html.replace(REGEX_STRIKE_START, '<$1s');
				}
				
				//Remove YUI ids from nodes
				html = html.replace(REGEX_NODE_ID_ATTR, '');
				
				//Remove un-editable classnames
				html = html.replace(REGEX_NODE_UNEDITABLE, '');
				
				//Replace styles with tags
				var regexTag = REGEX_TAG_START,
					regexStyle = REGEX_FIND_STYLE,
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
									var tmp = html.substr(tagOpenIndex, tagCloseIndex + 1 - tagOpenIndex).replace(regexStyle, '');
									
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
				html = html.replace(REGEX_FIND_STRONG, '<$1b$2>');
				
				//Convert <i> into <em>
				html = html.replace(REGEX_FIND_I, '<$1em$2>');
				
				//Moves whitespaces outside <A> tags
				html = html.replace(REGEX_FIND_A_START, ' $1');
				html = html.replace(REGEX_FIND_A_END, '$1 ');
				
				//Moves <BR> outside <A> tags
				html = html.replace(REGEX_A_BR, ' $1<br />');
				
				//Remove tags, which are not white-listed (SPAN is also removed)
				var white_list_tags = Supra.HTMLEditor.WHITE_LIST_TAGS;
				if (this.getPlugin("fonts")) {
					white_list_tags.push("font")
				}
				html = this.stripTags(html, white_list_tags);
				
				//Remove unneeded attributes from <SVG>
				var regex_attr = REGEX_ATTR;
				html = html.replace(REGEX_SVG, function (str) {
					return str.replace(regex_attr, function (m, property) {
						return property === 'id' ? m : '';
					});
				});
				
				//Convert <_ into <
				html = html.replace(REGEX_LT, '<$1');
				
				//Remove empty UL and OL tags
				html = html.replace(REGEX_EMPTY_UL_OL, '');
				
				//Remove blacklisted tag attributes
				var black_list_attrs = Supra.HTMLEditor.BLACK_LIST_ATTRS;
				html = html.replace(new RegExp("(<[^>]+?)(\\s+(" + black_list_attrs.join('|') + ")=(\"[^\"]*\"|'[^']*'|[^\\s>]+))+", "ig"), '$1');
				
				//Remove style attribute, except background-color and fill
				var regex_style_bg = REGEX_STYLE_BG;
				html = html.replace(REGEX_ATTR_STYLE, function (all, styles) {
					styles = styles.replace(/(^['"]|['"]$)/g, ''); // trim
					styles = styles.match(regex_style_bg);
					if (styles && styles.length) {
						return ' style="' + styles[0] + '"';
					}
					return '';
				});
				
				//Remove empty class attributes
				html = html.replace(REGEX_EMPTY_CLASS, '');
				
				//Remove YUI classnames
				html = html.replace(REGEX_YUI_CLASS, '');
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
				regex_tagname = /\/?([a-z]+)/i,
				inline = Supra.HTMLEditor.ELEMENTS_INLINE,
				not_closed = Supra.HTMLEditor.NOT_CLOSED_TAGS,
				regex_pre = /<pre(.|\r|\n)*?<\/pre[^>]*>/i,
				pre_tag = '',
				pre_tags = [];
			
			// Extract PRE tags to preserve spacing, line breaks, etc.
			while(pre_tag = html.match(regex_pre)) {
				html = html.replace(pre_tag[0], '%{PRE_' + pre_tags.length + '}')
				pre_tags.push(pre_tag[0]);
			}
			
			function insertNewLine () {
				var str = '';
				for(var i=0; i<indent; i++) str += '    ';
				out += '\n' + str;
			}
			
			for(; i<len; i++) {
				chr = html.charAt(i);
				if (chr == '<') {
					tag_name = html.substr(i+1).match(regex_tagname);
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
			
			// Restore PRE tags
			for (i=0,len=pre_tags.length; i<len; i++) {
				out = out.replace('%{PRE_' + i + '}', pre_tags[i]);
			}
			
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
			whiteList = ',' + (Supra.Y.Lang.isArray(whiteList) ? whiteList.join(',') : typeof whiteList == 'string' ? whiteList : '') + ',';
			whiteList = whiteList.toLowerCase();
			
			return html.replace(REGEXP_FIND_TAGS, function(match, tagName) {
				return whiteList.indexOf(',' + tagName.toLowerCase() + ',') != -1 ? match : '';
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
			var parts = {},
				regexStripQuotes = REGEX_STRIP_QUOTES;
			
			html.replace(REGEX_TAG_ATTRIBUTES, function (all, key, val) {
				val = val.replace(regexStripQuotes, '');
				try {
					parts[key] = decodeURIComponent(val);
				} catch (e) {
					parts[key] = val;
				}
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
			if (tagName != 'SPAN') return [tagName.toUpperCase()];
			
			//Convert style attribute into B, EM, U, S
			var style = node.getAttribute('style'),
				tagNames = [],
				styleToTag = STYLE_TO_TAG_NAME;
			
			for(var i=0,ii=styleToTag.length; i<ii; i++) {
				if (styleToTag[i][1].test(style)) {
					tagNames.push(styleToTag[i][0].toUpperCase());
				}
			}
			
			return (tagNames.length ? tagNames : ['SPAN']);
		},
		
		/**
		 * Returns content character count
		 * 
		 * @returns {Number} Character count
		 */
		getContentCharacterCount: function (node) {
			var node = node || this.get('srcNode'),
				text = '',
				brs  = 0;
			
			if (!(node instanceof Y.Node)) node = Y.Node(node);
			 
			 brs = node.all('br').size();
			 text = node.get('text').replace(/[\r\n]/g, '');
			 
			 // Each BR is one character
			 return text.length + brs;
		},
	});
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['supra.htmleditor-base']});