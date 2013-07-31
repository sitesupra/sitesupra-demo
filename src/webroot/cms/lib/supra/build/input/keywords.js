/**
 * Keyword input
 */
YUI.add("supra.input-keywords", function (Y) {
	//Invoke strict mode
	"use strict";
	
	/*
	 * Template
	 */
	var TEMPLATE_VALUES = Supra.Template.compile('<span class="suggestion-msg">Suggested:</span>\
				{% for value in values %}\
					<span data-value="{{ value|e }}">{{ value|e }}</span>\
				{% endfor %}\
			');
	
	
	function Input (config) {
		Input.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	// Input is inline
	Input.IS_INLINE = false;
	
	// Input is inside form
	Input.IS_CONTAINED = true;
	
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
		"suggestionRequestUri": {
			value: null
		},
		"values": {
			value: null
		},
		
		"suggestionsEnabled": {
			value: false,
			setter: '_setSuggestionsEnabled'
		}
	};
	
	Input.HTML_PARSER = {
		'suggestionsEnabled': function (srcNode) {
			var value = srcNode.getAttribute('data-suggestions-enabled');
			if (value === "true" || value === true || value === 1) {
				return true;
			} else {
				return false;
			}
		}
	};
	
	Y.extend(Input, Supra.Input.Proto, {
		INPUT_TEMPLATE: '<input type="text" value="" />',
		
		
		
		/**
		 * Key code constants
		 */
		KEY_RETURN:    13,
		KEY_ESCAPE:    27,
		KEY_COMMA:     188,
		KEY_SEMICOLON: 186,
		
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
			
			this.suggestions = [];
			
			var inputNode = this.get('inputNode'),
				inputListNode = Y.Node.create('<div class="input-list"></div>'),
				suggestionsNode = Y.Node.create('<div class="suggestions"></div>'),
				suggestionsButton = new Supra.Button({'label': '{#settings.suggestions#}', 'style': 'small', 'id': 'button-suggestions'}),
				suggestionsListNode = Y.Node.create('<div class="suggestions-list hidden"></div>'),
				suggestionsEnabled = this.get('suggestionsEnabled'),
				
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
			
			if (!suggestionsEnabled) {
				suggestionsNode.addClass('hidden');
			}
			if (this.get('disabled')) {
				suggestionsButton.set('disabled', true);
			}
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
			
			//On item click remove it
			inputListNode.delegate('click', this._onRemoveItem, 'a', this);
			
			//On click inside focus on input
			inputListNode.on('click', this.focus, this);

			//On button click load Items into suggestionList
			suggestionsButton.on('click', this.loadItems, this);

			//On item sugesstion click add it to suggestionsList
			suggestionsListNode.delegate('click', this.addSuggestion, 'span', this);

			//Onhide suggestion list
			clearAllLink.on('click', this.closeSuggestionsList, this);

			//Handle return and escape keys
			inputNode.on('keydown', this._onKeyDown, this);
			
			//Remove default behaviour, which is updating value on 'change'
			inputNode.detach('change');
			
			//On blur update item list
			inputNode.on('blur', this._onBlur, this);
			
			//Update value
			this.syncUI();
		},
		
		/**
		 * Update item list
		 */
		syncUI: function () {
			Input.superclass.syncUI.apply(this, arguments);
			
			var values = this.get('values'),
				inputListNode = this.get('inputListNode'),
				tempNode = null;
			
			if (!values) {
				values = [];
				this.set('values', values);
			}
			
			if (inputListNode) {
				inputListNode.all('span').remove();
				
				for(var i=values.length-1; i>=0; i--) {
					tempNode = Y.Node.create('<span></span>');
					tempNode.set('text', values[i]);
					tempNode.setAttribute('data-value', values[i]);
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
		loadItems: function () {
			this.get('suggestionsButton').set('loading', true);
			
			Supra.io(this.get('suggestionRequestUri'), {
				'data': {
					'page_id': Supra.data.get(['page', 'id'])
				},
				'on': {
					'complete': this.onLoadItems
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
		onLoadItems: function (data, status) {
			var suggestionsListNode = this.get('suggestionsListNode'),
				clearAllLink = this.get('clearAllLink'),
				values = this.get('values');
				
			if (status && data.length) {
				this.suggestions = data;
				suggestionsListNode.set('innerHTML', TEMPLATE_VALUES({'values': data}));
				
				this.showSuggestionList();
				
				if(values) {
					//Traverse all kewords and hide which are in suggestions list
					for(var j=0; j<=values.length-1; j++) {
						
						var node = Y.one('.suggestions-list span[data-value="' + values[j] + '"]');
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
		 * On blur add item to the list
		 * 
		 * @private
		 */
		_onBlur: function () {
			this.addItem(this.get('inputNode').get('value'));
			this.get('inputNode').set('value', '');
		},
		
		/**
		 * Handle escape and return keys
		 * 
		 * @param {Event} e Event fascade object
		 * @private
		 */
		_onKeyDown: function (e) {
			if (e.keyCode == this.KEY_RETURN || e.keyCode == this.KEY_COMMA || e.keyCode == this.KEY_SEMICOLON) {
				var inputValue = this.get('inputNode').get('value');
				this.addItem(inputValue);
				this.hideSuggestion(inputValue);
				this.get('inputNode').set('value', '');
				e.preventDefault();
			} else if (e.keyCode == this.KEY_ESCAPE) {
				this.get('inputNode').set('value', '');
				e.preventDefault();
			}
		},
		
		/**
		 * Remove item
		 * 
		 * @param {Event} e Event facade object
		 * @private
		 */
		_onRemoveItem: function (e) {
			if (this.get('disabled')) return;
			
			var target = e.target.closest('span'),
				value = target.getAttribute('data-value'),
				values = this.get('values'),
				index = null;
			
			if (!values) {
				values = [];
				this.set('values', values);
			}
			
			index = Y.Array.indexOf(values, value);
			
			if (index != -1) {
				values.splice(index, 1);
				target.remove();
			}
			
			// check if item was a suggestion and unhide it in suggestions list
			this.showSuggestion(value);
		},

		/**
		 * Add suggestion the list 
		 * 
		 * @param {Event} event
		 */
		addSuggestion: function (event) {
			if (this.get('disabled')) return;
			
			var target = event.target.closest('span'),
				value = target.getAttribute('data-value');
			
			this.addItem(value);
			this.hideSuggestion(value);
		},


		/**
		 * Hide suggestion in suggestion list 
		 * 
		 * @param {String} suggestion
		 */
		hideSuggestion: function (suggestion) {
			//check if suggestion is in items
			var escaped = Y.Escape.html(suggestion),
				node = Y.one('.suggestions-list span[data-value="' + escaped + '"]');
			
			if (node) {
				node.addClass('hidden');
			}
			
			this.updateScrollbars();
		},

		/**
		 * Show prevouisly hidden suggestion from suggestion list 
		 * 
		 * @param {String} suggestion
		 */
		showSuggestion: function (suggestion) {
			//check if suggestion is in items
			var escaped = Y.Escape.html(suggestion),
				node = Y.one('.suggestions-list span[data-value="' + escaped + '"]');
			
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
			if (this.get('disabled')) return;
			
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
		 * Add item the list 
		 * 
		 * @param {String} value
		 */
		addItem: function (value) {
			var values = this.get('values'),
				index = -1,
				inputNode = this.get('inputNode'),
				tempNode = Y.Node.create('<span></span>');
							
			if (!values) {
				values = [];
				this.set('values', values);
			}
			
			//Validate
			value = Y.Lang.trim(value);
			if (!value.length) {
				// Empty
				return;
			}
			if (value.split(/\s+/).length > 5) {
				// More than 5 words, why this limit exists???
				return;
			}
			if (values.join(';').toLowerCase().indexOf(value.toLowerCase()) != -1) {
				// Already is in list
				return;
			}
			
			//Add item
			values.push(value);
			
			//Add node
			tempNode.set('text', value);
			tempNode.setAttribute('data-value', value);
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
		
		
		/* ------------------------------- ATTRIBUTES ------------------------------- */
		
		
		_setValue: function (value) {
			this.get('inputNode').set('value', value);
			this.set('values', value ? value.split(';') : []);
			this.closeSuggestionsList();
			
			this.syncUI();
			return value;
		},
		
		_getValue: function () {
			return (this.get('values') || []).join(';');
		},
		
		/**
		 * SuggestionsEnabled attribute setter
		 * 
		 * @param {Boolean} value
		 * @return New value
		 * @type {Boolean}
		 * @private
		 */
		_setSuggestionsEnabled: function (enabled) {
			if (!this.get('rendered')) return !!enabled;
			
			if (enabled) {
				this.get('suggestionsNode').removeClass('hidden');
			} else {
				this.closeSuggestionsList();
				this.get('suggestionsNode').addClass('hidden');
			}
			
			return !!enabled;
		},
		
		/**
		 * Disabled attribute setter
		 * 
		 * @param {Boolean} value
		 * @return New value
		 * @type {Boolean}
		 * @private
		 */
		_setDisabled: function (value) {
			value = Input.superclass._setDisabled.apply(this, arguments);
			
			var button = this.get('suggestionsButton');
			if (button) {
				button.set('disabled', value);
			}
			
			return value;
		},
	
	});
	
	Supra.Input.Keywords = Input;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn);this.fn = function () {};
	
}, YUI.version, {requires:["supra.input-proto"]});
