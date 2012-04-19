/*
 * Sample widget
 */
YUI.add("mywidget", function (Y) {
	
	function MyWidget (config) {
		//Call super class constructor
		MyWidget.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	MyWidget.NAME = "mywidget";
	
	MyWidget.ATTRS = {
		"title": {
			"value": "My title"
		}
	};
	
	/*
	 * HTML_PARSER is used by Widget to populate configuration from markup
	 * already on the page
	 */
	MyWidget.HTML_PARSER = {
		"tabsNode": function (srcNode) {
			//Search node
			var node = srcNode.one("div.tabs");
			
			//If not found create it and insert into content
			if (!node) {
				node = Y.Node.create('<div class="tabs"></div>');
				this.get("contentBox").prepend(node);
			}
			
			this.set("tabsNode", node);		//<- is this needed?
			return node;
		}
	};
	
	/*
	 * Widget will have style of Panel
	 */
	Y.extend(MyWidget, Y.Panel, {
		
		tabs1: null,
		tabs2: null,
		
		//Automatically called when widget is created (after renderUI)
		//Purpose of bindUI is to bind all listeners when UI is rendered
		bindUI: function () {
			MyWidget.superclass.renderUI.apply(this, arguments);
			
			//When tab changes alert new value
			this.tabs1.on("activeTabChange", function (event) {
				alert("User selected " + event.newValue + " tab");
			});
		},
		
		//Automatically called when widget is created
		renderUI: function () {
			MyWidget.superclass.renderUI.apply(this, arguments);
			
			//Create tabs from existing node
				this.tabs1 = new Y.Tabs({"srcNode": this.get("tabsNode")});
				this.tabs1.render();
				
				//Add tab with ID tab1
				this.tabs1.addTab({"id": "tab1", "title": "My tab 1"});
				
				//Insert button into tab1 content
				var placeholder = this.tabs1.getTabContent("tab1");
				var button = new Y.Button({"label": "My button"});
				button.render(placeholder);
			
			//Create tabs programatically
				this.tabs2 = new Y.Tabs();
				this.tabs2.render(this.get("contentBox"));
		}
		
	});
	
	//Make it globally accessible
	Y.MyWidget = MyWidget;
	
	
}, YUI.version, {requires:["panel", "button", "tabs"]});


/*
 * Usage from another file
 * 
 * Only specified widgets will be 
 */ 
Supra("mywidget", function (Y) {
 	
	//Create new instance and append to document body
	var instance1 = new Y.MyWidget();
		instance1.render(document.body);
	
	//Create new instance from existing DOM
	var instance2 = new Y.MyWidget({"srcNode": "#myWidgetDiv"});
		instance2.render();
	 
});