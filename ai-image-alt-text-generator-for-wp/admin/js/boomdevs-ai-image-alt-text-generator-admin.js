(function( $ ) {
	'use strict';
	$.fn.ProgressBar = function(){
		let targetParent = $(this);
		targetParent.each(function(){

			//required variables
			let target = $(this).children();
			let offsetTop = $(this).offset().top;
			let winHeight = $(window).height();
			let data_width = target.attr("data-percent") + "%";
			let data_color = target.attr("data-color");

			//animation starts
			// if( winHeight > offsetTop ) {
			target.css({
				backgroundColor: data_color,
			});
			target.animate({
				width: data_width,
			}, 1000);
			// }

			//animation with scroll
			// $(window).scroll(function(){
			// 	let scrollBar = $(this).scrollTop();
			// 	let animateStart = offsetTop - winHeight;
			// 	if( scrollBar > animateStart ) {
			// 		target.css({
			// 			backgroundColor: data_color,
			// 		});
			// 		target.animate({
			// 			width: data_width,
			// 		}, 1000);
			// 	}
			// });
		});

		return this;
	}
	$(document).ready(function(){
		$(".progress-bar").ProgressBar();
	});
})( jQuery );

//
// const enableToolbarButtonOnBlocks = [
// 	'core/paragraph'
// ];
//
// const setToolbarButtonAttribute = ( settings, name ) => {
// 	// Do nothing if it's another block than our defined ones.
// 	if ( ! enableToolbarButtonOnBlocks.includes( name ) ) {
// 		return settings;
// 	}
//
// 	return Object.assign( {}, settings, {
// 		attributes: Object.assign( {}, settings.attributes, {
// 			paragraphAttribute: { type: 'string' }
// 		} ),
// 	} );
// };
// wp.hooks.addFilter(
// 	'blocks.registerBlockType',
// 	'custom-attributes/set-toolbar-button-attribute',
// 	setToolbarButtonAttribute
// );
//
//
// const withToolbarButton = createHigherOrderComponent( ( BlockEdit ) => {
// 	return ( props ) => {
//
// 		// If current block is not allowed
// 		if ( ! enableToolbarButtonOnBlocks.includes( props.name ) ) {
// 			return (
// 				<BlockEdit { ...props } />
// 			);
// 		}
//
// 		const { attributes, setAttributes } = props;
// 		const { paragraphAttribute } = attributes;
//
// 		return (
// 			<Fragment>
// 				<BlockControls group="block">
// 					<ToolbarGroup>
// 						<ToolbarButton
// 							icon="format-status"
// 							label={ __( 'Custom Button', 'core-block-custom-attributes' ) }
// 							isActive={ paragraphAttribute === 'custom' }
// 							onClick={ () => {
// 								if ( paragraphAttribute === 'custom' ) {
// 									setAttributes( { paragraphAttribute: false } )
// 								} else {
// 									setAttributes( { paragraphAttribute: 'custom' } )
// 								}
// 							} }
// 						/>
// 					</ToolbarGroup>
// 				</BlockControls>
// 				<BlockEdit { ...props } />
// 			</Fragment>
// 		);
// 	};
// }, 'withToolbarButton' );
// wp.hooks.addFilter(
// 	'editor.BlockEdit',
// 	'custom-attributes/with-toolbar-button',
// 	withToolbarButton
// );
//
// const withToolbarButtonProp = createHigherOrderComponent( ( BlockListBlock ) => {
// 	return ( props ) => {
//
// 		// If current block is not allowed
// 		if ( ! enableToolbarButtonOnBlocks.includes( props.name ) ) {
// 			return (
// 				<BlockListBlock { ...props } />
// 			);
// 		}
//
// 		const { attributes } = props;
// 		const { paragraphAttribute } = attributes;
//
// 		if ( paragraphAttribute && 'custom' === paragraphAttribute ) {
// 			return <BlockListBlock { ...props } className={ 'has-custom-attribute' } />
// 		} else {
// 			return <BlockListBlock { ...props } />
// 		}
// 	};
// }, 'withToolbarButtonProp' );
//
// wp.hooks.addFilter(
// 	'editor.BlockListBlock',
// 	'custom-attributes/with-toolbar-button-prop',
// 	withToolbarButtonProp
// );
//
//
// const saveToolbarButtonAttribute = ( extraProps, blockType, attributes ) => {
// 	// Do nothing if it's another block than our defined ones.
// 	if ( enableToolbarButtonOnBlocks.includes( blockType.name ) ) {
// 		const { paragraphAttribute } = attributes;
// 		if ( paragraphAttribute && 'custom' === paragraphAttribute ) {
// 			extraProps.className = classnames( extraProps.className, 'has-custom-attribute' )
// 		}
// 	}
//
// 	return extraProps;
//
// };
// wp.hooks.addFilter(
// 	'blocks.getSaveContent.extraProps',
// 	'custom-attributes/save-toolbar-button-attribute',
// 	saveToolbarButtonAttribute
// );