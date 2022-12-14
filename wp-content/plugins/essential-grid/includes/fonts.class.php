<?php
/**
 * @author    ThemePunch <info@themepunch.com>
 * @link      http://www.themepunch.com/
 * @copyright 2021 ThemePunch
 * @version   1.0.1
 */
 
if( !defined( 'ABSPATH') ) exit();

if(!class_exists('ThemePunch_Fonts')) {
	 
	class ThemePunch_Fonts {
		
		const TP_TEXTDOMAIN = 'themepunch-fonts';
		
		/**
		 * save all fonts
		 **/
		public function save_fonts($fonts){
			if(!empty($fonts)){
				foreach($fonts as $font){
					if(!isset($font['url']) || strlen($font['url']) < 3) return esc_attr__('Wrong parameter received', TP_TEXTDOMAIN);
					if(!isset($font['handle']) || strlen($font['handle']) < 3) return esc_attr__('Wrong handle received', TP_TEXTDOMAIN);
				}
			}
			$do = update_option('tp-google-fonts', $fonts);
			self::invalidate_privacy();
			
			return true;
		}
		
		/**
		 * Add a new Font 
		 */
		public function add_new_font($new_font){
			if(!isset($new_font['url']) || strlen($new_font['url']) < 3) return esc_attr__('Wrong parameter received', TP_TEXTDOMAIN);
			if(!isset($new_font['handle']) || strlen($new_font['handle']) < 3) return esc_attr__('Wrong handle received', TP_TEXTDOMAIN);
			
			$fonts = $this->get_all_fonts();
			if(!empty($fonts)){
				foreach($fonts as $font){
					if($font['handle'] == $new_font['handle']) return esc_attr__('Font with handle already exist, choose a different handle', TP_TEXTDOMAIN);
				}
			}
			
			$new = array('url' => $new_font['url'], 'handle' => $new_font['handle']);
			$fonts[] = $new;
			$do = update_option('tp-google-fonts', $fonts);
			self::invalidate_privacy();
			
			return true;
		}
		
		/**
		 * change font by handle
		 */
		public function edit_font_by_handle($edit_font){
			if(!isset($edit_font['handle']) || strlen($edit_font['handle']) < 3) return esc_attr__('Wrong Handle received', TP_TEXTDOMAIN);
			if(!isset($edit_font['url']) || strlen($edit_font['url']) < 3) return esc_attr__('Wrong Params received', TP_TEXTDOMAIN);
			
			$fonts = $this->get_all_fonts();
			if(!empty($fonts)){
				foreach($fonts as $key => $font){
					if($font['handle'] == $edit_font['handle']){
						$fonts[$key]['handle'] = $edit_font['handle'];
						$fonts[$key]['url'] = $edit_font['url'];
						
						$do = update_option('tp-google-fonts', $fonts);
						self::invalidate_privacy();
						return true;
					}
				}
			}
			
			return false;
		}
		
		/**
		 * Remove Font
		 */
		public function remove_font_by_handle($handle){
			$fonts = $this->get_all_fonts();
			if(!empty($fonts)){
				foreach($fonts as $key => $font){
					if($font['handle'] == $handle){
						unset($fonts[$key]);
						$do = update_option('tp-google-fonts', $fonts);
						self::invalidate_privacy();
						return true;
					}
				}
			}
			
			return esc_attr__('Font not found! Wrong handle given.', TP_TEXTDOMAIN);
		}
		
		/**
		 * get all fonts
		 */
		public function get_all_fonts(){
			$fonts = get_option('tp-google-fonts', array());
			if(empty($fonts)) $fonts = array();
			
			return $fonts;
		}
		
		/**
		 * get all handle of fonts 
		 */
		public function get_all_fonts_handle(){
			$fonts = array();
			$font = get_option('tp-google-fonts', array());
			if(!empty($font)){
				foreach($font as $f){
					$fonts[] = $f['handle'];
				}
			}
			
			return $fonts;
		}
		
		/**
		 * register all fonts
		 */
		public function register_fonts(){
			$http = (is_ssl()) ? 'https' : 'http';
			$font_url = $http.'://fonts.googleapis.com/css?family=';
			$font_url = apply_filters('punchfonts_modify_url', $font_url);
			
			$fonts = $this->get_all_fonts();
			if(!empty($fonts)){
				foreach($fonts as $font){
					if($font !== ''){
						$font = apply_filters('punchfonts_modify_font', $font);
						wp_register_style('tp-'.sanitize_title($font['handle']), $font_url.strip_tags($font['url']));
						wp_enqueue_style('tp-'.sanitize_title($font['handle']));
					}
				}
			}
		}

		/**
		 * register all fonts
		 */
		public function register_icon_fonts($focus){
			$enable_fontello = get_option('tp_eg_global_enable_fontello', 'backfront');
			$enable_font_awesome = get_option('tp_eg_global_enable_font_awesome', 'false');
			$enable_pe7 = get_option('tp_eg_global_enable_pe7', 'false');
			
			if ($focus=="admin") {
				if ($enable_pe7!="false") wp_enqueue_style('tp-stroke-7', ESG_PLUGIN_URL . 'public/assets/font/pe-icon-7-stroke/css/pe-icon-7-stroke.css', array(), Essential_Grid::VERSION );	
				if ($enable_font_awesome!="false") wp_enqueue_style('tp-font-awesome', ESG_PLUGIN_URL . 'public/assets/font/font-awesome/css/font-awesome.css', array(), Essential_Grid::VERSION );
			} else {
				if ($enable_fontello=="backfront") wp_enqueue_style('tp-fontello', ESG_PLUGIN_URL . 'public/assets/font/fontello/css/fontello.css', array(), Essential_Grid::VERSION );
				if ($enable_font_awesome=="backfront") wp_enqueue_style('tp-font-awesome', ESG_PLUGIN_URL . 'public/assets/font/font-awesome/css/font-awesome.css', array(), Essential_Grid::VERSION );
				if ($enable_pe7=="backfront") wp_enqueue_style('tp-stroke-7', ESG_PLUGIN_URL . 'public/assets/font/pe-icon-7-stroke/css/pe-icon-7-stroke.css', array(), Essential_Grid::VERSION );
			}
		}
		
		/**
		 * register all fonts
		 */
		public static function propagate_default_fonts($networkwide = false){
			global $wpdb;
			
			$default = array ();
			$default = apply_filters('essgrid_add_default_fonts', $default); //will be obsolete soon, use tp_add_default_fonts instead
			$default = apply_filters('tp_add_default_fonts', $default);
			
			if (function_exists('is_multisite') && is_multisite() && $networkwide) { 
				//do for each existing site
				// Get all blog ids and create tables
				$blogids = $wpdb->get_col("SELECT blog_id FROM ".$wpdb->blogs);
				foreach ($blogids as $blog_id) {
					switch_to_blog($blog_id);
					self::_propagate_default_fonts($default);
					// 2.2.5
					restore_current_blog();
				}
			} else {
				self::_propagate_default_fonts($default);
			}
		}
		
		/**
		 * register all fonts modified for multisite
		 * @since: 1.5.0
		 */
		public static function _propagate_default_fonts($default){
			$fonts = get_option('tp-google-fonts', array());
			if (empty($fonts)) {
				update_option('tp-google-fonts', $default);
				self::invalidate_privacy();
			}
		}

		/**
		 * real cookie banner: invalidate presets cache so Google Fonts gets shown in scanner
		 */
		protected static function invalidate_privacy() {
			if (function_exists('wp_rcb_invalidate_presets_cache')) {
				wp_rcb_invalidate_presets_cache();
			}
		}
		
	}
}
