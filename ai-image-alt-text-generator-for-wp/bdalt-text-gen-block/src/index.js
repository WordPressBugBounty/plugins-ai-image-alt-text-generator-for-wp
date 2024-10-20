import { useState } from 'react';
import { addFilter } from "@wordpress/hooks";
import { ToastContainer, toast, Bounce } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';
import { createHigherOrderComponent } from "@wordpress/compose";
import { InspectorControls } from "@wordpress/block-editor";
import {
	SelectControl,
	TextControl,
	PanelBody,
	PanelRow,
} from "@wordpress/components";
import { __ } from "@wordpress/i18n";

/**
 * Add the attribute to the block.
 * This is the attribute that will be saved to the database.
 *
 * @param {object} settings block settings
 * @param {string} name block name
 * @returns {object} modified settings
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/filters/block-filters/#blocks-registerblocktype
 */
addFilter(
	"blocks.registerBlockType",
	"bdaitgen/override_core_img",
	function (settings, name) {
		if (name !== "core/image") {
			return settings;
		}

		return {
			...settings,
			attributes: {
				...settings.attributes,
				isPostLink: {
					type: "string",
					default: "",
				},
				customFieldName: {
					type: "string",
					default: "",
				},
			},
		};
	}
);

/**
 * Edit component for the block.
 *
 * @param {object} props block props
 * @returns {JSX}
 */
function Edit(props) {
	const [isSelectedKeywords, setIsSelectedKeywords] = useState(false);
	const [isSelectedOverwrite, setIsSelectedOverwrite] = useState(false);
	const [keywords, setKeywords] = useState('');
	const [loading, setLoading] = useState(false);

	const setCustomFieldName = (value) => {
		props.setAttributes({ customFieldName: value });
	};

	const handleInputChange = (event) => {
		setKeywords(event.target.value);
	}

	const extractKeywords = (content) => {
		return content.split(',').map(function (item) {
			return item.trim();
		}).filter(function (item) {
			return item.length > 0;
		}).slice(0, 6);
	}

	const getCurrentBlockUrl = () => {
		setLoading(true);
		if(props.attributes.alt !== '' && !isSelectedOverwrite) {
			jQuery.toast({
				heading: 'Warning',
				text: __('This image has already alt text if you want to override please checked the Overwrite existing alt text', 'ai-image-alt-text-generator-for-wp'),
				showHideTransition: 'fade',
				bgColor: '#DD6B20',
				loader: false,
				icon: 'warning',
				allowToastClose: false,
				position: {
					right: 80,
					top: 60
				},
			});
			setLoading(false);
			return false;
		}

		const postId = document.getElementById('post_ID')?.value;
		jQuery.ajax({
			type: 'post',
			dataType: 'json',
			url: import_csv.ajaxurl,
			data: {
				action: "bulk_alt_image_generator_gutenburg_block",
				nonce: import_csv.nonce,
				post_id: postId,
				attachment_id: props.attributes.id,
				attachment: props.attributes.url,
				keywords: extractKeywords(keywords),
				overrite_existing_image: isSelectedOverwrite,
			},
			success: function(response) {
				if(response.success === true) {
					props.setAttributes({ alt: response.data.text});
					document.querySelector('.editor-post-publish-button__button').click();

					jQuery.toast({
						heading: 'Warning',
						text: response.data.message,
						showHideTransition: 'fade',
						bgColor: '#DD6B20',
						loader: false,
						icon: 'warning',
						allowToastClose: false,
						position: {
							right: 80,
							top: 60
						},
					})
				}

				if(response.success === false) {
					if(response.data.status && response.data.status === 'error') {
						window.location.href = response.data.redirect_url;
					}
				}

				setLoading(false);
			},
			error: function (error) {
				console.log(error);
				setLoading(false);
			}
		})
	}

	return (
		<InspectorControls>
			<PanelBody title={__("AI ALT TEXT", "ai-image-alt-text-generator-for-wp")}>
				<PanelRow>
					<div class="bdai_alt_text_gutenburg_generator">
						<div class="bdaiatg_alt_text_gutenburg_generator_content">
							<p>Populate alt text using values from your media library images. If missing, alt text will be generated for an image and added to the post.</p>
							<div class="bdaiatg_alt_text_gutenburg_generator_content_checkbox">
								<input onChange={() => setIsSelectedOverwrite(!isSelectedOverwrite)} type="checkbox" id="bdaiatg-generate-button-overwrite-checkbox"/>
								<label for="bdaiatg-generate-button-overwrite-checkbox">Overwrite existing alt text</label>
							</div>
							<div class="bdaiatg_alt_text_gutenburg_generator_content_checkbox">
								<input type="checkbox" id="bdaiatg-generate-button-keywords-checkbox" onChange={() => setIsSelectedKeywords(!isSelectedKeywords)}/>
								<label for="bdaiatg-generate-button-keywords-checkbox">Add SEO keywords</label>
								{isSelectedKeywords && (
									<input
										   type="text"
										   id="bdaiatg-generate-button-keywords-seo"
										   onChange={handleInputChange}
										   placeholder="keyword1, keyword2"
									/>
								)}
							</div>

							<div id="bdaiatg_alt_text_gen_btn">
								<button onClick={() => getCurrentBlockUrl()}>
									{loading ?
										<div className={'loader'}></div> :
										<span>Generate Alt Text</span>
									}
								</button>
							</div>
						</div>
					</div>
				</PanelRow>
				{"custom_field" === props.attributes.isPostLink && (
					<PanelRow>
						<TextControl
							label={__("Custom field name")}
							value={props.attributes.customFieldName}
							onChange={setCustomFieldName}
							help={__("The name of the custom field to link to.", "ai-image-alt-text-generator-for-wp")}
						/>
					</PanelRow>
				)}
			</PanelBody>
		</InspectorControls>
	);
}

/**
 * Add the edit component to the block.
 * This is the component that will be rendered in the editor.
 * It will be rendered after the original block edit component.
 *
 * @param {function} BlockEdit Original component
 * @returns {function} Wrapped component
 *
 * @see https://developer.wordpress.org/block-editor/developers/filters/block-filters/#editor-blockedit
 */
addFilter(
	"editor.BlockEdit",
	"bdaitgen/override_core_img",
	createHigherOrderComponent((BlockEdit) => {
		return (props) => {
			if (props.name !== "core/image") {
				return <BlockEdit {...props} />;
			}

			return (
				<>
					<BlockEdit {...props} />
					<Edit {...props} />
				</>
			);
		};
	})
);
