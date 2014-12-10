// Configuration for combobox
{
	"id": "some_id",
	"label": "Some Label",
	"type": "ComboBox",
	
	// Allow selecting multiple values
	"multiple": true,
	
	
	// 1. Data loaded from URL
	"source": "url",
	"sourceUrl": "/some_source_url",
	
	// 2. Data loaded from CRUD manager
	"source": "crud",
	"sourceId": "...",
	
	// 3. Static data, default
	"source": "",
	"values": [
		{"id": "...", "title": "..."}
	]
}

/*
 * 1. Data loaded from URL
 *
 *   /some_source_url
 *       ?q=Item
 *       &offset=0
 *       &resultsPerRequest=20
 *       &locale=en
 */
{
	"offset": 0,
	"total": 2,
	"results": [
		{"id": 1, "title": "Item 1"},
		{"id": 2, "title": "Item 2"}
	]
}

/*
 * 2. Data loaded from CRUD, if "display": "list" or data is for autocomplete
 *
 *   /cms/crud-manager-2/data/datalist.json
 *       ?sourceId=crudManagerId123
 *       &q=Item
 *       &offset=0
 *       &resultsPerRequest=20
 *       &locale=en
 */
{
	"offset": 0,
	"total": 2,
	"results": [
		{"id": 1, "title": "Item 1"},
		{"id": 2, "title": "Item 2"}
	]
}

/*
 * 2. Data loaded from CRUD, if "display": "tree" and NOT for autocomplete
 *   @TODO How will this work with search???
 * 
 *   /cms/crud-manager-2/data/datatree.json
 *       ?sourceId=crudManagerId123
 *       &parent_id=0
 *       &locale=en
 */
[
	{
		"id": 1,
		"title": "Item 1",
		"children_count": 0,
		"icon": "page",
		"type": "page" // default	
	},
	{
		"id": 2,
		"title": "Item 2",
		"children_count": 1,
		"icon": "folder", // because there this item has children
		"type": "page"
	}
]


/*
 * POST data when saving form with ComboBox input
 */
{
	// If "multiple": false
	"some_id": 2,
	
	// If "multiple": true
	"some_id": [1, 2]
}


/*
 * Data when loading form with ComboBox input
 */
{
	// If "multiple": false
	"some_id": {"id": 2, "title": "Item 2"},
	
	// If "multiple": true
	"some_id": [
		{"id": 1, "title": "Item 1"},
		{"id": 2, "title": "Item 2"}
	],
	
	// 3. When data is static then only IDs are sufficient to display UI
	// (optional, can be id + title too)
	"some_id": 2,
	"some_id": [1, 2]
}
