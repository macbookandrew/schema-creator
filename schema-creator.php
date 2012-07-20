<?php
/*
Plugin Name: Schema Creator
Plugin URI: http://andrewnorcross.com/plugins/schema-creator/
Description: Insert schema data into posts
Version: 0.1
Author: norcross
Author URI: http://andrewnorcross.com/
License: GPL v2



	Resources

	http://schema_creator.org/
	http://foolip.org/microdatajs/live/
	
*/


class ravenSchema
{

	/**
	 * This is our constructor, which is private to force the use of
	 * getInstance() to make this a Singleton
	 *
	 * @return ravenSchema
	 */
	public function __construct() {
		add_action					( 'admin_menu',				array( $this, 'schema_settings'	) );
		add_action					( 'admin_init', 			array( $this, 'reg_settings'	) );
		add_shortcode				( 'schema',					array( $this, 'shortcode'		) );
		add_action					( 'wp_enqueue_scripts',		array( $this, 'front_scripts'	) );
		add_action					( 'admin_enqueue_scripts',	array( $this, 'post_scripts'	) );
		add_action					( 'admin_enqueue_scripts',	array( $this, 'admin_scripts'	) );		
		add_filter					( 'media_buttons_context',	array( $this, 'media_button'	) );
		add_action					( 'admin_footer',			array( $this, 'schema_form'		) );
		add_action					( 'the_posts', 				array( $this, 'schema_loader'	) );
		add_filter					( 'the_content',			array( $this, 'schema_wrapper'	) );
		add_filter					( 'admin_footer_text',		array( $this, 'schema_footer'	) );
	}


	/**
	 * build out settings page
	 *
	 * @return ravenSchema
	 */


	public function schema_settings() {
	    add_submenu_page('options-general.php', 'Schema Creator', 'Schema Creator', 'edit_posts', 'schema-creator', array( $this, 'schema_creator_display' ));
	}

	/**
	 * Register settings
	 *
	 * @return ravenSchema
	 */


	public function reg_settings() {
		register_setting( 'schema_options', 'schema_options');		

	}

	/**
	 * Content for pop-up tooltips
	 *
	 * @since 1.0
	 */

	private $tooltip = array (
		"default_css"	=> "<h5 style='font-size:16px;margin:0 0 5px;text-align:right;'>Default CSS</h5><p style='font-size:13px;line-height:16px;margin:0 0 5px;'>Selecting this option will load a CSS file on any post / page that has a schema builder shortcode in it.</p>",
		"pending_tip"	=> "<h5 style='font-size:16px;margin:0 0 5px;text-align:right;'>Pending</h5><p style='font-size:13px;line-height:16px;margin:0 0 5px;'>This fancy little box will have helpful information in it soon.</p>",


		// end tooltip content
	);

	/**
	 * Display main options page structure
	 *
	 * @return ravenSchema
	 */
	 
	public function schema_creator_display() { ?>
	
		<div class="wrap">
    	<div class="icon32" id="icon-schema"><br></div>
		<h2>Schema Creator Settings</h2>
        
	        <div class="schema_options">
            	<div class="schema_form_text">
            	<p>My guess is that we'll have some sort of intro here about what schema is, where to learn more, etc.</p>
                </div>
                
                <div class="schema_form_options">
	            <form method="post" action="options.php">
			    <?php
                settings_fields( 'schema_options' );
				$schema_options	= get_option('schema_options');

				$css_show	= (isset($schema_options['css']) && $schema_options['css'] == 'true' ? 'checked="checked"' : '');
				$body_tag	= (isset($schema_options['body']) && $schema_options['body'] == 'true' ? 'checked="checked"' : '');
				$post_tag	= (isset($schema_options['post']) && $schema_options['post'] == 'true' ? 'checked="checked"' : '');								
				?>
        
				<p>
                <label for="schema_options[css]"><input type="checkbox" id="schema_css" name="schema_options[css]" class="schema_checkbox" value="true" <?php echo $css_show; ?>/> Exclude default CSS for schema output</label>
                <span class="ap_tooltip" tooltip="<?php echo $this->tooltip['default_css']; ?>">(?)</span>
                </p>

				<p>
                <label for="schema_options[body]"><input type="checkbox" id="schema_body" name="schema_options[body]" class="schema_checkbox" value="true" <?php echo $body_tag; ?> /> Apply itemprop & itemtype to main body tag</label>
                <span class="ap_tooltip" tooltip="<?php echo $this->tooltip['pending_tip']; ?>">(?)</span>
                </p>

				<p>
                <label for="schema_options[post]"><input type="checkbox" id="schema_post" name="schema_options[post]" class="schema_checkbox" value="true" <?php echo $post_tag; ?> /> Apply itemscope & itemtype to content wrapper</label>
                <span class="ap_tooltip" tooltip="<?php echo $this->tooltip['pending_tip']; ?>">(?)</span>
                </p>                
    
	    		<p class="submit"><input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" /></p>
				</form>
                </div>
    
            </div>

        </div>    
	
	<?php }
		

	/**
	 * load scripts adn style for post or page editor
	 *
	 * @return ravenSchema
	 */


	public function post_scripts($hook) {
		if ( $hook == 'post-new.php' || $hook == 'post.php' ) {
			wp_enqueue_style( 'schema-admin', plugins_url('/lib/css/schema-admin.css', __FILE__) );
			wp_enqueue_script( 'jquery-ui-core');
			wp_enqueue_script( 'jquery-ui-datepicker');
			wp_enqueue_script( 'jquery-ui-slider');
			wp_enqueue_script( 'jquery-timepicker', plugins_url('/lib/js/jquery.timepicker.js', __FILE__) , array('jquery'), null, true );
//			wp_enqueue_script( 'jquery-timeslider', plugins_url('/lib/js/timepicker.slider.js', __FILE__) , array('jquery'), null, true );
			wp_enqueue_script( 'format-currency', plugins_url('/lib/js/jquery.currency.min.js', __FILE__) , array('jquery'), null, true );
			wp_enqueue_script( 'schema-form', plugins_url('/lib/js/schema.form.init.js', __FILE__) , array('jquery'), null, true );
		}
	}

	/**
	 * load scripts and style for admin settings page
	 *
	 * @return ravenSchema
	 */


	public function admin_scripts() {
		$current_screen = get_current_screen();
		if ( 'settings_page_schema-creator' == $current_screen->base ) {
			wp_enqueue_style( 'schema-admin', plugins_url('/lib/css/schema-admin.css', __FILE__) );
			wp_enqueue_script( 'jquery-qtip', plugins_url('/lib/js/jquery.qtip.min.js', __FILE__) , array('jquery'), null, true );			
			wp_enqueue_script( 'schema-admin', plugins_url('/lib/js/schema.admin.init.js', __FILE__) , array('jquery'), null, true );
		}
	}


	/**
	 * add attribution link to settings page
	 *
	 * @return ravenSchema
	 */

	public function schema_footer($text) {
		$current_screen = get_current_screen();
		if ( 'settings_page_schema-creator' == $current_screen->base )
			$text = '<span id="footer-thankyou">This plugin brought to you by the fine folks at <a title="Internet Marketing Tools for SEO and Social Media" target="_blank" href="http://raventools.com/">Raven Tools</a>.</span>';

		if ( 'settings_page_schema-creator' !== $current_screen->base )
			$text = '<span id="footer-thankyou">Thank you for creating with <a href="http://wordpress.org/">WordPress</a>.</span>';

		return $text;
	}

	/**
	 * load scripts for front end
	 *
	 * @return ravenSchema
	 */


	public function front_scripts() {
		$schema_options = get_option('schema_options');
		if(isset($schema_options['body']) && $schema_options['body'] == 'true' )
			wp_enqueue_script( 'schema-init', plugins_url('/lib/js/schema.init.js', __FILE__) , array('jquery'), null, true );

	}

	/**
	 * load front-end CSS
	 *
	 * @return ravenSchema
	 */


	public function schema_loader($posts) {
		
		$schema_options = get_option('schema_options');

		if(isset($schema_options['css']) && $schema_options['css'] == 'true' )
			return $posts;		
		
		if ( empty($posts) )
			return $posts;
		
		// false because we have to search through the posts first
		$found = false;
		 
		// search through each post
		foreach ($posts as $post) {
			$meta_check	= get_post_meta($post->ID, '_raven_schema_load', true);
			// check the post content for the short code
			$content	= $post->post_content;
			if ( preg_match('/schema(.*)/', $content) )
				// we have found a post with the short code
				$found = true;
				// stop the search
				break;
			}
		 
			if ($found == true )
				wp_enqueue_style( 'schema-style', plugins_url('/lib/css/schema-style.css', __FILE__) );
		
			if (empty($meta_check) && $found == true )
				update_post_meta($post->ID, '_raven_schema_load', 'true');

			if ($found == false )
				delete_post_meta($post->ID, '_raven_schema_load');

			return $posts;
		}

	/**
	 * wrap content in markup
	 *
	 * @return ravenSchema
	 */

	public function schema_wrapper($content) {

		$schema_options = get_option('schema_options');

		if(isset($schema_options['post']) && $schema_options['post'] == 'true' )
			return $content;

        $content = '<div itemscope itemtype="http://schema.org/BlogPosting">'.$content.'</div>';
		
    // Returns the content.
    return $content;		
		
	}

	/**
	 * set CSS value at activation
	 *
	 * @return ravenSchema
	 */


	public function schema_setup() {

		update_option('schema_css', 'true');

	}

	/**
	 * Build out shortcode with variable array of options
	 *
	 * @return ravenSchema
	 */

	public function shortcode( $atts, $content = null ) {
		extract( shortcode_atts( array(
			'type'				=> '',
			'evtype'			=> '',
			'orgtype'			=> '',
			'name'				=> '',
			'orgname'			=> '',
			'jobtitle'			=> '',
			'url'				=> '',
			'description'		=> '',
			'bday'				=> '',
			'street'			=> '',
			'pobox'				=> '',
			'city'				=> '',
			'state'				=> '',
			'postalcode'		=> '',
			'country'			=> '',
			'email'				=> '',		
			'phone'				=> '',
			'brand'				=> '',
			'manfu'				=> '',
			'model'				=> '',
			'single_rating'		=> '',
			'agg_rating'		=> '',
			'prod_id'			=> '',
			'price'				=> '',
			'condition'			=> '',
			'sdate'				=> '',
			'stime'				=> '',
			'edate'				=> '',
			'duration'			=> '',
			'director'			=> '',
			'producer'			=> '',		
			'actor_1'			=> '',
			'author'			=> '',
			'publisher'			=> '',
			'pubdate'			=> '',
			'edition'			=> '',
			'isbn'				=> '',
			'ebook'				=> '',
			'paperback'			=> '',
			'hardcover'			=> '',
			
		), $atts ) );
		
		// create array of actor fields	
		$actors = array();
		foreach ( $atts as $key => $value ) {
			if ( strpos( $key , 'actor' ) === 0 )
				$actors[] = $value;
		}

		// wrap schema build out
		$sc_build = '<div id="schema_block" class="schema_'.$type.'">';
		
		// person 
		if(isset($type) && $type == 'person') {
		
		$sc_build .= '<div itemscope itemtype="http://schema.org/Person">';
		
			if(!empty($name) && !empty($url) ) {
				$sc_build .= '<a class="schema_url" target="_blank" itemprop="url" href="'.esc_url($url).'">';
				$sc_build .= '<div class="schema_name" itemprop="name">'.$name.'</div>';
				$sc_build .= '</a>';
			}

			if(!empty($name) && empty($url) )
				$sc_build .= '<div class="schema_name" itemprop="name">'.$name.'</div>';

			if(!empty($orgname)) {
				$sc_build .= '<div itemscope itemtype="http://schema.org/Organization">';
				$sc_build .= '<span class="schema_orgname" itemprop="name">'.$orgname.'</span>';
				$sc_build .= '</div>';
			}
			
			if(!empty($jobtitle))
				$sc_build .= '<div class="schema_jobtitle" itemprop="jobtitle">'.$jobtitle.'</div>';

			if(!empty($description))
				$sc_build .= '<div class="schema_description" itemprop="description">'.esc_textarea($description).'</div>';

			if(	!empty($street) ||
				!empty($pobox) ||
				!empty($city) ||
				!empty($state) ||
				!empty($postalcode) ||
				!empty($country)
				)
				$sc_build .= '<div itemprop="address" itemscope itemtype="http://schema.org/PostalAddress">';

			if(!empty($street))
				$sc_build .= '<div class="street" itemprop="streetAddress">'.$street.'</div>';
			
			if(!empty($pobox))
				$sc_build .= '<div class="pobox">P.O. Box: <span itemprop="postOfficeBoxNumber">'.$pobox.'</span></div>';

			if(!empty($city) && !empty($state)) {
				$sc_build .= '<div class="city_state">';
				$sc_build .= '<span class="locale" itemprop="addressLocality">'.$city.'</span>,';
				$sc_build .= '<span class="region" itemprop="addressRegion">'.$state.'</span>';
				$sc_build .= '</div>';
			}

				// secondary check if one part of city / state is missing to keep markup consistent
				if(empty($state) && !empty($city) )
					$sc_build .= '<div class="city_state"><span class="locale" itemprop="addressLocality">'.$city.'</span></div>';
					
				if(empty($city) && !empty($state) )
					$sc_build .= '<div class="city_state"><span class="region" itemprop="addressRegion">'.$state.'</span></div>';

			if(!empty($postalcode))
				$sc_build .= '<div class="postalcode" itemprop="postalCode">'.$postalcode.'</div>';

			if(!empty($country))
				$sc_build .= '<div class="country" itemprop="addressCountry">'.$country.'</div>';

			if(	!empty($street) ||
				!empty($pobox) ||
				!empty($city) ||
				!empty($state) ||
				!empty($postalcode) ||
				!empty($country)
				)
				$sc_build .= '</div>';

			if(!empty($email))
				$sc_build .= '<div class="email" itemprop="email">'.antispambot($email).'</div>';

			if(!empty($phone))
				$sc_build .= '<div class="phone" itemprop="telephone">'.$phone.'</div>';

			if(!empty($bday))
				$sc_build .= '<div class="bday"><meta itemprop="birthDate" content="'.$bday.'">DOB: '.date('m/d/Y', strtotime($bday)).'</div>';
	
			// close it up
			$sc_build .= '</div>';

		}

		// product 
		if(isset($type) && $type == 'product') {
		
		$sc_build .= '<div itemscope itemtype="http://schema.org/Product">';
		
			if(!empty($name) && !empty($url) ) {
				$sc_build .= '<a class="schema_url" target="_blank" itemprop="url" href="'.esc_url($url).'">';
				$sc_build .= '<div class="schema_name" itemprop="name">'.$name.'</div>';
				$sc_build .= '</a>';
			}

			if(!empty($name) && empty($url) )
				$sc_build .= '<div class="schema_name" itemprop="name">'.$name.'</div>';

			if(!empty($description))
				$sc_build .= '<div class="schema_description" itemprop="description">'.esc_textarea($description).'</div>';

			if(!empty($brand))
				$sc_build .= '<div class="brand" itemprop="brand" itemscope itemtype="http://schema.org/Organization"><span class="desc_type">Brand:</span> <span itemprop="name">'.$brand.'</span></div>';

			if(!empty($manfu))
				$sc_build .= '<div class="manufacturer" itemprop="manufacturer" itemscope itemtype="http://schema.org/Organization"><span class="desc_type">Manufacturer:</span> <span itemprop="name">'.$manfu.'</span></div>';

			if(!empty($model))
				$sc_build .= '<div class="model"><span class="desc_type">Model:</span> <span itemprop="model">'.$model.'</span></div>';

			if(!empty($prod_id))
				$sc_build .= '<div class="prod_id"><span class="desc_type">Product ID:</span> <span itemprop="productID">'.$prod_id.'</span></div>';

			if(!empty($single_rating) && !empty($agg_rating)) {
				$sc_build .= '<div itemprop="aggregateRating" itemscope itemtype="http://schema.org/AggregateRating">';
				$sc_build .= '<span itemprop="ratingValue">'.$single_rating.'</span> based on ';
				$sc_build .= '<span itemprop="reviewCount">'.$agg_rating.'</span> reviews';
				$sc_build .= '</div>';
			}

				// secondary check if one part of review is missing to keep markup consistent
				if(empty($agg_rating) && !empty($single_rating) )
					$sc_build .= '<div itemprop="aggregateRating" itemscope itemtype="http://schema.org/AggregateRating"><span itemprop="ratingValue"><span class="desc_type">Review:</span> '.$single_rating.'</span></div>';
					
				if(empty($single_rating) && !empty($agg_rating) )
					$sc_build .= '<div itemprop="aggregateRating" itemscope itemtype="http://schema.org/AggregateRating"><span itemprop="reviewCount">'.$agg_rating.'</span> total reviews</div>';

			if(!empty($price) && !empty($condition)) {
				$sc_build .= '<div class="offers" itemprop="offers" itemscope itemtype="http://schema.org/Offer">';
				$sc_build .= '<span class="price" itemprop="price">'.$price.'</span>';
				$sc_build .= '<link itemprop="itemCondition" href="http://schema.org/'.$condition.'Condition" /> '.$condition.'';
				$sc_build .= '</div>';
			}

			if(empty($condition) && !empty ($price))
				$sc_build .= '<div class="offers" itemprop="offers" itemscope itemtype="http://schema.org/Offer"><span class="price" itemprop="price">'.$price.'</span></div>';

	
			// close it up
			$sc_build .= '</div>';

		}
		
		// event
		if(isset($type) && $type == 'event') {
		
		$default   = (!empty($evtype) ? $evtype : 'Event');
		$sc_build .= '<div itemscope itemtype="http://schema.org/'.$default.'">';

			if(!empty($name) && !empty($url) ) {
				$sc_build .= '<a class="schema_url" target="_blank" itemprop="url" href="'.esc_url($url).'">';
				$sc_build .= '<div class="schema_name" itemprop="name">'.$name.'</div>';
				$sc_build .= '</a>';
			}

			if(!empty($name) && empty($url) )
				$sc_build .= '<div class="schema_name" itemprop="name">'.$name.'</div>';

			if(!empty($description))
				$sc_build .= '<div class="schema_description" itemprop="description">'.esc_textarea($description).'</div>';

			if(!empty($sdate) && !empty($stime) ) {
				$metatime = $sdate.'T'.date('G:i', strtotime($sdate.$stime));
				$sc_build .= '<div><meta itemprop="startDate" content="'.$metatime.'">Starts: '.date('m/d/Y', strtotime($sdate)).' '.$stime.'</div>';
			}
				// secondary check for missing start time
				if(empty($stime) && !empty($sdate) )
					$sc_build .= '<div><meta itemprop="startDate" content="'.$sdate.'">Starts: '.date('m/d/Y', strtotime($sdate)).'</div>';

			if(!empty($edate))
				$sc_build .= '<div><meta itemprop="endDate" content="'.$edate.':00.000">Ends: '.date('m/d/Y', strtotime($edate)).'</div>';

			if(!empty($duration)) {
					
				$hour_cnv	= date('G', strtotime($duration));
				$mins_cnv	= date('i', strtotime($duration));
				
				$hours		= (!empty($hour_cnv) && $hour_cnv > 0 ? $hour_cnv.' hours' : '');
				$minutes	= (!empty($mins_cnv) && $mins_cnv > 0 ? ' and '.$mins_cnv.' minutes' : '');
				
				$sc_build .= '<div><meta itemprop="duration" content="0000-00-00T'.$duration.'">Duration: '.$hours.$minutes.'</div>';
			}

			// close actual event portion
			$sc_build .= '</div>';
				
			if(	!empty($street) ||
				!empty($pobox) ||
				!empty($city) ||
				!empty($state) ||
				!empty($postalcode) ||
				!empty($country)
				)
				$sc_build .= '<div itemprop="address" itemscope itemtype="http://schema.org/PostalAddress">';

			if(!empty($street))
				$sc_build .= '<div class="street" itemprop="streetAddress">'.$street.'</div>';
			
			if(!empty($pobox))
				$sc_build .= '<div class="pobox">P.O. Box: <span itemprop="postOfficeBoxNumber">'.$pobox.'</span></div>';

			if(!empty($city) && !empty($state)) {
				$sc_build .= '<div class="city_state">';
				$sc_build .= '<span class="locale" itemprop="addressLocality">'.$city.'</span>,';
				$sc_build .= '<span class="region" itemprop="addressRegion"> '.$state.'</span>';
				$sc_build .= '</div>';
			}

				// secondary check if one part of city / state is missing to keep markup consistent
				if(empty($state) && !empty($city) )
					$sc_build .= '<div class="city_state"><span class="locale" itemprop="addressLocality">'.$city.'</span></div>';
					
				if(empty($city) && !empty($state) )
					$sc_build .= '<div class="city_state"><span class="region" itemprop="addressRegion">'.$state.'</span></div>';

			if(!empty($postalcode))
				$sc_build .= '<div class="postalcode" itemprop="postalCode">'.$postalcode.'</div>';

			if(!empty($country))
				$sc_build .= '<div class="country" itemprop="addressCountry">'.$country.'</div>';

			if(	!empty($street) ||
				!empty($pobox) ||
				!empty($city) ||
				!empty($state) ||
				!empty($postalcode) ||
				!empty($country)
				)
				$sc_build .= '</div>';
				
		}

		// organization
		if(isset($type) && $type == 'organization') {

		$default   = (!empty($orgtype) ? $orgtype : 'Organization');
		$sc_build .= '<div itemscope itemtype="http://schema.org/'.$default.'">';

			if(!empty($name) && !empty($url) ) {
				$sc_build .= '<a class="schema_url" target="_blank" itemprop="url" href="'.esc_url($url).'">';
				$sc_build .= '<div class="schema_name" itemprop="name">'.$name.'</div>';
				$sc_build .= '</a>';
			}

			if(!empty($name) && empty($url) )
				$sc_build .= '<div class="schema_name" itemprop="name">'.$name.'</div>';

			if(!empty($description))
				$sc_build .= '<div class="schema_description" itemprop="description">'.esc_textarea($description).'</div>';

			if(	!empty($street) ||
				!empty($pobox) ||
				!empty($city) ||
				!empty($state) ||
				!empty($postalcode) ||
				!empty($country)
				)
				$sc_build .= '<div itemprop="address" itemscope itemtype="http://schema.org/PostalAddress">';

			if(!empty($street))
				$sc_build .= '<div class="street" itemprop="streetAddress">'.$street.'</div>';
			
			if(!empty($pobox))
				$sc_build .= '<div class="pobox">P.O. Box: <span itemprop="postOfficeBoxNumber">'.$pobox.'</span></div>';

			if(!empty($city) && !empty($state)) {
				$sc_build .= '<div class="city_state">';
				$sc_build .= '<span class="locale" itemprop="addressLocality">'.$city.'</span>,';
				$sc_build .= '<span class="region" itemprop="addressRegion"> '.$state.'</span>';
				$sc_build .= '</div>';
			}

				// secondary check if one part of city / state is missing to keep markup consistent
				if(empty($state) && !empty($city) )
					$sc_build .= '<div class="city_state"><span class="locale" itemprop="addressLocality">'.$city.'</span></div>';
					
				if(empty($city) && !empty($state) )
					$sc_build .= '<div class="city_state"><span class="region" itemprop="addressRegion">'.$state.'</span></div>';

			if(!empty($postalcode))
				$sc_build .= '<div class="postalcode" itemprop="postalCode">'.$postalcode.'</div>';

			if(!empty($country))
				$sc_build .= '<div class="country" itemprop="addressCountry">'.$country.'</div>';

			if(	!empty($street) ||
				!empty($pobox) ||
				!empty($city) ||
				!empty($state) ||
				!empty($postalcode) ||
				!empty($country)
				)
				$sc_build .= '</div>';

			// close it up
			$sc_build .= '</div>';
			
		}

		// movie 
		if(isset($type) && $type == 'movie') {
		
		$sc_build .= '<div itemscope itemtype="http://schema.org/Movie">';
		
			if(!empty($name) && !empty($url) ) {
				$sc_build .= '<a class="schema_url" target="_blank" itemprop="url" href="'.esc_url($url).'">';
				$sc_build .= '<div class="schema_name" itemprop="name">'.$name.'</div>';
				$sc_build .= '</a>';
			}

			if(!empty($name) && empty($url) )
				$sc_build .= '<div class="schema_name" itemprop="name">'.$name.'</div>';

			if(!empty($description))
				$sc_build .= '<div class="schema_description" itemprop="description">'.esc_textarea($description).'</div>';


			if(!empty($director)) 
				$sc_build .= '<div itemprop="director" itemscope itemtype="http://schema.org/Person">Directed by: <span itemprop="name">'.$director.'</span></div>';

			if(!empty($producer)) 
				$sc_build .= '<div itemprop="producer" itemscope itemtype="http://schema.org/Person">Produced by: <span itemprop="name">'.$producer.'</span></div>';

			if(!empty($actor_1)) {
				$sc_build .= '<div>Starring:';
					foreach ($actors as $actor) {
						$sc_build .= '<div itemprop="actors" itemscope itemtype="http://schema.org/Person">';
						$sc_build .= '<span itemprop="name">'.$actor.'</span>';
						$sc_build .= '</div>';
					}
				$sc_build .= '</div>';			
			}

	
			// close it up
			$sc_build .= '</div>';

		}

		// book 
		if(isset($type) && $type == 'book') {
		
		$sc_build .= '<div itemscope itemtype="http://schema.org/Book">';
		
			if(!empty($name) && !empty($url) ) {
				$sc_build .= '<a class="schema_url" target="_blank" itemprop="url" href="'.esc_url($url).'">';
				$sc_build .= '<div class="schema_name" itemprop="name">'.$name.'</div>';
				$sc_build .= '</a>';
			}

			if(!empty($name) && empty($url) )
				$sc_build .= '<div class="schema_name" itemprop="name">'.$name.'</div>';

			if(!empty($description))
				$sc_build .= '<div class="schema_description" itemprop="description">'.esc_textarea($description).'</div>';

			if(!empty($author)) 
				$sc_build .= '<div itemprop="author" itemscope itemtype="http://schema.org/Person">Written by: <span itemprop="name">'.$author.'</span></div>';

			if(!empty($publisher)) 
				$sc_build .= '<div itemprop="publisher" itemscope itemtype="http://schema.org/Organization">Published by: <span itemprop="name">'.$publisher.'</span></div>';

			if(!empty($pubdate))
				$sc_build .= '<div class="bday"><meta itemprop="datePublished" content="'.$pubdate.'">Date Published: '.date('m/d/Y', strtotime($pubdate)).'</div>';

			if(!empty($edition)) 
				$sc_build .= '<div>Edition: <span itemprop="bookEdition">'.$edition.'</span></div>';

			if(!empty($isbn)) 
				$sc_build .= '<div>ISBN: <span itemprop="isbn">'.$isbn.'</span></div>';

			if( !empty($ebook) || !empty($paperback) || !empty($hardcover) ) { 
				$sc_build .= '<div>Available in: ';

					if(!empty($ebook)) 
						$sc_build .= '<link itemprop="bookFormat" href="http://schema.org/Ebook">Ebook ';
	
					if(!empty($paperback)) 
						$sc_build .= '<link itemprop="bookFormat" href="http://schema.org/Paperback">Paperback ';
	
					if(!empty($hardcover)) 
						$sc_build .= '<link itemprop="bookFormat" href="http://schema.org/Hardcover">Hardcover ';

				$sc_build .= '</div>';
			}
			

			// close it up
			$sc_build .= '</div>';

		}

		
		// close schema wrap
		$sc_build .= '</div>';

	// return entire build array
	return $sc_build;
	
	}

	/**
	 * Add button to top level media row
	 *
	 * @return ravenSchema
	 */

	public function media_button($context) {
		
		// don't display button for users who don't have access
		if ( ! current_user_can('edit_posts') && ! current_user_can('edit_pages') )
			return;
		
		$button = '<a href="#TB_inline?width=650&inlineId=schema_build_form" class="thickbox schema_clear" id="add_schema" title="' . __('Schema Creator Form') . '">' . __('Schema Creator Form') . '</a>';

	return $context . $button;
}

	/**
	 * Build form and add into footer
	 *
	 * @return ravenSchema
	 */

	public function schema_form() { 
		
		// don't display form for users who don't have access
		if ( ! current_user_can('edit_posts') && ! current_user_can('edit_pages') )
		return;

	?>
	
		<script type="text/javascript">
			function InsertSchema() {
					var type			= jQuery('#schema_builder select#schema_type').val();
					var evtype			= jQuery('#schema_builder select#schema_evtype').val();
					var orgtype			= jQuery('#schema_builder select#schema_orgtype').val();
					var name			= jQuery('#schema_builder input#schema_name').val();
					var orgname			= jQuery('#schema_builder input#schema_orgname').val();
					var jobtitle		= jQuery('#schema_builder input#schema_jobtitle').val();
					var url				= jQuery('#schema_builder input#schema_url').val();
					var description		= jQuery('#schema_builder textarea#schema_description').val();
					var bday			= jQuery('#schema_builder input#schema_bday-format').val();
					var street			= jQuery('#schema_builder input#schema_street').val();
					var pobox			= jQuery('#schema_builder input#schema_pobox').val();
					var city			= jQuery('#schema_builder input#schema_city').val();
					var state			= jQuery('#schema_builder input#schema_state').val();
					var postalcode		= jQuery('#schema_builder input#schema_postalcode').val();
					var country			= jQuery('#schema_builder input#schema_country').val();
					var email			= jQuery('#schema_builder input#schema_email').val();
					var phone			= jQuery('#schema_builder input#schema_phone').val();
					var brand			= jQuery('#schema_builder input#schema_brand').val();
					var manfu			= jQuery('#schema_builder input#schema_manfu').val();
					var model			= jQuery('#schema_builder input#schema_model').val();
					var prod_id			= jQuery('#schema_builder input#schema_prod_id').val();
					var single_rating	= jQuery('#schema_builder input#schema_single_rating').val();
					var agg_rating		= jQuery('#schema_builder input#schema_agg_rating').val();
					var price			= jQuery('#schema_builder input#schema_price').val();
					var condition		= jQuery('#schema_builder select#schema_condition').val();
					var sdate			= jQuery('#schema_builder input#schema_sdate-format').val();
					var stime			= jQuery('#schema_builder input#schema_stime').val();
					var edate			= jQuery('#schema_builder input#schema_edate-format').val();
					var duration		= jQuery('#schema_builder input#schema_duration').val();
					var actor_group		= jQuery('#schema_builder input#schema_actor_1').val();
					var director		= jQuery('#schema_builder input#schema_director').val();
					var producer		= jQuery('#schema_builder input#schema_producer').val();
					var author			= jQuery('#schema_builder input#schema_author').val();
					var publisher		= jQuery('#schema_builder input#schema_publisher').val();
					var edition			= jQuery('#schema_builder input#schema_edition').val();
					var isbn			= jQuery('#schema_builder input#schema_isbn').val();
					var pubdate			= jQuery('#schema_builder input#schema_pubdate-format').val();
					var ebook			= jQuery('#schema_builder input#schema_ebook').is(':checked');
					var paperback		= jQuery('#schema_builder input#schema_paperback').is(':checked');
					var hardcover		= jQuery('#schema_builder input#schema_hardcover').is(':checked');
					
			// output setups
			output = '[schema ';
				output += 'type="' + type + '" ';

				// person
				if(type == 'person' ) {
					if(name)
						output += ' name="' + name + '" ';
					if(orgname)
						output += ' orgname="' + orgname + '" ';
					if(jobtitle)
						output += ' jobtitle="' + jobtitle + '" ';
					if(url)
						output += ' url="' + url + '" ';
					if(description)
						output += ' description="' + description + '" ';
					if(bday)
						output += ' bday="' + bday + '" ';
					if(street)
						output += ' street="' + street + '" ';
					if(pobox)
						output += ' pobox="' + pobox + '" ';
					if(city)
						output += ' city="' + city + '" ';
					if(state)
						output += ' state="' + state + '" ';
					if(postalcode)
						output += ' postalcode="' + postalcode + '" ';
					if(country)
						output += ' country="' + country + '" ';
					if(email)
						output += ' email="' + email + '" ';
					if(phone)
						output += ' phone="' + phone + '" ';
				}

				// product
				if(type == 'product' ) {
					if(url)
						output += ' url="' + url + '" ';
					if(name)
						output += ' name="' + name + '" ';
					if(description)
						output += ' description="' + description + '" ';
					if(brand)
						output += ' brand="' + brand + '" ';
					if(manfu)
						output += ' manfu="' + manfu + '" ';
					if(model)
						output += ' model="' + model + '" ';
					if(prod_id)
						output += ' prod_id="' + prod_id + '" ';
					if(single_rating)
						output += ' single_rating="' + single_rating + '" ';
					if(agg_rating)
						output += ' agg_rating="' + agg_rating + '" ';
					if(price)
						output += ' price="' + price + '" ';
					if(condition)
						output += ' condition="' + condition + '" ';

				}

				// event
				if(type == 'event' ) {
					if(evtype)
						output += ' evtype="' + evtype + '" ';
					if(url)
						output += ' url="' + url + '" ';
					if(name)
						output += ' name="' + name + '" ';
					if(description)
						output += ' description="' + description + '" ';
					if(sdate)
						output += ' sdate="' + sdate + '" ';
					if(stime)
						output += ' stime="' + stime + '" ';
					if(edate)
						output += ' edate="' + edate + '" ';
					if(duration)
						output += ' duration="' + duration + '" ';
					if(street)
						output += ' street="' + street + '" ';
					if(pobox)
						output += ' pobox="' + pobox + '" ';
					if(city)
						output += ' city="' + city + '" ';
					if(state)
						output += ' state="' + state + '" ';
					if(postalcode)
						output += ' postalcode="' + postalcode + '" ';
					if(country)
						output += ' country="' + country + '" ';
				}

				// organization
				if(type == 'organization' ) {
					if(orgtype)
						output += ' orgtype="' + orgtype + '" ';
					if(url)
						output += ' url="' + url + '" ';
					if(name)
						output += ' name="' + name + '" ';
					if(description)
						output += ' description="' + description + '" ';
					if(street)
						output += ' street="' + street + '" ';
					if(pobox)
						output += ' pobox="' + pobox + '" ';
					if(city)
						output += ' city="' + city + '" ';
					if(state)
						output += ' state="' + state + '" ';
					if(postalcode)
						output += ' postalcode="' + postalcode + '" ';
					if(country)
						output += ' country="' + country + '" ';				
				}

				// movie
				if(type == 'movie' ) {
					if(url)
						output += ' url="' + url + '" ';
					if(name)
						output += ' name="' + name + '" ';
					if(description)
						output += ' description="' + description + '" ';
					if(director)
						output += ' director="' + director + '" ';						
					if(producer)
						output += ' producer="' + producer + '" ';
					if(actor_group) {
						var count = 0;
						jQuery('div.sc_actor').each(function(){
							count++;
							var actor = jQuery(this).find('input').val();
							output += ' actor_' + count + '="' + actor + '" ';
						});
					}

				}

				// book
				if(type == 'book' ) {
					if(url)
						output += ' url="' + url + '" ';
					if(name)
						output += ' name="' + name + '" ';
					if(description)
						output += ' description="' + description + '" ';
					if(author)
						output += ' author="' + author + '" ';						
					if(publisher)
						output += ' publisher="' + publisher + '" ';
					if(pubdate)
						output += ' pubdate="' + pubdate + '" ';
					if(edition)
						output += ' edition="' + edition + '" ';
					if(isbn)
						output += ' isbn="' + isbn + '" ';
					if(ebook == true )
						output += ' ebook="yes" ';
					if(paperback == true )
						output += ' paperback="yes" ';
					if(hardcover == true )
						output += ' hardcover="yes" ';

				}

			output += ']';
	
			window.send_to_editor(output);
			}
		</script>
	
			<div id="schema_build_form" style="display:none;">
			<div id="schema_builder" class="schema_wrap">
			<!-- schema type dropdown -->	
				<div id="sc_type">
					<label for="schema_type">Schema Type</label>
					<select name="schema_type" id="schema_type" class="schema_drop schema_thindrop">
						<option class="holder" value="none">(Select A Type)</option>
						<option value="person">Person</option>
						<option value="product">Product</option>
						<option value="event">Event</option>
						<option value="organization">Organization</option>
						<option value="movie">Movie</option>
						<option value="book">Book</option>
						<option value="review">Review</option>
					</select>
				</div>
			<!-- end schema type dropdown -->	

				<div id="sc_evtype" class="sc_option" style="display:none">
					<label for="schema_evtype">Event Type</label>
					<select name="schema_evtype" id="schema_evtype" class="schema_drop schema_thindrop">
						<option value="Event">General</option>
						<option value="BusinessEvent">Business</option>
						<option value="ChildrensEvent">Childrens</option>
						<option value="ComedyEvent">Comedy</option>
						<option value="DanceEvent">Dance</option>
						<option value="EducationEvent">Education</option>
						<option value="Festival">Festival</option>
						<option value="FoodEvent">Food</option>
						<option value="LiteraryEvent">Literary</option>
						<option value="MusicEvent">Music</option>
						<option value="SaleEvent">Sale</option>
						<option value="SocialEvent">Social</option>
						<option value="SportsEvent">Sports</option>
						<option value="TheaterEvent">Theater</option>
						<option value="UserInteraction">User Interaction</option>
						<option value="VisualArtsEvent">Visual Arts</option>
					</select>
				</div>

				<div id="sc_orgtype" class="sc_option" style="display:none">
					<label for="schema_orgtype">Organziation Type</label>
					<select name="schema_orgtype" id="schema_orgtype" class="schema_drop schema_thindrop">
						<option value="Organization">General</option>
						<option value="Corporation">Corporation</option>
						<option value="EducationalOrganization">School</option>
						<option value="GovernmentOrganization">Government</option>
						<option value="LocalBusiness">Local Business</option>
						<option value="NGO">NGO</option>
						<option value="PerformingGroup">Performing Group</option>
						<option value="SportsTeam">Sports Team</option>
					</select>
				</div>

				<div id="sc_name" class="sc_option" style="display:none">
					<label for="schema_name">Name</label>
					<input type="text" name="schema_name" class="form_full" value="" id="schema_name" />
				</div>

				<div id="sc_orgname" class="sc_option" style="display:none">
					<label for="schema_orgname">Organization</label>
					<input type="text" name="schema_orgname" class="form_full" value="" id="schema_orgname" />
				</div>
	
				<div id="sc_jobtitle" class="sc_option" style="display:none">
					<label for="schema_jobtitle">Job Title</label>
					<input type="text" name="schema_jobtitle" class="form_full" value="" id="schema_jobtitle" />
				</div>
	
				<div id="sc_url" class="sc_option" style="display:none">
					<label for="schema_url">Website</label>
					<input type="text" name="schema_url" class="form_full" value="" id="schema_url" />
				</div>
	
				<div id="sc_description" class="sc_option" style="display:none">
					<label for="schema_description">Description</label>
					<textarea name="schema_description" id="schema_description"></textarea>
				</div>

				<div id="sc_director" class="sc_option" style="display:none">
					<label for="schema_director">Director</label>
					<input type="text" name="schema_director" class="form_full" value="" id="schema_director" />
				</div>

				<div id="sc_producer" class="sc_option" style="display:none">
					<label for="schema_producer">Producer</label>
					<input type="text" name="schema_producer" class="form_full" value="" id="schema_producer" />
				</div>

				<div id="sc_actor_1" class="sc_option sc_actor sc_repeater" style="display:none">
                        <label for="schema_actor_1">Actor</label>
                        <input type="text" name="schema_actor_1" class="form_full actor_input" value="" id="schema_actor_1" />
				</div>

				<input type="button" id="clone_actor" value="Add" style="display:none;" />


				<div id="sc_sdate" class="sc_option" style="display:none">
					<label for="schema_sdate">Start Date</label>
					<input type="text" id="schema_sdate" name="schema_sdate" class="schema_datepicker timepicker form_third" value="" />
					<input type="hidden" id="schema_sdate-format" class="schema_datepicker-format" value="" />
				</div>

				<div id="sc_stime" class="sc_option" style="display:none">
					<label for="schema_stime">Start Time</label>
					<input type="text" id="schema_stime" name="schema_stime" class="schema_timepicker form_third" value="" />
				</div>

				<div id="sc_edate" class="sc_option" style="display:none">
					<label for="schema_edate">End Date</label>
					<input type="text" id="schema_edate" name="schema_edate" class="schema_datepicker form_third" value="" />
					<input type="hidden" id="schema_edate-format" class="schema_datepicker-format" value="" />
				</div>

				<div id="sc_duration" class="sc_option" style="display:none">
					<label for="schema_duration">Duration</label>
					<input type="text" id="schema_duration" name="schema_duration" class="schema_timepicker form_third" value="" />
				</div>
	
				<div id="sc_bday" class="sc_option" style="display:none">
					<label for="schema_bday">Birthday</label>
					<input type="text" id="schema_bday" name="schema_bday" class="schema_datepicker form_third" value="" />
					<input type="hidden" id="schema_bday-format" class="schema_datepicker-format" value="" />
				</div>
	
				<div id="sc_street" class="sc_option" style="display:none">
					<label for="schema_street">Address</label>
					<input type="text" name="schema_street" class="form_full" value="" id="schema_street" />
				</div>
	
				<div id="sc_pobox" class="sc_option" style="display:none">
					<label for="schema_pobox">PO Box</label>
					<input type="text" name="schema_pobox" class="form_third" value="" id="schema_pobox" />
				</div>
	
				<div id="sc_city" class="sc_option" style="display:none">
					<label for="schema_city">City</label>
					<input type="text" name="schema_city" class="form_full" value="" id="schema_city" />
				</div>
	
				<div id="sc_state" class="sc_option" style="display:none">
					<label for="schema_state">State / Region</label>
					<input type="text" name="schema_state" class="form_third" value="" id="schema_state" />
				</div>
	
				<div id="sc_postalcode" class="sc_option" style="display:none">
					<label for="schema_postalcode">Postal Code</label>
					<input type="text" name="schema_postalcode" class="form_third" value="" id="schema_postalcode" />
				</div>
	
				<div id="sc_country" class="sc_option" style="display:none">
					<label for="schema_country">Country</label>
					<input type="text" name="schema_country" class="form_full" value="" id="schema_country" />
				</div>
	
				<div id="sc_email" class="sc_option" style="display:none">
					<label for="schema_email">Email Address</label>
					<input type="text" name="schema_email" class="form_full" value="" id="schema_email" />
				</div>
	
				<div id="sc_phone" class="sc_option" style="display:none">
					<label for="schema_phone">Telephone</label>
					<input type="text" name="schema_phone" class="form_half" value="" id="schema_phone" />
				</div>
	
   				<div id="sc_brand" class="sc_option" style="display:none">
					<label for="schema_brand">Brand</label>
					<input type="text" name="schema_brand" class="form_full" value="" id="schema_brand" />
				</div>

   				<div id="sc_manfu" class="sc_option" style="display:none">
					<label for="schema_manfu">Manufacturer</label>
					<input type="text" name="schema_manfu" class="form_full" value="" id="schema_manfu" />
				</div>

   				<div id="sc_model" class="sc_option" style="display:none">
					<label for="schema_model">Model</label>
					<input type="text" name="schema_model" class="form_full" value="" id="schema_model" />
				</div>

   				<div id="sc_prod_id" class="sc_option" style="display:none">
					<label for="schema_prod_id">Product ID</label>
					<input type="text" name="schema_prod_id" class="form_full" value="" id="schema_prod_id" />
				</div>

   				<div id="sc_ratings" class="sc_option" style="display:none">
					<label for="sc_ratings">Aggregate Rating</label>
                    <div class="labels_inline">
					<label for="sc_single_rating">Avg Rating</label>
                    <input type="text" name="schema_single_rating" class="form_eighth" value="" id="schema_single_rating" />
                    <label for="sc_agg_rating">based on </label>
					<input type="text" name="schema_agg_rating" class="form_eighth" value="" id="schema_agg_rating" />
                    <label>reviews</label>
                    </div>
				</div>

   				<div id="sc_price" class="sc_option" style="display:none">
					<label for="schema_price">Price</label>
					<input type="text" name="schema_price" class="form_third sc_currency" value="" id="schema_price" />
				</div>

				<div id="sc_condition" class="sc_option" style="display:none">
					<label for="schema_condition">Condition</label>
					<select name="schema_condition" id="schema_condition" class="schema_drop">
						<option class="holder" value="none">(Select)</option>
						<option value="New">New</option>
						<option value="Used">Used</option>
						<option value="Refurbished">Refurbished</option>
						<option value="Damaged">Damaged</option>
					</select>
				</div>

   				<div id="sc_author" class="sc_option" style="display:none">
					<label for="schema_author">Author</label>
					<input type="text" name="schema_author" class="form_full" value="" id="schema_author" />
				</div>

   				<div id="sc_publisher" class="sc_option" style="display:none">
					<label for="schema_publisher">Publisher</label>
					<input type="text" name="schema_publisher" class="form_full" value="" id="schema_publisher" />
				</div>

				<div id="sc_pubdate" class="sc_option" style="display:none">
					<label for="schema_pubdate">Published Date</label>
					<input type="text" id="schema_pubdate" name="schema_pubdate" class="schema_datepicker form_third" value="" />
					<input type="hidden" id="schema_pubdate-format" class="schema_datepicker-format" value="" />
				</div>

   				<div id="sc_edition" class="sc_option" style="display:none">
					<label for="schema_edition">Edition</label>
					<input type="text" name="schema_edition" class="form_full" value="" id="schema_edition" />
				</div>

   				<div id="sc_isbn" class="sc_option" style="display:none">
					<label for="schema_isbn">ISBN</label>
					<input type="text" name="schema_isbn" class="form_full" value="" id="schema_isbn" />
				</div>

   				<div id="sc_formats" class="sc_option" style="display:none">
				<label class="list_label">Formats</label>
                	<div class="form_list">
                    <span><input type="checkbox" class="schema_check" id="schema_ebook" name="schema_ebook" value="ebook" /><label for="schema_ebook" rel="checker">Ebook</label></span>
                    <span><input type="checkbox" class="schema_check" id="schema_paperback" name="schema_paperback" value="paperback" /><label for="schema_paperback" rel="checker">Paperback</label></span>
                    <span><input type="checkbox" class="schema_check" id="schema_hardcover" name="schema_hardcover" value="hardcover" /><label for="schema_hardcover" rel="checker">Hardcover</label></span>
                    </div>
				</div>

				<div id="sc_revdate" class="sc_option" style="display:none">
					<label for="schema_revdate">Review Date</label>
					<input type="text" id="schema_revdate" name="schema_revdate" class="schema_datepicker form_third" value="" />
					<input type="hidden" id="schema_revdate-format" class="schema_datepicker-format" value="" />
				</div>
                
			<!-- button for inserting -->	
				<div class="insert_button" style="display:none">
					<input class="schema_insert schema_button" type="button" value="<?php _e('Insert'); ?>" onclick="InsertSchema();"/>
					<input class="schema_cancel schema_clear schema_button" type="button" value="<?php _e('Cancel'); ?>" onclick="tb_remove(); return false;"/>                
				</div>

			<!-- various messages -->
				<div id="sc_messages">
                <p class="start">Select a schema type above to get started</p>
                <p class="pending" style="display:none;">This schema type is currently being constructed.</p>
                </div>
	
			</div>
			</div>
	
	<?php }


/// end class
}


// Instantiate our class
$ravenSchema = new ravenSchema();