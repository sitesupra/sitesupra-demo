//Invoke strict mode
"use strict";

YUI.add('website.input-slider-cashier', function (Y) {
	
	function Input (config) {
		Input.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	Input.NAME = 'input-slider-cashier';
	Input.CSS_PREFIX = 'su-' + Input.NAME;
	
	Input.CLASS_NAME = Y.ClassNameManager.getClassName(Input.NAME);
	Input.ATTRS = {
		/**
		 * Value/option list
		 */
		'values': {
			value: []
		},
		/**
		 * Slider length in px
		 */
		'length': {
			value: 412
		},
	};
	
	Input.HTML_PARSER = {
		'values': function () {
			var input = this.get('inputNode'),
				values = [];
			
			if (input && input.test('select')) {
				var options = Y.Node.getDOMNode(input).options;
				for(var i=0,ii=options.length; i<ii; i++) {
					values.push({
						'id': options[i].value,
						'title': options[i].text
					});
				}
			} else {
				values = this.get('values') || [];
			}
			
			return values;
		},
		'length': function (srcNode) {
			var input = this.get('inputNode');
			return input.getAttribute('suLength') || 412;
		}
	};
	
	Y.extend(Input, Supra.Input.Proto, {
		INPUT_TEMPLATE: '<select class="hidden"></select>',
		LABEL_TEMPLATE: '<label></label>',
		
		/**
		 * Slider instance
		 * @see Y.Slider
		 * @type {Object}
		 * @private
		 */
		slider: null,
		
		/**
		 * Rail node
		 * @type {Object}
		 * @private
		 */
		rail: null,
		
		/**
		 * Label inputs
		 * @type {Array}
		 * @private
		 */
		labels: null,
		
		bindUI: function () {
			var input = this.get('inputNode');
			input.on('focus', this._onFocus, this);
			input.on('blur', this._onBlur, this);
			
			//Handle value attribute change
			this.on('valueChange', this._afterValueChange, this);
		},
		
		_onFocus: function () {
			if (this.get('boundingBox').hasClass('su-input-focused')) return;
			
			this.get('boundingBox').addClass('su-input-focused');
			this.get('inputNode').focus();
		},
		_onBlur: function () {
			this.get('boundingBox').removeClass('su-input-focused');
		},
		
		renderUI: function () {
			Input.superclass.renderUI.apply(this, arguments);
			
			var values = this.get('values'),
				value = this.get('value'),
				index = 0,
				has_value_match = false,
				contentBox = this.get('contentBox'),
				input = Y.Node.getDOMNode(this.get('inputNode'));
			
			//No need to add options if they already exist
			if (!input.options || input.options.length) {
				input = null;
			}
			
			if (contentBox.test('input,select')) {
				contentBox = this.get('boundingBox');
			}
			
			//Rail node
			this.rail = Y.Node.create('<div class="yui3-slider-rail-background"><div></div></div>');
			
			//Slide will be placed instead of input
			var labelsNode = Y.Node.create('<div class="labels"></div>'),
				labelNode = null;
			
			this.get('inputNode').addClass('hidden');
			
			for(var i=0,ii=values.length-1; i<=ii; i++) {
				
				if (input) {
					//Add options to allow selecting value
					input.options[input.options.length] = new Option(values[i].title, values[i].id);
					if (value == values[i].id) input.value = value;
				}
				
				labelNode = Y.Node.create('<label class="label-' + (i+1) + '"><span></span>' + Y.Escape.html(values[i].title) + '</label>');
				labelsNode.append(labelNode);
				
				if (values[i].id == value) {
					//Mark value as found
					labelNode.addClass('active');
					has_value_match = true;
					index = i;
					
					//Rail offset
					var offset = Math.min(this.get('length'), 1 / (values.length - 1) * index * this.get('length') + 14);
					this.rail.setStyle('width', offset + 'px');
				}
				
			}
			
			//Labels
			this.labels = labelsNode.all('label');
			
			//Create slider
			this.slider = new Y.Slider({
				'axis': 'x',
				'min': 0,
				'max': values.length - 1,
				'value': index,
				'length': this.get('length')
			});
			this.slider.after('slideEnd', this._onChange, this);
			this.slider.after('slideEnd', this.slider.syncUI, this.slider);
			
			this.slider.after('railMouseDown', this._onChange, this);
			this.slider.after('railMouseDown', this.slider.syncUI, this.slider);
			
			this.slider.after('thumbMove', this._onMove, this);
			
			this.slider.render(contentBox);
			
			//Rail node
			this.slider.rail.append(this.rail);
			this.slider.rail.append(labelsNode);
			
			//Set value
			if (!has_value_match) {
				if (values.length) {
					value = values[0].id;
					this.labels.item(0).addClass('active');
					this.rail.setStyle('width', '0px');
					if (input) input.value = value;
				}
			}
			
		},
		
		_onMove: function (event) {
			if (this.rail) {
				var offset = event.offset + 14;
				if (offset > this.get('length') - 20) {
					offset = this.get('length');
				}
				
				this.rail.setStyle('width', offset + 'px');
			}
		},
		
		_onChange: function (event) {
			var index = this.slider.get('value'),
				values = this.get('values'),
				value = values[index].id;
			
			this.set('value', value);
		},
		
		_setValue: function (value) {
			if (!this.get('rendered')) return value;
			
			this.get('inputNode').set('value', value);
			
			if (this.slider) {
				var index = 0,
					values = this.get('values');
				
				for(var i=0,ii=values.length; i<ii; i++) if (values[i].id == value) {
					index = i;
					break;
				}
				
				if (this.slider.get('value') != index) {
					this.slider.set('value', index);
				}
				
				//Fade out label style
				this._fadeOutLabel();
				this._fadeInLabel(index);
			}
			
			return value;
		},
		
		/**
		 * Fade out currently selected item label
		 * 
		 * @private
		 */
		_fadeOutLabel: function () {
			this.labels.each(function (label) {
				if (label.hasClass('active')) {
					label.removeClass('active');
					label.one('span').setStyle('display', 'block').transition({
						'opacity': 0,
						'duration': 0.35
					});
				}
			});
		},
		
		/**
		 * Fade in label by index
		 * 
		 * @param {Number} index Selected item index
		 * @private
		 */
		_fadeInLabel: function (index) {
			var label = this.labels.item(index),
				span  = label.one('span');
			
			label.addClass('active');
			
			span.setStyles({
				'display': 'block',
				'opacity': 0
			}).transition({
				'opacity': 1,
				'duration': 0.35
			});
		},
		
		_getValue: function () {
			return this.get('inputNode').get('value');
		},
		
		_afterValueChange: function (evt) {
			if (evt.prevVal != evt.newVal) {
				this.fire('change', {'value': evt.newVal});
			}
		},
		
		_setDisabled: function (value) {
			value = Input.superclass._setDisabled.apply(this, arguments);
			
			//Disable slider
			if (this.slider) this.slider.set('disabled', value);
			
			return value;
		},
		
		/**
		 * Reset value to default
		 */
		resetValue: function () {
			var value = this.get('defaultValue'),
				values = this.get('values');
			
			this.set('value', value !== null ? value : (values.length ? values[0].id : ''));
			return this;
		},
		
	});
	
	Supra.Input.SliderCashier = Input;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.input-proto', 'slider', 'transition']});