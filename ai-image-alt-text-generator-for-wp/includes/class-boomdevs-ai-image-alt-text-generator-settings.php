<?php

	/**
	 * Prevent direct access to this file.
	 */
	if (!defined('ABSPATH')) {
		exit;
	}

	/**
	 * Class for managing settings and options in the WP Swiss Toolkit plugin.
	 */
	if (!class_exists('BDAIATG_Boomdevs_Ai_Image_Alt_Text_Generator_Settings')) {
		class BDAIATG_Boomdevs_Ai_Image_Alt_Text_Generator_Settings
		{
			public static $all_images;
			public static $missing_alt_text_count;


			/**
			 * Plugin url.
			 *
			 * @var string
			 */
			public static $plugin_file_url  = BDAIATG_AI_IMAGE_ALT_TEXT_GENERATOR_URL;
			/**
			 * Plugin settings prefix.
			 *
			 * @var string
			 */
			public static $prefix = BDAIATG_AI_IMAGE_ALT_TEXT_GENERATOR_NAME;


			/**
			 * Constructor function for initializing actions and filters.
			 */
			public function __construct()
			{
				add_filter('Boomdevs_Ai_Image_Alt_Text_Generator_register_options_panel', array($this, 'register_options_panel'), 1, 2);
				$this->get_media_images_and_alt_text();
			}

			/**
			 * Default config for settings panel.
			 *
			 * @param $options_panel_func string Settings panel function name.
			 * @param $options_panel_config array Settings panel configurations.
			 *
			 * @return array
			 */
			public function register_options_panel($options_panel_func, $options_panel_config)
			{
				return array(
					'func'   => $options_panel_func,
					'config' => $options_panel_config,
				);
			}

			/**
			 * Generate the settings panel for WP Swiss Toolkit.
			 *
			 * This method configures and creates the settings panel for the WP Swiss Toolkit plugin.
			 * It defines various options and sections for the panel, including appearance and functionality settings.
			 */
			public function generate_settings()
			{
				/**
				 * Configuration settings for the WP Swiss Toolkit plugin's options panel.
				 *
				 * This array defines various configuration options for the plugin's settings panel,
				 * including panel title, menu settings, and appearance settings.
				 */
				$options_panel_config = array(
					'framework_title' => __('Ai Image Alt Text Generator for WP', 'ai-image-alt-text-generator-for-wp'),
					'footer_text'     => sprintf('Our Alt Text Generator documentation can help you get started <a href="%s">documentation</a>', esc_url('https://wpmessiah.com/docs/')),
					'footer_credit'   => sprintf('A proud creation of <a href="%s">BoomDevs</a>', esc_url('https://wpmessiah.com/')),
					'menu_title'      => esc_html__('Alt Text Generator', 'ai-image-alt-text-generator-for-wp'),
					'menu_icon'       => BDAIATG_AI_IMAGE_ALT_TEXT_GENERATOR_URL . 'admin/img/plugin-logo.svg',
					'menu_slug'       => 'boomdevs-ai-image-alt-text-generator-settings',
					'database'        => 'option',
					'transport'       => 'refresh',
					'capability'      => 'manage_options',
                    'menu_hidden'     => true,
                    'show_bar_menu'   => false,
					'menu_type'       => 'menu',
					'save_defaults'   => true,
					'enqueue_webfont' => true,
					'async_webfont'   => true,
					'output_css'      => true,
					'show_all_options' => false,
					'ajax_save'       => true,
					'show_reset_all'  => true,
					'show_reset_section'  => false,
					'show_search'     => false,
					// Additional configuration options (commented out for now)
					// 'nav'              => 'inline',
					// 'theme'            => 'light',
					'class'           => 'boomdevs_ai_img_alt_text_generator',
					'menu_position'   => 59,
				);

				// Register settings panel type
				$options_panel_func   = 'createOptions';
				$options_panel_builder = apply_filters('Boomdevs_Ai_Image_Alt_Text_Generator_register_options_panel', $options_panel_func, $options_panel_config);

				CSF::{$options_panel_builder['func']}(self::$prefix, $options_panel_builder['config']);

				$parent = '';

				if ($options_panel_builder['func'] == 'createCustomizeOptions') {
					// Add to level section if in customizer mode
					CSF::createSection(self::$prefix, array(
						'id'    => self::$prefix,
						'title' => BDAIATG_AI_IMAGE_ALT_TEXT_GENERATOR_FULL_NAME
					));

					$parent = self::$prefix;
				}

				// Create a section
				CSF::createSection(self::$prefix, array(
					'parent' => $parent,
					'title'  => esc_html__('Settings', 'ai-image-alt-text-generator-for-wp'),
					'fields' => array(
						array(
							'type'    => 'content',
							'content' => esc_html__('Adjust the settings to access all the essential features without the hassle of installing numerous plugins.', 'ai-image-alt-text-generator-for-wp'),
						),

						// Start API Key
						array(
							'id'     => 'bdaiatg_api_key_wrapper',
							'type'   => 'fieldset',
							'title'   => esc_html__('API Key', 'ai-image-alt-text-generator-for-wp'),
							'fields' => array(
								array(
									'id'    => 'bdaiatg_api_key',
									'type'  => 'text',
									'validate' => 'validate_bdaiatg_api_key',
									'after' => '<a href="https://aialttextgenerator.com/register/" target="_blank">Manage your account</a> and additional settings.'
								),
							),
						),

						// Start Alt Text Language
						array(
							'id'     => 'bdaiatg_alt_text_language_wrapper',
							'type'   => 'fieldset',
							'title'   => esc_html__('Alt Text Language', 'ai-image-alt-text-generator-for-wp'),
							'fields' => array(
								array(
									'id'    => 'bdaiatg_alt_text_language',
									'type'  => 'select',
									'options'     => array(
										'bs'          => 'Bosnian',
										'ee_TG'       => 'Ewe (Togo)',
										'ms'          => 'Malay',
										'kam_KE'      => 'Kamba (Kenya)',
										'mt'          => 'Maltese',
										'ha'          => 'Hausa',
										'es_HN'       => 'Spanish (Honduras)',
										'ml_IN'       => 'Malayalam (India)',
										'ro_MD'       => 'Romanian (Moldova)',
										'kab_DZ'      => 'Kabyle (Algeria)',
										'he'          => 'Hebrew',
										'es_CO'       => 'Spanish (Colombia)',
										'my'          => 'Burmese',
										'es_PA'       => 'Spanish (Panama)',
										'az_Latn'     => 'Azerbaijani (Latin)',
										'mer'         => 'Meru',
										'en_NZ'       => 'English (New Zealand)',
										'xog_UG'      => 'Soga (Uganda)',
										'sg'          => 'Sango',
										'fr_GP'       => 'French (Guadeloupe)',
										'sr_Cyrl_BA'  => 'Serbian (Cyrillic, Bosnia and Herzegovina)',
										'hi'          => 'Hindi',
										'fil_PH'      => 'Filipino (Philippines)',
										'lt_LT'       => 'Lithuanian (Lithuania)',
										'si'          => 'Sinhala',
										'en_MT'       => 'English (Malta)',
										'si_LK'       => 'Sinhala (Sri Lanka)',
										'luo_KE'      => 'Luo (Kenya)',
										'it_CH'       => 'Italian (Switzerland)',
										'teo'         => 'Teso',
										'mfe'         => 'Morisyen',
										'sk'          => 'Slovak',
										'uz_Cyrl_UZ'  => 'Uzbek (Cyrillic, Uzbekistan)',
										'sl'          => 'Slovenian',
										'rm_CH'       => 'Romansh (Switzerland)',
										'az_Cyrl_AZ'  => 'Azerbaijani (Cyrillic, Azerbaijan)',
										'fr_GQ'       => 'French (Equatorial Guinea)',
										'kde'         => 'Makonde',
										'sn'          => 'Shona',
										'cgg_UG'      => 'Chiga (Uganda)',
										'so'          => 'Somali',
										'fr_RW'       => 'French (Rwanda)',
										'es_SV'       => 'Spanish (El Salvador)',
										'mas_TZ'      => 'Masai (Tanzania)',
										'en_MU'       => 'English (Mauritius)',
										'sq'          => 'Albanian',
										'hr'          => 'Croatian',
										'sr'          => 'Serbian',
										'en_PH'       => 'English (Philippines)',
										'ca'          => 'Catalan',
										'hu'          => 'Hungarian',
										'mk_MK'       => 'Macedonian (Macedonia)',
										'fr_TD'       => 'French (Chad)',
										'nb'          => 'Norwegian Bokmål',
										'sv'          => 'Swedish',
										'kln_KE'      => 'Kalenjin (Kenya)',
										'sw'          => 'Swahili',
										'nd'          => 'North Ndebele',
										'sr_Latn'     => 'Serbian (Latin)',
										'el_GR'       => 'Greek (Greece)',
										'hy'          => 'Armenian',
										'ne'          => 'Nepali',
										'el_CY'       => 'Greek (Cyprus)',
										'es_CR'       => 'Spanish (Costa Rica)',
										'fo_FO'       => 'Faroese (Faroe Islands)',
										'pa_Arab_PK'  => 'Punjabi (Arabic, Pakistan)',
										'seh'         => 'Sena',
										'ar_YE'       => 'Arabic (Yemen)',
										'ja_JP'       => 'Japanese (Japan)',
										'ur_PK'       => 'Urdu (Pakistan)',
										'pa_Guru'     => 'Punjabi (Gurmukhi)',
										'gl_ES'       => 'Galician (Spain)',
										'zh_Hant_HK'  => 'Chinese (Traditional Han, Hong Kong SAR China)',
										'ar_EG'       => 'Arabic (Egypt)',
										'nl'          => 'Dutch',
										'th_TH'       => 'Thai (Thailand)',
										'es_PE'       => 'Spanish (Peru)',
										'fr_KM'       => 'French (Comoros)',
										'nn'          => 'Norwegian Nynorsk',
										'kk_Cyrl_KZ'  => 'Kazakh (Cyrillic, Kazakhstan)',
										'kea'         => 'Kabuverdianu',
										'lv_LV'       => 'Latvian (Latvia)',
										'kln'         => 'Kalenjin',
										'tzm_Latn'    => 'Central Morocco Tamazight (Latin)',
										'yo'          => 'Yoruba',
										'gsw_CH'      => 'Swiss German (Switzerland)',
										'ha_Latn_GH'  => 'Hausa (Latin, Ghana)',
										'is_IS'       => 'Icelandic (Iceland)',
										'pt_BR'       => 'Portuguese (Brazil)',
										'cs'          => 'Czech',
										'en_PK'       => 'English (Pakistan)',
										'fa_IR'       => 'Persian (Iran)',
										'zh_Hans_SG'  => 'Chinese (Simplified Han, Singapore)',
										'luo'         => 'Luo',
										'ta'          => 'Tamil',
										'fr_TG'       => 'French (Togo)',
										'kde_TZ'      => 'Makonde (Tanzania)',
										'mr_IN'       => 'Marathi (India)',
										'ar_SA'       => 'Arabic (Saudi Arabia)',
										'ka_GE'       => 'Georgian (Georgia)',
										'mfe_MU'      => 'Morisyen (Mauritius)',
										'id'          => 'Indonesian',
										'fr_LU'       => 'French (Luxembourg)',
										'de_LU'       => 'German (Luxembourg)',
										'ru_MD'       => 'Russian (Moldova)',
										'cy'          => 'Welsh',
										'zh_Hans_HK'  => 'Chinese (Simplified Han, Hong Kong SAR China)',
										'te'          => 'Telugu',
										'bg_BG'       => 'Bulgarian (Bulgaria)',
										'shi_Latn'    => 'Tachelhit (Latin)',
										'ig'          => 'Igbo',
										'ses'         => 'Koyraboro Senni',
										'ii'          => 'Sichuan Yi',
										'es_BO'       => 'Spanish (Bolivia)',
										'th'          => 'Thai',
										'ko_KR'       => 'Korean (South Korea)',
										'ti'          => 'Tigrinya',
										'it_IT'       => 'Italian (Italy)',
										'shi_Latn_MA' => 'Tachelhit (Latin, Morocco)',
										'pt_MZ'       => 'Portuguese (Mozambique)',
										'ff_SN'       => 'Fulah (Senegal)',
										'haw'         => 'Hawaiian',
										'zh_Hans'     => 'Chinese (Simplified Han)',
										'so_KE'       => 'Somali (Kenya)',
										'bn_IN'       => 'Bengali (India)',
										'en_UM'       => 'English (U.S. Minor Outlying Islands)',
										'to'          => 'Tonga',
										'id_ID'       => 'Indonesian (Indonesia)',
										'uz_Cyrl'     => 'Uzbek (Cyrillic)',
										'en_GU'       => 'English (Guam)',
										'es_EC'       => 'Spanish (Ecuador)',
										'en_US_POSIX' => 'English (United States, Computer)',
										'sr_Latn_BA'  => 'Serbian (Latin, Bosnia and Herzegovina)',
										'is'          => 'Icelandic',
										'luy'         => 'Luyia',
										'tr'          => 'Turkish',
										'en_NA'       => 'English (Namibia)',
										'it'          => 'Italian',
										'da'          => 'Danish',
										'bo_IN'       => 'Tibetan (India)',
										'vun_TZ'      => 'Vunjo (Tanzania)',
										'ar_SD'       => 'Arabic (Sudan)',
										'uz_Latn_UZ'  => 'Uzbek (Latin, Uzbekistan)',
										'az_Latn_AZ'  => 'Azerbaijani (Latin, Azerbaijan)',
										'de'          => 'German',
										'es_GQ'       => 'Spanish (Equatorial Guinea)',
										'ta_IN'       => 'Tamil (India)',
										'de_DE'       => 'German (Germany)',
										'fr_FR'       => 'French (France)',
										'rof_TZ'      => 'Rombo (Tanzania)',
										'ar_LY'       => 'Arabic (Libya)',
										'en_BW'       => 'English (Botswana)',
										'asa'         => 'Asu',
										'zh'          => 'Chinese',
										'ha_Latn'     => 'Hausa (Latin)',
										'fr_NE'       => 'French (Niger)',
										'es_MX'       => 'Spanish (Mexico)',
										'bem_ZM'      => 'Bemba (Zambia)',
										'zh_Hans_CN'  => 'Chinese (Simplified Han, China)',
										'bn_BD'       => 'Bengali (Bangladesh)',
										'pt_GW'       => 'Portuguese (Guinea-Bissau)',
										'om'          => 'Oromo',
										'jmc'         => 'Machame',
										'de_AT'       => 'German (Austria)',
										'kk_Cyrl'     => 'Kazakh (Cyrillic)',
										'sw_TZ'       => 'Swahili (Tanzania)',
										'ar_OM'       => 'Arabic (Oman)',
										'et_EE'       => 'Estonian (Estonia)',
										'or'          => 'Oriya',
										'da_DK'       => 'Danish (Denmark)',
										'ro_RO'       => 'Romanian (Romania)',
										'zh_Hant'     => 'Chinese (Traditional Han)',
										'bm_ML'       => 'Bambara (Mali)',
										'ja'          => 'Japanese',
										'fr_CA'       => 'French (Canada)',
										'naq'         => 'Nama',
										'zu'          => 'Zulu',
										'en_IE'       => 'English (Ireland)',
										'ar_MA'       => 'Arabic (Morocco)',
										'es_GT'       => 'Spanish (Guatemala)',
										'uz_Arab_AF'  => 'Uzbek (Arabic, Afghanistan)',
										'en_AS'       => 'English (American Samoa)',
										'bs_BA'       => 'Bosnian (Bosnia and Herzegovina)',
										'am_ET'       => 'Amharic (Ethiopia)',
										'ar_TN'       => 'Arabic (Tunisia)',
										'haw_US'      => 'Hawaiian (United States)',
										'ar_JO'       => 'Arabic (Jordan)',
										'fa_AF'       => 'Persian (Afghanistan)',
										'uz_Latn'     => 'Uzbek (Latin)',
										'en_BZ'       => 'English (Belize)',
										'nyn_UG'      => 'Nyankole (Uganda)',
										'ebu_KE'      => 'Embu (Kenya)',
										'te_IN'       => 'Telugu (India)',
										'cy_GB'       => 'Welsh (United Kingdom)',
										'uk'          => 'Ukrainian',
										'nyn'         => 'Nyankole',
										'en_JM'       => 'English (Jamaica)',
										'en_US'       => 'English (United States)',
										'fil'         => 'Filipino',
										'ar_KW'       => 'Arabic (Kuwait)',
										'af_ZA'       => 'Afrikaans (South Africa)',
										'en_CA'       => 'English (Canada)',
										'fr_DJ'       => 'French (Djibouti)',
										'ti_ER'       => 'Tigrinya (Eritrea)',
										'ig_NG'       => 'Igbo (Nigeria)',
										'en_AU'       => 'English (Australia)',
										'ur'          => 'Urdu',
										'fr_MC'       => 'French (Monaco)',
										'pt_PT'       => 'Portuguese (Portugal)',
										'pa'          => 'Punjabi',
										'es_419'      => 'Spanish (Latin America)',
										'fr_CD'       => 'French (Congo - Kinshasa)',
										'en_SG'       => 'English (Singapore)',
										'bo_CN'       => 'Tibetan (China)',
										'kn_IN'       => 'Kannada (India)',
										'sr_Cyrl_RS'  => 'Serbian (Cyrillic, Serbia)',
										'lg_UG'       => 'Ganda (Uganda)',
										'gu_IN'       => 'Gujarati (India)',
										'ee'          => 'Ewe',
										'nd_ZW'       => 'North Ndebele (Zimbabwe)',
										'bem'         => 'Bemba',
										'uz'          => 'Uzbek',
										'sw_KE'       => 'Swahili (Kenya)',
										'sq_AL'       => 'Albanian (Albania)',
										'hr_HR'       => 'Croatian (Croatia)',
										'mas_KE'      => 'Masai (Kenya)',
										'el'          => 'Greek',
										'ti_ET'       => 'Tigrinya (Ethiopia)',
										'es_AR'       => 'Spanish (Argentina)',
										'pl'          => 'Polish',
										'en'          => 'English',
										'eo'          => 'Esperanto',
										'shi'         => 'Tachelhit',
										'kok'         => 'Konkani',
										'fr_CF'       => 'French (Central African Republic)',
										'fr_RE'       => 'French (Réunion)',
										'mas'         => 'Masai',
										'rof'         => 'Rombo',
										'ru_UA'       => 'Russian (Ukraine)',
										'yo_NG'       => 'Yoruba (Nigeria)',
										'dav_KE'      => 'Taita (Kenya)',
										'gv_GB'       => 'Manx (United Kingdom)',
										'pa_Arab'     => 'Punjabi (Arabic)',
										'es'          => 'Spanish',
										'teo_UG'      => 'Teso (Uganda)',
										'ps'          => 'Pashto',
										'es_PR'       => 'Spanish (Puerto Rico)',
										'fr_MF'       => 'French (Saint Martin)',
										'et'          => 'Estonian',
										'pt'          => 'Portuguese',
										'eu'          => 'Basque',
										'ka'          => 'Georgian',
										'rwk_TZ'      => 'Rwa (Tanzania)',
										'nb_NO'       => 'Norwegian Bokmål (Norway)',
										'fr_CG'       => 'French (Congo - Brazzaville)',
										'cgg'         => 'Chiga',
										'zh_Hant_TW'  => 'Chinese (Traditional Han, Taiwan)',
										'sr_Cyrl_ME'  => 'Serbian (Cyrillic, Montenegro)',
										'lag'         => 'Langi',
										'ses_ML'      => 'Koyraboro Senni (Mali)',
										'en_ZW'       => 'English (Zimbabwe)',
										'ak_GH'       => 'Akan (Ghana)',
										'vi_VN'       => 'Vietnamese (Vietnam)',
										'sv_FI'       => 'Swedish (Finland)',
										'to_TO'       => 'Tonga (Tonga)',
										'fr_MG'       => 'French (Madagascar)',
										'fr_GA'       => 'French (Gabon)',
										'fr_CH'       => 'French (Switzerland)',
										'de_CH'       => 'German (Switzerland)',
										'es_US'       => 'Spanish (United States)',
										'ki'          => 'Kikuyu',
										'my_MM'       => 'Burmese (Myanmar [Burma])',
										'vi'          => 'Vietnamese',
										'ar_QA'       => 'Arabic (Qatar)',
										'ga_IE'       => 'Irish (Ireland)',
										'rwk'         => 'Rwa',
										'bez'         => 'Bena',
										'ee_GH'       => 'Ewe (Ghana)',
										'kk'          => 'Kazakh',
										'as_IN'       => 'Assamese (India)',
										'ca_ES'       => 'Catalan (Spain)',
										'kl'          => 'Kalaallisut',
										'fr_SN'       => 'French (Senegal)',
										'ne_IN'       => 'Nepali (India)',
										'km'          => 'Khmer',
										'ms_BN'       => 'Malay (Brunei)',
										'ar_LB'       => 'Arabic (Lebanon)',
										'ta_LK'       => 'Tamil (Sri Lanka)',
										'kn'          => 'Kannada',
										'ur_IN'       => 'Urdu (India)',
										'fr_CI'       => 'French (Côte d’Ivoire)',
										'ko'          => 'Korean',
										'ha_Latn_NG'  => 'Hausa (Latin, Nigeria)',
										'sg_CF'       => 'Sango (Central African Republic)',
										'om_ET'       => 'Oromo (Ethiopia)',
										'zh_Hant_MO'  => 'Chinese (Traditional Han, Macau SAR China)',
										'uk_UA'       => 'Ukrainian (Ukraine)',
										'fa'          => 'Persian',
										'mt_MT'       => 'Maltese (Malta)',
										'ki_KE'       => 'Kikuyu (Kenya)',
										'luy_KE'      => 'Luyia (Kenya)',
										'kw'          => 'Cornish',
										'pa_Guru_IN'  => 'Punjabi (Gurmukhi, India)',
										'en_IN'       => 'English (India)',
										'kab'         => 'Kabyle',
										'ar_IQ'       => 'Arabic (Iraq)',
										'ff'          => 'Fulah',
										'en_TT'       => 'English (Trinidad and Tobago)',
										'bez_TZ'      => 'Bena (Tanzania)',
										'es_NI'       => 'Spanish (Nicaragua)',
										'uz_Arab'     => 'Uzbek (Arabic)',
										'ne_NP'       => 'Nepali (Nepal)',
										'fi'          => 'Finnish',
										'khq'         => 'Koyra Chiini',
										'gsw'         => 'Swiss German',
										'zh_Hans_MO'  => 'Chinese (Simplified Han, Macau SAR China)',
										'en_MH'       => 'English (Marshall Islands)',
										'hu_HU'       => 'Hungarian (Hungary)',
										'en_GB'       => 'English (United Kingdom)',
										'fr_BE'       => 'French (Belgium)',
										'de_BE'       => 'German (Belgium)',
										'saq'         => 'Samburu',
										'be_BY'       => 'Belarusian (Belarus)',
										'sl_SI'       => 'Slovenian (Slovenia)',
										'sr_Latn_RS'  => 'Serbian (Latin, Serbia)',
										'fo'          => 'Faroese',
										'fr'          => 'French',
										'xog'         => 'Soga',
										'fr_BF'       => 'French (Burkina Faso)',
										'tzm'         => 'Central Morocco Tamazight',
										'sk_SK'       => 'Slovak (Slovakia)',
										'fr_ML'       => 'French (Mali)',
										'he_IL'       => 'Hebrew (Israel)',
										'ha_Latn_NE'  => 'Hausa (Latin, Niger)',
										'ru_RU'       => 'Russian (Russia)',
										'fr_CM'       => 'French (Cameroon)',
										'teo_KE'      => 'Teso (Kenya)',
										'seh_MZ'      => 'Sena (Mozambique)',
										'kl_GL'       => 'Kalaallisut (Greenland)',
										'fi_FI'       => 'Finnish (Finland)',
										'kam'         => 'Kamba',
										'es_ES'       => 'Spanish (Spain)',
										'af'          => 'Afrikaans',
										'asa_TZ'      => 'Asu (Tanzania)',
										'cs_CZ'       => 'Czech (Czech Republic)',
										'tr_TR'       => 'Turkish (Turkey)',
										'es_PY'       => 'Spanish (Paraguay)',
										'tzm_Latn_MA' => 'Central Morocco Tamazight (Latin, Morocco)',
										'lg'          => 'Ganda',
										'ebu'         => 'Embu',
										'en_HK'       => 'English (Hong Kong SAR China)',
										'nl_NL'       => 'Dutch (Netherlands)',
										'en_BE'       => 'English (Belgium)',
										'ms_MY'       => 'Malay (Malaysia)',
										'es_UY'       => 'Spanish (Uruguay)',
										'ar_BH'       => 'Arabic (Bahrain)',
										'kw_GB'       => 'Cornish (United Kingdom)',
										'ak'          => 'Akan',
										'chr'         => 'Cherokee',
										'dav'         => 'Taita',
										'lag_TZ'      => 'Langi (Tanzania)',
										'am'          => 'Amharic',
										'so_DJ'       => 'Somali (Djibouti)',
										'shi_Tfng_MA' => 'Tachelhit (Tifinagh, Morocco)',
										'sr_Latn_ME'  => 'Serbian (Latin, Montenegro)',
										'sn_ZW'       => 'Shona (Zimbabwe)',
										'or_IN'       => 'Oriya (India)',
										'ar'          => 'Arabic',
										'as'          => 'Assamese',
										'fr_BI'       => 'French (Burundi)',
										'jmc_TZ'      => 'Machame (Tanzania)',
										'chr_US'      => 'Cherokee (United States)',
										'eu_ES'       => 'Basque (Spain)',
										'saq_KE'      => 'Samburu (Kenya)',
										'vun'         => 'Vunjo',
										'lt'          => 'Lithuanian',
										'naq_NA'      => 'Nama (Namibia)',
										'ga'          => 'Irish',
										'af_NA'       => 'Afrikaans (Namibia)',
										'kea_CV'      => 'Kabuverdianu (Cape Verde)',
										'es_DO'       => 'Spanish (Dominican Republic)',
										'lv'          => 'Latvian',
										'kok_IN'      => 'Konkani (India)',
										'de_LI'       => 'German (Liechtenstein)',
										'fr_BJ'       => 'French (Benin)',
										'az'          => 'Azerbaijani',
										'guz_KE'      => 'Gusii (Kenya)',
										'rw_RW'       => 'Kinyarwanda (Rwanda)',
										'mg_MG'       => 'Malagasy (Madagascar)',
										'km_KH'       => 'Khmer (Cambodia)',
										'gl'          => 'Galician',
										'shi_Tfng'    => 'Tachelhit (Tifinagh)',
										'ar_AE'       => 'Arabic (United Arab Emirates)',
										'fr_MQ'       => 'French (Martinique)',
										'rm'          => 'Romansh',
										'sv_SE'       => 'Swedish (Sweden)',
										'az_Cyrl'     => 'Azerbaijani (Cyrillic)',
										'ro'          => 'Romanian',
										'so_ET'       => 'Somali (Ethiopia)',
										'en_ZA'       => 'English (South Africa)',
										'ii_CN'       => 'Sichuan Yi (China)',
										'fr_BL'       => 'French (Saint Barthélemy)',
										'hi_IN'       => 'Hindi (India)',
										'gu'          => 'Gujarati',
										'mer_KE'      => 'Meru (Kenya)',
										'nn_NO'       => 'Norwegian Nynorsk (Norway)',
										'gv'          => 'Manx',
										'ru'          => 'Russian',
										'ar_DZ'       => 'Arabic (Algeria)',
										'ar_SY'       => 'Arabic (Syria)',
										'en_MP'       => 'English (Northern Mariana Islands)',
										'nl_BE'       => 'Dutch (Belgium)',
										'rw'          => 'Kinyarwanda',
										'be'          => 'Belarusian',
										'en_VI'       => 'English (U.S. Virgin Islands)',
										'es_CL'       => 'Spanish (Chile)',
										'bg'          => 'Bulgarian',
										'mg'          => 'Malagasy',
										'hy_AM'       => 'Armenian (Armenia)',
										'zu_ZA'       => 'Zulu (South Africa)',
										'guz'         => 'Gusii',
										'mk'          => 'Macedonian',
										'es_VE'       => 'Spanish (Venezuela)',
										'ml'          => 'Malayalam',
										'bm'          => 'Bambara',
										'khq_ML'      => 'Koyra Chiini (Mali)',
										'bn'          => 'Bengali',
										'ps_AF'       => 'Pashto (Afghanistan)',
										'so_SO'       => 'Somali (Somalia)',
										'sr_Cyrl'     => 'Serbian (Cyrillic)',
										'pl_PL'       => 'Polish (Poland)',
										'fr_GN'       => 'French (Guinea)',
										'bo'          => 'Tibetan',
										'om_KE'       => 'Oromo (Kenya)',
									),
									'default'     => 'en_US'
								),
							),
						),

						// Start Alt Text Image
						array(
							'id'     => 'bdaiatg_alt_text_image_wrapper',
							'type'   => 'fieldset',
							'title'  => esc_html__('Image captions, titles, and descriptions', 'ai-image-alt-text-generator-for-wp'),
							'fields' => array(
								array(
									'id'         => 'bdaiatg_alt_text_image_title',
									'type'       => 'checkbox',
									'options'    => array(
										'update_title' => esc_html__('Set the image title with the generated alt text', 'ai-image-alt-text-generator-for-wp'),
									),
								),
								array(
									'id'         => 'bdaiatg_alt_text_image_caption',
									'type'       => 'checkbox',
									'options'    => array(
										'update_caption' => esc_html__('Set the image caption with the generated alt text.', 'ai-image-alt-text-generator-for-wp'),
									),
								),
								array(
									'id'         => 'bdaiatg_alt_text_image_description',
									'type'       => 'checkbox',
									'options'    => array(
										'update_description' => esc_html__('Set the image description with the generated alt text.', 'ai-image-alt-text-generator-for-wp'),
									),
								),
								array(
									'id'    => 'bdaiatg_alt_text_prefix',
									'type'  => 'text',
									'before'   => esc_html__('Add Prefix keyword to every generated alt-text', 'ai-image-alt-text-generator-for-wp'),
								),
								array(
									'id'    => 'bdaiatg_alt_text_suffix',
									'type'  => 'text',
									'before'   => esc_html__('Add Suffix keyword to every generated alt-text', 'ai-image-alt-text-generator-for-wp'),
								),
							),
						),

						// Start Alt Text Image Generator
						array(
							'id'     => 'bdaiatg_alt_text_image_generator',
							'type'   => 'fieldset',
							'title'  => 'Automatic Alt Text Generation',
							'fields' => array(
								array(
									'id'         => 'bdaiatg_alt_text_image_generator_enable',
									'type'       => 'checkbox',
									'after'      => esc_html__('Note: It is ays possible to generate alt text using the Bulk Generate page or the pdate Alt Text button on an individual image.', 'ai-image-alt-text-generator-for-wp'),
									'options'    => array(
										'enable' => esc_html__('Create alt text automatically when new images are uploaded.', 'ai-image-alt-text-generator-for-wp'),
									),
								),
							),
						),

						array(
							'id'          => 'bdaiatg_alt_text_length',
							'type'        => 'select',
							'title'       => esc_html__('Select the length of the alt text', 'ai-image-alt-text-generator-for-wp'),
							'options'     => array(
							  '60'  => esc_html__('Short', 'ai-image-alt-text-generator-for-wp'),
							  '120' => esc_html__('Medium', 'ai-image-alt-text-generator-for-wp'),
							  '160' => esc_html__('Long', 'ai-image-alt-text-generator-for-wp')
							),
						),

						array(
							'id'      => 'bdaiatg_alt_description',
							'type'    => 'checkbox',
							'title'   => esc_html__('Enable Description', 'ai-image-alt-text-generator-for-wp'),
                            'after' => esc_html__('Note: An additional 1 credit will be deducted for each image. (Available only for Gutenberg Post, Block, and Media Editor)', 'ai-image-alt-text-generator-for-wp'),
                            'label'   => esc_html__('Enable', 'ai-image-alt-text-generator-for-wp'),
							'default' => false,
							'dependency' => array(
								'bdaiatg_alt_text_image_wrapper|bdaiatg_alt_text_image_description', '==', 'false'
							),
						),
						// Start Alt Text Image Types
						array(
							'id'     => 'bdaiatg_alt_text_image_types_wrapper',
							'type'   => 'fieldset',
							'title'  => 'Types of images for Automatic Alt text generation:',
							'fields' => array(
								array(
									'id'         => 'bdaiatg_alt_text_image_types',
									'type'       => 'text',
									'before'      => esc_html__('Make sure that multiple extensions are separated by commas. Example: jpg,webp,png', 'ai-image-alt-text-generator-for-wp'),
									'after'      => esc_html__('The following file extensions are the only ones that will generate alt text automatically when uploading. if you want all types of images to be generated then leave it blank:', 'ai-image-alt-text-generator-for-wp'),
								),
							),
						),
					)
				));
			}

			protected function bulk_generate() {
				ob_start();
				?>
                <div class="bdaiatg_bulk_generate_wrapper">
					<div class="bdaiatg_bulk_generate_result">
						<h2>Bulk Generate Alt Text</h2>
						<div class="bdaiatg_bulk_generate_result_outputs">
							<div class="bdaiatg_bulk_generate_result_outputs_item">
								<div class="bdaiatg_bulk_generate_result_outputs_item_text">Total Images</div>
								<span><?php echo esc_html( BDAIATG_Boomdevs_Ai_Image_Alt_Text_Generator_Settings::$all_images); ?></span>
							</div>
							<div class="bdaiatg_bulk_generate_result_outputs_item">
								<div class="bdaiatg_bulk_generate_result_outputs_item_text">Images Missing Alt Text</div>
								<span><?php echo esc_html( BDAIATG_Boomdevs_Ai_Image_Alt_Text_Generator_Settings::$missing_alt_text_count); ?></span>
							</div>
						</div>
					</div>

					<div class="bdaiatg_bulk_generate_available_credits">
						<p>Available credits: 25 ( <a href="#">Get more credits</a> )</p>
					</div>

					<div class="bdaiatg_bulk_generate_keywords">
						<h2>Keywords</h2>
						<div class="bdaiatg_bulk_generate_keywords_options">
							<div class="bdaiatg_bulk_generate_keywords_options_item">
								<label for="keywords">
									<span>[optional] SEO Keywords</span>
									<span>(try to include these in the generated alt text)</span>
								</label>
								<div>
									<input type="text" size="60" maxlength="512" name="keywords" id="bulk-generate-keywords">
								</div>
							</div>
							<div class="bdaiatg_bulk_generate_keywords_options_item">
								<label for="negative-keywords">
									<span>[optional] Negative keywords</span>
									<span>(do not include these in the generated alt text)</span>
								</label>
								<div>
									<input type="text" size="60" maxlength="512" name="negative-keywords" id="bulk-generate-negative-keywords">
								</div>
							</div>
						</div>
					</div>

					<div class="bdaiatg_bulk_generate_options_select">
						<div class="bdaiatg_bulk_generate_options_select_item">
							<input type="checkbox" id="bdaiatg_bulk_generate_all">
							<label for="bdaiatg_bulk_generate_all">Include images that already have alt text (overwrite existing alt text).</label>
						</div>
					</div>
				</div>
				<?php
				return ob_get_clean();
			}

			protected function sync_library() {
				ob_start();
				?>
                <div class="bdaiatg_sync_library">
                    <div class="bdaiatg_sync_upcoming_features">
                        <img src="<?php echo esc_url( self::$plugin_file_url . 'admin/img/coming-soon-poster.jpg'); ?>" alt="upcoming-img">
                    </div>
                </div>
				<?php
				return ob_get_clean();
			}

			public function get_media_images_and_alt_text() {
				if(!current_user_can('manage_options')) {
					wp_send_json_error(array(
						'message' => 'Permission denied!',
					));
					return false;
				}
				
                $total_images_count = 0;
                $missing_alt_text_count = 0;

                $args = array(
                    'post_type'      => 'attachment',
                    'post_status'    => 'inherit',
                    'posts_per_page' => -1,
                    'post_mime_type' => 'image',
                );

                $attachments = get_posts($args);

                foreach ($attachments as $attachment) {
                    $total_images_count++;
                    $alt_text = get_post_meta($attachment->ID, '_wp_attachment_image_alt', true);

                    if (empty($alt_text)) {
                        $missing_alt_text_count++;
                    }
                }

                $data = array(
                    'all_images' => $total_images_count,
                    'missing_alt_text_count' => $missing_alt_text_count,
                );

				BDAIATG_Boomdevs_Ai_Image_Alt_Text_Generator_Settings::$all_images = $data['all_images'];
				BDAIATG_Boomdevs_Ai_Image_Alt_Text_Generator_Settings::$missing_alt_text_count = $data['missing_alt_text_count'];
			}

			/**
			 * Retrieve all settings for the WP Swiss Toolkit plugin.
			 *
			 * @return array|string Plugin settings values.
			 */
			public static function get_settings()
			{
				// Retrieve and return the plugin settings using the prefix defined in Boomdevs_Swiss_Toolkit_Setting::$prefix
				return get_option(BDAIATG_Boomdevs_Ai_Image_Alt_Text_Generator_Settings::$prefix);
			}
		}
	}
