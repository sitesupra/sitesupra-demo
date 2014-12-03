(function (demo) {
	
	//
	// Patch input
	//
	function Patch (options) {
		this.node = options.node;
		this.items = {};
		this._value = options.value || '';
		this._valueMap = {};
		this._backgroundColor = options.background || null;
		
		if (options.values) {
			this.values(options.values);
		}
		
		this.node.on('mousedown', 'a', $.proxy(this._uiItemClick, this));
	}
	Patch.prototype = {
		
		_uiItemClick: function (e) {
			this.value($(e.target).closest('a').data('id'));
			return false;
		},
		
		value: function (value) {
			if (value && this._value != value) {
				if (this.items[this._value]) {
					this.items[this._value].removeClass('active');
				}
				if (this.items[value]) {
					this.items[value].addClass('active');
				}
				
				this._value = value;
				$(this).trigger('change', {
					'value': value,
					'valueData': this.getValueData(value)
				});
			} else {
				return this._value;
			}
		},
		
		getValueData: function (id) {
			var values = this._values || [],
				i      = 0,
				ii     = values.length;
			
			for (; i<ii; i++) {
				if (values[i].id == id) {
					return values[i];
				}
			}
			
			return null;
		},
		
		values: function (values) {
			var map   = this._valueMap = {},
				node  = this.node,
				item  = null,
				items = this.items = {},
				
				i   = 0,
				ii  = values.length,
				val = null,
				
				value = this._value || (ii ? values[0].id : ''),
				
				bgColor = this._backgroundColor;
			
			this._value = value;
			this._values = values;
			node.empty();
			
			for (; i<ii; i++) {
				val = values[i];
				map[val.id] = val;
				
				if (val.colors && val.colors.length) {
					item = $('<a data-id="' + val.id + '" ' + (val.id == value ? 'class="active"' : '') + '>' +
									'<span class="c-color" style="background: ' + val.colors[0] + ';"></span>' +
									'<span class="c-color" style="background: ' + val.colors[1] + ';"></span>' +
									'<span class="c-color" style="background: ' + val.colors[2] + ';"></span>' +
								  '</a>');
				} else if (val.icon) {
					item = $('<a data-id="' + val.id + '" ' + (val.id == value ? 'class="active"' : '') + '><span class="c-left"><span class="c-right">' +
									'<span class="c-icon" style="background: ' + (bgColor ? bgColor + ' ' : (val.backgroundColor ? val.backgroundColor + ' ' : '')) + 'url(' + val.icon + ') 0 0 repeat;"></span>' +
								  '</span></span></a>');
				} else {
					item = $('<a data-id="' + val.id + '" ' + (val.id == value ? 'class="active"' : '') + '><span class="c-left"><span class="c-right">' +
									'<span class="c-icon" style="background: ' + (bgColor ? bgColor : (val.backgroundColor || '#ffffff')) + ';"></span>' +
								  '</span></span></a>');
				}
				
				node.append(item);
				items[val.id] = item;
			}
		},
		
		on: function (event, handle) {
			return $(this).on(event, handle);
		}
	};
	
	demo.Patch = Patch;
	
})(demo);