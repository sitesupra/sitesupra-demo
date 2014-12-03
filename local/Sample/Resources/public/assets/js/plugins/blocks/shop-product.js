/**
 * Shop product block
 * @version 1.0.0
 */
"use strict";

(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(['jquery', 'refresh/refresh', 'plugins/helpers/responsive', 'plugins/helpers/input-mask'], function ($) {
            return factory($);
        });
    } else {
        // AMD is not supported, assume all required scripts are
        // already loaded
        factory(jQuery);
    }
}(this, function ($) {
	
	var DATA_INSTANCE_PROPERTY = 'shop-product',
		STANDARD_OPTIONS = {
			'price': true,
			'sale_price': true,
			'on_sale': true,
			'weight': true
		};
	
	
	/**
	 * Variant matrix
	 * 
	 * @param {Object} data Variant data
	 * @param {Object} options Property names for which to create matrix
	 */
	function VariantMatrix (data, property_names) {
		this.data = data;
		this.cache = {};
		this.setPropertyNames(property_names);
	}
	
	VariantMatrix.prototype = {
		/**
		 * Data
		 * @type {Object}
		 */
		'data': null,
		
		/**
		 * Property names
		 * @type {Array}
		 */
		'property_names': null,
		
		/**
		 * List of properties
		 * @type {Array}
		 */
		'properties': null,
		
		/**
		 * List of properties mapped by id
		 * @type {Object}
		 */
		'properties_by_id': null,
		
		/**
		 * List of properties mapped by name
		 * @type {Object}
		 */
		'properties_by_name': null,
		
		/**
		 * Cached options
		 * @type {Object}
		 */
		'cache': null,
		
		/**
		 * Set property names
		 * 
		 * @param {Array} property_names Property names
		 */
		'setPropertyNames': function (property_names) {
			var by_id      = this.properties_by_id   = {},
				by_name    = this.properties_by_name = {},
				properties = this.properties         = [],
				i  = 0,
				ii = property_names.length,
				id,
				name,
				
				standard_options = STANDARD_OPTIONS;
			
			for (; i<ii; i++) {
				name = property_names[i];
				
				if (!(name in standard_options)) {
					id = this.getIdByName(name);
					
					properties.push(by_id[id] = by_name[name] = {
						'id': id,
						'name': name,
						'index': i
					});
				}
			}
			
			this.property_names = property_names;
		},
		
		/**
		 * Returns data which match filter
		 * 
		 * @param {Object} filter Filter values
		 * @param {Boolean} first Returns only first record
		 * @returns {Array|Object} List of matching data or first matching item
		 */
		'getData': function (filter, first) {
			var cache_id = this.serializeFilter(filter),
				cache    = this.cache;
			
			if (cache_id in cache) {
				return !first ? cache[cache_id] : (cache[cache_id].length ? cache[cache_id][0] : null);
			}
			
			var data = this.data,
				i    = 0,
				ii   = data.length,
				
				by_id = this.properties_by_id,
				by_name = this.properties_by_name,
				name,
				id,
				match,
				matches = [];
			
			// Itterate through all data to find variants which match
			for (; i<ii; i++) {
				match = true;
				
				if (!data[i].unlimited_stock && data[i].in_stock <= 0) {
					// Out of stock
					continue;
				}
				
				for (id in filter) {
					// Filter are by id, data is by bane
					name = (by_name[id] || by_id[id]).name;
					
					if (filter[id] !== data[i][name]) {
						match = false;
					}
				}
				
				if (match) {
					if (first) {
						return data[i];
					} else {
						matches.push(data[i]);
					}
				}
			}
			
			return first ? null : (cache[cache_id] = matches);
		},
		
		/**
		 * Returns options for property which match filter
		 * 
		 * @param {Object} filter Filter values
		 * @param {String} property Property name
		 * @returns {Array} List of property values
		 */
		'getOptions': function (filter, property) {
			var by_id = this.properties_by_id,
				by_name = this.properties_by_name,
				property_name = (property ? (by_id[property] || by_name[property]).name : null),
				
				data = this.getData(filter),
				i    = 0,
				ii   = data.length,
				
				options = [];
			
			for (; i<ii; i++) {
				options.push(data[i][property_name]);
			}
			
			return $.unique(options).reverse();
		},
		
		/**
		 * Returns properties
		 * 
		 * @returns {Array} Properties
		 */
		'getProperties': function () {
			return this.properties;
		},
		
		/**
		 * Serialize filter into a string
		 * 
		 * @param {Object} filter Filter values
		 * @return {String} Serialized filter values into a string
		 */
		'serializeFilter': function (filter) {
			var filter_arr = [],
				key,
				by_id = this.properties_by_id,
				by_name = this.properties_by_name,
				item,
				serialized = [];
			
			// Convert to array
			for (key in filter) {
				item = (by_id[key] || by_name[key]);
				filter_arr.push({'name': item.name, 'value': filter[key], 'index': item.index});
			}
			
			// Sort filters by natural order
			filter_arr.sort(function (a, b) {
				return a.index > b.index ? 1 : -1;
			});
			
			// Convert to string
			serialized = $.map(filter_arr, function (filter) {
				return filter.name + ':' + filter.value;
			});
			
			return serialized.join(',');
		},
		
		/**
		 * Returns id by name
		 * 
		 * @param {String} name Name
		 * @returns {String} id Id
		 */
		'getIdByName': function (name) {
			var id = name.toLowerCase().replace(/[^a-z0-9_]+/g, '_').replace(/_{2,}/g, '_').replace(/(^_+|_+$)/g, '');
			if (!id) {
				id = 'data' + (+(new Date()) + '' + Math.floor(Math.random()*60000));
			}
			return id;
		}
	};
	
	
	/**
	 * Widget
	 * 
	 * @param {Object} element Container element
	 * @param {Object} options Configuration options
	 */
	function ShopProduct (element, options) {
		this.element = element;
		this.options = $.extend({
			'data': typeof window.variantsData === 'object' ? window.variantsData : {},
			'options': typeof window.variantsOptions === 'object' ? window.variantsOptions : {},
			'currencySymbol': typeof window.currencySymbol === 'string' ? window.currencySymbol : '$'
		}, options);
		
		this.createVariantsMatrix(this.options.data, this.options.variants);
		this.renderVariants();
		
		this.attachQuantityLimit();
		
		// Set correct initial data
		this.updateOutput(null);
	}
	
	ShopProduct.prototype = {
		/**
		 * VariantMatrix instance
		 * @type {Object}
		 */
		'matrix': null,
		
		/**
		 * Widgets / nodes
		 * @type {Object}
		 */
		'widgets': null,
		
		/**
		 * Current filter values for matrix
		 * @type {Object}
		 */
		'filters': null,
		
		/**
		 * Quantity inpput
		 * @type {Object}
		 */
		'inputQuantity': null,
		
		/**
		 * Create matrix with all variants
		 * 
		 * @param {Object} variants List of all variants
		 */
		'createVariantsMatrix': function (variants) {
			this.matrix = window.matrix = new VariantMatrix(this.options.data, this.options.options);
		},
		
		/**
		 * Render inputs and nodes for variants
		 */
		'renderVariants': function () {
			var properties = this.matrix.getProperties(),
				i = 0,
				ii = properties.length,
				widgets = this.widgets = {},
				dropdown = null,
				node = null,
				
				container = this.element.find('.custom-product-variants'),
				
				filters = {},
				dependancies = [],
				data = this.matrix.getData({}, true),
				
				values;
			
			for (; i<ii; i++) {
				values = this.matrix.getOptions(filters, properties[i].name);
				node = $('<p class="custom ' + (values.length == 1 && values[0] ? '' : 'hidden') + '"><span class="prop-name">' + this.escape(properties[i].name) + ': </span><span class="prop-value">' + (values.length == 1 ? this.escape(values[0]) : '') + '</span></p>');
				dropdown = this.renderDropdown(properties[i], values, data[properties[i].name]);
				
				widgets[properties[i].name] = {
					'node': node,
					'nodeValue': node.find('span.prop-value'),
					'dropdown': dropdown,
					'dropdownInput': dropdown.find('select')
				};
				
				container.append(dropdown);
				container.append(node);
				
				dropdown.on('change', $.proxy(this.handlePropertyChange, this));
				
				dependancies = [].concat(dependancies).concat(properties[i].name);
				filters[properties[i].name] = data[properties[i].name];
			}
			
			$.app.parse(container);
		},
		
		/**
		 * Render dropdown element
		 */
		'renderDropdown': function (property, values, value) {
			var values_html = '',
				i = 0,
				ii = values.length;
			
			for (; i<ii; i++) {
				values_html += '<option value="' + this.escape(values[i], 'html_attr') + '"' + (values[i] == value ? ' selected="selected"' : '') + '>' + this.escape(values[i]) + '</option>';
			}
			
			return $('<div class="row ' + (ii > 1 ? '': 'hidden') + '">' +
					 	'<label for="field_' + property.id + '">' + this.escape(property.name) + '</label>' +
					 	'<select name="' + this.escape(property.name, 'html_attr') + '" id="field_' + property.id + '" data-attach="$.fn.dropdown" data-require="plugins/widgets/dropdown">' +
					 		values_html +
					 	'</select>' +
				 	 '</div>');
		},
		
		'handlePropertyChange': function (e) {
			var input = $(e.target),
				name = input.attr('name');
			
			this.updateOutput(name);
		},
		
		'updateOutput': function (name) {
			var widgets = this.widgets,
				
				properties = this.matrix.getProperties(),
				i = 0,
				ii = properties.length,
				
				found = false,
				filters = {};
			
			for (; i<ii; i++) {
				if (properties[i].name === name) {
					found = true;
				} else if (found) {
					// Must update
					this.updatePropertyOptions(properties[i].name, this.matrix.getOptions(filters, properties[i].name));
				}
				
				filters[properties[i].name] = widgets[properties[i].name].dropdownInput.val();
			}
			
			this.filters = filters;
			this.updateVariationOutput(this.matrix.getData(filters, true));
		},
		
		/**
		 * 
		 */
		'updatePropertyOptions': function (name, options) {
			var widget = this.widgets[name],
				value = widget.dropdownInput.val(),
				i = 0,
				ii = options.length,
				options_html = '',
				input = widget.dropdownInput;
			
			if ($.inArray(value, options) === -1) {
				value = options[0] || '';
			}
			
			if (options.length == 0 || (options.length == 1 && !options[0])) {
				// There are no options
				input.find('option').remove();
				input.append($('<option value="" selected="selected"></option>'));
				
				widget.dropdown.addClass('hidden');
				widget.node.addClass('hidden');
			} else if (options.length == 1) {
				input.find('option').remove();
				input.append($('<option value="' + this.escape(options[0], 'html_attr') + '" selected="selected">' + this.escape(options[0]) + '</option>'));
				widget.nodeValue.text(options[0]);
				
				widget.dropdown.addClass('hidden');
				widget.node.removeClass('hidden');
				input.dropdown('update');
			} else {
				input.find('option').remove();
				for (; i<ii; i++) {
					options_html += '<option value="' + this.escape(options[i], 'html_attr') + '"' + (value == options[i] ? ' selected="selected"' : '') + '>' + this.escape(options[i]) + '</option>';
				}
				
				input.append(options_html);
				widget.dropdown.removeClass('hidden');
				widget.node.addClass('hidden');
				input.dropdown('update');
			}
		},
		
		/**
		 * 
		 */
		'updateVariationOutput': function (data) {
			// Price
			var node_price = this.element.find('.price').not('.old-price, .sale-price'),
				node_price_new = this.element.find('.sale-price'),
				node_price_old = this.element.find('.old-price');
			
			if ((data.on_sale === true || data.on_sale === "true") && data.price != data.sale_price) {
				node_price.addClass('hidden');
				node_price_new.removeClass('hidden').text(this.options.currencySymbol + this.formatNumber(data.sale_price));
				node_price_old.removeClass('hidden').find('strike').text(this.options.currencySymbol + this.formatNumber(data.price));
			} else {
				node_price.removeClass('hidden').text(this.options.currencySymbol + this.formatNumber(data.price));
				node_price_new.addClass('hidden');
				node_price_old.addClass('hidden');
			}
			
			// SKU
			this.element.find('input[name^="addToOrder"]').attr('name', 'addToOrder[' + data.sku + ']');
			
			// Quantity
			this.validateQuantityAmount();
		},
		
		/**
		 * Limit amount which user can enter in the field
		 */
		'attachQuantityLimit': function () {
			var input = this.inputQuantity = this.element.find('input[name="quantity"]'),
				button = this.submitButton = this.element.find('button[type="submit"]'),
				message = this.stockMessage = this.element.find('label.error'),
				row_quantity = this.element.find('.row-quantity');
			
			if (this.matrix.getData({}, true)) {
				input.valueMask(/^[0-9]*$/);
				input.on('keyup', $.proxy(this.validateQuantityAmount, this));
				
				this.validateQuantityAmount();
			}
		},
		
		/**
		 * On quantity change check stock
		 */
		'validateQuantityAmount': function (e) {
			var data = this.matrix.getData(this.filters, true),
				input = this.inputQuantity,
				button = this.submitButton,
				message = this.stockMessage,
				timer = this.validateQuantityTimer,
				
				old_val = parseInt(input.val(), 10),
				new_val = old_val;
			
			if (!data.unlimited_stock && new_val > data.in_stock) { // stock
				new_val = Math.max(0, data.in_stock);
				
				// show error
				input.parent().addClass('error');
				message.removeClass('hidden');
				
				// hide error after 2 sec
				if (timer) {
					clearTimeout(timer);
				}
				this.validateQuantityTimer = setTimeout(function () {
					input.parent().removeClass('error');
					message.addClass('hidden');
				}, 5000);
			} else if (new_val > 999999999) { // integer limit (kinda)
				new_val = 999999999;
			}
			
			if (old_val != new_val && !isNaN(new_val)) {
				// normal validation, replace value
				input.val(new_val);
			}
			
			// If value is not valid then disable submit button
			if (isNaN(new_val) || new_val === 0) {
				button.prop('disabled', true);
				button.addClass('disabled');
			} else {
				button.prop('disabled', false);
				button.removeClass('disabled');
			}
			
			if (!data.unlimited_stock) {
				message.find('span').text(Math.max(0, data.in_stock));
			} else {
				// There can't be an error
				input.parent().removeClass('error');
				message.addClass('hidden');
			}
		},
		
		'formatNumber': function (number) {
			return parseFloat(number).toFixed(2);
		},
		
		/**
		 * Escape string for use in HTML
		 * 
		 * @param {String} str String to escape
		 * @param {String} type Optional, either 'html' or 'html_attr'. Default is 'html'
		 * @returns {String} Escaped string
		 */
		'escape': function (str, type) {
			if (type == 'html_attr') {
				return (''+str)
					.replace(/&/g, '&amp;')
					.replace(/'/g, '&apos;')
					.replace(/"/g, '&quot;')
					.replace(/</g, '&lt;')
					.replace(/>/g, '&gt;')
			        .replace(/\r\n/g, '\n')
			        .replace(/[\r\n]/g, '\n');
			} else {
				return (''+str).replace(/&/g, '&amp;')
							   .replace(/</g, '&lt;')
							   .replace(/>/g, '&gt;')
							   .replace(/"/g, '&quot;')
							   .replace(/'/g, '&#39;');
			}
		},
		
		/**
		 * Escape character for use in html attribute value
		 * 
		 * @private
		 */
		'_escapeHTMLAttr': function (matches) {
			var chr = matches[0],
				ord = chr.charCodeAt(0),
				hex = '',
				entities = {34: 'quot', 38: 'amp', 60: 'lt', 62: 'gt'};
			
			if (entities[ord]) {
				return '&' + entities[ord] + ';';
			}
			
			// Characters undefined in HTML
			if ((ord <= 0x1f && chr != "\t" && chr != "\n" && chr != "\r") || (ord >= 0x7f && ord <= 0x9f)) {
				return '&#xFFFD;';
			}
		    
			hex = ('00' + ord.toString(16)).substr(-2).toUpperCase();
			return '&#x' + hex + ';';
		},
		
		/**
		 * Destructor
		 */
		'destroy': function () {
			
		}
		
	};
	
	
	/*
	 * jQuery plugin
	 * Create widget or apply options or call a function
	 */
	$.fn.shopProduct = function (prop) {
		var options = typeof prop === 'object' ? prop : null,
			fn = typeof prop === 'string' && typeof ShopProduct.prototype[prop] === 'function' && prop[0] !== '_' ? prop : null;
		
		return this.each(function () {
			
			var element = $(this),
				widget = element.data(DATA_INSTANCE_PROPERTY);
			
			if (!widget) {
				widget = new ShopProduct (element, $.extend({}, element.data(), options || {}));
				element.data(DATA_INSTANCE_PROPERTY, widget);
			} else {
				if (fn) {
					widget[fn].call(widget);
				}
			}
		});
	};
	
	//$.refresh implementation
	$.refresh.on('refresh/shop-product', function (event, info) {
		info.target.shopProduct(info.target.data());
	});
	
	$.refresh.on('cleanup/shop-product', function (event, info) {
		var instance = info.target.data(DATA_INSTANCE_PROPERTY);
		if (instance) {
			instance.destroy();
			info.target.data(DATA_INSTANCE_PROPERTY, null)
		}
	});
	
	
}));