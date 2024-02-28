<?php
/**
 * Gravity Forms Feed
 */

// Exit if accessed directly.
if ( !defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Include the addon framework
 */
GFForms::include_feed_addon_framework();


/**
 * The class.
 */
class GF_Discord extends GFFeedAddOn {

	/**
	 * Plugin Version
	 *
	 * @var string $_version
	 */
	protected $_version = GFDISC_VERSION;


	/**
	 * Minimum required version of Gravity Forms
	 *
	 * @var string $_min_gravityforms_version
	 */
	protected $_min_gravityforms_version = '2.2';


	/**
	 * Plugin Slug
	 *
	 * @var string $_slug
	 */
	protected $_slug = GFDISC_TEXTDOMAIN;


	/**
	 * Plugin Path
	 *
	 * @var string $_path
	 */
	protected $_path = GFDISC_TEXTDOMAIN.'/'.GFDISC_TEXTDOMAIN.'.php';


	/**
	 * Plugin Full Path
	 *
	 * @var [type]
	 */
	protected $_full_path = __FILE__;


	/**
	 * Title of Add-On
	 *
	 * @var string $_title
	 */
	protected $_title = 'Gravity Forms/Discord Integration';


	/**
	 * Short Title of Add-On
	 *
	 * @var string $_short_title
	 */
	protected $_short_title = 'Discord';

	
	/**
	 * Core singleton class
	 *
	 * @var self - pattern realization
	 */
	private static $_instance;


	/**
	 * Default accent color
	 *
	 * @var string
	 */
	public $default_accent_color = '#FF0000';


	/**
	 * Get an instance of this class.
	 *
	 * @return GF_Discord
	 */
	public static function get_instance() {
		if ( null === self::$_instance ) {
			self::$_instance = new GF_Discord();
		}

		return self::$_instance;
	} // End get_instance()


	/**
	 * Handles hooks
	 */
	public function init() {
		parent::init();

		// Add plugin settings
		$plugin_settings = GFCache::get( 'gfdisc_plugin_settings' );
		if ( empty( $plugin_settings ) ) {
			$plugin_settings = $this->get_plugin_settings();
			GFCache::set( 'gfdisc_plugin_settings', $plugin_settings );
		}

		// Add a meta box to the entries
        add_filter( 'gform_entry_detail_meta_boxes', [ $this, 'entry_meta_box' ], 10, 3 );
	} // End init()


	/**
	 * Enqueue needed scripts.
	 *
	 * @return array
	 */
	public function scripts() {
		$scripts = [
			[
				'handle'    => 'gf_gfdisc_media_uploader',
				'src'       => GFDISC_PLUGIN_DIR.'media-uploader.js',
				'version'   => $this->_version,
				'deps'      => [ 'jquery' ],
				'callback'  => 'wp_enqueue_media',
				'in_footer' => true,
				'enqueue'   => [
					[
						'query' => 'page=gf_settings&subview='.GFDISC_TEXTDOMAIN
					],
				],
			],
		];
		return array_merge( parent::scripts(), $scripts );
	} // End scripts()


	/**
     * Add entry meta box
     *
     * @param array $meta_boxes
     * @param array $entry
     * @param array $form
     * @return array
     */
    public function entry_meta_box( $meta_boxes, $entry, $form ) {
        // Link to Debug Form and Entry
        if ( !isset( $meta_boxes[ 'gfdisc' ] ) ) {
            $meta_boxes[ 'gfdisc' ] = [
                'title'         => esc_html__( 'Discord', 'gf-discord' ),
                'callback'      => [ $this, 'entry_meta_box_content' ],
                'context'       => 'side',
                'callback_args' => [ $entry, $form ],
            ];
        }
     
        // Return the boxes
        return $meta_boxes;
    } // End entry_meta_box()


    /**
     * The content of the meta box
     *
     * @param array $args
     * @return void
     */
    public function entry_meta_box_content( $args ) {
        // Get the form and entry
        $form  = $args[ 'form' ];
        $entry = $args[ 'entry' ];

		// Get the feeds
		$feeds = GFAPI::get_feeds( null, $form[ 'id' ], GFDISC_TEXTDOMAIN );

		// Start the container
        $results = '<div>';

		// Check for a wp error
		if ( !is_wp_error( $feeds ) ) {

			// If there are feeds
			if ( !empty( $feeds ) ) {

				// Send the form entry if query string says so
				if ( isset( $_GET[ 'gfdisc' ] ) && sanitize_text_field( $_GET[ 'gfdisc' ] )  == 'true' &&
					isset( $_GET[ 'feed_id' ] ) && absint( $_GET[ 'feed_id' ] ) != '' ) {

					// The feed id
					$feed_id = absint( $_GET[ 'feed_id' ] );

					// Get the feed
					$feed = GFAPI::get_feed( $feed_id );

					// Process the feed
					$this->process_feed( $feed, $entry, $form );

					// Remove the query strings
					$this->redirect_without_qs( [ 'gfdisc', 'feed_id' ] );
				}

				// Multiple feeds?
				if ( count( $feeds ) > 1 ) {
					$br = '<br><br>';
				} else {
					$br = '';
				}

				// Iter the feeds
				foreach ( $feeds as $feed ) {

					// The feed title
					$results .= '<strong><a href="'.$this->feed_settings_url( $form[ 'id' ], $feed[ 'id' ] ).'">'.$feed[ 'meta' ][ 'feedName' ].'</a>:</strong><br><br>';

					// The current url
					$current_url = '/wp-admin/admin.php?page=gf_entries&view=entry&id='.$form[ 'id' ].'&lid='.$entry[ 'id' ];

					// Resend button
					$results .= '<a class="button" href="'.$current_url.'&feed_id='.$feed[ 'id' ].'&gfdisc=true">Resend</a>';

					// Space between
					$results .= $br;
				}

			} else {

				// The feed url
				$feed_url = '/wp-admin/admin.php?page=gf_edit_forms&view=settings&subview='.GFDISC_TEXTDOMAIN.'&id='.$form[ 'id' ];

				// Resend button
				$results .= '<a class="button" href="'.$feed_url.'">Add New Feed</a>';
			}

		// Error
		} else {

			// The feed url
			$feed_url = '/wp-admin/admin.php?page=gf_edit_forms&view=settings&subview='.GFDISC_TEXTDOMAIN.'&id='.$form[ 'id' ];

			// Resend button
			$results .= '<a class="button" href="'.$feed_url.'">Add New Feed</a>';
		}
        
        // Start the container
        $results .= '</div>';
    
        // Return everything
        echo wp_kses_post( $results );
    } // End entry_meta_box_content()


	/**
	 * Form settings icon
	 *
	 * @return string
	 */
	public function get_menu_icon() {
		return file_get_contents( $this->get_base_path().'/img/discord-icon.svg' );
	} // End get_menu_icon()


	/**
	 * Note avatar
	 *
	 * @return string
	 */
	public function note_avatar() {
		return GFDISC_PLUGIN_DIR.'img/discord-logo.png';
	} // End note_avatar()


	/**
	 * Remove unneeded settings.
	 */
	public function uninstall() {
		parent::uninstall();
		GFCache::delete( 'gfdisc_plugin_settings' );
	} // End uninstall()


	/**
	 * Prevent feeds being listed or created if an api key isn't valid.
	 *
	 * @return bool
	 */
	public function can_create_feed() {
        return true;
	} // End can_create_feed()


	/**
	 * Configures which columns should be displayed on the feed list page.
	 *
	 * @return array
	 */
	public function feed_list_columns() {
		return [
			'feedName' => esc_html__( 'Feed Name', 'gf-discord' ),
			'channel'  => esc_html__( 'Channel', 'gf-discord' ),
        ];
	} // End feed_list_columns()


	/**
	 * Get the feed settings url
	 *
	 * @param int $form_id
	 * @param int $feed_id
	 * @return string
	 */
	public function feed_settings_url( $form_id, $feed_id ) {
		return admin_url( 'admin.php?page=gf_edit_forms&view=settings&subview='.GFDISC_TEXTDOMAIN.'&id='.$form_id.'&fid='.$feed_id );
	} // End feed_settings_url()


	/**
	 * Configures the settings which should be rendered on the add-on settings tab.
	 *
	 * @return array
	 */
	public function plugin_settings_fields() {
		return [
			[
				'title'       => esc_html__( 'Discord Integration Settings', 'gf-discord' ),
				'description' => '<p>'.esc_html__( 'Send form entries to a Discord channel.', 'gf-discord' ).'</p>',
				'fields'      => [
					[
						'name'              => 'site_name',
						'tooltip'           => esc_html__( 'The site name displayed on the messages. Limited to 50 characters.', 'gf-discord' ),
						'label'             => esc_html__( 'Site Name', 'gf-discord' ),
						'type'              => 'text',
						'class'             => 'medium',
						'default_value' 	=> get_bloginfo( 'name' ),
						'feedback_callback' => [ $this, 'is_valid_setting' ],
                    ],
					[
						'name'              => 'site_logo',
						'tooltip'           => esc_html__( 'Upload a logo to be used on the messages. For best results, use a small image with the same width and height around 100x100px.', 'gf-discord' ),
						'label'             => esc_html__( 'Site Logo', 'gf-discord' ),
						'type'              => 'text',
						'class'             => 'medium',
						'default_value' 	=> GFDISC_PLUGIN_DIR.'img/wordpress-logo.png',
						'feedback_callback' => [ $this, 'validate_image' ],
                    ],
					[
						'name'              => 'upload_image_button',
						'type'              => 'media_upload',
						'class'             => 'medium',
						'args'  => [
                            'button' => [
                                'name'    => 'upload_image_button',
								'class'   => 'button',
								'value'   => 'Upload Image'
							],
						],
                    ],
					[
						'name'              => 'gfdisc_preview',
						'type'              => 'gfdisc_preview',
						'class'             => 'medium',
                    ],
					[
						'name'              => 'footer_text',
						'tooltip'           => esc_html__( 'The text that will be displayed in the footer of the messages.', 'gf-discord' ),
						'label'             => esc_html__( 'Message Footer Text', 'gf-discord' ),
						'type'              => 'text',
						'class'             => 'medium',
						'default_value' 	=> home_url(),
						'feedback_callback' => [ $this, 'is_valid_setting' ],
                    ],
                ],
            ],
			[
				'title'       => esc_html__( 'Instructions', 'gf-discord' ),
				'description' => '<p>'.esc_html__( 'How to add a webhook to Discord and setting up feeds.', 'gf-discord' ).'</p><br>',
				'fields'      => [
					[
						'name'              => 'instructions',
						'type'              => 'instructions',
						'class'             => 'medium',
                    ],
                ],
            ],
        ];
	} // End form_settings_fields()


	/**
	 * Media Uploader Button
	 *
	 * @param array $field
	 * @param boolean $echo
	 * @return void
	 */
	public function settings_media_upload( $field, $echo = true ) {
        // get the button settings from the main field and then render the button
        $button = $field[ 'args' ][ 'button' ];
        printf(
            '<input type="button" id="%s" class="%s" value="%s"/>',
            $button[ 'name' ],
            $button[ 'class' ],
            $button[ 'value' ],
        );
    } // End settings_media_upload()


	/**
	 * Site logo preview
	 *
	 * @param array $field
	 * @param boolean $echo
	 * @return void
	 */
	public function settings_gfdisc_preview( $field, $echo = true ) {
		// Get the site name
		$get_site_name = sanitize_text_field( $this->get_plugin_setting( 'site_name' ) );
        if ( $get_site_name && $get_site_name != '' ) {
            $site_name = $get_site_name;
        } else {
            $site_name = get_bloginfo( 'name' ); // Blog Name
        }

		// Get the current logo
		$get_site_logo = esc_url_raw( $this->get_plugin_setting( 'site_logo' ) );
		if ( $get_site_logo && $get_site_logo != '' ) {
            $url = $get_site_logo;
        } else {
            $url = GFDISC_PLUGIN_DIR.'img/wordpress-logo.png'; // WP Logo
        }

		// Add some css
		echo '</pre>
		<style>
		#gfdisc-preview { margin-top: 30px; }
		#site-logo-preview {
			background: center / contain no-repeat url('.esc_url_raw( $url ).'); 
			width: 5.2rem; 
			height: 5.2rem; 
			border-radius: 4px;
			display: inline-block;
		}
		#site-info-preview { display: inline-block; margin: 5px 0 0 10px; vertical-align: top; }
		#site-name-preview { 
			display: block;
			font-size: 1.4rem;
			font-weight: 600;
		}
		#site-url-preview { display: block; font-size: 1rem; color: #7074D0; }
		#mode-preview { float: right; }
		#mode-preview a:active, #mode-preview a:focus, #mode-preview a:hover { color: #7074D0; }
		.gform-settings-panel__content,
		.gform-settings-panel__content label,
		.gform-settings-panel__content input {
			transition: all 1s ease;
		}
		</style>';

		// Display it
        echo '<div id="gfdisc-preview">
			<div id="site-logo-preview"></div>
			<div id="site-info-preview">
				<span id="site-name-preview">'.esc_html( $site_name ).'</span><br>
				<span id="site-url-preview">'.esc_url( home_url() ).'</span>
			</div>
			<div id="mode-preview"><a href="#" onclick="gfdiscLightMode(); return false;">Light</a> | <a href="#" onclick="gfdiscDarkMode(); return false;">Dark</a></div>
		</div>';

		// Add JS to update immediately
		echo '<script>
		// Switch to light mode
		function gfdiscLightMode() {
			let panel = document.querySelector( ".gform-settings-panel__content" );
			panel.style.background = "transparent";
			panel.style.color = "#1d2327";

			let panelLabels = document.querySelectorAll( ".gform-settings-panel__content label" );
			panelLabels.forEach( label => {
				label.style.color = "#1d2327";
			} );

			let panelInputs = document.querySelectorAll( ".gform-settings-panel__content input" );
			panelInputs.forEach( input => {
				input.style.backgroundColor = "revert";
				input.style.color = "revert";
			} );
		}

		// Switch to dark mode
		function gfdiscDarkMode() {
			let panel = document.querySelector( ".gform-settings-panel__content" );
			panel.style.background = "#2E2E2E";
			panel.style.color = "white";

			let panelLabels = document.querySelectorAll( ".gform-settings-panel__content .gform-settings-label" );
			panelLabels.forEach( label => {
				label.style.color = "white";
			} );

			let panelInputs = document.querySelectorAll( ".gform-settings-panel__content input" );
			panelInputs.forEach( input => {
				input.style.backgroundColor = "#292929";
				input.style.color = "white";
			} );
		}

		// Update the site name in real time
		updateSiteName();
		function updateSiteName() {
			// Get the site logo value
			let nameField = document.getElementById( "site_name" );
			let preview = document.getElementById( "site-name-preview" );

			// The max limit
			let maxChars = 50;
			
			// Limit character count on FeedName
			nameField.addEventListener( "keydown", ( e ) => {
				if ( nameField.value.length > maxChars ) {
					nameField.value = nameField.value.substr( 0, maxChars );
				}
			} );

			// Listen for change
			nameField.addEventListener( "keyup", ( event ) => {
				if ( nameField.value.length > maxChars ) {
					nameField.value = nameField.value.substr( 0, maxChars );
				}

				let name = nameField.value;
				if ( name == "" ) {
					name = "'.esc_html( get_bloginfo( 'name' ) ).'";
				}
				preview.innerHTML = name;
			} );
		}

		// Update the logo in real time
		updateSiteLogoPreview();
		function updateSiteLogoPreview() {
			// Get the site logo value
			let logoField = document.getElementById( "site_logo" );
			let preview = document.getElementById( "site-logo-preview" );

			// Listen for change
			logoField.addEventListener( "keyup", ( event ) => {
				let url = logoField.value;
				if ( url == "" ) {
					url = "'.esc_attr( GFDISC_PLUGIN_DIR ).'img/wordpress-logo.png";
				}
				preview.style.background = "center / contain no-repeat url(" + url + ")";
			} );
		}
		</script>
		<pre>';
    } // End settings_gfdisc_preview()


	/**
	 * Instructions field
	 *
	 * @param array $field
	 * @param boolean $echo
	 * @return void
	 */
	public function settings_instructions( $field, $echo = true ) {
        echo '</pre>
		<div><h2>'.esc_html__( 'On Discord (must be web view):', 'gf-discord' ).'</h2><ol>
			<li>'.esc_html__( 'Go to Server Settings > Integrations', 'gf-discord' ).'</li>
			<li>'.esc_html__( 'Click on Webhooks', 'gf-discord' ).'</li>
			<li>'.esc_html__( 'Click on "New Webhook"', 'gf-discord' ).'</li>
			<li>'.esc_html__( 'Scroll down and click on your new webhook (probably named "Captain Hook")', 'gf-discord' ).'</li>
			<li>'.esc_html__( 'Name your webhook (this will be used as the name that the messages are posted by)', 'gf-discord' ).'</li>
			<li>'.esc_html__( 'Upload a logo for your webhook', 'gf-discord' ).'</li>
			<li>'.esc_html__( 'Choose the channel the messages should be posted in', 'gf-discord' ).'</li>
			<li>'.esc_html__( 'Click on "Copy Webhook URL"; it will save to your clipboard', 'gf-discord' ).'</li>
			<li>'.esc_html__( 'Save this webhook; you will be needing this to add to your form feed', 'gf-discord' ).'</li>
		</ol></div>
		<br><br>
		<div><h2>'.esc_html__( 'On Gravity Forms:', 'gf-discord' ).'</h2><ol>
			<li>'.esc_html__( 'Go to your form settings', 'gf-discord' ).'</li>
			<li>'.esc_html__( 'Click on Discord', 'gf-discord' ).'</li>
			<li>'.esc_html__( 'Add a new feed', 'gf-discord' ).'</li>
			<li>'.esc_html__( 'Choose a title', 'gf-discord' ).'</li>
			<li>'.esc_html__( 'Enter the webhook URL you copied from Discord', 'gf-discord' ).'</li>
			<li>'.esc_html__( 'Select the fields you need to map', 'gf-discord' ).'</li>
			<li>'.esc_html__( 'Save the settings', 'gf-discord' ).'</li>
			<li>'.esc_html__( 'Complete the form and see your entry appear!', 'gf-discord' ).'</li>
		</ol></div>
		<pre>';
    } // End settings_instructions()


	/**
	 * Configures the settings which should be rendered on the Form Settings > Discord tab.
	 *
	 * @return array
	 */
	public function feed_settings_fields() {
		// Get the current feed
		if ( $feed =  $this->get_current_feed() ) {

			// Get the form
			$form = GFAPI::get_form( $feed[ 'form_id' ] );
			
		// Or else get the id from the query string
		} elseif ( isset( $_GET[ 'id' ] ) && absint( $_GET[ 'id' ] ) != '' ) {

			// Get the form
			$form = GFAPI::get_form( absint( $_GET[ 'id' ] ) );

		// Or no form
		} else {
			$form = false;
		}

		// Return the fields
		return [
			[
				'title'  => esc_html__( 'Feed Settings', 'gf-discord' ),
				'fields' => [
					[
						'name'     => 'feedName',
						'label'    => esc_html__( 'Title', 'gf-discord' ),
						'type'     => 'text',
						'required' => true,
						'class'    => 'medium',
						'limit'	   => 60,
						'tooltip'  => esc_html__( 'Enter a title to uniquely identify this message. This will be used as your feed name as well as the title of your Discord message.', 'gf-discord' ),
					],
					[
						'name'     => 'webhook',
						'label'    => esc_html__( 'Incoming Webhook URL', 'gf-discord' ),
						'type'     => 'text',
						'required' => true,
						'tooltip'  => esc_html__( 'Add the Incoming Webhook URL. You will find this in your Discord Incoming Webhook App setup.', 'gf-discord' ),
					],
					[
						'name'     => 'bot_name',
						'label'    => esc_html__( 'Override Bot Name (Optional)', 'gf-discord' ),
						'type'     => 'text',
						'required' => false,
						'tooltip'  => esc_html__( 'Use a different bot name than the default one you created on your Discord webhook settings.', 'gf-discord' ),
					],
					[
						'name'     => 'channel',
						'label'    => esc_html__( 'Channel Name (Optional)', 'gf-discord' ),
						'type'     => 'text',
						'required' => false,
						'tooltip'  => esc_html__( 'The Discord channel name. For reference only.', 'gf-discord' ),
					],
					[
						'name'          => 'color',
						'label'         => esc_html__( 'Accent Color (Optional)', 'gf-discord' ),
						'type'          => 'text',
						'required'      => false,
						'tooltip'       => esc_html__( 'Choose a color for the top bar of the messages using a hex code. Default is red (#FF0000).', 'gf-discord' ),
						'default_value' => '#FF0000',
					],
					[
						'name'    => 'message',
						'label'   => esc_html__( 'Message (Optional) — Discord Formatting/Markdown is Allowed', 'gf-discord' ),
						'type'    => 'textarea',
						'class'   => 'medium merge-tag-support mt-position-right',
						'tooltip' => esc_html__( 'You can mention a user on the server with {{@user_id}}. Likewise, you can tag a role with {{@&role_id}} and a channel with {{#channel_id}}.', 'gf-discord' ), 
					],
					[
						'name'  	=> 'top_section_footer',
						'type'  	=> 'top_section_footer',
						'class' 	=> 'medium',
                    ],
				],
			],
			[
				'title'  => esc_html__( 'Fields', 'gf-discord' ),
				'fields' => [
					[
						'name'      => 'mappedFields',
						'label'     => esc_html__( 'Match required fields', 'gf-discord' ),
						'type'      => 'field_map',
						'field_map' => $this->merge_vars_field_map(),
						'tooltip'   => esc_html__( 'Setup the message values by selecting the appropriate form field from the list.', 'gf-discord' ),
					],
					[
						'name'    => 'checkboxgroup',
						'label'   => esc_html__( 'Include the following fields and additional information in Discord Message' ),
						'type'    => 'checkbox',
						'tooltip' => esc_html__( 'Select which fields should be included in the Discord message.' ),
						'choices' => $this->get_list_facts( $form )
					],
					[
						'name'    => 'hideblankgroup',
						'label'   => esc_html__( 'Hide fields with blank values' ),
						'type'    => 'checkbox',
						'tooltip' => esc_html__( 'Removes fields from Discord message if the values are empty.' ),
						'choices' => [
							[
								'label' => 'Yes',
								'name'  => 'hide_blank'
							]
						]
					],
				],
			],
			[
				'title'  => 'Feed Conditions',
				'fields' => [
					[
						'name'           => 'condition',
						'label'          => esc_html__( 'Condition', 'gf-discord' ),
						'type'           => 'feed_condition',
						'checkbox_label' => esc_html__( 'Enable Condition', 'gf-discord' ),
						'instructions'   => esc_html__( 'Process this feed if', 'gf-discord' ),
					],
				],
			],
		];
	} // End feed_settings_fields()


	/**
	 * Plugin settings link "field"
	 *
	 * @param array $field
	 * @param boolean $echo
	 * @return void
	 */
	public function settings_color( $field, $echo = true ) {
		// Get the color
		$color = $this->get_setting( 'color' );
		printf(
			'<input type="color" id="%s" name="%s" class="%s" value="%s" style="width: 10rem;"/>',
			$field[ 'name' ],
			$field[ 'name' ],
			$field[ 'class' ],
			$color,
		);
    } // End settings_color()


	/**
	 * Plugin settings link "field"
	 *
	 * @param array $field
	 * @param boolean $echo
	 * @return void
	 */
	public function settings_top_section_footer( $field, $echo = true ) {
		// Get the color
		$color = $this->sanitize_and_validate_color( $this->get_setting( 'color' ), $this->default_accent_color );

		// Add CSS
		echo '</pre>
		<style>
		#gform-settings-section-feed-settings { 
			border-left: 5px solid #'.esc_attr( $color ).' !important; 
		}
		</style>';

		// Color div and Link to plugin settings page
        echo '<div id="plugin-settings-link" style="margin-top: 30px;"><a href="'.esc_url( GFDISC_SETTINGS_URL ).'">Plugin Settings</a></div>
		<pre>';
    } // End settings_plugin_link()


	/**
	 * Return an array of list fields which can be mapped to the Form fields/entry meta.
	 *
	 * @return array
	 */
	public function merge_vars_field_map() {

		// Initialize field map array.
		$field_map = [];

		// Get merge fields.
		$merge_fields = $this->get_list_merge_fields();

		// If merge fields exist, add to field map.
		if ( ! empty( $merge_fields ) && is_array( $merge_fields ) ) {

			// Loop through merge fields.
			foreach ( $merge_fields as $field => $config ) {

				// Define required field type.
				$field_type = null;

				switch ( strtolower( $config['type'] ) ) {
					case 'name':
						$field_type = [ 'name', 'text' ];
						break;

					case 'email':
						$field_type = [ 'email' ];
						break;

					case 'textarea':
						$field_type = [ 'textarea' ];
						break;

					default:
						$field_type = [ 'text', 'hidden' ];
						break;
				}

				// Add to field map.
				$field_map[ $field ] = [
					'name'       => $field,
					'label'      => $config[ 'name' ],
					'required'   => $config[ 'required' ],
					'field_type' => $field_type,
					'tooltip'	 => isset( $config[ 'description' ] ) ? $config[ 'description' ] : '',
				];
			}
		}

		return $field_map;
	} // End merge_vars_field_map()


	// # FEED PROCESSING -----------------------------------------------------------------------------------------------


	/**
	 * Process the feed
	 *
	 * @param array $feed  The feed object to be processed.
	 * @param array $entry The entry object currently being processed.
	 * @param array $form  The form object currently being processed.
	 *
	 * @return array
	 */
	public function process_feed( $feed, $entry, $form ) {
		// Log that we are processing feed.
		$this->log_debug( __METHOD__ . '(): Processing feed.' );

		// Get the webhook
		$webhook = filter_var( $feed[ 'meta' ][ 'webhook' ], FILTER_SANITIZE_URL );

		// Check if the webhook is empty
		if ( !$webhook || $webhook == '' || empty( $webhook ) ) {
			$this->add_feed_error( esc_html__( 'Aborted: Empty Incoming Webhook URL', 'gf-discord' ), $feed, $entry, $form );
			return $entry;
		}

		// Retrieve the name => value pairs for all fields mapped in the 'mappedFields' field map.
		$field_map = $this->get_field_map_fields( $feed, 'mappedFields' );

		// Get mapped email address.
		$email = $this->get_field_value( $form, $entry, $field_map[ 'email' ] );

		// If email address is invalid, log error and return.
		if ( GFCommon::is_invalid_or_empty_email( $email ) ) {
			$this->add_feed_error( esc_html__( 'A valid email address must be provided.', 'gf-discord' ), $feed, $entry, $form );
			return $entry;
		}

		// Loop through the fields from the field map setting building an array of values to be passed to the third-party service.
		$merge_vars = [];
		foreach ( $field_map as $name => $field_id ) {

			// If no field is mapped, skip it.
			if ( rgblank( $field_id ) ) {
				continue;
			}

			// Get field value.
			$field_value = $this->get_field_value( $form, $entry, $field_id );

			// If field value is empty, skip it.
			if ( empty( $field_value ) ) {
				continue;
			}

			// Get the field value for the specified field id
			$merge_vars[ $name ] = $field_value;
		}

		// Check if there are empty mapped fields
		if ( empty( $merge_vars ) ) {
			$this->add_feed_error( esc_html__( 'Aborted: Empty merge fields', 'gf-discord' ), $feed, $entry, $form );
			return $entry;
		}

		// If sending failed
		if ( !$this->send_form_entry( $feed, $entry, $form, $email ) ) {

			// Log that registration failed.
			$this->add_feed_error( esc_html__( $this->_short_title.' error when trying to send message to channel', 'gf-discord' ), $feed, $entry, $form ); // phpcs:ignore
			return false;

		// If we sent the form entry successfully
		} else {

			// Add channel?
			if ( isset( $feed[ 'meta' ][ 'channel' ] ) && $feed[ 'meta' ][ 'channel' ] != '' ) {
				$incl_channel = ' | Channel: '.$feed[ 'meta' ][ 'channel' ];
			} else {
				$incl_channel = '';
			}

			// Succcesful 
            $note = 'Entry sent successfully to Discord<br>Feed: '.$feed[ 'meta' ][ 'feedName' ].$incl_channel;
            $sub_type = 'success';
            
            // Log that the registrant was added.
            RGFormsModel::add_note( $entry[ 'id' ], 0, __( $this->_short_title, 'gf-discord' ), $note, 'gfdisc', $sub_type );
			$this->log_debug( __METHOD__ . '(): Message sent successfully.' ); // phpcs:ignore
		}

		// Return the entry
		return $entry;
	} // End process_feed()


    /**
     * Send form entry as a message to Discord
     *
     * @param array $entry
     * @param array $form
     * @return array
     */
    public function send_form_entry( $feed, $entry, $form, $email ) {
		// Are we hiding empty values?
		if ( isset( $feed[ 'meta' ][ 'hide_blank' ] ) && $feed[ 'meta' ][ 'hide_blank' ] ) {
			$hiding = true;
		} else {
			$hiding = false;
		}

        // Store the message facts
        $facts = [];

        // Iter the fields
        foreach ( $form[ 'fields' ] as $field ) {
    
            // Skip HTML fields
            if ( $field->type == 'html' || $field->type == 'section' ) {
                continue;
            }

            // Get the field ID
            $field_id = $field->id;

			// Skip field if not enabled
			if ( isset( $feed[ 'meta' ][ $field_id ] ) && !$feed[ 'meta' ][ $field_id ] ) {
				continue;
			}

			// Get the field label
            $label = $field->label;

            // Store the value here
            $value = '';
              
            // Consent fields
            if ( $field->type == 'consent' ) {

                // If they selected the consent checkbox
                if ( isset( $entry[ $field_id ] ) && $entry[ $field_id ] == 1 ) {
                    $value = 'True';
                }

			// Checkbox
            } elseif ( $field->type == 'checkbox' ) {
                
				// Get the choices
                $value = $this->get_gf_checkbox_values( $form, $entry, $field_id );
            
            // Radio/survey/select
            } elseif ( $field->type != 'quiz' && $field->choices && !empty( $field->choices ) ) {
                
				// Get the choices
                $choices = $field->choices;

                // Iter the choices
                foreach ( $choices as $choice ) {
                    
                    // Get the choice
                    if ( strpos( $entry[ $field_id ], $choice[ 'value' ] ) !== false ) {

                        // Get the value
                        $value = $choice[ 'text' ];
                    }
                }

			// Otherwise just return the field value    
            } elseif ( $field->type == 'name' ) {
                $value = $entry[ $field_id.'.3' ].' '.$entry[ $field_id.'.6' ];

            // Otherwise just return the field value    
            } elseif ( isset( $entry[ $field_id ] ) ) {
                $value = $entry[ $field_id ];
			}

			// Does the label end with ? or :
			if ( !str_ends_with( $label, '?' ) && !str_ends_with( $label, ':' ) ) {
				$label = $label.':';
			}

            // Should we add it?
			if ( !$hiding || ( $hiding && $value != '' ) ) {

				// Decode &amp;, etc
				$value = htmL_entity_decode( $value );

				// Add it
				$facts[] = [
					'name'   => $label,
					'value'  => $value,
				];
			}

            // Check if the field type is a survey
            if ( !$email && $field->type == 'email' && isset( $entry[ $field_id ] ) ) {
                $email = $entry[ $field_id ];
            }
        }

        // Check for a user id
        $user_id = $entry[ 'created_by' ];        

        // Did we not find an email?
        if ( ( !$email || $email == '' ) && $user_id > 0 ) {

            // Check if the user exists
            if ( $user = get_userdata( $user_id ) ) {

                // Get the email
                $email = $user->user_email;
            }
        }

		// Last resort user id
		if ( $email && $email != '' && ( !$user_id || $user_id == 0 || $user_id == '' ) ) {
			
			// Attempt to find user by email
			if ( $user = get_user_by( 'email', $email ) ) {
				
				// Set the user id
				$user_id = $user->ID;
			}
		}

        // Add the source url as a fact
		if ( isset( $feed[ 'meta' ][ 'source_url' ] ) && $feed[ 'meta' ][ 'source_url' ] ) {
			$facts[] = [
				'name'  => 'Source URL:',
				'value' => $entry[ 'source_url' ],
				'inline' => true
			];
		}

		// Add the user id as a fact
		if ( isset( $feed[ 'meta' ][ 'user_id' ] ) && $feed[ 'meta' ][ 'user_id' ] && $user_id ) {
			$facts[] = [
				'name'  => 'User ID:',
				'value' => $user_id,
				'inline' => true
			];
		}

		// Add the footer
		if ( isset( $feed[ 'meta' ][ 'footer' ] ) && !$feed[ 'meta' ][ 'footer' ] ) {
			$footer = false;
		} else {
			$footer = true;
		}

        // Put the message args together
        $args = [
			'user_id'  => $user_id,
            'email'    => $email,
			'webhook'  => $feed[ 'meta' ][ 'webhook' ],
			'date'     => $entry[ 'date_created' ],
			'footer'   => $footer
        ];

        // Send the message
        if ( $this->send_msg( $args, $facts, $form, $entry, $feed ) ) {

            // Return true
            return true;

        } else {

            // Return false
            return false;
        }
    } // End send_form_entry()


    /**
     * Send a message to MS Discord channel
     *
     * @return void
     */
    public function send_msg( $args, $facts, $form, $entry, $feed  ) {
		// Get the site name
		$get_site_name = sanitize_text_field( $this->get_plugin_setting( 'site_name' ) );
        if ( $get_site_name && $get_site_name != '' ) {
            $site_name = $get_site_name;
        } else {
            $site_name = get_bloginfo( 'name' ); // Blog Name
        }

        // Get the site logo
		$get_site_logo = esc_url_raw( $this->get_plugin_setting( 'site_logo' ) );
		if ( $get_site_logo && $get_site_logo != '' ) {
            $image = $get_site_logo;
        } else {
            $image = GFDISC_PLUGIN_DIR.'img/wordpress-logo.png'; // WP Logo
        }

		// Get the title
		$get_title = sanitize_text_field( $feed[ 'meta' ][ 'feedName' ] );
        if ( $get_title && $get_title != '' ) {
            $title = $get_title;
        } else {
            $title = 'New Form Entry';
        }

		// Get the webhook
		$get_webhook = $args[ 'webhook' ];
		if ( $get_webhook && $get_webhook != '' ) {
            $webhook = $get_webhook;
        } else {
            return false;
        }

		// Get the message
		$get_message = sanitize_textarea_field( $feed[ 'meta' ][ 'message' ] );
        if ( $get_message && $get_message != '' ) {
			$message = GFCommon::replace_variables( $get_message, $form, $entry, false, true, false, 'text' );

			// Allow mentioning users, roles and channels
			$message = preg_replace( '/\{\{@([0-9]*?)\}\}/', '<@$1>', $message );
			$message = preg_replace( '/\{\{@\&([0-9]*?)\}\}/', '<@&$1>', $message );
			$message = preg_replace( '/\{\{#([0-9]*?)\}\}/', '<#$1>', $message );
        } else {
            $message = '';
        }

        // Get the accent color
		$color = $this->sanitize_and_validate_color( $feed[ 'meta' ][ 'color' ], $this->default_accent_color );

		// Message data
        $data = [
            'tts' => false,
            // 'file' => '',
			// 'content' => esc_html( $args[ 'msg' ] ),
			// 'avatar_url' => esc_url( $args[ 'bot_avatar_url' ] ),
        ];

		// Override the bot name
		$get_bot_name = sanitize_text_field( $feed[ 'meta' ][ 'bot_name' ] );
        if ( $get_bot_name && $get_bot_name != '' ) {
            $data[ 'username' ] = $get_bot_name;
        }

        // Embed
        $data[ 'embeds' ] = apply_filters( 'gf_discord_embeds', [
			[
				'type'        => 'rich',
				'color'       => hexdec( $color ),
				'author'      => [
					'name'       => $site_name,
					'url'        => home_url()
				],
				'title'       => $title,
				'url'         => admin_url( 'admin.php?page=gf_entries&view=entry&id='.$form[ 'id' ].'&lid='.$entry[ 'id' ] ),
				'description' => $message,
				'fields'      => $facts,
				'image'       => [
					'url'        => ''
				],
				'thumbnail'   => [
					'url'        => $image
				]
			]
		], $form, $entry );

		// Get the footer
		$has_footer = $args[ 'footer' ];
        if ( $has_footer ) {

			// Get the footer text
			$get_footer_text = sanitize_text_field( $this->get_plugin_setting( 'footer_text' ) );
			if ( $get_footer_text && $get_footer_text != '' ) {
				$footer_text = $get_footer_text;
			} else {
				$footer_text = home_url(); // URL
			}

			// Add the footer
			$data[ 'embeds' ][0][ 'footer' ] = [
				'text'       => $footer_text,
				'icon_url'   => GFDISC_PLUGIN_DIR.'img/wordpress-logo.png'
			];

			// Add the timestamp, that shows up in the footer
			$data[ 'embeds' ][0][ 'timestamp' ] = $this->convert_timezone( date( 'Y-m-d H:i:s', strtotime( $args[ 'date' ] ) ), 'c' );
        }

        // Encode
        $json_data = json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

		// Remote post options
        $options = [
            'body'        => $json_data,
            'headers'     => [
                'Content-Type' => 'application/json',
            ],
            'timeout'     => 60,
            'redirection' => 5,
            'blocking'    => true,
            'httpversion' => '1.0',
            'sslverify'   => false,
            'data_format' => 'body',
        ];
        
		// Send the message
        $response = wp_remote_post( $webhook, $options );

		// Did we get a response?
		if ( $response ) {
            return true;
        } else {
            return false;
        }
    } // End send_msg()


	// # HELPERS -------------------------------------------------------------------------------------------------------


	/**
     * Get Gravity Form checkbox values
     *
     * @param array $form
     * @param array $entry
     * @param int|float $field_id
     * @return mixed
     */
    public static function get_gf_checkbox_values( $form, $entry, $field_id ) {
        $field = GFAPI::get_field( $form, $field_id );
        return $field->get_value_export( $entry );
    } // End get_gf_checkbox_values()


	/**
	 * Sanitize image
	 *
	 * @param string $input
	 * @return string
	 */
	public function validate_image( $image_url ) {
		// Default output
		$output = '';
	 
		// Check file type
		$filetype = wp_check_filetype( $image_url );
		$mime_type = $filetype[ 'type' ];
	 
		// Only mime type "image" allowed
		if ( strpos( $mime_type, 'image' ) !== false ){
			$output = $image_url;
		}
	 
		// Return the output
		return esc_url_raw( $output );
	} // End validate_image()


	/**
	 * Get merge fields for list.
	 *
	 * @return array
	 */
	public function get_list_merge_fields() {
		// Our mapped fields
		$fields = [
			'email'  => [
				'type' 		  => 'email',
				'name'		  => 'Email',
				'required'	  => false,
			],
		];

		// Return the fields
		return $fields;
	} // End get_list_merge_fields()


	/**
	 * Get the list of form fields to include as facts
	 *
	 * @param array $form
	 * @return array
	 */
	public function get_list_facts( $form ) {
		// Store the messsage facts
        $facts = [];

        // Iter the fields
        foreach ( $form[ 'fields' ] as $field ) {
    
            // Skip HTML fields
            if ( $field->type == 'html' || $field->type == 'section' ) {
                continue;
            }

			// Get the field label
            $label = $field->label;

            // Get the field ID
            $field_id = $field->id;

            // Add the fact
            $facts[] = [
                'label' 		=> $label,
                'name'  		=> $field_id,
				'default_value' => true,
            ];
        }

		// Add the user id
		$facts[] = [
			'label' 		=> 'User ID',
        	'name' 			=> 'user_id',
			'default_value' => true,
		];

		// Add the source
		$facts[] = [
			'label' 		=> 'Source URL',
        	'name' 			=> 'source_url',
			'default_value' => true,
		];

		// Add the footer
		$facts[] = [
			'label' 		=> 'Footer',
        	'name' 			=> 'footer',
			'default_value' => true,
		];

		// Return the array
		return $facts;
	} // End get_list_facts()


	/**
	 * Convert timezone
	 * 
	 * @param string $date
	 * @param string $format
	 * @param string|null $timezone
	 * 
	 * @return string
	 */
	public function convert_timezone( $date, $format = 'F j, Y g:i A', $timezone = null ) {
		// Get the date
		$date = new DateTime( $date, new DateTimeZone( 'UTC' ) );

		// Get the timezone string
		if ( !is_null( $timezone ) ) {
			$timezone_string = $timezone;
		} else {
			$timezone_string = wp_timezone_string();
		}

		// Set the timezone
		$date->setTimezone( new DateTimeZone( $timezone_string ) );

		// Format
		$new_date = $date->format( $format );

		// Return the new date/time
		return $new_date;
	} // End convert_timezone()


	/**
	 * Remove query strings from url without refresh
	 * 
	 * @param string
	 * @return string
	 */
	public function redirect_without_qs( $qs ) {
		// Get the current url without the query string
		$new_url = home_url( remove_query_arg( $qs ) );

		// Redirect
		wp_safe_redirect( $new_url );
		exit();
	} // End remove_qs_without_refresh()


	/**
	 * Sanitize a hex color and force hash
	 *
	 * @param string $color
	 * @param string $default
	 * @return string|void
	 */
	public function sanitize_and_validate_color( $color, $default ) {
		// Check if color exists and if it's still not blank after sanitation
		if ( $color && ( sanitize_hex_color( $color ) != '' || sanitize_hex_color_no_hash( $color ) != '' ) ) {
			
			// If it has hash
			if ( str_starts_with( $color, '#' ) ) {
				$color = sanitize_hex_color( $color );

			// If it does not have hash
			} else {
				$color = sanitize_hex_color_no_hash( $color );
			}

		// Otherwise return the sanitized default
		} else {
			$color = sanitize_hex_color( $default );
		}

		// Remove hashtag
		$color = ltrim( $color, '\#' );

		// Return the color
		return $color;
	} // End sanitize_and_validate_color()
}