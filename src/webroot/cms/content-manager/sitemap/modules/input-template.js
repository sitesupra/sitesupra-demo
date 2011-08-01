/**
 * Template selection input
 */
YUI.add("website.input-template", function (Y) {
	
	function Input (config) {
		Input.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
		
		this.templates = null;
	}
	
	Input.NAME = "input-template";
	Input.CLASS_NAME = Y.ClassNameManager.getClassName(Input.NAME);
	Input.ATTRS = {
		"previewNode": {
			value: null
		},
		"templateRequestUri": {
			value: ""
		}
	};
	
	Input.HTML_PARSER = {};
	
	Y.extend(Input, Supra.Input.Proto, {
		INPUT_TEMPLATE: '<input type="hidden" value="" />',
		LABEL_TEMPLATE: null,
		
		/**
		 * Template list
		 * @type {Array}
		 * @private
		 */
		templates: null,
		
		/**
		 * Popup panel
		 * @type {Object}
		 * @private
		 */
		panel: null,
		
		/**
		 * Load templates
		 * 
		 * @private
		 */
		loadTemplates: function () {
			var uri = this.get('templateRequestUri');
			if (!uri) {
				uri = SU.Manager.getAction('PageSettings').getDataPath('templates');
			}
			
			Supra.io(uri, Y.bind(this.loadTemplatesComplete, this));
		},
		
		/**
		 * Handle template load complete
		 * 
		 * @param {Array} data Loaded data
		 * @param {Boolean} status Success status
		 * @private
		 */
		loadTemplatesComplete: function (data, status) {
			this.templates = data;
			this.syncUI();
			
			//Render template list
			var node_list = this.panel.get('contentBox').one('ul'),
				node_item = null,
				value = this.get('value');
			
			node_list.empty();
			for(var i=0,ii=data.length; i<ii; i++) {
				node_item = Y.Node.create('<li data-template="' + data[i].id + '" class="clearfix ' + (data[i].id == value ? 'selected' : '') + '"><div><img src="' + data[i].img + '" alt="" /></div><p>' + Y.Lang.escapeHTML(data[i].title) + '</p></li>');
				node_item.setData('templateId', data[i].id);
				node_list.append(node_item);
			}
		},
		
		/**
		 * Synchronize data and UI
		 * 
		 * @private
		 */
		syncUI: function () {
			Input.superclass.syncUI.apply(this, arguments);
			
			var templates = this.templates;
			
			if (!templates) {
				this.loadTemplates();
				return;
			}
			
			var value = this.get('value'),
				template = null,
				template_title = '',
				template_src = '/cms/lib/supra/img/px.gif';
			
			for(var i=0,ii=templates.length; i<ii; i++) {
				if (templates[i].id == value) {
					template = templates[i];
				}
			}
			
			if (!template) {
				template = templates[0];
			}
			
			if (template) {
				template_title = template.title;
				template_src = template.img;
			}
			
			var node = this.get('titleNode');
			if (node) {
				node.set("text", template_title);
			}
			
			var node = this.get('previewNode');
			if (node) {
				node.set("src", template_src);
			}
			
			var list_node = this.panel.get('contentBox').one('ul'),
				list_items = list_node.all('li'),
				list_item = list_node.one('li[data-template="' + value + '"]');
			
			if (list_items) list_items.removeClass('selected');
			if (list_item) list_item.addClass('selected');
		},
		
		/**
		 * Attach event listeners
		 * 
		 * @private
		 */
		bindUI: function () {
			Input.superclass.bindUI.apply(this, arguments);
			
			//Open template list on click
			this.get('boundingBox').on('click', this.openPanel, this);
			
			//On panel click prevent propagation
			this.panel.get('boundingBox').on('click', function (evt) { evt.halt(); });
			this.panel.get('boundingBox').one('ul').on('click', this.onTemplateClick, this);
			
			//On change update template title and src
			this.after('valueChange', function () {
				this.syncUI();
				this.fire('change');
			});
			
			//On document click hide panel
			var evt = null;
			var fn = function (event) {
				var target = event.target.closest('div.sitemap-template-panel');
				if (!target) this.hide();
			};
			
			//When panel is hidden remove 'click' event listener from document
			this.panel.on('visibleChange', function (event) {
				if (event.newVal) {
					if (evt) evt.detach();
					evt = Y.one(document).on('click', fn, this);
				} else if (evt) {
					evt.detach();
				}
			});
		},
		
		/**
		 * Created required elements
		 * 
		 * @private
		 */
		renderUI: function () {
			Input.superclass.renderUI.apply(this, arguments);
			
			//Find or create title node
			var node = this.get("boundingBox").one("p");
			if (!node) {
				node = Y.Node.create("<p></p>");
				this.get("boundingBox").prepend(node);
			}
			this.set("titleNode", node);  
			
			//Find or create preview node
			var node = this.get("boundingBox").one("img");
			if (!node) {
				node = Y.Node.create("<img src=\"/cms/lib/supra/img/px.gif\" alt=\"\" />");
				var div = Y.Node.create("<div></div>");
				
				div.append(node);
				this.get("boundingBox").prepend(div);
			}
			this.set("previewNode", node);
			
			//Find or create replacement node
			if (this.get("useReplacement")) {
				var node = this.get("boundingBox").one("span");
				if (!node) {
					node = Y.Node.create("<span></span>");
					this.get("boundingBox").prepend(node);
				}
				node.set("innerHTML", Y.Lang.escapeHTML(this.get("value")));
				this.set("replacementNode", node);
			}
			
			//Create panel
			this.panel = new Supra.Panel({
				'arrowPosition': ['L', 'C'],
				'arrowVisible': true,
				'zIndex': 2,
				'constrain': SU.Manager.SiteMap.one()
			});
			this.panel.hide();
			this.panel.render(document.body);
			this.panel.get('boundingBox').addClass('sitemap-template-panel');
			this.panel.get('contentBox').append(Y.Node.create('<ul class="yui3-sitemap-template-list"></ul><div class="clear"><!-- --></div>'));
		},
		
		openPanel: function (evt) {
			if (this.get('disabled')) return;
			
			//Position panel
			this.panel.set('align', {'node': this.get('previewNode'), 'points': [Y.WidgetPositionAlign.LC, Y.WidgetPositionAlign.RC]});
			this.panel.set('arrowAlign', this.get('previewNode'));
			this.panel.show();
			this.panel.syncUI();
			
			evt.halt();
		},
		
		/**
		 * On template click
		 * 
		 * @param {Object} evt
		 */
		onTemplateClick: function (evt) {
			var target = evt.target.closest('LI');
			if (target) {
				this.set('value', target.getData('templateId'));
				this.panel.hide();
			}
		},
		
		/**
		 * Value setter
		 * 
		 * @param {Number} value
		 * @return New value
		 * @type {Number}
		 * @private
		 */
		_setValue: function (value) {
			this.get("inputNode").set("value", value);
			this._original_value = value;
			return value;
		}
		
	});
	
	Supra.Input.Template = Input;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:["supra.input-proto"]});