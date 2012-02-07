//Invoke strict mode
"use strict";

/**
 * Keyword input
 */
YUI.add("website.input-keywords", function (Y) {
	
	function Input (config) {
		Input.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
		
		this._templates = null;
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
		"keywordRequestUri": {
			value: ""
		},
		"keywords": {
			value: null
		}
	};
	
	Input.HTML_PARSER = {};
	
	Y.extend(Input, Supra.Input.Proto, {
		INPUT_TEMPLATE: '<input type="text" value="" />',
		LABEL_TEMPLATE: null,
		
		
		
		/**
		 * Key code constants
		 */
		KEY_RETURN: 13,
		KEY_ESCAPE: 27,
		
		/**
		 * Add needed nodes, etc.
		 * 
		 * @private
		 */
		renderUI: function () {
			Input.superclass.renderUI.apply(this, arguments);
			
			var inputNode = this.get('inputNode'),
				inputListNode = Y.Node.create('<div class="input-list"></div>');
			
			inputNode.insert(inputListNode, 'after');
			inputListNode.append(inputNode);
			
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
				inputNode = this.get('inputNode');
			
			//On keyword click remove it
			inputListNode.delegate('click', this._onRemoveKeyword, 'span', this);
			
			//On click inside focus on input
			inputListNode.on('click', this.focus, this);
			
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
					inputListNode.prepend(tempNode);
				}
			}
			
			this.get('inputNode').set('value', '');
			
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
				this.addKeyword(this.get('inputNode').get('value'));
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
			if (!keyword.length || Y.Array.indexOf(keywords, keyword) != -1) {
				return;
			}
			
			//Add keyword
			keywords.push(keyword);
			
			//Add node
			tempNode.set('text', keyword);
			tempNode.setAttribute('data-keyword', keyword);
			
			inputNode.insert(tempNode, 'before');
			
			//Events
			this.fire('change', {'value': this.get('value')});
		},
		
		_setValue: function (value) {
			console.log('_setValue(', value, ')');
			this.get('inputNode').set('value', value);
			this.set('keywords', value ? value.split(';') : []);
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
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:["supra.input-proto"]});