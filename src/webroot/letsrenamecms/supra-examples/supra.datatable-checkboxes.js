/*
 * Data table checkbox plugin adds checkboxes to data table and
 * allows easy manipulate them
 */

/* Assuming this.datatable is Supra.DataTable instance */
this.datatable.plug(Supra.DataTable.CheckboxPlugin);

/* To check/uncheck all rows */
this.datatable.checkboxes.checkAll();
this.datatable.checkboxes.uncheckAll();

/* To get checked rows (Supra.DataTableRow) */
var rows = this.datatable.checkboxes.getCheckedRows();

for(var i=0,ii=rows.length; i<ii; i++) {
    var data = rows[i].data;
    Y.Log('Row #' + data.id + ' has title ' + data.title);
}

/* To access checkbox nodes */
var nodes = this.datatable.checkboxes.getCheckboxes();