(function( $ ) {
	'use strict';
	const { __, sprintf } = wp.i18n;
	window.bdaiatg = window.bdaiatg || { postsPerPage: 1, lastPostId: 0, intervals: {}, redirectUrl: '' };

	let availableToken = 0;
	let apiKeyInvalid = false;
	let jobLists = 0;
	let creditZero = 0;
	function isPostDirty() {
		try {
			// Check for Gutenberg
			if (window.wp && wp.data && wp.blocks) {
				return wp.data.select('core/editor').isEditedPostDirty();
			}
		} catch (error) {
			console.error('Error checking Gutenberg post dirty status: ', error);
			return true;
		}

		// TODO: Check for Classic Editor

		return true;
	}

	async function singleGenerateAJAX(attachmentId, keywords = [], site_url, attachment_url, api_key, language, image_title, image_caption, image_description, image_suffix, image_prefix) {
		const data = {
			'website_url': site_url,
			'file_url': attachment_url,
			'language': language,
			'keywords': keywords,
			'image_suffix': image_suffix,
			'image_prefix': image_prefix,
		}

		const response = await fetch('https://aialttext.boomdevs.com/wp-json/alt-text-generator/v1/get-alt-text', {
			method: 'POST',
			mode: 'cors',
			headers: {
				"Content-Type": "application/json",
				'Access-Control-Allow-Origin': '*',
				'token': api_key,
			},
			body: JSON.stringify(data),
		});

		const response_json = await response.json();

		if (!response_json.data.status === true) {
			return false;
		}

		jQuery.ajax({
			type: 'post',
			dataType: 'json',
			url: import_csv.ajaxurl,
			data: {
				action: 'bdaiatg_save_alt_text',
				nonce: import_csv.nonce,
				attachment_id: attachmentId,
				keywords: keywords,
				alt_text: response_json.data.generated_text,
				image_title: image_title,
				image_caption: image_caption,
				image_description: image_description,
			},
			success: function (response) {

			},
			error: function (response) {
				console.log(response)
			}
		});

		return response_json;
	}

	async function checkAvailableToken() {
		const response = await fetch('https://aialttext.boomdevs.com/wp-json/alt-text-generator/v1/available-token', {
			method: 'POST',
			mode: 'cors',
			headers: {
				"Content-Type": "application/json",
				'Access-Control-Allow-Origin': '*',
				'token': import_csv.api_key,
			},
		});

		return await response.json();
	}

	$('#bdaiatg-generate-button-keywords-checkbox').change(function (event) {
		if (this.checked) {
			$('#bdaiatg-generate-button-keywords-seo').css({
				display: 'block',
			});
		} else {
			$('#bdaiatg-generate-button-keywords-seo').css({
				display: 'none',
			});
		}
	});

	let overwrite_existing_img_alt;
	$(document).on('click', '#bdaiatg-generate-button-overwrite-checkbox', function() {
		overwrite_existing_img_alt = this.checked;
	});

	$('#bdaiatg_alt_text_gen_btn').click(function () {

		if(isPostDirty()) {
			// Ask for consent
			const consent = confirm(__('[AI Image ALT Text] Make sure to save any changes before proceeding -- any unsaved changes will be lost. Are you sure you want to continue?', 'ai-image-alt-text-generator-for-wp'));

			// If user doesn't consent, return
			if (!consent) {
				return;
			}
		}

		const postId = document.getElementById('post_ID')?.value;
		const keywords = document.getElementById('bdaiatg-generate-button-keywords-seo')?.value;
		const extractSeoKeywords = (keywords !== '') ? extractKeywords(keywords) : [];

		$('.bdaiatg_alt_text_gen_btn_post .loader').css({
			display: 'block'
		});

		$('.bdaiatg_alt_text_gen_btn_post .button_text').css({
			display: 'none'
		});

		let imageUrlArr = [];
		$('#editor img').each(function() {
			// Do something with each image, for example:
			let imageUrl = $(this).attr('src');
			let imgAlt = $(this).attr('alt');
			if (imageUrl.includes('wp-content/uploads')) {
				imageUrlArr.push(imageUrl);
				// if(overwrite_existing_img_alt) {
				// 	imageUrlArr.push(imageUrl);
				// }else {
				// 	if( imgAlt === '' || imgAlt.includes('This image has an empty alt attribute')) {
				// 		imageUrlArr.push(imageUrl);
				// 	}
				// }
			}
		});

		if(imageUrlArr.length === 0) {
			$.toast({
				heading: 'Warning',
				text: __('All image has alt text if you want to override please select Overwrite existing alt text.'),
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

			$('.bdaiatg_alt_text_gen_btn_post .loader').css({
				display: 'none'
			});
			$('.bdaiatg_alt_text_gen_btn_post .button_text').css({
				display: 'block'
			});
			return false;
		}

		jQuery.ajax({
			type: 'post',
			dataType: 'json',
			url: import_csv.ajaxurl,
			data: {
				action: "bulk_alt_image_generator_gutenburg_post",
				nonce: import_csv.nonce,
				post_id: postId,
				attachments: imageUrlArr,
				keywords: extractSeoKeywords,
				overrite_existing_images: overwrite_existing_img_alt ? overwrite_existing_img_alt : false,
			},
			success: function(response) {
				if(response.success) {
					$.toast({
						heading: 'Success',
						text: response.data.message,
						showHideTransition: 'fade',
						bgColor: '#38A169',
						loader: false,
						icon: 'success',
						allowToastClose: false,
						position: {
							right: 80,
							top: 60
						},
					});

					window.location.reload();
				}

				if(response.success === false) {
					if(response.data.redirect && response.data.redirect === true) {
						window.location.href = response.data.redirect_url;
					} else {
						$.toast({
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
				}

				$('.bdaiatg_alt_text_gen_btn_post .loader').css({
					display: 'none'
				});
				$('.bdaiatg_alt_text_gen_btn_post .button_text').css({
					display: 'block'
				});
			},
			error: function (error) {
				console.log(error);
				$('.bdaiatg_alt_text_gen_btn_post .loader').css({
					display: 'none'
				});
				$('.bdaiatg_alt_text_gen_btn_post .button_text').css({
					display: 'block'
				});
			}
		});
	});

	// Get all jobs list if default localize jobs not found
	function getJobsLists() {
		jQuery.ajax({
			type: 'post',
			dataType: 'json',
			url: import_csv.ajaxurl,
			data: {
				action: "get_all_added_jobs",
				nonce: import_csv.nonce,
			},
			success: function(response) {
				$('.baiatgd_bulk_progress_card').css({
					display: 'block',
				});

				fetchGenerateJobs();
				buttonStatusDisableSet();
			}
		})
	}

	// Plan token
	async function plan_credit() {
		if($(".boomdevs_ai_img_alt_text_generator_dashboard").length === 0) {
			return false;
		}
		let response_json = await checkAvailableToken();

		if(!response_json.success) {
			apiKeyInvalid = true;
			showWarning(response_json.data.message);
			return false;
		}

		const available_token = document.getElementById('bdaiatg_available_token_num')
		const subscription_plan = document.getElementById('subscription_plan')
		const remaining_credit = document.getElementById('remaining_credit');
		const total_token = document.getElementById('bdaiatg_token_token_num');
		const spent_token_percent = document.getElementById('bdaiatg_spent_token');
		const percent_start = document.getElementById('bdiatgd_percent_start');
		const progress = document.getElementById('progress');
		const avail =  response_json.data.available_token;

		availableToken = avail;
		const total =  response_json.data.total_token;

		const remainingToken = parseInt(total) - parseInt(avail);

		const afterAvailableToken = parseInt(total) - parseInt(remainingToken);
		creditZero = afterAvailableToken;

		if (response_json.data.subscriptions.hasOwnProperty('sumo_product_name') && response_json.data.subscriptions.sumo_product_name.length > 0) {
			subscription_plan.innerText = response_json.data.subscriptions.sumo_product_name[0];
		} else {
			subscription_plan.innerText = 'Free plan';
		}

		remaining_credit.innerText = afterAvailableToken;

		const percentCalc = (avail && total) ? ((remainingToken / total) * 100).toFixed(0) : 0;
		available_token.innerText = avail ? remainingToken : 0;
		total_token.innerText = total ? total : 0;
		spent_token_percent.innerText = !isNaN(percentCalc) ? percentCalc : '0';
		percent_start.innerText = !isNaN(percentCalc) ? percentCalc + '%' : '0%';

		if (!isNaN(percentCalc)) {
			// progress.innerHTML = percentCalc;
			progress.setAttribute('data-percent', percentCalc);
			progress.style.width = percentCalc + '%';
		} else {
			// progress.innerHTML = '0';
			progress.setAttribute('data-percent', '0');
			progress.style.width = '0%';
		}
	}
	plan_credit();

	$(document).on('click', '#cancel_bulk_alt_image_generator', function() {
		localStorage.removeItem('buttonDisabledStatus');

		let spinner = $('.spinner-icon');
		spinner.css({
			display: 'block',
		});

		jQuery.ajax({
			type: 'post',
			dataType: 'json',
			url: import_csv.ajaxurl,
			data: {
				action: "cancel_bulk_alt_image_generator",
				nonce: import_csv.nonce,
			},
			success: function(response) {
				console.log(response)
				$('.baiatgd_bulk_progress_card').css({
					display: 'none',
				});
				spinner.css({
					display: 'none',
				});
			},
			error: function (error) {
				console.log(error)
				spinner.css({
					display: 'none',
				});
			}
		})
	});

	let overrite_existing_images, bulk_generate_only_new;

	$(document).on('click', '#bdaiatg_bulk_generate_all', function() {
		overrite_existing_images = this.checked;
	})

	$(document).on('click', '#bdaiatg_bulk_generate_only_new', function() {
		bulk_generate_only_new = this.checked;
	})

	const buttonDisabledStatus = localStorage.getItem('buttonDisabledStatus');
	const generateAllTextButton = document.getElementById('generate_alt_text');

	if(buttonDisabledStatus === 'true') {
		generateAllTextButton.disabled = true;
	}

	let alreadyShownSuccessToast = false;
	async function fetchGenerateJobs() {
		const button = document.getElementById('generate_alt_text');

		try {
			const response = await fetch('/wp-json/alt-text-generator/v1/fetch-jobs');
			const response_json = await response.json();

			const bulk_alt_text_progress = document.getElementById('bulk_alt_text_progress');
			const total_attachment_count = document.getElementById('total_attachment_count');
			const attachment_generated_count = document.getElementById('attachment_generated_count');
			const bulk_progress = document.getElementById('bulk-progress');

			if(bulk_alt_text_progress !== null) {
				bulk_alt_text_progress.innerText = Math.floor(response_json.data.progress_percentage) + '%';
				bulk_progress.setAttribute('data-percent', response_json.data.progress_percentage);
				bulk_progress.style.width = response_json.data.progress_percentage + '%';

				total_attachment_count.innerText = response_json.data.total_jobs_count;
				attachment_generated_count.innerText = response_json.data.count_increase;

				if(response_json.data.all_status) {
					generateAllTextButton.disabled = false;
					generateAllTextButton.style.background = 'transparent';
					generateAllTextButton.style.backgroundImage = 'linear-gradient(to right, #060097, #8204FF, #C10FFF)';
					localStorage.removeItem('buttonDisabledStatus');

					plan_credit();
					hideProgressBar();
					buttonStatusEnableSet();

					$.toast({
						heading: 'Success',
						text: response_json.data.total_jobs_count + ' images has been successfully generated',
						showHideTransition: 'fade',
						bgColor: '#38A169',
						loader: false,
						icon: 'success',
						allowToastClose: false,
						position: {
							right: 80,
							top: 60
						},
					});
				} else {
					setTimeout(fetchGenerateJobs, 20000);
					generateAllTextButton.disabled = true;
					$('.baiatgd_bulk_progress_card').css({
						display: 'block',
					});
				}
			}

			await checkAvailableToken();
		} catch (error) {
			console.log(error);
		}
	}

	function buttonStatusDisableSet() {
		$('.bd_aitgen_loader').css("visibility", "visible");
		generateAllTextButton.disabled = true;
	}

	function buttonStatusEnableSet() {
		$('.bd_aitgen_loader').css("visibility", "hidden");
		generateAllTextButton.disabled = false;
	}

	function showProgressBar() {
		if(import_csv.has_jobs_list !== '0') {
			$('.baiatgd_bulk_progress_card').css({
				display: 'block',
			});
			fetchGenerateJobs();
			buttonStatusDisableSet();
		}
	}

	showProgressBar();

	function hideProgressBar() {
		$('.baiatgd_bulk_progress_card').css({
			display: 'none',
		});
	}

	// showProgressBar();

	$(document).on('click', '#generate_alt_text', function() {
		buttonStatusDisableSet();

		if(import_csv.api_key === '') {
			buttonStatusEnableSet();
			showWarning('Please set api key from settings menu.');
			return false;
		}

		if(apiKeyInvalid) {
			buttonStatusEnableSet();
			showWarning('Invalid api key please contact with support.');
			return false;
		}

		if(availableToken === 0) {
			buttonStatusEnableSet();
			showWarning("You don't have sufficient credit please purchases more and try again letter");
			return false;
		}

		if(creditZero === 0) {
			buttonStatusEnableSet();
			showWarning("You don't have sufficient credit please purchases more and try again letter");
			return false;
		}

		jQuery.ajax({
			type: 'post',
			dataType: 'json',
			url: import_csv.ajaxurl,
			data: {
				action: "bulk_alt_image_generator",
				nonce: import_csv.nonce,
				overrite_existing_images: overrite_existing_images ? overrite_existing_images : false,
			},
			success: function(response) {
				console.log(response)
				if(!response.success) {
					if(response.data.message) {
						$.toast({
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
					buttonStatusEnableSet();
				} else {
					if(parseInt(import_csv.has_jobs_list) === 0) {
						getJobsLists();
					}
				}
			},
			error: function (error) {
				console.log(error);
			}
		});
	});

	// function check_no_credit() {
	// 	jQuery.ajax({
	// 		type: 'post',
	// 		dataType: 'json',
	// 		url: import_csv.ajaxurl,
	// 		data: {
	// 			action: "check_no_credit",
	// 		},
	// 		success: function(response) {
	// 			// console.log(response)
	// 			if(!response.success) {
	// 				$.toast({
	// 					heading: 'Warning',
	// 					text: response.data.message,
	// 					showHideTransition: 'fade',
	// 					bgColor: '#DD6B20',
	// 					loader: false,
	// 					icon: 'warning',
	// 					allowToastClose: false,
	// 					position: {
	// 						right: 80,
	// 						top: 60
	// 					},
	// 				})
	// 			}
	// 		},
	// 		error: function (error) {
	// 			console.log("Hey there")
	// 			console.log(error);
	// 		}
	// 	})
	// }

	function showWarning(msg) {
		$.toast({
			heading: 'Warning',
			text: msg,
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

	$(document).on('click', '#wp_default_button', function(e) {
		e.preventDefault();
		let fileInput = document.getElementById('file_input');

		if (fileInput.files.length > 0) {
			let file = fileInput.files[0];
			let reader = new FileReader();

			reader.onload = function(event) {
				let csvContent = event.target.result;
				let lines = csvContent.trim().split("\n");
				let result = [];

				for (let i = 1; i < lines.length; i++) {
					let obj = {};
					let currentline = lines[i].split(",");
					for (let j = 0; j < currentline.length; j++) {
						obj[j] = currentline[j].trim();
					}
					result.push(obj);
				}

				// console.log(result);
				// event.currentTarget.submit();

				jQuery.ajax({
					type: 'post',
					dataType: 'json',
					url: import_csv.ajaxurl,
					data: {
						action: "import_csv",
						nonce: import_csv.nonce,
						result: result,
					},
					success: function(response) {
						console.log(response)
					}
				})
			};
			reader.readAsText(file);
		} else {
			alert('Please select a file.');
		}
	});

	function extractKeywords(content) {
		return content.split(',').map(function (item) {
			return item.trim();
		}).filter(function (item) {
			return item.length > 0;
		}).slice(0, 6);
	}

	function getQueryParam(name) {
		name = name.replace(/[[]/, '\\[').replace(/[\]]/, '\\]');
		let regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
		let paramSearch = regex.exec(window.location.search);

		return paramSearch === null ? '' : decodeURIComponent(paramSearch[1].replace(/\+/g, ' '));
	}

	function addGenerateButtonToModal(hostWrapperId, generateButtonId, attachmentId) {
		let hostWrapper = document.getElementById(hostWrapperId);

		// Remove existing button, if any
		let oldGenerateButton = document.getElementById(generateButtonId);

		if (oldGenerateButton) {
			oldGenerateButton.remove();
		}

		if (hostWrapper) {
			let generateButton = createGenerateButton(generateButtonId, attachmentId, 'modal');
			hostWrapper.appendChild(generateButton);

			return true;
		}

		return false;
	}

	function createGenerateButton(generateButtonId, attachmentId, context) {
		const generateUrl = new URL(window.location.href);
		generateUrl.searchParams.set('bdaiatg_action', 'generate');

		// Button wrapper
		const button = document.createElement('div');
		button.id = generateButtonId;

		// Clickable anchor inside the wrapper for initiating the action
		const anchor = document.createElement('a');
		anchor.id = generateButtonId + '-anchor';
		anchor.href = generateUrl;
		anchor.className = 'button-secondary button-large';

		// Create checkbox wrapper
		const keywordsCheckboxWrapper = document.createElement('div');
		keywordsCheckboxWrapper.id = generateButtonId + '-checkbox-wrapper';

		// Create checkbox
		const keywordsCheckbox = document.createElement('input');
		keywordsCheckbox.type = 'checkbox';
		keywordsCheckbox.id = generateButtonId + '-keywords-checkbox';
		keywordsCheckbox.name = 'bdaiatg-generate-button-keywords-checkbox';

		// Create label for checkbox
		const keywordsCheckboxLabel = document.createElement('label');
		keywordsCheckboxLabel.htmlFor = 'bdaiatg-generate-button-keywords-checkbox';
		keywordsCheckboxLabel.innerText = 'Add SEO keywords';

		// Create text field wrapper
		const keywordsTextFieldWrapper = document.createElement('div');
		keywordsTextFieldWrapper.id = generateButtonId + '-textfield-wrapper';
		keywordsTextFieldWrapper.style.display = 'none';

		// Create text field
		const keywordsTextField = document.createElement('input');
		keywordsTextField.type = 'text';
		keywordsTextField.id = generateButtonId + '-textfield';
		keywordsTextField.name = 'bdaiatg-generate-button-keywords';
		keywordsTextField.size = 40;

		// Append checkbox and label to its wrapper
		keywordsCheckboxWrapper.appendChild(keywordsCheckbox);
		keywordsCheckboxWrapper.appendChild(keywordsCheckboxLabel);

		// Append text field to its wrapper
		keywordsTextFieldWrapper.appendChild(keywordsTextField);

		// Event listener to show/hide text field on checkbox change
		keywordsCheckbox.addEventListener('change', function () {
			if (this.checked) {
				keywordsTextFieldWrapper.style.display = 'block';
				keywordsTextField.setSelectionRange(0, 0);
				keywordsTextField.focus();
			} else {
				keywordsTextFieldWrapper.style.display = 'none';
			}
		});

		// anchor.title = __('AltText.ai: Update alt text for this single image', 'alttext-ai');
		anchor.onclick = function () {
			this.classList.add('disabled');
			let span = this.querySelector('span');

			if (span) {
				span.innerText = 'Processing...';
			}
		};

		// Button icon
		const img = document.createElement('img');
		img.src = import_csv.icon_button_generate;
		img.alt = 'Update Alt Text with AltText.ai';
		anchor.appendChild(img);

		// Button label/text
		const span = document.createElement('span');
		span.innerText = 'Update Alt Text';
		anchor.appendChild(span);

		// Append anchor to the button
		button.appendChild(anchor);

		// Append checkbox and text field wrappers to the button
		button.appendChild(keywordsCheckboxWrapper);
		button.appendChild(keywordsTextFieldWrapper);

		// Event listener to initiate generation
		anchor.addEventListener('click', async function (event) {
			event.preventDefault();

			const titleEl = (context === 'single') ? document.getElementById('title') : document.querySelector('[data-setting="title"] input');
			const captionEl = (context === 'single') ? document.getElementById('attachment_caption') : document.querySelector('[data-setting="caption"] textarea');
			const descriptionEl = (context === 'single') ? document.getElementById('attachment_content') : document.querySelector('[data-setting="description"] textarea');
			const altTextEl = (context === 'single') ? document.getElementById('attachment_alt') : document.querySelector('[data-setting="alt"] textarea');
			const attachmentEl = (context === 'single') ? document.getElementById('attachment_url') : document.querySelector('[data-setting="url"] input');
			const keywords = keywordsCheckbox.checked ? extractKeywords(keywordsTextField.value) : [];

			if(!import_csv.api_key) {
				window.location.href = 'admin.php?page=boomdevs-ai-image-alt-text-generator-settings';
			}

			const response = await singleGenerateAJAX(attachmentId, keywords, import_csv.site_url, attachmentEl.value, import_csv.api_key, import_csv.language, import_csv.image_title[0], import_csv.image_caption[0], import_csv.image_description[0], import_csv.image_suffix, import_csv.image_prefix);

			// Update alt text in DOM
			if (response.data.status === true) {
				altTextEl.value = response.data.generated_text;

				if(import_csv.image_title[0] === 'update_title') {
					titleEl.value = response.data.generated_text;
				}

				if(import_csv.image_caption[0] === 'update_caption') {
					captionEl.value = response.data.generated_text;
				}

				if(import_csv.image_description[0] === 'update_description') {
					descriptionEl.value = response.data.generated_text;
				}
			}

			anchor.classList.remove('disabled');
			anchor.querySelector('span').innerText = 'Update Alt Text';
		});

		return button;
	}

	document.addEventListener('DOMContentLoaded', async () => {
		const isAttachmentPage = window.location.href.includes('post.php') && jQuery('body').hasClass('post-type-attachment');
		const isEditPost = window.location.href.includes('post-new.php') || (window.location.href.includes('post.php') && !jQuery('body').hasClass('post-type-attachment'));
		const isAttachmentModal = window.location.href.includes('upload.php');
		let attachmentId = null;
		let generateButtonId = 'bdaiatg-generate-button';
		let hostWrapperId = 'alt-text-description';

		if (isAttachmentPage) {
			attachmentId = getQueryParam('post');

			// Bail early if no post ID.
			if (!attachmentId) {
				return false;
			}

			attachmentId = parseInt(attachmentId, 10);

			// Bail early if post ID is not a number.
			if (!attachmentId) {
				return;
			}

			let hostWrapper = document.getElementById(hostWrapperId);

			if (hostWrapper) {
				let generateButton = createGenerateButton(generateButtonId, attachmentId, 'single');
				hostWrapper.appendChild(generateButton);
			}
		} else if (isAttachmentModal || isEditPost) {
			attachmentId = getQueryParam('item');

			// Listen to modal open
			$(document).on('click', 'ul.attachments li.attachment', function () {
				let element = $(this);

				// Bail early if no data-id attribute.
				if (!element.attr('data-id')) {
					return;
				}

				attachmentId = parseInt(element.attr('data-id'), 10);

				// Bail early if post ID is not a number.
				if (!attachmentId) {
					return;
				}

				addGenerateButtonToModal(hostWrapperId, generateButtonId, attachmentId);
			});

			// Listen to modal navigation
			document.addEventListener('click', function (event) {
				// Bail early if not clicking on the modal navigation.
				if (!event.target.matches('.media-modal .right, .media-modal .left')) {
					return;
				}

				// Get attachment ID from URL.
				const urlParams = new URLSearchParams(window.location.search);
				attachmentId = urlParams.get('item');

				console.log(attachmentId);

				// Bail early if post ID is not a number.
				if (!attachmentId) {
					return;
				}

				addGenerateButtonToModal(hostWrapperId, generateButtonId, attachmentId);
			});

			// Bail early if no post ID.
			if (!attachmentId) {
				return false;
			}

			// Check if this is a modal based on the attachment ID
			if (attachmentId) {
				// Wait until modal is in the DOM.
				let intervalCount = 0;
				window.bdaiatg.intervals['singleModal'] = setInterval(() => {
					intervalCount++;

					if (intervalCount > 20) {
						clearInterval(interval);
						return;
					}

					attachmentId = parseInt(attachmentId, 10);

					// Bail early if post ID is not a number.
					if (!attachmentId) {
						return;
					}

					let buttonAdded = addGenerateButtonToModal(hostWrapperId, generateButtonId, attachmentId);

					if (buttonAdded) {
						clearInterval(window.bdaiatg.intervals['singleModal']);
					}
				}, 500);
			}
		} else {
			return false;
		}
	});
})( jQuery );
