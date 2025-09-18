/**
 * Accordion FAQ Schema Toggle – block editor extension
 */

import { addFilter } from '@wordpress/hooks';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { Fragment } from '@wordpress/element';

// 1. Add the custom attribute to core/accordion.
function addFaqSchemaAttribute( settings, name ) {
	if ( name !== 'core/accordion' ) return settings;

	settings.attributes = {
		...settings.attributes,
		addFaqSchema: {
			type: 'boolean',
			default: false,
		},
	};
	return settings;
}
addFilter(
	'blocks.registerBlockType',
	'viney/accordion-faq-schema-toggle/attribute',
	addFaqSchemaAttribute
);

// 2. Add a toggle to the block inspector panel.
function withFaqSchemaInspector( BlockEdit ) {
	return ( props ) => {
		if ( props.name !== 'core/accordion' ) {
			return <BlockEdit { ...props } />;
		}

		const { attributes: { addFaqSchema }, setAttributes } = props;

		return (
			<Fragment>
				<BlockEdit { ...props } />
				<InspectorControls>
					<PanelBody title="FAQ Schema" initialOpen={ true }>
						<ToggleControl
							label="Output FAQ Schema"
							checked={ addFaqSchema }
							onChange={ ( value ) => setAttributes( { addFaqSchema: value } ) }
						/>
					</PanelBody>
				</InspectorControls>
			</Fragment>
		);
	};
}
addFilter(
	'editor.BlockEdit',
	'viney/accordion-faq-schema-toggle/inspector',
	withFaqSchemaInspector
);
