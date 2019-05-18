( function( wp ) {

	/**
	 * Returns a new element of given type. Element is an abstraction layer atop React.
	 * @see https://github.com/WordPress/gutenberg/tree/master/element#element
	 */
	var el = wp.element.createElement,
		__ = wp.i18n.__,
		registerBlockType = wp.blocks.registerBlockType,
		ServerSideRender = wp.components.ServerSideRender;

	/**
	 * Every block starts by registering a new block type definition.
	 * @see https://wordpress.org/gutenberg/handbook/block-api/
	 */
	registerBlockType( 'coworking-group/group', {

		title: __( 'Group Photos' ),
		icon: 'groups',
		category: 'layout',
		render_callback: 'block_dynamic_render',
		supports: {
			// Removes support for an HTML mode.
			html: false,
		},

		/**
		 * The edit function describes the structure of your block in the context of the editor.
		 * This represents what the editor will render when the block is used.
		 * @see https://wordpress.org/gutenberg/handbook/block-edit-save/#edit
		 *
		 * @param {Object} [props] Properties passed from the editor.
		 * @return {Element}       Element to render.
		 */
		edit: function( props ) {
			// ensure the block attributes matches this plugin's name
			return (
				el(ServerSideRender, {
					block: "coworking-group/group",
					attributes:  props.attributes
				})
			);
		},

		/* // Display static content in the editor.
		edit: function( props ) {
			// ensure the block attributes matches this plugin's name
			return (
				el(ServerSideRender, {
					block: "coworking-blocks/choose-plan",
					attributes:  props.attributes
				})
			);
		},
		*/

		/**
		 * The save function defines the way in which the different attributes should be combined
		 * into the final markup, which is then serialized by Gutenberg into `post_content`.
		 * @see https://wordpress.org/gutenberg/handbook/block-edit-save/#save
		 *
		 * @return {Element}       Element to render.
		 */
		save: function() {
			return null;
		}
	} );
} )(
	window.wp
);
