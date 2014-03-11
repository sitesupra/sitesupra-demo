/**
 * Keyword input
 */
YUI.add("supra.input-keywords", function (Y) {
	//Invoke strict mode
	"use strict";
	
	/*
	 * Template
	 */
	var TEMPLATE_VALUES = Supra.Template.compile('{% if label %}<span class="suggestion-msg">{{ label|e }}</span>{% endif %}' +
				'{% for value in values %}' +
					'<span data-value="{{ value|e }}">{{ value|e }}</span>' +
				'{% endfor %}');
	
	
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
		
		"suggestedLabel": {
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
		"suggestions": {
			value: null
		},
		
		"suggestionsSelectNode": {
			value: null
		},
		
		/*
		 * Only allow selecting values from suggestions
		 * User input is not allowed
		 */
		"suggestionsStrict": {
			value: false,
			setter: '_setSuggestionsStrict'
		},
		
		"suggestionsStrictNode": {
			value: null
		},
		
		"suggestionsStrictLabel": {
			value: "Please select"
		},
		
		/*
		 * Suggestion are automatically visible
		 */
		"suggestionAutoVisible": {
			value: false,
		},
		
		/*
		 * Suggestions are enabled
		 */
		"suggestionsEnabled": {
			value: false,
			setter: '_setSuggestionsEnabled'
		},
		
		/*
		 * Array of values
		 */
		"values": {
			value: null
		},
		
		/*
		 * Maximum number of keywords
		 */
		"maxCount": {
			value: null,
			setter: '_setMaxCount'
		}
	};
	
	Input.HTML_PARSER = {
		'suggestedLabel': function (srcNode) {
			var value = srcNode.getAttribute('data-suggestions-label');
			if (value) {
				return value;
			}
		},
		'suggestionsEnabled': function (srcNode) {
			var value = srcNode.getAttribute('data-suggestions-enabled');
			if (value === "true" || value === true || value === 1) {
				return true;
			} else {
				return false;
			}
		},
		'suggestionRequestUri': function (srcNode) {
			var value = srcNode.getAttribute('data-suggestions-uri');
			if (value) {
				return value;
			}
		},
		'suggestionAutoVisible': function (srcNode) {
			var value = srcNode.getAttribute('data-suggestions-visible');
			if (value && value === "true" || value === "1") {
				return true;
			}
		},
		'suggestionsStrict': function (srcNode) {
			var value = srcNode.getAttribute('data-suggestions-strict');
			if (value && value === "true" || value === "1") {
				return true;
			}
		},
		'suggestionsStrictLabel': function (srcNode) {
			var value = srcNode.getAttribute('data-suggestions-strict-label');
			if (value) {
				return value;
			}
		},
		'maxCount': function (srcNode) {
			var value = srcNode.getAttribute('data-max-count');
			if (value) {
				value = parseInt(value, 10);
				if (value) return value;
			}
		},
	};
	
	Y.extend(Input, Supra.Input.Proto, {
		INPUT_TEMPLATE: '<input type="text" value="" />',
		
		
		
		/**
		 * Key code constants
		 */
		KEY_RETURN:    13,  // key code
		KEY_ESCAPE:    27,  // key code
		KEY_COMMA:     44,  // character code
		KEY_SEMICOLON: 59,  // character code
		
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
				suggestionsStrict = this.get('suggestionsStrict'),
				
				clearAllLabel = Supra.Intl.get(['settings', 'clear_all']),
				clearAllLink = new Y.Node.create('<a class="link-clear-all hidden">' + clearAllLabel + '</div>'),
				
				suggestionsSelectNode;

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
			} else {
				suggestionsButton.hide();
				clearAllLink.addClass('hidden');
			}
			
			if (suggestionsStrict) {
				this._uiSetSuggestionsStrict(suggestionsStrict);
			}
			
			if (this.get('disabled')) {
				suggestionsButton.set('disabled', true);
			}
			
			if (this.get('suggestionAutoVisible')) {
				this.loadItems();
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
			inputNode.on('keypress', this._onKeyDown, this);
			
			//Remove default behaviour, which is updating value on 'change'
			inputNode.detach('change');
			
			//On blur update item list
			inputNode.on('blur', this._onBlur, this);
			
			//After suggestions change update UI
			this.after('suggestionsChange', this.loadItems, this);
			
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
					
					this.hideSuggestion(values[i]);
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
			var uri = this.get('suggestionRequestUri');
			
			if (uri) {
				this.get('suggestionsButton').set('loading', true);
				
				Supra.io(this.get('suggestionRequestUri'), {
					'data': {
						'page_id': Supra.data.get(['page', 'id'])
					},
					'on': {
						'complete': this.onLoadItems
					}
				}, this);
			} else {
				this.suggestions = this.get('suggestions') || [];
				
				if (this.get('rendered')) {
					this.onLoadItems(this.suggestions, 1);
				}
			}
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
				values = this.get('values'),
				label = this.get('suggestedLabel');
			
			if (status && data && data.length) {
				data = this._normalizeValue(data);
				
				if (typeof label !== 'string') {
					label = Supra.Intl.get(['inputs', 'suggestions']);
				}
				
				this.suggestions = data;
				suggestionsListNode.set('innerHTML', TEMPLATE_VALUES({'values': data, 'label': label}));
				
				this.showSuggestionList();
				
				var select = this.get('suggestionsStrictNode');
				if (select) {
					select.set('values', 
						[
							{'id': '', 'title': this.get('suggestionsStrictLabel')}
						].concat(Y.Array.map(this.suggestions, function (value) {
							return {'id': value, 'title': value};
						}))
					);
				}
				
				if (values) {
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
			var key = Y.Event.charCodeFromEvent(e);
			
			if (key == this.KEY_RETURN || key == this.KEY_COMMA || key == this.KEY_SEMICOLON) {
				var inputValue = this.get('inputNode').get('value');
				this.addItem(inputValue);
				this.hideSuggestion(inputValue);
				this.get('inputNode').set('value', '');
				e.preventDefault();
			} else if (key == this.KEY_ESCAPE) {
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
			if (e.target.closest('.yui3-input-select')) return;
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
			
			//Events
			this.fire('change', {'value': this.get('value')});
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
				node = Y.one('.suggestions-list span[data-value="' + escaped + '"]'),
				select = this.get('suggestionsStrictNode');
			
			if (node) {
				node.addClass('hidden');
			}
			if (select) {
				node = select.getValueNode(suggestion);
				
				if (node) {
					node.addClass('hidden');
				}
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
				node = Y.one('.suggestions-list span[data-value="' + escaped + '"]'),
				select = this.get('suggestionsStrictNode');
			
			if (node) {
				node.removeClass('hidden');
			}
			if (select) {
				node = select.getValueNode(suggestion);
				
				if (node) {
					node.addClass('hidden');
				}
			}
			
			this.updateScrollbars();
		},
		
		/**
		 * Show suggestion list
		 * 
		 * @private
		 */
		showSuggestionList: function () {
			if (this.get('suggestionAutoVisible')) {
				this.get('boundingBox').addClass(this.getClassName('auto-suggestions'));
				this.get('suggestionsListNode').removeClass('hidden');
			} else {
				this.get('suggestionsButton').hide();
				this.get('suggestionsListNode').removeClass('hidden');
				this.get('clearAllLink').removeClass('hidden');
			}
		},

		/**
		 * Hide Suggestion List
		 * 
		 * @private
		 */
		closeSuggestionsList: function () {
			if (this.get('disabled') || this.get('suggestionAutoVisible')) return;
			
			var suggestionsButton = this.get('suggestionsButton'),
				suggestionsListNode = this.get('suggestionsListNode'),
				clearAllLink = this.get('clearAllLink'),
				boundingBox = this.get('boundingBox');

			if (suggestionsListNode) {
				suggestionsListNode.addClass('hidden');
			}
			if (clearAllLink) {
				clearAllLink.addClass('hidden');
			}
			if (suggestionsButton) {
				suggestionsButton.show();	
			}
			if (boundingBox) {
				boundingBox.removeClass(this.getClassName('auto-suggestions'));
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
				listNode = this.get('inputListNode'),
				tempNode = Y.Node.create('<span></span>'),
				limit = this.get('maxCount'),
				node = null;
							
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
			
			// Create node
			tempNode.set('text', value);
			tempNode.setAttribute('data-value', value);
			tempNode.appendChild('<a></a>');
			
			
			if (limit && values.length >= limit) {
				//If limit has been reached, then replace last item with this
				values[limit - 1] = value;
				
				//Replace node
				var nodes = listNode.all('span');
				node = nodes.item(nodes.size() - 1);
				
				this.showSuggestion(node.getAttribute('data-value'));
				node.replace(tempNode);
			} else {
				//Add item
				values.push(value);
				
				//Add node
				inputNode.insert(tempNode, 'before');
			}
			
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
		
		
		_uiSetSuggestionsStrict: function (strict) {
			if (strict) {
				var select = this.get('suggestionsStrictNode') || this._uiRenderSuggestionsStrict();
				
				this.get('suggestionsNode').addClass('hidden');
				this.get('inputNode').hide();
			} else {
				this.get('inputNode').show();
				
				if (this.get('suggestionsEnabled')) {
					this.get('suggestionsNode').removeClass('hidden');
				}
				
				this.get('suggestionsNode').addClass('hidden');
			}
		},
		
		/**
		 * Render suggestion dropdown
		 * 
		 * @returns {Object} Node
		 * @private
		 */
		_uiRenderSuggestionsStrict: function () {
			var values,
				node;
			
			values = [
					{'id': '', 'title': this.get('suggestionsStrictLabel')}
				].concat(Y.Array.map(this.suggestions, function (value) {
					return {'id': value, 'title': value};
				}));
			
			var node = new Supra.Input.Select({
				values: values
			});
			
			node.render(this.get('contentBox'));
			this.set('suggestionsStrictNode', node);
			this.get('inputListNode').insert(node.get('boundingBox'), 'after');
			
			node.after('valueChange', this._uiSuggestionsStrictChange, this);
			
			return node;
		},
		
		_uiSuggestionsStrictChange: function (e) {
			if (this._uiStrictFrozen) return;
			this._uiStrictFrozen = true;
			
			this.addItem(e.newVal);
			this.get('suggestionsStrictNode').set('value', '');
			
			this._uiStrictFrozen = false;
		},
		
		
		/* ------------------------------- ATTRIBUTES ------------------------------- */
		
		
		/**
		 * Normalize value to array with strings
		 * 
		 * @param {Array|String} value Value
		 * @returns {Array} Array with keywords
		 * @private
		 */
		_normalizeValue: function (value) {
			if (Y.Lang.isArray(value)) {
				var out = [],
					i   = 0,
					ii  = value.length;
				
				for (; i<ii; i++) {
					if (typeof value[i] === 'string') {
						out.push(value[i]);
					} else if (Y.Lang.isObject(value[i])) {
						if ('title' in value[i]) {
							out.push(value[i].title);
						} else if ('name' in value[i]) {
							out.push(value[i].name);
						}
					}
				}
				
				return out;
			} else if (typeof value === 'string') {
				return value ? value.split(';') : [];
			}
			
			return [];
		},
		
		/**
		 * Value attribute setter
		 * 
		 * @param {String|Array} value List of keywords
		 * @returns {String} New value
		 * @private 
		 */
		_setValue: function (value) {
			var value = this._normalizeValue(value),
				value_str = '',
				limit = this.get('maxCount');
			
			if (limit && value.length >= limit) {
				value = value.slice(0, limit);
			}
			
			value_str = value.join(';');
			
			this.get('inputNode').set('value', value_str);
			this.set('values', value);
			this.closeSuggestionsList();
			
			this.syncUI();
			return value_str;
		},
		
		/**
		 * Value attribute getter
		 * 
		 * @returns {String} Value
		 * @private
		 */
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
				if (this.get('suggestionsStrict')) {
					this.loadItems();
				} else {
					this.get('suggestionsNode').removeClass('hidden');
					
					if (this.get('suggestionAutoVisible')) {
						this.loadItems();
					}
				}
			} else {
				this.closeSuggestionsList();
				this.get('suggestionsNode').addClass('hidden');
			}
			
			return !!enabled;
		},
		
		/**
		 * suggestionsStrict attribute setter
		 * 
		 * @param {Boolean} value
		 * @return New value
		 * @type {Boolean}
		 * @private
		 */
		_setSuggestionsStrict: function (strict) {
			strict = !!strict;
			
			if (!this.get('rendered')) return strict;
			
			this._uiSetSuggestionsStrict(strict);
			
			return strict;
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
		
		/**
		 * Max count attribute setter
		 * 
		 * @param {Number|Null} value
		 * @returns {Number} New value
		 * @private
		 */
		_setMaxCount: function (value) {
			if (!value) return null;
			var values = this.get('values'),
				list   = this.get('inputListNode'),
				nodes  = null;
			
			if (values && values.length > value) {
				this.set('values', values.slice(0, value));
				
				if (list) {
					nodes = list.all('span');
					
					for (var i=nodes.size - 1; i >= value; i--) {
						nodes.item(i).remove();
					}
				}
			}
		}
	
	});
	
	Supra.Input.Keywords = Input;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn);this.fn = function () {};
	
}, YUI.version, {requires:["supra.input-proto"]});
