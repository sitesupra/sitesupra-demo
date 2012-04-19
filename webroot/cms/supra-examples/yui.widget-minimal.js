/*
 * Minimal code to create widget
 */
YUI.add("mywidget", function (Y) {
	
	function MyWidget (config) {
		//Call super class constructor
		MyWidget.superclass.constructor.apply(this, arguments);
		
		//Initialize widget
		this.init.apply(this, arguments);
	}
	
	MyWidget.NAME = "mywidget";
	
	Y.extend(MyWidget, Y.Widget, {
		
		syncUI: function () {
			MyWidget.superclass.syncUI.apply(this, arguments);
			
		},
		
		bindUI: function () {
			MyWidget.superclass.bindUI.apply(this, arguments);
			//...
			
		},
		
		renderUI: function () {
			MyWidget.superclass.renderUI.apply(this, arguments);
			//...
		}
		
	});
	
	//Make it globally accessible
	Y.MyWidget = MyWidget;
	
}, YUI.version, {requires:["widget"]});