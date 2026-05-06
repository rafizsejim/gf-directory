/* global wp */
( function ( blocks, element, blockEditor, components, i18n ) {
	'use strict';

	const { registerBlockType } = blocks;
	const { createElement: el, Fragment } = element;
	const { InspectorControls, useBlockProps } = blockEditor;
	const { PanelBody, TextControl } = components;
	const { __ } = i18n;

	registerBlockType( 'gf-directory/directory', {
		edit( props ) {
			const { attributes, setAttributes } = props;
			const blockProps = useBlockProps();

			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Directory', 'gf-directory' ), initialOpen: true },
						el( TextControl, {
							label: __( 'Form ID', 'gf-directory' ),
							help: __( 'Numeric ID of the Gravity Forms form whose directory to display.', 'gf-directory' ),
							type: 'number',
							value: attributes.formId || '',
							onChange: ( v ) => setAttributes( { formId: parseInt( v, 10 ) || 0 } ),
						} )
					)
				),
				el(
					'div',
					blockProps,
					attributes.formId
						? el(
								'div',
								{ style: { padding: '24px', background: '#F7F7FA', border: '1px dashed #C5C7CD', borderRadius: '12px', textAlign: 'center', color: '#6E7280', fontSize: '14px' } },
								__( 'GF Directory · form #' + attributes.formId, 'gf-directory' ),
								el( 'br' ),
								el( 'small', null, __( 'Front-end will render the full archive.', 'gf-directory' ) )
						  )
						: el(
								'div',
								{ style: { padding: '24px', background: '#FFF6E0', border: '1px dashed #B07206', borderRadius: '12px', textAlign: 'center', color: '#B07206', fontSize: '14px' } },
								__( 'Set a Form ID in the block sidebar to render a directory.', 'gf-directory' )
						  )
				)
			);
		},
		save() {
			return null; // server-rendered.
		},
	} );
} )( window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components, window.wp.i18n );
