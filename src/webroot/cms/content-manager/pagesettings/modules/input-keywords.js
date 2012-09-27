/**
 * Keyword input
 */
YUI.add("website.input-keywords", function (Y) {
	//Invoke strict mode
	"use strict";
	
	/*
	 * Template
	 */
	var TEMPLATE_KEYWORDS = Supra.Template.compile('<span class="suggestion-msg">Suggested:</span>\
				{% for keyword in keywords %}\
					<span data-keyword="{{ keyword|e }}">{{ keyword|e }}</span>\
				{% endfor %}\
			');
	
	
	function Input (config) {
		Input.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	Input.NAME = "input-keywords";
	Input.CLASS_NAME = Y.ClassNameManager.getClassName(Input.NAME);
	Input.ATTRS = {
		"inputNode": {
			value: null
		},
		"inputListNode": {
			value: null
		},
		"suggestionsNode": {
			value: null
		},
		"suggestionsListNode": {
			value: null
		},
		"keywordRequestUri": {
			value: null
		},
		"keywords": {
			value: null
		}
	};
	
	Input.HTML_PARSER = {};
	
	Y.extend(Input, Supra.Input.Proto, {
		INPUT_TEMPLATE: '<input type="text" value="" />',
		
		
		
		/**
		 * Key code constants
		 */
		KEY_RETURN: 13,
		KEY_ESCAPE: 27,
		
		/**
		 * List of suggestions
		 * @private
		 */
		suggestions: [],
		
		/**
		 * Add needed nodes, etc.
		 * 
		 * @private
		 */
		renderUI: function () {
			Input.superclass.renderUI.apply(this, arguments);
			
			var inputNode = this.get('inputNode'),
				inputListNode = Y.Node.create('<div class="input-list"></div>'),
				suggestionsNode = Y.Node.create('<div class="suggestions"></div>'),
				suggestionsButton = new Supra.Button({'label': '{#settings.suggestions#}', 'style': 'small', 'id': 'button-suggestions'}),
				suggestionsListNode = Y.Node.create('<div class="suggestions-list hidden"></div>'),
				
				clearAllLabel = Supra.Intl.get(['settings', 'clear_all']),
				clearAllLink = new Y.Node.create('<a class="link-clear-all hidden">' + clearAllLabel + '</div>');

			inputNode.insert(inputListNode, 'after');
			inputListNode.append(inputNode);

			inputListNode.insert(suggestionsNode, 'after');
			suggestionsButton.render(suggestionsNode);
			suggestionsNode.append(suggestionsListNode);
			suggestionsNode.append(clearAllLink);

			this.set('suggestionsNode', suggestionsNode);
			this.set('suggestionsButton', suggestionsButton);
			this.set('suggestionsListNode', suggestionsListNode);
			this.set('clearAllLink', clearAllLink);
			this.set('inputListNode', inputListNode);
		},
		
		/**
		 * Attach event listeners
		 * 
		 * @private
		 */
		bindUI: function () {
			Input.superclass.bindUI.apply(this, arguments);
			
			var inputListNode = this.get('inputListNode'),
				suggestionsButton = this.get('suggestionsButton'),
				suggestionsListNode = this.get('suggestionsListNode'),
				clearAllLink = this.get('clearAllLink'),
				inputNode = this.get('inputNode');
			
			//On keyword click remove it
			inputListNode.delegate('click', this._onRemoveKeyword, 'a', this);
			
			//On click inside focus on input
			inputListNode.on('click', this.focus, this);

			//On button click load Keywords into suggestionList
			suggestionsButton.on('click', this.loadKeywords, this);

			//On keyword sugesstion click add it to suggestionsList
			suggestionsListNode.delegate('click', this.addSuggestion, 'span', this);

			//Onhide suggestion list
			clearAllLink.on('click', this.closeSuggestionsList, this);

			//Handle return and escape keys
			inputNode.on('keydown', this._onKeyDown, this);
			
			//Remove default behaviour, which is updating value on 'change'
			inputNode.detach('change');
			
			//On blur update keyword list
			inputNode.on('blur', this._onBlur, this);
			
			//Update value
			this.syncUI();
		},
		
		/**
		 * Update keyword list
		 */
		syncUI: function () {
			Input.superclass.syncUI.apply(this, arguments);
			
			var keywords = this.get('keywords'),
				inputListNode = this.get('inputListNode'),
				tempNode = null;
			
			if (!keywords) {
				keywords = [];
				this.set('keywords', keywords);
			}
			
			if (inputListNode) {
				inputListNode.all('span').remove();
				
				for(var i=keywords.length-1; i>=0; i--) {
					tempNode = Y.Node.create('<span></span>');
					tempNode.set('text', keywords[i]);
					tempNode.setAttribute('data-keyword', keywords[i]);
					tempNode.appendChild('<a></a>');
					inputListNode.prepend(tempNode);
				}
			}
			
			this.get('inputNode').set('value', '');
			
			this.updateScrollbars();
			
		},
		
		/**
		 * Load list of suggestions and populate list
		 * 
		 * @private
		 */
		loadKeywords: function () {
			this.get('suggestionsButton').set('loading', true);
			
			Supra.io(this.get('keywordRequestUri'), {
				'data': {
					'page_id': Supra.data.get(['page', 'id'])
				},
				'on': {
					'complete': this.onLoadKeywords
				}
			}, this);
		},
		
		/**
		 * Handle suggestion load event
		 * 
		 * @param {Object} data Request response data
		 * @param {Boolean} status Request response status
		 * @private
		 */
		onLoadKeywords: function (data, status) {
			var suggestionsListNode = this.get('suggestionsListNode'),
				clearAllLink = this.get('clearAllLink'),
				keywords = this.get('keywords');
				
			if (status && data.length) {
				this.suggestions = data;
				suggestionsListNode.set('innerHTML', TEMPLATE_KEYWORDS({'keywords': data}));
				
				this.showSuggestionList();
				
				if(keywords) {
					//Traverse all kewords and hide which are in suggestions list
					for(var j=0; j<=keywords.length-1; j++) {
						
						var node = Y.one('.suggestions-list span[data-keyword="' + keywords[j] + '"]');
						if (node) {
							node.addClass('hidden');
						}
					}
				}
					
			} else {
				this.suggestions = [];
				suggestionsListNode.set('innerHTML', '');
			}

			this.get('suggestionsButton').set('loading', false);
			this.updateScrollbars();
		},
		
		/**
		 * On blur add keyword to the list
		 * 
		 * @private
		 */
		_onBlur: function () {
			this.addKeyword(this.get('inputNode').get('value'));
			this.get('inputNode').set('value', '');
		},
		
		/**
		 * Handle escape and return keys
		 * 
		 * @param {Event} e Event fascade object
		 * @private
		 */
		_onKeyDown: function (e) {
			if (e.keyCode == this.KEY_RETURN) {
				var inputValue = this.get('inputNode').get('value');
				this.addKeyword(inputValue);
				this.hideSuggestion(inputValue);
				this.get('inputNode').set('value', '');
			} else if (e.keyCode == this.KEY_ESCAPE) {
				this.get('inputNode').set('value', '');
			}
		},
		
		/**
		 * Remove keyword
		 * 
		 * @param {Event} e Event facade object
		 * @private
		 */
		_onRemoveKeyword: function (e) {
			var target = e.target.closest('span'),
				keyword = target.getAttribute('data-keyword'),
				keywords = this.get('keywords'),
				index = null;
			
			if (!keywords) {
				keywords = [];
				this.set('keywords', keywords);
			}
			
			index = Y.Array.indexOf(keywords, keyword);
			
			if (index != -1) {
				keywords.splice(index, 1);
				target.remove();
			}
			
			// check if keyword was a suggestion and unhide it in suggestions list
			this.showSuggestion(keyword);
		},

		/**
		 * Add suggestion the list 
		 * 
		 * @param {Event} event
		 */
		addSuggestion: function (event) {
			var target = event.target.closest('span'),
				keyword = target.getAttribute('data-keyword');
			
			this.addKeyword(keyword);
			this.hideSuggestion(keyword);
		},


		/**
		 * Hide suggestion in suggestion list 
		 * 
		 * @param {String} keyword
		 */
		hideSuggestion: function (suggestion) {
			//check if suggestion is in keywords
			var escaped = Y.Escape.html(suggestion),
				node = Y.one('.suggestions-list span[data-keyword="' + escaped + '"]');
			
			if (node) {
				node.addClass('hidden');
			}
			
			this.updateScrollbars();
		},

		/**
		 * Show prevouisly hidden suggestion from suggestion list 
		 * 
		 * @param {String} keyword
		 */
		showSuggestion: function (suggestion) {
			//check if suggestion is in keywords
			var escaped = Y.Escape.html(suggestion),
				node = Y.one('.suggestions-list span[data-keyword="' + escaped + '"]');
			
			if (node) {
				node.removeClass('hidden');
			}
			
			this.updateScrollbars();
		},
		
		/**
		 * Show suggestion list
		 * 
		 * @private
		 */
		showSuggestionList: function () {
			this.get('suggestionsButton').hide();
			this.get('suggestionsListNode').removeClass('hidden');
			this.get('clearAllLink').removeClass('hidden');
		},

		/**
		 * Hide Suggestion List
		 * 
		 * @private
		 */
		closeSuggestionsList: function () {
			var suggestionsButton = this.get('suggestionsButton'),
				suggestionsListNode = this.get('suggestionsListNode'),
				clearAllLink = this.get('clearAllLink');

			if (suggestionsListNode) {
				suggestionsListNode.addClass('hidden');
			}
			if (clearAllLink) {
				clearAllLink.addClass('hidden');
			}
			if (suggestionsButton) {
				suggestionsButton.show();	
			}
			
			this.updateScrollbars();
		},
		

		/**
		 * Add keyword the list 
		 * 
		 * @param {String} keyword
		 */
		addKeyword: function (keyword) {
			var keywords = this.get('keywords'),
				inputNode = this.get('inputNode'),
				tempNode = Y.Node.create('<span></span>');
							
			if (!keywords) {
				keywords = [];
				this.set('keywords', keywords);
			}
			
			//Validate
			keyword = Y.Lang.trim(keyword);
			if (!keyword.length 
				|| Y.Array.indexOf(keywords, keyword) != -1
				|| keyword.split(/\s+/).length > 5) {
				return;
			}
			
			//Add keyword
			keywords.push(keyword);
			
			//Add node
			tempNode.set('text', keyword);
			tempNode.setAttribute('data-keyword', keyword);
			tempNode.appendChild('<a></a>');
			
			inputNode.insert(tempNode, 'before');
			
			this.updateScrollbars();
			
			//Events
			this.fire('change', {'value': this.get('value')});
		},
		
		/**
		 * Update scrollbar position and size
		 * 
		 * @private
		 */
		updateScrollbars: function () {
			var node = this.get('boundingBox').closest('.su-scrollable-content');
			if (node) {
				node.fire('contentResize');
			}
		},
		
		_setValue: function (value) {
			this.get('inputNode').set('value', value);
			this.set('keywords', value ? value.split(';') : []);
			this.closeSuggestionsList();
			
			this.syncUI();
			return value;
		},
		
		_getValue: function () {
			return (this.get('keywords') || []).join(';');
		}
	
	});
	
	Supra.Input.Keywords = Input;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn);this.fn = function () {};
	
}, YUI.version, {requires:["supra.input-proto"]});
