//Invoke strict mode
"use strict";

YUI.add('website.permission-list', function (Y) {
	
	
	var Manager = Supra.Manager;
	
	
	
	function PermissionList (config) {
		PermissionList.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
		this.removed = {};
	}
	
	PermissionList.NAME = 'permission-list';
	
	PermissionList.ATTRS = {
		/**
		 * Tree instance which is drag source
		 * @type {Object}
		 */
		'tree': {
			value: null
		},
		
		/**
		 * Tree is localized
		 */
		'localized': false,
		
		/**
		 * Sublabel text
		 * @type {String}
		 */
		'sublabel': {
			value: null,
			setter: '_setSubLabel'
		},
		
		/**
		 * Sub property
		 * @type {Object}
		 */
		'subproperty': {
			value: null
		},
		
		/**
		 * Label node
		 * @type {Object}
		 */
		'labelNode': {
			value: null
		}
	},
	
	PermissionList.CLASS_NAME = Y.ClassNameManager.getClassName(PermissionList.NAME);
	
	
	/* 
     * The HTML_PARSER static constant is used by the Widget base class to populate 
     * the configuration for the button instance from markup already on the page.
     *
     * The Button class attempts to set the label, style, disabled, wrapper element of the Button widget if it
     * finds the appropriate elements on the page
     */
	PermissionList.HTML_PARSER = {
		'labelNode': function (srcNode) {
			var node = srcNode.one('label');
			if (!node) {
				node = Y.Node.create('<label class="lbl"></labe>');
				srcNode.append(node);
			}
			return node;
		}
	};
	
	
	Y.extend(PermissionList, Y.Widget, {
		
		/**
		 * Y.DD.Drop instance
		 * @type {Object}
		 * @see Y.DD.Drop
		 * @private
		 */
		dd: null,
		
		/**
		 * Permission properties
		 * @type {Object}
		 * @private
		 */
		data: null,
		
		/**
		 * List of removed items
		 * @type {Object}
		 * @private
		 */
		removed: null,
		
		
		
		/**
		 * Add nodes
		 */
		renderUI : function() {
			
			//Data
			this.data = [];
			
			//Add drag and drop support
			this.dd = new Y.DD.Drop({
				'node': this.get('contentBox')
			});
			
			if (this.get('sublabel')) {
				this._setSubLabel(this.get('sublabel'));
			}
		},
		
		/**
		 * 
		 */
		syncUI: function () {
			
		},
		
		/**
		 * Bind even listeners
		 */
		bindUI: function () {
			this.dd.on('drop:hit', this.onTreeNodeDrop, this);
			
			this.on('visibleChange', function (event) {
				if (event.newVal != 1) {
					this.resetValue();
				}
			}, this);
			
			this.get('contentBox').delegate('click', this.removeItem, 'a.remove', this);
		},
		
		/**
		 * Handle node drop
		 * 
		 * @param {Event} event Event
		 * @private
		 */
		onTreeNodeDrop: function (event /* Event */) {
			var drag = Y.DD.DDM.activeDrag,
				drag_node = drag.get('node').ancestor(),
				node_id = drag_node.getData('nodeId');
			
			this.addPermissionException(node_id);
		},
		
		/**
		 * Returns language information from locale
		 *
		 * @param {String} locale
		 * @return Language information
		 * @type {Object}
		 */
		getLanguageByLocale: function (locale) {
			var data = Supra.data.get('contexts'),
				langs = null;
			
			for(var i=0,ii=data.length; i<ii; i++) {
				langs = data[i].languages;
				for(var k=0, kk=langs.length; k<kk; k++) {
					if (langs[k].id == locale) return langs[k];
				}
			}
			
			return null;
		},
		
		/**
		 * Add permission exception
		 * 
		 * @param {String} node_id Tree node ID
		 */
		addPermissionException: function (data) {
			
			var tree = this.get('tree'),
				values = null,
				localized = this.get('localized'),
				locale = '',
				flag = '<img src="/cms/lib/supra/img/flags/16x11/px.png" alt="" />',
				existing = false;
			
			if (typeof data == 'object' && !Y.Lang.isArray(data)) {
				values = data.value;
				locale = data.locale;
				existing = true;
			} else {
				data = tree.getNodeById(data).get('data');
				locale = this.get('languagebar').get('locale');
			}
			
			if (localized) {
				var lang = locale ? this.getLanguageByLocale(locale) : null,
				flag = '<img src="/cms/lib/supra/img/flags/16x11/' + (lang ? lang.flag : 'blank') + '.png" alt="" />';
			} else {
				locale = '';
			}
			
			//Remove from removed
			if (data.id in this.removed) {
				delete(this.removed[data.id]);
			}
			
			//Check if it's not already in the list
			for(var i=0,ii=this.data.length; i<ii; i++) {
				if (typeof this.data[i] != 'object') {
					if (this.data[i] == data.id) return false;
				} else {
					if (this.data[i].get('id') == data.id) return false;
				}
			}
			
			//Add property
			var node = Y.Node.create('<div class="' + Y.ClassNameManager.getClassName(PermissionList.NAME, 'item') + '"></div>'),
				subproperty = this.get('subproperty');
			
			if (subproperty) {
				
				var title = data.title;
				if(!title) {
					title = tree.getNodeById(data.id).get('data').title;
				}
				
				subproperty = Supra.mix({}, subproperty, {
					'label': title || '',
					'id': data.id,
					'locale': locale
				});
				
				this.data[i] = Supra.Form.factoryField(subproperty);
				this.data[i].render(node);
				
				if (values) {
					this.data[i].set('value', values);
				}
				
				//Add flag to label
				if (flag) {
					flag = Y.Node.create(flag);
					if (flag) this.data[i].get('labelNode').prepend(flag);
				}
				
				//Add remove button
				var button = Y.Node.create('<a class="remove"></a>');
				this.data[i].get('contentBox').append(button);
				
				button.setData('item-locale', locale);
				button.setData('item-id', data.id);
				
				//When property changes fire event on this
				this.data[i].after('valueChange', function () {
					this.fire('change', {'subtype': 'change', 'id': data.id, 'locale': locale});
				}, this);
				
			} else {
				node.set('innerHTML', '<p>' + Y.Escape.html(data.title) + '</p>');
			}
			
			this.get('labelNode').insert(node, 'before');
			
			//Execute event
			if (!existing) {
				this.fire('change', {'subtype': 'add', 'id': data.id, 'locale': locale});
			}
		},
		
		/**
		 * Get all values
		 */
		getValue: function () {
			var data = this.data,
				removed = this.removed,
				values = [];
			
			for(var i=0,ii=data.length; i<ii; i++) {
				if (typeof data[i] != 'object') {
					values.push({'id': data[i]});
				} else {
					values.push({'id': data[i].get('id'), 'value': data[i].getValue()});
				}
			}
			
			for(var i in removed) {
				values.push({'id': i});
			}
			
			return values;
		},
		
		/**
		 * Set values
		 * 
		 * @param {Array} values
		 */
		setValue: function (values) {
			this.resetValue();
			
			for(var i=0,ii=values.length; i<ii; i++) {
				if (values[i].value) {
					this.addPermissionException(values[i]);
				}
			}
		},
		
		/**
		 * Reset value
		 */
		resetValue: function () {
			
			for(var i=0,ii=this.data.length; i<ii; i++) {
				if (this.data[i].destroy) this.data[i].destroy();
			}
			
			this.data = [];
			this.removed = {};
			this.get('contentBox').all('div').remove();
			
		},
		
		/**
		 * Remove item
		 */
		removeItem: function (e) {
			var target = e.target,
				id = target.getData('item-id'),
				locale = target.getData('item-locale'),
				data = this.data;
			
			for(var i=0,ii=data.length; i<ii; i++) {
				if (data[i].get('id') == id) {
					id = data[i].get('id');
					
					data[i].destroy();
					data.splice(i, 1);
					this.removed[id] = true;
					
					this.fire('change', {'subtype': 'change', 'id': id, 'locale': locale});
					return;
				}
			}
		},
		
		/**
		 * sublabel attribute setter
		 * 
		 * @param {String} sublabel Sublabel attribute value
		 * @return Sublabel new value
		 * @type {String}
		 * @private
		 */
		_setSubLabel: function (sublabel /* Sublabel attribute value */) {
			var label_text = Supra.Intl.get(['userpermissions', 'drop_here']);
				label_text = Y.substitute(label_text, {'sublabel': sublabel});
			
			this.get('labelNode').set('text', label_text);
			
			return sublabel;
		},
		
		/**
		 * Destructor
		 * 
		 * @private
		 */
		destructor: function () {
			this.resetValue();
			this.dd.destroy();
			delete(this.dd);
			
		}
	});
	
	Supra.PermissionList = PermissionList;
	
	//Since this Widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['dd', 'supra.input']});
