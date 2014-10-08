/*
 * Example:
 * 	- how to create widgets
 * 	- bind to widget event listeners
 * 	- set configuration
 * 	- Ajax requests
 */
Supra("button", "tabs", "panel", "form", function (Y) {
 	//This is executed when all previously listed widgets are loaded
	
	//Panel with close button
	var panel = new Y.Panel({"showClose": true, "visible": false});
		panel.render(document.body);
	
	//Tabs
	var tabs = new Y.Tabs();
		tabs.render(panel.get("contentBox"));				//Insert tabs into panel content
		
		tabs.addTab({"title": "First tab", "id": "tab1"});
		tabs.addTab({"title": "Second tab", "id": "tab2"});
	
	
	//Insert simple HTML into DOC, for example "Saved" message
		var message = Y.Node.create("<p>Saved</p>");
			message.appendTo(tabs.getTabContent("tab1"));	//Insert into first tab
			message.addClass("hidden");						//Hide until needed
	
	//Creates form with 2 fields and appends it to the first tab
		var fields = {
			"title": {
				"id": "title",
				"type": "String",
				"value": "",
				"label": "Title:"
			},
			"enabled": {
				"id": "enabled",
				"type": "Checkbox",
				"label": "Is it enabled?"
			}
		};
		
		var form = new Y.Form({"fields": fields});
		form.render(tabs.getTabContent("tab1"));		//Insert into first tab
	
	//"Save" button
	var button = new Y.Button({"title": "Save"});
		button.render(panel.get("contentBox"));			//Insert after tabs
		
		//When user click save take form values
		//and send them to the server
		button.on("click", function () {
			
			/*
			 * form.getValues() returns something like:
			 * { "title": "whatever user entered here" , "enabled": true }
			 */
			
			//Send data
			Supra.IO("/admin/saveFormData", {
				"data": form.getValues(),
				"method": "POST",
				"on": {
					"success": function (transaction, data) {
						//Show "Saved" message
						message.removeClass("hidden");
					}
				}
			});
			
		});
	
	
	//Load data from server
	Supra.IO("/admin/getFormData", {
		"on": {
			"success": function (transaction, data) {
				form.setValues(data);
				panel.show();
			}
		}
	});
	
});