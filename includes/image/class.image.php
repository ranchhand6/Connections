<?php

/**
 * Uses WordPress's Image Editor Classes to crop/resize and/or filter images.
 *
 * @package     Connections
 * @subpackage  Image
 * @copyright   Copyright (c) 2014, Steven A. Zahm
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       8.1
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class cnImage {

	/**
	 * Stores the instance of this class.
	 *
	 * @access private
	 * @since 8.1
	 * @var (object)
	*/
	private static $instance;

	/**
	 * A dummy constructor to prevent the class from being loaded more than once.
	 *
	 * @access public
	 * @since 8.1
	 * @see cnImage::getInstance()
	 * @see cnImage();
	 */
	public function __construct() { /* Do nothing here */ }

	/**
	 * Setup the class.
	 *
	 * @access public
	 * @since 8.1
	 */
	public static function init() {

		if ( ! isset( self::$instance ) ) {

			// Set priority 11 so we know cnMessage has been init'd.
			add_action( 'admin_init', array( __CLASS__, 'checkEditorSupport' ), 11 );
			add_action( 'parse_query', array( __CLASS__, 'query'), -1 );
		}

	}

	public static function checkEditorSupport() {

		if ( ! is_admin() ) {

			return;
		}

		$atts = array( 'mime_type' => 'image/jpeg' );

		if ( wp_image_editor_supports( $atts ) !== TRUE ) {

			cnMessage::create( 'error', 'image_edit_support_failed' );
		}

		return TRUE;
	}

	public static function imageEditors( $editors ) {

		// Require the WP core Image Editor class if it has not been loaded.
		if ( ! class_exists('WP_Image_Editor') ) {

			require_once ABSPATH . WPINC . '/class-wp-image-editor.php';
		}

		// Require the WP core GD Image Editor class if it has not been loaded.
		if ( ! class_exists('WP_Image_Editor_GD') ) {

			require_once ABSPATH . WPINC . '/class-wp-image-editor-gd.php';
		}

		// Include the Connections core GD Image Editor class if it has not been loaded.
		if ( ! class_exists('CN_Image_Editor_GD') ) {

			include_once 'editors/class.gd.php';
		}

		// Require the WP core Imagick Image Editor class if it has not been loaded.
		if ( ! class_exists('WP_Image_Editor_GD') ) {

			require_once ABSPATH . WPINC . '/class-wp-image-editor-imagick.php';
		}

		// Include the Connections core Imagick Image Editor class if it has not been loaded.
		if ( ! class_exists('CN_Image_Editor_Imagick') ) {

			include_once 'editors/class.imagick.php';
		}

		// Require the Gmagick Image Editor class if it has not been loaded.
		if ( ! class_exists('WP_Image_Editor_Gmagick') ) {

			require_once 'editors/class-wp-image-editor-gmagick.php';
		}

		// Include the Connections core Gmagick Image Editor class if it has not been loaded.
		if ( ! class_exists('CN_Image_Editor_Gmagick') ) {

			include_once 'editors/class.gmagick.php';
		}

		/*
		 * Add the editor in order of least priority.
		 * WP will choose the first editor that contains the all required class methods to process the image.
		 * Least priority, not listed here are 'WP_Image_Editor_Imagick' and 'WP_Image_Editor_GD' the WP core editors.
		 */

		if ( ! in_array( 'WP_Image_Editor_Gmagick', $editors ) ) {

			array_unshift( $editors, 'WP_Image_Editor_Gmagick' );
		}

		if ( ! in_array( 'CN_Image_Editor_GD', $editors ) && class_exists('CN_Image_Editor_GD') ) {

			array_unshift( $editors, 'CN_Image_Editor_GD' );
		}

		if ( ! in_array( 'CN_Image_Editor_Imagick', $editors ) && class_exists('CN_Image_Editor_Imagick') ) {

			array_unshift( $editors, 'CN_Image_Editor_Imagick' );
		}

		if ( ! in_array( 'CN_Image_Editor_Gmagick', $editors ) && class_exists('CN_Image_Editor_Gmagick') ) {

			array_unshift( $editors, 'CN_Image_Editor_Gmagick' );
		}

		return $editors;
	}

	/**
	 * Parses a TimThumb compatible URL via the CN_IMAGE_ENDPOINT root rewrite endpoint
	 * and stream the image to the browser with the correct headers for the image type being served.
	 *
	 * @access private
	 * @since  8.1
	 * @static
	 * @uses   get_query_var()
	 * @uses   path_is_absolute()
	 * @uses   cnColor::rgb2hex2rgb()
	 * @uses   self::get()
	 * @uses   is_wp_error[]
	 *
	 * @return stream Streams an image resoure to the browser or a error message.
	 */
	public static function query() {
		global $wpdb;

		if ( get_query_var( CN_IMAGE_ENDPOINT ) ) {

			if ( path_is_absolute( get_query_var( 'src' ) ) ) {

				header ( $_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
				echo '<h1>ERROR/s:</h1><ul><li>Source is file path. Source must be a local file URL.</li></ul>';

				exit();
			}

			$atts = array();

			if ( get_query_var( 'cn-entry-slug' ) ) {

				$sql = $wpdb->prepare( 'SELECT slug FROM ' . CN_ENTRY_TABLE . ' WHERE slug=%s', get_query_var( 'cn-entry-slug' ) );

				$result = $wpdb->get_var( $sql );

				if ( is_null( $result ) ) {

					header ( $_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
					echo '<h1>ERROR/s:</h1><ul><li>Cheating?</li></ul>';

					exit();

				} else {

					$atts['sub_dir'] = $result;
				}

			}

			if ( get_query_var( 'w' ) ) $atts['width'] = get_query_var( 'w' );
			if ( get_query_var( 'h' ) ) $atts['height'] = get_query_var( 'h' );

			if ( get_query_var( 'zc' ) || get_query_var( 'zc' ) === '0' ) $atts['crop_mode'] = get_query_var( 'zc' );

			if ( get_query_var( 'a' ) ) {

				$atts['crop_focus'] = array( 'center', 'center' );

				if ( strpos( get_query_var( 'a' ), 't' ) !== FALSE ) {
					$atts['crop_focus'][1] = 'top';
				}

				if ( strpos( get_query_var( 'a' ), 'r' ) !== FALSE ) {
					$atts['crop_focus'][0] = 'right';
				}

				if ( strpos( get_query_var( 'a' ), 'b' ) !== FALSE ) {
					$atts['crop_focus'][1] = 'bottom';
				}

				if ( strpos( get_query_var( 'a' ), 'l' ) !== FALSE ) {
					$atts['crop_focus'][0] = 'left';
				}

				$atts['crop_focus'] = implode( ',', $atts['crop_focus'] );
			}

			if ( get_query_var( 'f' ) ) {

				$filters = explode ( '|', get_query_var( 'f' ) );

				foreach ( $filters as $filter ) {

					$param = explode( ',', $filter );

					for ( $i = 0; $i < 4; $i ++ ) {

						if ( ! isset( $param[ $i ] ) ) {

							$param[ $i ] = NULL;

						} else {

							$param[ $i ] = $param[ $i ];
						}
					}

					switch ( $param[0] ) {

						case '1':
							$atts['negate'] = TRUE;
							break;

						case '2':
							$atts['grayscale'] = TRUE;
							break;

						case '3':
							$atts['brightness'] = $param[1];
							break;

						case '4':
							$atts['contrast'] = $param[1];
							break;

						case '5':
							$atts['colorize'] = cnColor::rgb2hex2rgb( $param[1] . ',' . $param[2] . ',' . $param[3] );
							break;

						case '6':
							$atts['detect_edges'] = TRUE;
							break;

						case '7':
							$atts['emboss'] = TRUE;
							break;

						case '8':
							$atts['gaussian_blur'] = TRUE;
							break;

						case '9':
							$atts['blur'] = TRUE;
							break;

						case '10':
							$atts['sketchy'] = TRUE;
							break;

						case '11':
							$atts['smooth'] = $param[1];
					}
				}
			}

			if ( get_query_var( 's' ) && get_query_var( 's' ) === '1' ) $atts['sharpen'] = TRUE;

			if ( get_query_var( 'o' ) ) $atts['opacity'] = get_query_var( 'o' );

			if ( get_query_var( 'q' ) ) $atts['quality'] = get_query_var( 'q' );
			if ( get_query_var( 'cc' ) ) $atts['canvas_color'] = get_query_var( 'cc' );

			// This needs to be set after the `cc` query var because it should override any value set using the `cc` query var, just like TimThumb.
			if ( get_query_var( 'ct' ) && get_query_var( 'ct' ) === '1' ) $atts['canvas_color'] = 'transparent';

			// Process the image.
			$image = self::get( get_query_var( 'src' ), $atts, 'editor' );

			// If there been an error
			if ( is_wp_error( $image ) ) {

				$errors =  implode( '</li><li>', $image->get_error_messages() );

				// Display the error messages.
				header ( $_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
				echo '<h1>ERROR/s:</h1><ul><li>' . wp_kses_post( $errors ) . '</li></ul>';

				exit();
			}

			$image->stream();

			exit();

		}

	}

	/**
	 * Uses WP's Image Editor Class to crop and resize images.
	 *
	 * Derived from bfithumb.
	 * @url https://github.com/bfintal/bfi_thumb
	 * Incorporated crop only from Github pull request 13
	 * @url https://github.com/bfintal/bfi_thumb/pull/13
	 * Also incorporates positional cropping and image qaulity change from the Dominic Whittle bfithumb fork:
	 * @url https://github.com/dominicwhittle/bfi_thumb
	 * Crop mode support was inspired by TimThumb.
	 * @url http://www.binarymoon.co.uk/projects/timthumb/
	 *
	 * bfiThumb was inspired by Aqua Resizer
	 * @url https://github.com/syamilmj/Aqua-Resizer
	 *
	 * @todo  Should an option be added to control the order filters should be applied be added? Filter order can affect result...
	 *
	 * Accepted option for the $atts property are:
	 * 	width (int|string) Width in pixels or percentage. If using percentage, the percentage symbol must be included, example `50%`.
	 * 	height (int|string) Height in pixels or percentage. If using percentage, the percentage symbol must be included, example `50%`.
	 *
	 * 	negate (bool) Whether or not to apply the negate filter. Default: FALSE
	 * 	grayscale (bool) Whether or not to apply the grayscale filter. Default: FALSE
	 * 	brightness (int) Adjust the image brightness. Valid range is -255–255 (-255 = min brightness, 0 = no change, +255 = max brightness). Default: 0
	 * 	colorize (string) Colorize the image. Either a valid hex-color #000000–#ffffff or a HTML named color like `red` can be supplied. Default: NULL
	 * 		@see  cnColor::$colors for a list of valid named colors.
	 * 	contrast (int) Ajust the image contrast. Valid range is -100–100 (-100 = max contrast, 0 = no change, +100 = min contrast [note the direction]) Default: 0
	 * 	detect_edges (bool) Whether of not to apply the detect edges filter. Default: FALSE
	 * 	emboss (bool) Whether or not to apply the emboss filter. Default: FALSE
	 * 	gassian_blur (bool) Whether of not to apply a gaussian blur. Default: FALSE
	 * 	blur (bool) Whether or not to apply the blur filter. Default: FALSE
	 * 	sketchy (bool) Whether or not to apply the the skethy filter. Default: FALSE
	 * 	sharpen (bool) Whether or not to apply the sharpen filter. Default: FALSE
	 * 	smooth (int) Apply the smooth filter. Valid range is -100–100 (-100 = max smooth, 100 = min smooth). Default: NULL
	 * 	opacity (int) Set the image opacity. Valid range is 0–100 (0 = fully transparent, 100 = fully opaque). Default: 100
	 *
	 * 	crop_mode (int) Which crop mode to utilitize when rescaling the image. Valid range is 0–3. Default: 1
	 * 		0 == Resize to Fit specified dimensions with no cropping. Aspect ratio will not be maintained.
	 * 		1 == Crop and resize to best fit dimensions maintaining aspect ration. Default.
	 * 		2 == Resize proportionally to fit entire image into specified dimensions, and add margins if required.
	 * 			Use the canvas_color option to set the color to be used when adding margins.
	 * 		3 == Resize proportionally adjusting size of scaled image so there are no margins added.
	 * 	crop_focus (array|string) The crop focus/positional cropping is used to determine the center origin of a crop when crop_mode is set 1.
	 * 		Valid range is (float) 0.0–1.0
	 * 		Default: array( .5, .5)
	 * 		Text options are also supported:
	 * 			'left,top' | array( 'left', 'top' ) == array( 0, 0 )
	 * 			'center,top' | array( 'center', 'top' ) == array( .5, 0 )
	 * 			'right,top' | array( 'right', 'top' ) == array( 1, 0 )
	 * 			'left,center' | array( 'left', 'center' )  == array( 0, .5 )
	 * 			'center,center' | array( 'center', 'center' ) == array( .5, .5) [the default crop focus].
	 * 			'right,center' | array( 'right', 'center' ) == array( 1, .5 )
	 * 			'left,bottom' | array( 'left', 'bottom' ) == array( 0, 1 )
	 * 			'center,bottom' | array( 'center', 'bottom' ) == array( .5, 1 )
	 * 			'right,bottom' | array( 'right', 'bottom' ) == array( 1, 1 )
	 *
	 * 	crop_only (bool) Whether or not to just crop the image.
	 * 		If set to TRUE, crop_x, crop_y, crop_width, crop_height must be supplied.
	 * 		This overrides crop_mode.
	 * 		Default: FALSE
	 * 	crop_x (int|string) The x-axis crop origin start in pixels or percentage. If using percentage, the percentage symbol must be included, example `50%`.
	 * 	crop_y (int|string) The y-axis crop origin start in pixels or percentage. If using percentage, the percentage symbol must be included, example `50%`.
	 * 	crop_width (int|string) The resize width of the crop in pixels or percentage. If using percentage, the percentage symbol must be included, example `50%`.
	 * 		The width option can be set to determine the final scaled width.
	 * 	crop_height (int|string) The resize height of the crop in pixels or percentage. If using percentage, the percentage symbol must be included, example `50%`.
	 * 		The height option can be set to determine the final scaled height.
	 *
	 * 	canvas_color (string) Either a valid hex-color #000000–#ffffff or a HTML named color like `red` can be supplied or set to 'transparent'.
	 * 		The canvas_color is only used when using crop_mode=2. This will be the color of the margins.
	 * 		Default: #FFFFFF
	 * 		@see  cnColor::$colors for a list of valid named colors.
	 *
	 * 	quality (int) The image quality to be used when saving the image. Valid range is 1–100. Default: 90
	 *
	 * @param  string $source The local image path or URL to process. The image must be in the upload folder or the theme folder.
	 * @param  array  $atts   An associative array containing the options used to process the image.
	 * @param  string $return
	 *
	 * @return mixed  array | object | string | stream
	 *                If $return is `base64` then base64 encoded image data URI will be returned. Suitable for use in CSS or img src attribute.
	 *                If $return is `data` and array of the image meta is returned.
	 *                If $retuen is `editor` an instance if the WP_Image_Editor is returned.
	 *                If $return is `stream` the image resource will be streamed to the browser with the correct headers set.
	 *                If $return is `url` the image URL will be returned. [Default]
	 */
	public static function get( $source, $atts = array(), $return = 'url' ) {
		global $wp_filter;

		$filter  = array();
		$methods = array();
		$log     = new cnLog();

		/*
		 * Temporarily store the filters hooked to the image editor filters.
		 */
		$filter['editors'] = isset( $wp_filter['wp_image_editors'] ) ? $wp_filter['wp_image_editors'] : '';
		$filter['resize']  = isset( $wp_filter['image_resize_dimensions'] ) ? $wp_filter['image_resize_dimensions'] : '';

		/*
		 * Remove all filters hooked into the the image editor filters to prevent conlicts.
		 */
		remove_all_filters( 'wp_image_editors' );
		remove_all_filters( 'image_resize_dimensions' );

		/*
		 * Start an instance of the logger if WP_DEBUG is defind and TRUE.
		 */
		$log->add( 'image_process_start', __( 'Image processing started.', 'connections' ) );

		/*
		 * Use the cnImage editors.
		 */
		add_filter( 'wp_image_editors', array( __CLASS__, 'imageEditors' ) );


		/*
		 * Do not use the default resizer since we want to allow resizing to larger sizes than the original image.
		 * Parts are borrowed from media.php file in WordPress core.
		 */
		add_filter( 'image_resize_dimensions', array( __CLASS__, 'resize_dimensions' ), 10, 6 );

		$defaults = array(
			'width'         => 0,
			'height'        => 0,
			'negate'        => FALSE,
			'grayscale'     => FALSE,
			'brightness'    => 0,
			'colorize'      => NULL,
			'contrast'      => 0,
			'detect_edges'  => FALSE,
			'emboss'        => FALSE,
			'gaussian_blur' => FALSE,
			'blur'          => FALSE,
			'sketchy'       => FALSE,
			'sharpen'       => FALSE,
			'smooth'        => NULL,
			'opacity'       => 100,
			'crop_mode'     => 1,
			'crop_focus'    => array( .5, .5 ),
			'crop_only'     => FALSE,
			'canvas_color'  => '#FFFFFF',
			'quality'       => 90,
			'sub_dir'       => '',
		);

		$atts = wp_parse_args( $atts, $defaults );

		extract( $atts );

		/*
		 * --> START <-- Sanitize/Validate $atts values.
		 */

		if ( path_is_absolute( $source ) ) {

			$log->add( 'image_path', __( sprintf( 'Supplied Source Path: %s', $source ), 'connections' ) );

		} else {

			$source = esc_url( $source );
			$log->add( 'image_url', __( sprintf( 'Supplied Source URL: %s', $source ), 'connections' ) );
		}

		if ( empty( $source ) ) {

			return new WP_Error( 'no_path_or_url_provided', __( 'No image path or URL supplied.', 'connections' ), $source );
		}

		if ( ! is_bool( $negate ) ) $negate = FALSE;
		if ( ! is_bool( $grayscale ) ) $grayscale = FALSE;

		if ( ! is_numeric( $brightness ) || empty( $brightness ) ) unset( $brightness );
		if ( ! is_numeric( $contrast ) || empty( $contrast ) ) unset( $contrast );

		if ( ! is_null( $colorize ) ) {

			// If $colorize is a HEX color, ensure it is hashed.
			$colorize = cnFormatting::maybeHashHEXColor( $colorize );

			// Check to see if $colorize is a valid HEX color.
			if ( ! cnSanitize::hexColor( $colorize ) ) {

				// Since $colorize is not a valid HEX color, check to see if it is a named color.
				$colorize = cnColor::name2hex( $colorize );

				// If $colorize is not a named color, unset it.
				if ( $colorize === FALSE ) {

					unset( $colorize );
				}
			}

		} else {

			unset( $colorize );
		}

		if ( ! is_bool( $detect_edges ) ) $detect_edges = FALSE;
		if ( ! is_bool( $emboss ) ) $emboss = FALSE;
		if ( ! is_bool( $gaussian_blur ) ) $gaussian_blur = FALSE;
		if ( ! is_bool( $blur ) ) $blur = FALSE;
		if ( ! is_bool( $sketchy ) ) $sketchy = FALSE;
		if ( ! is_bool( $sharpen ) ) $sharpen = FALSE;
		if ( ! is_numeric( $smooth ) || is_null( $smooth ) ) unset( $smooth );

		// Ensure valid value for opacity.
		if ( ! is_numeric( $opacity ) ) $opacity = 100;

		// Ensure valid value for crop mode.
		if ( filter_var( (int) $crop_mode, FILTER_VALIDATE_INT, array( 'options' => array( 'min_range' => 0, 'max_range' => 3 ) ) ) === FALSE ) {

			$crop_mode = 1;
		}

		$log->add( 'image_crop_mode' , __( sprintf( 'Crop Mode: %d' , $crop_mode ), 'connections' ) );

		// Crop can be defined as either an array or string, sanitized/validate both.
		if ( is_array( $crop_focus ) || is_string( $crop_focus ) ) {

			// If $crop_focus is a string, lets check if is a positional crop and convert to an array.
			if ( is_string( $crop_focus ) && stripos(  $crop_focus, ',' ) !== FALSE ) {

				$crop_focus = explode( ',', $crop_focus );
				array_walk( $crop_focus, 'trim' );
			}

			// Convert the string values to their float equivalents.

			switch ( $crop_focus[0] ) {

				case 'left':
					$crop_focus[0] = 0;
					break;

				case 'right':
					$crop_focus[0] = 1;
					break;

				default:
					$crop_focus[0] = .5;
					break;
			}

			switch ( $crop_focus[1] ) {

				case 'top':
					$crop_focus[1] = 0;
					break;

				case 'bottom':
					$crop_focus[1] = 1;
					break;

				default:
					$crop_focus[1] = .5;
					break;
			}

			// Ensure if an array was supplied, that there are only two keys, if not, set the default positional crop.
			if ( count( $crop_focus ) !== 2 ) {

				$crop_focus = array( .5, .5 );

			// Values must be a float within the range of 0–1, if is not, set the default positional crop.
			} else {

				if ( ( ! $crop_focus[0] >= 0 || ! $crop_focus <= 1 ) && ( filter_var( (float) $crop_focus[0], FILTER_VALIDATE_FLOAT ) === FALSE ) ) $crop_focus[0] = .5;
				if ( ( ! $crop_focus[1] >= 0 || ! $crop_focus <= 1 ) && ( filter_var( (float) $crop_focus[1], FILTER_VALIDATE_FLOAT ) === FALSE ) ) $crop_focus[1] = .5;

			}

			$log->add( 'image_crop_focus', 'Crop Focus: ' . implode( ',', $crop_focus ) );

		} else {

			// If $crop_focus is not an array, it must be a (bool) FALSE and if it is not, set $crop_focus to FALSE.
			$crop_focus = FALSE;
		}

		if ( ! is_bool( $crop_only ) ) $crop_only = FALSE;

		// If $canvas_color is a HEX color, ensure it is hashed.
		$canvas_color = cnFormatting::maybeHashHEXColor( $canvas_color );

		// Check to see if $canvas_color is a valid HEX color.
		if ( ! cnSanitize::hexColor( $canvas_color ) ) {

			// Check to see if it is `transparent`, if not, check if it is a named color.
			if ( strtolower( $canvas_color ) === 'transparent' ) {

				$canvas_color = 'transparent';

			} else {

				// Check to see if it is a named color.
				$canvas_color = cnColor::name2hex( $canvas_color );

				// Not a named color, set the default.
				if ( $canvas_color === FALSE ) {

					$canvas_color = '#FFFFFF';
				}

			}
		}

		// Ensure valid value for $quality. If invalid valid is supplied reset to the defaut of 90, matching WP core.
		if ( filter_var( (int) $quality, FILTER_VALIDATE_INT, array( 'options' => array( 'min_range' => 1, 'max_range' => 100 ) ) ) === FALSE ) {

			$quality = 90;
		}

		/*
		 * --> END <-- Sanitize/Validate $atts values.
		 */

		// Define upload path & dir.
		// $upload_info = wp_upload_dir();
		$upload_dir  = CN_IMAGE_PATH;
		$upload_url  = CN_IMAGE_BASE_URL;
		$theme_url   = get_stylesheet_directory_uri();
		$theme_dir   = get_stylesheet_directory();

		if ( path_is_absolute( $source ) ) {

			// Ensure the supplied path is in either the WP_CONTENT/UPLOADS directory or
			// the STYLESHEETPATH directory.
			if ( strpos( $source, $upload_dir ) !== FALSE ||
				 strpos( $source, $theme_dir ) !== FALSE
				) {

				$img_path = $source;

			} else {

				$img_path = FALSE;
			}

		} else {

			// find the path of the image. Perform 2 checks:
			// #1 check if the image is in the uploads folder
			if ( strpos( $source, $upload_url ) !== FALSE ) {

				$rel_path = str_replace( $upload_url, '', $source );
				$img_path = $upload_dir . $rel_path;

			// #2 check if the image is in the current theme folder
			} else if ( strpos( $source, $theme_url ) !== FALSE ) {

				$rel_path = str_replace( $theme_url, '', $source );
				$img_path = $theme_dir . $rel_path;
			}

		}

		// Fail if we can't find the image in our WP local directory
		if ( empty( $img_path ) || ! @file_exists( $img_path ) ) {

			return new WP_Error( 'image_path_not_found', __( sprintf( 'Image path %s does not exist.', $img_path ), 'connections' ), $img_path );
		}

		// Check if img path exists, and is an image.
		if ( ( $image_info = getimagesize( $img_path ) ) === FALSE ) {

			return new WP_Error( 'image_not_image', __( sprintf( 'The file %s is not an image.', basename( $img_path ) ), 'connections' ), basename( $img_path ) );
		}

		$log->add( 'image_path' , __( sprintf( 'Verified Source Path: %s' , $img_path ), 'connections' ) );

		// This is the filename.
		$basename = basename( $img_path );
		$log->add( 'image_base_filename', 'Original filename: ' . $basename );

		// Path/File info.
		$path = pathinfo( $img_path );
		$ext  = $path['extension'];

		// Image info.
		$orig_w = $image_info[0];
		$orig_h = $image_info[1];
		$orig_mime_type = $image_info['mime'];

		$log->add( 'image_original_info', 'Original width: ' . $orig_w );
		$log->add( 'image_original_info', 'Original height: ' . $orig_h );
		$log->add( 'image_original_info', 'Original mime: ' . $orig_mime_type );

		// Support percentage dimensions. Compute percentage based on the original dimensions.
		if ( is_string( $width ) && ! is_numeric( $width ) ) {

			if ( stripos( $width, '%' ) !== FALSE ) {

				$log->add( 'image_width_percentage', 'Width set as percentage.' );

				$width = (int) ( (float) str_replace( '%', '', $width ) / 100 * $orig_w );

				$log->add( 'image_width', 'Width: ' . $width );
			}

		} else {

			$width = absint( $width );
			$log->add( 'image_width', 'Width: ' . ( $width === 0 ? '0' : $width ) );
		}

		if ( is_string( $height ) && ! is_numeric( $height ) ) {

			if ( stripos( $height, '%') !== FALSE ) {

				$log->add( 'image_height_percentage', 'Height set as percentage.' );

				$height = (int) ( (float) str_replace( '%', '', $height ) / 100 * $orig_h );

				$log->add( 'image_height', 'Height: ' . $height );
			}

		} else {

			$height = absint( $height );
			$log->add( 'image_height', 'Height: ' . ( $height === 0 ? '0' : $height ) );
		}

		// The only purpose of this is to determine the final width and height
		// without performing any actual image manipulation. This will be used
		// to check whether a resize was done previously.
		if ( ( ! empty( $width ) || ! empty( $height ) ) && $crop_only === FALSE ) {

			switch ( $crop_mode ) {

				case 0:

					$dims  = image_resize_dimensions( $orig_w, $orig_h, ( empty( $width ) ? NULL : $width ), ( empty( $height ) ? NULL : $height ), FALSE );
					$dst_w = $dims[4];
					$dst_h = $dims[5];

					break;

				case 1:

					$dims  = image_resize_dimensions( $orig_w, $orig_h, ( empty( $width ) ? NULL : $width ), ( empty( $height ) ? NULL : $height ), ( is_array( $crop_focus ) ? $crop_focus : TRUE ) );
					$dst_w = $dims[4];
					$dst_h = $dims[5];

					break;

				case 2:

					$canvas_w = $width;
					$canvas_h = $height;

					// generate new w/h if not provided
					if ( $width && ! $height ) {

						$height = floor ( $orig_h * ( $width / $orig_w ) );
						$canvas_h = $height;

					} else if ( $height && ! $width ) {

						$width = floor ( $orig_w * ( $height / $orig_h ) );
						$canvas_w = $width;
					}

					$final_height = $orig_h * ($width / $orig_w);

					if ($final_height > $height) {

						$origin_x = $width / 2;
						$width    = $orig_w * ($height / $orig_h);
						$origin_x = round ($origin_x - ($width / 2));

					} else {

						$origin_y = $height / 2;
						$height   = $final_height;
						$origin_y = round ($origin_y - ($height / 2));

					}

					$dst_w = $canvas_w;
					$dst_h = $canvas_h;

					break;

				case 3:

					// generate new w/h if not provided
					if ( $width && ! $height ) {

						$height = floor ( $orig_h * ( $width / $orig_w ) );

					} else if ( $height && ! $width ) {

						$width = floor ( $orig_w * ( $height / $orig_h ) );
					}

					$final_height = $orig_h * ( $width / $orig_w );

					if ( $final_height > $height ) {

						$width = $orig_w * ( $height / $orig_h );

					} else {

						$height = $final_height;
					}

					$dims  = image_resize_dimensions( $orig_w, $orig_h, ( empty( $width ) ? NULL : $width ), ( empty( $height ) ? NULL : $height ), FALSE );
					$dst_w = $dims[4];
					$dst_h = $dims[5];

					break;
			}

			$log->add( 'image_resize_width', 'Resize width: ' . ( $dst_w === 0 ? '0' : $dst_w ) );
			$log->add( 'image_resize_height', 'Resize height: ' . ( $dst_h === 0 ? '0' : $dst_h ) );

		// Do not resize, only a crop in the image.
		} elseif ( $crop_only === TRUE ) {

			// get x position to start croping
			$src_x = (isset($crop_x)) ? $crop_x : 0;

			// get y position to start croping
			$src_y = (isset($crop_y)) ? $crop_y : 0;

			// width of the crop
			if(isset($crop_width)) {
				$src_w = $crop_width;
			} else if(isset($width)) {
				$src_w = $width;
			} else {
				$src_w = $orig_w;
			}

			// height of the crop
			if(isset($crop_height)) {
				$src_h = $crop_height;
			} else if(isset($height)) {
				$src_h = $height;
			} else {
				$src_h = $orig_h;
			}

			// set the width resize with the crop
			if(isset($crop_width) && isset($width)) {
				$dst_w = $width;
			} else {
				$dst_w = null;
			}

			// set the height resize with the crop
			if(isset($crop_height) && isset($height)) {
				$dst_h = $height;
			} else {
				$dst_h = null;
			}

			// allow percentages
			if (isset($dst_w)) {
				if (stripos($dst_w, '%') !== false) {
					$dst_w = (int)((float)str_replace('%', '', $dst_w) / 100 * $orig_w);
				}
			}
			if (isset($dst_h)) {
				if (stripos($dst_h, '%') !== false) {
					$dst_h = (int)((float)str_replace('%', '', $dst_h) / 100 * $orig_h);
				}
			}

			$dims = image_resize_dimensions($src_w, $src_h, $dst_w, $dst_h, false);
			$dst_w = $dims[4];
			$dst_h = $dims[5];

			// Make the pos x and pos y work with percentages
			if (stripos($src_x, '%') !== false) {
				$src_x = (int)((float)str_replace('%', '', $width) / 100 * $orig_w);
			}
			if (stripos($src_y, '%') !== false) {
				$src_y = (int)((float)str_replace('%', '', $height) / 100 * $orig_h);
			}

			// allow center to position crop start
			if($src_x === 'center') {
				$src_x = ($orig_w - $src_w) / 2;
			}
			if($src_y === 'center') {
				$src_y = ($orig_h - $src_h) / 2;
			}
		}

		// Create the hash for the saved file.
		// This to check whether we need to create a new file or use an existing file.
		$hash = (string) filemtime( $img_path ) .
			( empty( $width ) ? str_pad( (string) $width, 5, '0', STR_PAD_LEFT ) : '00000' ) .
			( empty( $height ) ? str_pad( (string) $height, 5, '0', STR_PAD_LEFT ) : '00000' ) .
			( $negate ? '1' : '0' ) .
			( $grayscale ? '1' : '0' ) .
			( isset( $brightness ) ? str_pad( (string) ( (int) $brightness >= 0 ? '1' . (string) $brightness : str_replace( '-', '0', (string) $brightness ) ), 4, '0', STR_PAD_LEFT ) : '0000' ) .
			( isset( $colorize ) ? str_pad( preg_replace( '#^\##', '', $colorize ), 8, '0', STR_PAD_LEFT ) : '00000000' ) .
			( isset( $contrast ) ? str_pad( (string) ( (int) $contrast >= 0 ? '1' . (string) $contrast : str_replace( '-', '0', (string) $contrast ) ), 4, '0', STR_PAD_LEFT ) : '0000' ) .
			( $detect_edges ? '1' : '0' ) .
			( $emboss ? '1' : '0' ) .
			( $gaussian_blur ? '1' : '0' ) .
			( $blur ? '1' : '0' ) .
			( $sketchy ? '1' : '0' ) .
			( $sharpen ? '1' : '0' ) .
			( isset( $smooth ) ? str_pad( (string) ( (float) $smooth >= 0 ? '1' . (string) $smooth : str_replace( '-', '0', (string) $smooth ) ), 4, '0', STR_PAD_LEFT ) : '0000' ) .
			str_pad( (string) $opacity, 3, '0', STR_PAD_LEFT ) .
			( $crop_focus ? ( is_array( $crop_focus ) ? str_replace( '.', '', join( '', $crop_focus ) ) : '1' ) : '0' ) .
			$crop_mode .
			( $crop_only ? '1' : '0' ) .
			(isset($src_x) ? str_pad((string)$src_x, 5, '0', STR_PAD_LEFT) : '00000') .
			(isset($src_y) ? str_pad((string)$src_y, 5, '0', STR_PAD_LEFT) : '00000') .
			(isset($src_w) ? str_pad((string)$src_w, 5, '0', STR_PAD_LEFT) : '00000') .
			(isset($src_h) ? str_pad((string)$src_h, 5, '0', STR_PAD_LEFT) : '00000') .
			(isset($dst_w) ? str_pad((string)$dst_w, 5, '0', STR_PAD_LEFT) : '00000') .
			(isset($dst_h) ? str_pad((string)$dst_h, 5, '0', STR_PAD_LEFT) : '00000') .
			str_pad( preg_replace( '#^\##', '', $canvas_color ), 8, '0', STR_PAD_LEFT ) .
			str_pad( (string) $quality, 3, '0', STR_PAD_LEFT );

		$log->add( 'image_file_hash', 'Hash: ' . $hash );

		// Hash the filename suffix.
		$suffix = md5( $hash );
		$log->add( 'image_base_name_suffix', 'Base filename suffix: ' . $suffix );

		// Use this to check if cropped image already exists, so we can return that instead.
		$dst_rel_path = str_replace( '.' . $ext, '', basename( $img_path ) );

		// Set file ext to `png` if the opacity has been set less than 100 or if the crop mode is `2` and the canvas color was set to transparent.
		if ( $opacity < 100 || ( $canvas_color === 'transparent' && $crop_mode == 2 ) ) $ext = 'png';

		// Create the upload subdirectory, this is where the generated images are saved.
		$upload_dir .= ( is_string( $atts['sub_dir'] ) && ! empty( $atts['sub_dir'] ) ) ? $atts['sub_dir'] : CN_IMAGE_PATH;
		$upload_url .= ( is_string( $atts['sub_dir'] ) && ! empty( $atts['sub_dir'] ) ) ? $atts['sub_dir'] : CN_IMAGE_BASE_URL;
		cnFileSystem::mkdir( $upload_dir );

		// Desination paths and URL.
		$destfilename = "{$upload_dir}/{$dst_rel_path}-{$suffix}.{$ext}";
		$img_url      = "{$upload_url}/{$dst_rel_path}-{$suffix}.{$ext}";

		// If file exists, just return it.
		if ( @file_exists( $destfilename ) && ( $image_info = getimagesize( $destfilename ) ) ) {

			$mime_type = $image_info['mime'];

			$log->add( 'image_serve_from_cache', 'Image found in cache, no processing required.' );

			$editor = wp_get_image_editor( $destfilename );

			// If there is an error, return WP_Error object.
			if ( $result = is_wp_error( $editor ) ) {

				return $result;
			}

			$log->add( 'image_editor_engine', __( sprintf( 'Image processing parent class is %s', get_parent_class( $editor ) ), 'connections' ) );
			$log->add( 'image_editor_engine', __( sprintf( 'Image processing class is %s', get_class( $editor ) ), 'connections' ) );

		} else {

			// Setup the $methods var to be passed to wp_get_image_editor()
			// so the correct image editor engine can be chosen for processing the image.
			if ( $negate ) $methods['methods'][]              = 'negate';
			if ( $grayscale ) $methods['methods'][]           = 'grayscale';
			if ( isset( $brightness ) ) $methods['methods'][] = 'brightness';
			if ( isset( $colorize ) ) $methods['methods'][]   = 'colorize';
			if ( isset( $contrast ) ) $methods['methods'][]   = 'contrast';
			if ( $detect_edges ) $methods['methods'][]        = 'detect_edges';
			if ( $emboss ) $methods['methods'][]              = 'emboss';
			if ( $gaussian_blur ) $methods['methods'][]       = 'gaussian_blur';
			if ( $blur ) $methods['methods'][]                = 'blur';
			if ( $sketchy ) $methods['methods'][]             = 'sketchy';
			if ( $sharpen ) $methods['methods'][]             = 'sharpen';
			if ( isset( $smooth ) ) $methods['methods'][]     = 'smooth';
			if ( isset( $opacity ) ) $methods['methods'][]    = 'opacity';
			if ( $crop_focus ) $methods['methods'][]          = 'crop';
			if ( $crop_mode == 2 ) $methods['methods'][]      = 'resize_padded';
			if ( $crop_only ) $methods['methods'][]           = 'resize';
			$methods['methods'][]                             = 'set_quality';

			// Perform resizing and other filters.
			$editor = wp_get_image_editor( $img_path, $methods );

			// If there is an error, return WP_Error object.
			if ( $result = is_wp_error( $editor ) ) {

				return $result;
			}

			$log->add( 'image_editor_engine', __( sprintf( 'Image processing parent class is %s', get_parent_class( $editor ) ), 'connections' ) );
			$log->add( 'image_editor_engine', __( sprintf( 'Image processing class is %s', get_class( $editor ) ), 'connections' ) );

			/*
			 * Perform image manipulations.
			 */
			if ( $crop_only === FALSE ) {

				if ( ( ! empty( $width ) && $width ) || ( ! empty( $height ) && $height ) ) {

					switch ( $crop_mode ) {

						case 0:

							if ( is_wp_error(
								$result = $editor->resize(
									( empty( $width ) ? NULL : $width ),
									( empty( $height ) ? NULL : $height ),
									FALSE
								) )
								) {

								return $result;
							}

							$log->add( 'image_resized', __( 'Image successfully resized to fit new width and height without maintaining proportion.', 'connections' ) );

							break;

						case 1:

							if ( is_wp_error(
								$result = $editor->resize(
									( empty( $width ) ? NULL : $width ),
									( empty( $height ) ? NULL : $height ),
									( is_array( $crop_focus ) ? $crop_focus : TRUE )
								) )
								) {

								return $result;
							}

							if ( is_bool( $crop_focus ) ) {

								$log->add( 'image_resized', __( 'Image successfully resized with cropping.', 'connections' ) );

							} elseif ( is_array( $crop_focus ) ) {

								$log->add( 'image_resized', __( sprintf( 'Image successfully resized with cropping from origin %s,%s.', $crop_focus[0], $crop_focus[1] ), 'connections' ) );
							}

							break;

						case 2:

							if ( is_wp_error(
								$result = $editor->resize_padded(
									$canvas_w,
									$canvas_h,
									$canvas_color,
									( empty( $width ) ? NULL : $width ),
									( empty( $height ) ? NULL : $height ),
									$orig_w,
									$orig_h,
									( empty( $origin_x ) ? 0 : $origin_x ),
									( empty( $origin_y ) ? 0 : $origin_y )
								) )
								) {

								return $result;
							}

							$log->add( 'image_resized', __( 'Image successfully resized proportionally with padding.', 'connections' ) );

							break;

						case 3:

							if ( is_wp_error(
								$result = $editor->resize(
									( empty( $width ) ? NULL : $width ),
									( empty( $height ) ? NULL : $height ),
									FALSE
								) )
								) {

								return $result;
							}

							$log->add( 'image_resized', __( 'Image successfully resized proportionally with no padding.', 'connections' ) );

							break;

					}

				}

			} else {

				if ( is_wp_error( $result = $editor->crop( $src_x, $src_y, $src_w, $src_h, $dst_w, $dst_h ) ) ) {

					return $result;
				}
			}

			if ( $negate ) {

				if ( is_wp_error( $result = $editor->negate() ) ) {

					return $result;
				}

				$log->add( 'image_filter_negate', __( 'Negate filter applied.', 'connections' ) );
			}

			if ( $grayscale ) {

				if ( is_wp_error( $result = $editor->grayscale() ) ) {

					return $result;
				}

				$log->add( 'image_filter_grayscale', __( 'Grayscale filter applied.', 'connections' ) );
			}

			if ( isset( $brightness ) ) {

				if ( is_wp_error( $result = $editor->brightness( $brightness ) ) ) {

					return $result;
				}

				$log->add( 'image_filter_brightnes', __( sprintf( 'Brightness level %s applied.', $brightness ), 'connections' ) );
			}

			if ( isset( $contrast ) ) {

				if ( is_wp_error( $result = $editor->contrast( $contrast ) ) ) {

					return $result;
				}

				$log->add( 'image_filter_contrast', __( sprintf( 'Contrast level %s applied.', $contrast ), 'connections' ) );
			}

			if ( isset( $colorize ) ) {

				if ( is_wp_error( $result = $editor->colorize( $colorize ) ) ) {

					return $result;
				}

				$log->add( 'image_filter_colorize', __( sprintf( 'Colorized using %s.', $colorize ), 'connections' ) );
			}

			if ( $detect_edges ) {

				if ( is_wp_error( $result = $editor->detect_edges() ) ) {

					return $result;
				}

				$log->add( 'image_filter_edge_detect', __( 'Edge Detect filter applied.', 'connections' ) );
			}

			if ( $emboss ) {

				if ( is_wp_error( $result = $editor->emboss() ) ) {

					return $result;
				}

				$log->add( 'image_filter_emboss', __( 'Emboss filter applied.', 'connections' ) );
			}

			if ( $gaussian_blur ) {

				if ( is_wp_error( $result = $editor->gaussian_blur() ) ) {

					return $result;
				}

				$log->add( 'image_filter_gaussian_blur', __( 'Gaussian Blur filter applied.', 'connections' ) );
			}

			if ( $blur ) {

				if ( is_wp_error( $result = $editor->blur() ) ) {

					return $result;
				}

				$log->add( 'image_filter_blur', __( 'Blur filter applied.', 'connections' ) );
			}

			if ( $sketchy ) {

				if ( is_wp_error( $result = $editor->sketchy() ) ) {

					return $result;
				}

				$log->add( 'image_filter_sketchy', __( 'Sketchy filter applied.', 'connections' ) );
			}

			if ( $sharpen ) {

				if ( is_wp_error( $result = $editor->sharpen( $sharpen ) ) ) {

					return $result;
				}

				$log->add( 'image_filter_sharpen', __( 'Sharpen filter applied.', 'connections' ) );
			}

			if ( isset( $smooth ) ) {

				if ( is_wp_error( $result = $editor->smooth( $smooth ) ) ) {

					return $result;
				}

				$log->add( 'image_filter_smooth', __( sprintf( 'Smooth filter applied with level %s.', $smooth ), 'connections' ) );
			}


			if ( is_wp_error( $result = $editor->opacity( $opacity ) ) ) {

				return $result;
			}

			$log->add( 'image_filter_opacity', __( sprintf( 'Opacity set at %d.', $opacity ), 'connections' ) );

			// Set image save quality.
			$editor->set_quality( $quality );

			$log->add( 'image_save_quality', __( sprintf( 'Saving quality set at %s.', $editor->get_quality() ), 'connections' ) );

			// Save the new image, set file type to PNG if the opacity has been set less than 100 or if the crop mode is `2` and the canvas color was set to transparent.
			$mime_type = $opacity < 100 || ( $canvas_color === 'transparent' && $crop_mode == 2 ) || $orig_mime_type == 'image/png' ? 'image/png' : 'image/jpeg';

			$log->add( 'image_save_mime_type', __( sprintf( 'Saving file as %s.', $mime_type ), 'connections' ) );

			$log->add( 'image_save_file_path', __( sprintf( 'Saving file in path: %s', $destfilename ), 'connections' ) );

			$resized_file = $editor->save( $destfilename, $mime_type );

			$log->add( 'image_save', __( 'File saved successfully.', 'connections' ) );
		}

		$log->add( 'image_cache_url', __( sprintf( 'Cache URL: %s', $img_url ), 'connections' ) );

		/*
		 * Remove the cnImage filters.
		 */
		remove_filter( 'wp_image_editors', array( __CLASS__, 'imageEditors' ) );

		remove_filter( 'image_resize_dimensions', array( __CLASS__, 'resize_dimensions' ), 10 );

		/*
		 * Be a good citizen and add the filters that were hooked back to image editor filters.
		 */
		if ( ! empty( $filter['editors'] ) ) $wp_filter['wp_image_editors']       = $filter['editors'];
		if ( ! empty( $filter['resize'] ) ) $wp_filter['image_resize_dimensions'] = $filter['resize'];

		switch ( $return ) {

			case 'base64':

				$image = 'data:image/' . ( isset( $mime_type ) ? $mime_type : $orig_mime_type ) . ';base64,' . base64_encode( file_get_contents( $destfilename ) );
				break;

			case 'data':

				$image = array (
					'name'   => "{$dst_rel_path}-{$suffix}.{$ext}",
					'path'   => $destfilename,
					'url'    => $img_url,
					'width'  => isset($dst_w) ? $dst_w : $orig_w,
					'height' => isset($dst_h) ? $dst_h : $orig_h,
					'size'   => 'height="' . ( isset($dst_h) ? $dst_h : $orig_h ) . '" width="' . ( isset($dst_w) ? $dst_w : $orig_w ) . '"',
					'mime'   => isset( $mime_type ) ? $mime_type : $orig_mime_type,
					'type'   => $image_info[2],
					'log'    => defined( 'WP_DEBUG' ) && WP_DEBUG === TRUE ? $log : __( 'WP_DEBUG is not defined or set to FALSE, set to TRUE to enable image processing log.', 'connections' ),
				);
				break;

			case 'editor':

				$image = $editor;
				break;

			case 'stream':

				$image = $editor->stream();
				break;

			default:

				$image = $img_url;
				break;
		}

		return $image;
	}

	/**
	 * Filter callback which override WP core image_resize_dimensions().
	 *
	 * Retrieve calculated resize dimensions for use in WP_Image_Editor.
	 *
	 * Calculates dimensions and coordinates for a resized image that fits
	 * within a specified width and height.
	 *
	 * Cropping behavior is dependent on the value of $crop:
	 * 1. If false (default), images will not be cropped.
	 * 2. If an array in the form of array( x_crop_position, y_crop_position ):
	 *    - x_crop_position accepts (float) range 0.0–1.0
	 *    - y_crop_position accepts (float) range 0.0–1.0
	 *    Images will be cropped to the specified dimensions within the defined crop area.
	 * 3. If true, images will be cropped to the specified dimensions using center center .5, .5.
	 *
	 * @access private
	 * @since 8.1
	 *
	 * @param NULL       $payload NULL value being passed by the image_resize_dimensions filter
	 * @param int        $orig_w  Original width in pixels.
	 * @param int        $orig_h  Original height in pixels.
	 * @param int        $dest_w  New width in pixels.
	 * @param int        $dest_h  New height in pixels.
	 * @param bool|array $crop    Optional. Whether to crop image to specified height and width or resize.
	 *                            An array can specify positioning of the crop area. Default false.
	 * @return bool|array         Returned array matches parameters for `imagecopyresampled()`.
	 */
	public static function resize_dimensions( $payload, $orig_w, $orig_h, $dest_w, $dest_h, $crop = false) {

		if ( $crop ) {
			// crop the largest possible portion of the original image that we can size to $dest_w x $dest_h
			$aspect_ratio = $orig_w / $orig_h;
			$new_w = $dest_w;
			$new_h = $dest_h;

			if ( !$new_w ) {
				$new_w = intval($new_h * $aspect_ratio);
			}

			if ( !$new_h ) {
				$new_h = intval($new_w / $aspect_ratio);
			}

			$size_ratio = max($new_w / $orig_w, $new_h / $orig_h);

			$crop_w = round($new_w / $size_ratio);
			$crop_h = round($new_h / $size_ratio);

			if ( ! is_array( $crop ) || count( $crop ) !== 2 ) {
				$crop = array( 0.5, 0.5 );
			}

			list( $x, $y ) = $crop;

			// Ideal offsets
			$ideal_s_x = $x * $orig_w - ( $crop_w / 2 );
			$ideal_s_y = $y * $orig_h - ( $crop_h / 2 );

			// Ideally we want our x,y crop-focus-point perfectly in the middle...
			// but to put (for example) the top left corner in the centre of our cropped
			// image we end up with black strips where there isn't enough image on the
			// left and top.
			// This maths takes our ideal offsets and gets as close to it as possible.

			if ( $ideal_s_x < 0 ):
			  $s_x = 0;
			elseif ( $ideal_s_x + $crop_w > $orig_w ):
			  $s_x = $orig_w - $crop_w;
			else:
			  $s_x = floor( $ideal_s_x );
			endif;

			if ( $ideal_s_y < 0 ):
			  $s_y = 0;
			elseif ( $ideal_s_y + $crop_h > $orig_h ):
			  $s_y = $orig_h - $crop_h;
			else:
			  $s_y = floor( $ideal_s_y );
			endif;

		} else {

			$s_x = 0;
			$s_y = 0;

			$aspect_ratio = $orig_w / $orig_h;

			$new_w = $dest_w;
			$new_h = $dest_h;

			if ( !$new_w ) {
				$new_w = intval($new_h * $aspect_ratio);
			}

			if ( !$new_h ) {
				$new_h = intval($new_w / $aspect_ratio);
			}

			$size_ratio = max($new_w / $orig_w, $new_h / $orig_h);

			$crop_w = round($new_w / $size_ratio);
			$crop_h = round($new_h / $size_ratio);
		}

		// the return array matches the parameters to imagecopyresampled()
		// int dst_x, int dst_y, int src_x, int src_y, int dst_w, int dst_h, int src_w, int src_h
		return array( 0, 0, (int) $s_x, (int) $s_y, (int) $new_w, (int) $new_h, (int) $crop_w, (int) $crop_h );

	}

	/**
	 * Upload a file to the WP_CONTENT_DIR/CN_IMAGE_DIR_NAME or in the defined subdirectory.
	 *
	 * @access public
	 * @since  8.1
	 * @uses   trailingslashit()
	 * @uses   cnUpload
	 * @param array  $file Reference to a single element of $_FILES.
	 * @param array  $atts An associative array containing the upload params.
	 *
	 * @return mixed array | object On success an associative array of the uploadewd file details. On failure, an instance of WP_Error.
	 */
	public static function upload( $file, $subDirectory = '' ) {

		$atts = array(
			'sub_dir' => empty( $subDirectory ) ? CN_IMAGE_DIR_NAME : trailingslashit( CN_IMAGE_DIR_NAME ) . $subDirectory ,
			'mimes'   => array(
				'jpeg' => 'image/jpeg',
				'jpg'  => 'image/jpeg',
				'gif'  => 'image/gif',
				'png'  => 'image/png',
				 ),
			);

		$upload = new cnUpload( $file, $atts );

		$result = $upload->result();

		if ( ! is_wp_error( $result ) && $image = @getimagesize( $result['path'] ) ) {

			$result['width']  = $image[0];
			$result['height'] = $image[1];
			$result['size']   = $image[3];
			$result['mime']   = $image['mime'];
			$result['type']   = $image[2];

			$order = array(
				'name'   => '',
				'path'   => '',
				'url'    => '',
				'width'  => '',
				'height' => '',
				'size'   => '',
				'mime'   => '',
				'type'   => ''
				);

			$result = array_merge( $order, $result );
		}

		return $result;
	}

}

// Init the Image API
cnImage::init();
