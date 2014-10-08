$(function () {
	
	//On CMS layout change reload content
	$.refresh.on('update/text', function (event, info) {
		//Remove all classnames
		if (info.propertyValueList) {
			$.each(info.propertyValueList, function (index, item) {
				if (item.id) {
					info.target.removeClass(item.id);
				}
			});
		}

		switch (info.propertyName) {
			case "layout":
				//This will tell CMS to reload block content
				return false;
			case "background_image":
				if (info.propertyValue) {
					if (info.propertyValue.classname) {
						info.target.addClass(info.propertyValue.classname);
					}
					if (info.propertyValue.image) {
						var data = info.propertyValue.image;
						info.target.css({
							"backgroundImage": "url(" + data.image.file_web_path + ")",
							"backgroundPosition": -data.crop_left + "px -" + data.crop_top + "px",
							"backgroundSize": data.size_width + "px " + data.size_height + "px",
							"backgroundRepeat": "no-repeat"
						});
					} else {
						//Reset background image
						info.target.css({
							"backgroundImage": "",
							"backgroundPosition": "",
							"backgroundSize": "",
							"backgroundRepeat": ""
						});
					}
				}
				break;
			case "background_color":
				info.target.css("backgroundColor", info.propertyValue || "transparent");
				break;
			case "border_color":
				info.target.css("borderColor", info.propertyValue || "transparent");
				break;
			case "border_style":
				info.target.addClass(info.propertyValue);
				break;
		}
	});
	
});
