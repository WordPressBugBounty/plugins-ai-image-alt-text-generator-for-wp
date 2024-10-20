function addCoverAttribute(settings, name) {
    if (typeof settings.attributes !== 'undefined') {
        if (name === 'core/cover') {
            settings.attributes = Object.assign(settings.attributes, {
                hideOnMobile: {
                    type: 'boolean',
                }
            });
        }
    }
    return settings;
}

wp.hooks.addFilter(
    'blocks.registerBlockType',
    'awp/cover-custom-attribute',
    addCoverAttribute
);

const coverAdvancedControls = wp.compose.createHigherOrderComponent((BlockEdit) => {
    return (props) => {
        const { Fragment } = wp.element;
        const { ToggleControl } = wp.components;
        const { InspectorAdvancedControls } = wp.blockEditor;
        const { attributes, setAttributes, isSelected } = props;
        return (
            <Fragment>
                <BlockEdit {...props} />
                {isSelected && (props.name == 'core/cover') &&
                    <InspectorAdvancedControls>
                        <ToggleControl
                            label={wp.i18n.__('Hide on mobile', 'awp')}
                            checked={!!attributes.hideOnMobile}
                            onChange={(newval) => setAttributes({ hideOnMobile: !attributes.hideOnMobile })}
                        />
                    </InspectorAdvancedControls>
                }
            </Fragment>
        );
    };
}, 'coverAdvancedControls');

wp.hooks.addFilter(
    'editor.BlockEdit',
    'awp/cover-advanced-control',
    coverAdvancedControls
);