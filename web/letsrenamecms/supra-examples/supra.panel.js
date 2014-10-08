Supra('supra.panel', function (Y) {
	
	panel = new Supra.Panel();
	panel.render();
	
	/*
	 * Set if panel close button should be visible.
	 * Default: false
	 * Attribute: 'closeVisibile'
	 */
	panel.setCloseVisible(true);
	
	/*
	 * Set if arrow should be visible.
	 * Default: false
	 * Attribute: 'arrowVisibile'
	 */
	panel.setArrowVisible(true);
	
	/*
	 * Set arrow position to left side, centered vertically
	 * Default: none
	 * Attribute: 'arrowPosition'
	 */
	panel.setArrowPosition([Supra.Panel.ARROW_L, Supra.Panel.ARROW_C]);
	
	/*
	 * Set arrow position to point at specific element
	 * Changes arrowPosition attribute value
	 */
	panel.arrowAlign(node);
	
});