YUI().add('supra.htmleditor-parser', function (Y) {
	
	/* Tag white list, all other tags will be removed */
	Supra.HTMLEditor.WHITE_LIST_TAGS = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'b', 'em', 'small', 'sub', 'sup', 'a', 'img', 'br', 's', 'strike', 'u', 'blockquote', 'q', 'big', 'table', 'tbody', 'tr', 'td', 'thead', 'th', 'ul', 'ol', 'li', 'div', 'dl', 'dt', 'dd', 'col', 'colgroup', 'caption'];
	
	/* List of inline elements */
	Supra.HTMLEditor.ELEMENTS_INLINE = {'b': 'b', 'i': 'i', 'span': 'span', 'em': 'em', 'sub': 'sub', 'sup': 'sup', 'small': 'small', 'strong': 'strong', 's': 's', 'strike': 'strike', 'a': 'a', 'u': 'u', 'img': 'img', 'br': 'br', 'q': 'q', 'big': 'big'};
	
	/* Elements which should be checked for inline style */
	Supra.HTMLEditor.STYLED_INLINE   = ['span', 'b', 'i', 'em', 'sub', 'sup', 'small', 'strong', 's', 'strike', 'a', 'u', 'q', 'big'];
	
	/* Find all tags */
	var REGEXP_FIND_TAGS = /<\/?([a-z][a-z0-9]*)\b[^>]*>/gi;
	
	/* List of tagNames and matching regular expressions to find correct tag name */
	var STYLE_TO_TAG_NAME = [
		['B', /font-weight:\s?bold/i],
		['EM', /font-style:\s?italic/i],
		['U', /text-decoration:[^"']*underline/i],
		['S', /text-decoration:[^"']*strike-through/i]
	];
	
	Y.mix(Supra.HTMLEditor.prototype, {
		/**
		 * Converts html into browser compatible format
		 * @param {Object} html
		 */
		uncleanHTML: function (html) {
			//Convert <strong> into <b>
			html = html.replace(/<(\/?)strong([^>]*)>/g, '<$1b$2>');
			
			//Convert <i> into <em>
			html = html.replace(/<(\/?)i((\s[^>]+)?)>/g, '<$1em$2>');
			
			//Convert B, EM, U, S into SPAN
			var tagToSpan = [
					['b',  'font-weight: bold'],
					['em', 'font-style: italic'],
					['u',  'text-decoration: underline'],
					['s',  'text-decoration: strike-through']
				],
				tag,
				expression;
			
			for(var i=0,ii=tagToSpan.length; i<ii; i++) {
				tag = tagToSpan[i][0];
				
				expression = new RegExp("<" + tag + "(\s[^>]*)?>", "ig");
				html = html.replace(expression, '<span style="' + tagToSpan[i][1] + ';">');
				
				expression = new RegExp("<\/" + tag + "(\s[^>]*)?>", "ig");
				html = html.replace(expression, '</span>');
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
		 * 		Input:  <span style="text-decoration: underline strike-through;">text</span>
		 * 		Output: <u><s>text</s></u>
		 *  
		 * @param {Object} html
		 */
		cleanHTML: function (html) {
			//Convert <span> into B, EM, U, S
			var styleToTag = [
				['b', /font-weight:\s?bold/i],
				['em', /font-style:\s?italic/i],
				['u', /text-decoration:[^"']*underline/i],
				['s', /text-decoration:[^"']*strike-through/i]
			];
			
			//Remove YUI ids from nodes
			html = html.replace(/\s+id="yui_[^"]*"/gi, '');
			
			//Remove un-editable classnames
			html = html.replace(/su\-(un)?editable/gi, '');
			
			var styleTags = Supra.HTMLEditor.STYLED_INLINE,
				styleTag,
				regexStyle,
				tagIndex,
				tagOpenStart,
				tagOpenEnd,
				tagClose,
				tagContent,
				tagsAdd = [],
				tagsAppend = '';
				tagsPrepend = '';
				k = 0,
				kk = styleToTag.length;
			
			for(var i=0,ii=styleTags.length; i<ii; i++) {
				styleTag = styleTags[i];
				
				tagOpenStart = html.lastIndexOf('<' + styleTag);
				tagOpenEnd = html.indexOf('>', tagOpenStart);
				tagsAdd = [];
				
				while(tagOpenStart != -1) {
					tagClose = html.indexOf('</' + styleTag, tagOpenStart);
					
					if (tagClose == -1) {
						//Tag is not closed, remove it
						html = html.substring(0, tagOpenStart) + html.substr(tagOpenEnd + 1);
					} else {
						tagContent = html.substring(tagOpenStart, tagOpenEnd);
						
						for(k=0; k<kk; k++) {
							if (tagContent.match(styleToTag[k][1])) {
								tagsAdd[tagsAdd.length] = styleToTag[k][0];
							}
						}
						
						tagsPrepend = tagsAdd.length ? '<_' + tagsAdd.join('><_') + '>' : '';
						tagsAppend  = tagsAdd.length ? '</_' + tagsAdd.reverse().join('></_') + '>' : '';
						
						html = html.substring(0, tagOpenStart)
							 + '<_' + html.substring(tagOpenStart + 1, tagOpenEnd + 1)
							 + tagsPrepend + html.substring(tagOpenEnd + 1, tagClose) + tagsAppend
							 + '</_' + html.substring(tagClose + 2);
					}
					
					tagOpenStart = html.lastIndexOf('<' + styleTag);
					tagOpenEnd = html.indexOf('>', tagOpenStart);
					tagsAdd = [];
				}
			}
			
			//Convert <_ into <
			html = html.replace(/<(\/?)_/g, '<$1');
			
			//Convert <strong> into <b>
			html = html.replace(/<(\/?)strong([^>]*)>/g, '<$1b$2>');
			
			//Convert <i> into <em>
			html = html.replace(/<(\/?)i((\s[^>]+)?)>/g, '<$1em$2>');
			
			//Remove tags, which are not white-listed (SPAN is also removed)
			html = this.stripTags(html, Supra.HTMLEditor.WHITE_LIST_TAGS);
			
			//Remove style attribute
			html = html.replace(/\s+style="[^"]*"/gi, '');
			
			//Fire event to allow plugins to clean up after themselves
			var event = {'html': html};
			this.fire('cleanHTML', {}, event);
			
			return event.html || '';
		},
		
		/**
		 * Strip tags from HTML leaving only whitelisted tags
		 * 
		 * @param {String} html HTML from which tags should be striped
		 * @param {Array} whiteList List of allowed tags. Array or comma separated list
		 * @return Striped html
		 * @type {String}
		 */
		stripTags: function (html, whiteList) {
			whiteList = SU.Y.Lang.isArray(whiteList) ? whiteList.join(',') : typeof whiteList == 'string' ? whiteList : '';
			whiteList = whiteList.toLowerCase();
			
			return html.replace(REGEXP_FIND_TAGS, function(match, tagName){
				return whiteList.indexOf(tagName.toLowerCase()) != -1 ? match : '';
			});
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