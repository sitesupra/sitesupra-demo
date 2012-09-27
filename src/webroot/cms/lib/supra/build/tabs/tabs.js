YUI.add('supra.tabs', function(Y) {
	//Invoke strict mode
	"use strict";
	
	function Tabs (config) {
		this.tabs = {};
		
		Tabs.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	Tabs.NAME = 'Tabs';
	
	Tabs.CLASS_NAME = Y.ClassNameManager.getClassName(Tabs.NAME.toLowerCase());
	
	Tabs.ATTRS = {
		'nodeButtonContainer': {
			value: null,
			readOnly: true
		},
		'nodeContentContainer': {
			value: null,
			readOnly: true
		},
		'visible': {
			value: true,
			setter: '_setVisible'
		},
		'tabs': {
			value: null
		},
		'activeTab': {
			value: null,
			setter: '_setActiveTab'
		},
		'toggleEnabled': {
			value: false,
			setter: '_setToggleEnabled'
		},
		'style': {
			value: null
		},
		'buttonStyle': {
			value: 'mid'
		},
		'className': {
			value: Tabs.NAME.toLowerCase()
		}
	},
	
	Tabs.HTML_PARSER = {
		'nodeButtonContainer': function (srcNode) {
			var node = srcNode.one('.yui3-tabs-buttons');
			if (!node) {
				node = Y.Node.create('<div class="yui3-tabs-buttons"></div>');
				this.get('contentBox').append(node);
			}
			
			this.set('nodeButtonContainer', node);
			return node;
		},
		'nodeContentContainer': function (srcNode) {
			var className = this.getClassName('contents'),
				node = srcNode.one('.yui3-tabs-contents');
			if (!node) {
				node = Y.Node.create('<div class="yui3-tabs-contents"></div>');
				this.get('contentBox').append(node);
			}
			
			this.set('nodeContentContainer', node);
			return node;
		}
	};
	
	Y.extend(Tabs, Y.Widget, {
		
		tabs: {},
		
		getClassName: function () {
			var args = [this.get('className')].concat([].splice.call(arguments, 0));
			return Y.ClassNameManager.getClassName.apply(Y.ClassNameManager, args);
		},
		
		renderUI : function() {
			Tabs.superclass.renderUI.apply(this, arguments);
			
			if (this.get('toggleEnabled')) {
				this._setToggleEnabled(true);
			}
			
			var style = this.get('style'),
				boundingBox = this.get('boundingBox');
			
			if (style) {
				var classname = this.getClassName(style);
				boundingBox.addClass(classname);
			}
			
			var attrClassName = this.getClassName();
			if (attrClassName != Tabs.CLASS_NAME) {
				boundingBox.addClass(attrClassName);
				boundingBox.removeClass(Tabs.CLASS_NAME);
				
				var nodeButtonContainer = this.get('nodeButtonContainer'),
					nodeContentContainer = this.get('nodeContentContainer');
				
				nodeButtonContainer.removeClass('yui3-tabs-buttons');
				nodeButtonContainer.addClass(this.getClassName('buttons'));
				nodeContentContainer.removeClass('yui3-tabs-contents');
				nodeContentContainer.addClass(this.getClassName('contents'));
			}
			
			/*
			var tabs = this.get('tabs');
			if (tabs) {
				//@TODO
			}
			*/
		},
		
		addTab: function (data) {
			if (!(data.id in this.tabs)) {
				var toggleEnabled = this.get('toggleEnabled');
				var first = true; for(var i in this.tabs) { first = false; break; }
				var id = data.id;
				var container = Y.Node.create('<div class="' + this.getClassName('item') + '"></div>');
					this.get('nodeButtonContainer').append(container);
				
				var btn = new Supra.Button({'label': data.title, 'type': 'toggle', 'icon': data.icon, 'style': this.get('buttonStyle') || 'mid'});
					btn.render(container);
				
				var cont = Y.Node.create('<div class="' + this.getClassName('content') + '"></div>');
					this.get('nodeContentContainer').append(cont);
				
				this.tabs[id] = data;
				this.tabs[id].button = btn;
				this.tabs[id].container = container;
				this.tabs[id].content = cont;
				
				if (data.visible === false) {
					this.tabs[id].button.hide();
				}
				
				btn.on('click', function () {
					if (!this.get('toggleEnabled') || this.get('activeTab') != id) {
						this.set('activeTab', id);
					} else {
						this.set('activeTab', null);
					}
				}, this);
				
				if (first && !toggleEnabled) {
					this.set('activeTab', id);
				} else {
					cont.addClass('hidden');
				}
				
				return cont;
			}
		},
		
		removeTab: function (id) {
			if (id in this.tabs) {
				this.tabs[id].button.destroy(true);
				this.tabs[id].content.destroy(true);
				delete(this.tabs[id]);
			}
		},
		
		getTabContent: function (id) {
			if (id in this.tabs) {
				return this.tabs[id].content;
			}
		},
		
		hasTab: function (id) {
			return !!(id in this.tabs);
		},
		
		getTabButton: function (id) {
			if (id in this.tabs) {
				return this.tabs[id].button;
			}
		},
		
		syncUI: function () {
			Tabs.superclass.syncUI.apply(this, arguments);
		},
		
		bindUI: function () {
			Tabs.superclass.bindUI.apply(this, arguments);
		},
		
		_setVisible: function (visible) {
			var box = this.get('boundingBox');
			if (box) {
				box.toggleClass('hidden', !visible);
			}
			return visible;
		},
		
		showTab: function (id) {
			var btn = this.getTabButton(id);
			if (btn) {
				btn.show();
			}
		},
		
		hideTab: function (id) {
			var btn = this.getTabButton(id);
			if (btn) {
				btn.hide();
				if (this.get('activeTab') == id) {
					for(var i in this.tabs) {
						if (i != id) {
							this.set('activeTab', i);
							break;
						}
					}
				}
			}
		},
		
		_setToggleEnabled: function (value) {
			var container = this.get('boundingBox');
			var classname = this.getClassName('toggle');
			if (value) {
				container.addClass(classname);
				this.get('nodeContentContainer').toggleClass('hidden', !this.get('activeTab'));
			} else {
				container.removeClass(classname);
				this.get('nodeContentContainer').removeClass('hidden');
			}
			return value;
		},
		
		_setActiveTab: function (value) {
			if (!value && !this.get('toggleEnabled')) return this.get('activeTab');
			
			var old = this.get('activeTab');
			
			if (old && old !== value) {
				this.tabs[old].button.set('down', false);
				this.tabs[old].content.addClass('hidden');
				this.tabs[old].container.removeClass(this.getClassName('item', 'selected'));
			}
			
			if (value) {
				this.tabs[value].button.set('down', true);
				this.tabs[value].content.removeClass('hidden');
				this.tabs[value].container.addClass(this.getClassName('item', 'selected'));
			}
			
			this.get('nodeContentContainer').toggleClass('hidden', !value);
			
			this.fire('activeTabChange', {'prevValue': old, 'newValue': value});
			
			return value;
		},
		
		addClass: function () {
			var box = this.get('boundingBox');
			if (box) return box.addClass.apply(box, arguments);
			return null;
		},
		
		toggleClass: function () {
			var box = this.get('boundingBox');
			if (box) return box.toggleClass.apply(box, arguments);
			return null;
		},
		
		removeClass: function () {
			var box = this.get('boundingBox');
			if (box) return box.removeClass.apply(box, arguments);
			return null;
		},
		
		hasClass: function () {
			var box = this.get('boundingBox');
			if (box) return box.hasClass.apply(box, arguments);
			return null;
		}
	});
	
	Supra.Tabs = Tabs;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version ,{requires:['supra.button']});