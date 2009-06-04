<?php
/**
 * @package YD_P2P-Widget
 * @author Yann Dubois
 * @version 0.2.0
 */

/*
 Plugin Name: Post2Peer
 Plugin URI: http://www.yann.com/wp-plugins/post2peer-wordpress-plugin
 Description: Allows to exchange and share post links + thumbnails across Wordpress blogs, based on matching tags and categories.
 Author: Yann Dubois
 Version: 0.2.0
 Author URI: http://www.yann.com/
 */

/**
 * @copyright 2009  Yann Dubois  ( email : yann _at_ abc.fr )
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */
/**
 Revision 0.1.0:
 - Initial release
 Revision 0.2.0:
 - default options at install
 - Bottom credits optional (additional checkbox) / no more link+text option
 - Porn filter options (pornlover=1, justporn=1) 
 - TODO:fixed image style (was not implemented)
  */
/**
 *	TODO:
 *  - Code and css mutualization with yd_recent_posts_widget
 *  - Allow multiple widgets
 *  - Choose specific default tags
 *  - Custom choice of agregated RSS with site: syntax
 */

/** Create Text Domain For Translations **/
add_action('init', 'yd_p2p_widget_textdomain');
function yd_p2p_widget_textdomain() {
	$plugin_dir = basename(dirname(__FILE__));
	load_plugin_textdomain(
		'yd-p2p-widget', 
		'wp-content/plugins/' . $plugin_dir, $plugin_dir 
	);
}

/** Install or reset plugin defaults **/
function yd_p2p_plugin_reset( $force ) {
	/** Init values **/
	$yd_p2p_plugin_version	= "0.2.0";
	$default_image_width	= 60;
	$default_image_height	= 60;
	$default_image_style	= 'padding-right:5px;padding-bottom:5px;float:left;';
	$default_date_format	= __('F j', 'yd-p2p-widget');
	$default_link			= 'http://post2peer.com/about';
	$default_text			= 'Powered by Post2Peer beta';
	$default_thumbnail_img	= 'http://www.yann.com/yd-post2peer-widget-v020-logo.gif';
	$newoption				= 'yd_p2p_widget';
	$newvalue				= '';
	if( $df = get_option( 'date_format' ) ) $default_date_format = $df;
	/** TODO **/
	//$default_image_width = get_option( 'thumbnail_size_w' ) ? get_option( 'thumbnail_size_w' ) : $default_image_width;
	//$default_image_height = get_option( 'thumbnail_size_h' ) ? get_option( 'thumbnail_size_h' ) : $default_image_height;
	// ...this would need to generate the CSS file dynamically at plugin init
	$default_image_style =	'width:' . $default_image_width . 'px;' .
							'height:' . $default_image_height . 'px;' . $default_image_style;
	$prev_options = get_option( $newoption );
	if( ( isset( $force ) && $force ) || !isset($prev_options['plugin_version']) ) {
		// those default options are set-up at plugin first-install or manual reset only
		// they will not be changed when the plugin is just upgraded or deactivated/reactivated
		$newvalue['plugin_version'] = $yd_p2p_plugin_version;
		$newvalue['style'] = $default_image_style;
		$newvalue['date_format'] = $default_date_format;
		$newvalue['link'] = $default_link;
		$newvalue['text'] = $default_text;
		$newvalue['home_bottomlink'] = $default_bottomlink;
		$newvalue['home_bottomtext'] = $default_bottomtext;
		$newvalue['default_image'] = $default_thumbnail_img;
		$newvalue['load_css'] = 1;
		$newvalue['credits'] = 1;
		$newvalue['language'] = 'en';
		if( $prev_options ) {
			update_option( $newoption, $newvalue );
		} else {
			add_option( $newoption, $newvalue );
		}
	}
}
register_activation_hook(__FILE__, 'yd_p2p_plugin_reset');

/** Widget function: P2P **/
function yd_p2p_widget( $args ) {
	if( isset( $args ) && $args === FALSE ) {
		$echo = FALSE;
	} else {
		if( is_array( $args ) ) extract( $args );
		$echo = TRUE;
	}
	$plugin_dir = 'yd-p2p-widget';
	$html = '';
	
	// --
	$options = get_option( 'yd_p2p_widget' );
	$title = empty( $options['title'] ) ? 
		__( 'Post2Peer beta', 'yd-p2p-widget' ) : 
		apply_filters( 'widget_title', $options['title'] );
	$link = empty( $options['link'] ) ? 
		__( 'http://post2peer.com/about', 'yd-p2p-widget' ) : $options['link'];
	$text = empty( $options['text'] ) ? 
		__( 'Powered by Post2Peer beta', 'yd-p2p-widget' ) : $options['text'];
		$html .= $before_widget;
	if( $options['load_css'] ) 		
		$html .= '<link type="text/css" rel="stylesheet" href="' . 
		get_bloginfo('wpurl') . '/wp-content/plugins/' . $plugin_dir . 
		'/css/yd_rp.css" />';
	list( $listhtml, $rss_url ) = yd_p2p_widget_main();
	$rss_img = '<img src="/wp-includes/images/rss.png" style="float:right;margin: 0 5px 0 0;" alt="Post2Peer custom rss" />';
	$rss_img = '<a href="' . $rss_url . '" title="Post2Peer custom RSS feed" target="_out">' . $rss_img . '</a>';
	if( $title ) $html .= $before_title . $rss_img . $title . $after_title . $listhtml;
	if( $options['credits'] ) {
		$html .= '<small style="float:right"><a href="' . $link . '" target="_out">' . $text . '</a></small>';
	}
	$html .= $after_widget;
	// --
		
	if( $echo ) {
		echo $html;
	} else {
		return $html;
	}
}

/**
 * Manage P2P widget options.
 *
 * Displays management form for changing the P2P widget options.
 *
 * inspired by /wp-includes/widget.php lines 1885-1904
 */
function yd_p2p_widget_control() {
	$default_image_style	= 'padding-right:5px;padding-bottom:5px;float:left;';
	$options = $newoptions = get_option('yd_p2p_widget');

	if ( isset($_POST['p2p_widget-submit']) ) {
		$newoptions['title']	= strip_tags(stripslashes($_POST['p2p_widget-title']));
		$newoptions['language']	= strip_tags(stripslashes($_POST['p2p_widget-language']));
		$newoptions['credits']	= strip_tags(stripslashes($_POST['p2p_widget-credits']));
		$newoptions['debug']	= strip_tags(stripslashes($_POST['p2p_widget-debug']));
		$newoptions['min']		= strip_tags(stripslashes($_POST['p2p_widget-min']));
		$newoptions['max']		= strip_tags(stripslashes($_POST['p2p_widget-max']));
		$newoptions['porn']		= strip_tags(stripslashes($_POST['p2p_widget-porn']));
		$newoptions['load_css']	= strip_tags(stripslashes($_POST['p2p_widget-load_css']));
		$newoptions['style']	= strip_tags(stripslashes($_POST['p2p_widget-style']));
		if( !$newoptions['load_css'] && !$newoptions['style'] ) $newoptions['style'] = $default_image_style;
	}

	if ( $options != $newoptions ) {
		$options = $newoptions;
		update_option('yd_p2p_widget', $options);
	}

	$title		= attribute_escape( $options['title'] );
	$language	= attribute_escape( $options['language'] );
	$credits 	= attribute_escape( $options['credits'] );
	$debug 		= attribute_escape( $options['debug'] );
	$min 		= attribute_escape( $options['min'] );
	$max 		= attribute_escape( $options['max'] );
	$porn 		= attribute_escape( $options['porn'] );
	$load_css 	= attribute_escape( $options['load_css'] );
	$style 		= attribute_escape( $options['style'] );
	?>
	<img src="http://www.yann.com/yd-post2peer-widget-v020-logo.gif" style="float:right;width:10px;height:10px;">
	<p><label for="p2p_widget-title">
	<?php _e('Title:') ?> 
	<input type="text" class="widefat" id="p2p_widget-title" name="p2p_widget-title" 
		value="<?php echo $title ?>" /></label></p>
	
	<p><label for="p2p_widget-language">
	<?php _e('Language:') ?> 
	<select id="p2p_widget-language" name="p2p_widget-language">
	<?php
	$langs = yd_get_p2p_language_list();
	foreach( array_keys( $langs ) as $lang ) {
		echo '<option value="' . $lang . '" ';
		if( $language == $lang ) echo ' selected="selected"';
		echo '>' . $langs[$lang] . '</option>';
	}
	?>
	</select></label></p>
	
	<p><label for="p2p_widget-min">
	<?php _e('Minimum number of links:') ?> 
	<input type="text" class="widefat" id="p2p_widget-min" name="p2p_widget-min" 
		value="<?php echo $min ?>" /></label></p>
		
	<p><label for="p2p_widget-max">
	<?php _e('Maximum number of links:') ?> 
	<input type="text" class="widefat" id="p2p_widget-max" name="p2p_widget-max" 
		value="<?php echo $max ?>" /></label></p>
		
	<p><label for="p2p_widget-porn">
	<?php _e('Allow porn:') ?> 
	<input type="checkbox" class="widefat" id="p2p_widget-porn" name="p2p_widget-porn" 
		value="1" <?php if( $porn ) echo ' checked="checked" ' ?> /></label></p>
			
	<p><label for="p2p_widget-load_css">
	<?php _e('Load stylesheet:') ?> 
	<input type="checkbox" class="widefat" id="p2p_widget-load_css" name="p2p_widget-load_css" 
		value="1" <?php if( $load_css ) echo ' checked="checked" ' ?> /></label></p>
		
	<p><label for="p2p_widget-style">
	<?php _e('CSS Style:') ?> 
	<input type="text" class="widefat" id="p2p_widget-style" name="p2p_widget-style" 
		value="<?php echo $style ?>" /></label></p>

	<p><label for="p2p_widget-credits">
	<?php _e('Display credits:') ?> 
	<input type="checkbox" class="widefat" id="p2p_widget-credits" name="p2p_widget-credits" 
		value="1" <?php if( $credits ) echo ' checked="checked" ' ?> /></label></p>
			
	<p><label for="p2p_widget-debug">
	<?php _e('Debug:') ?> 
	<input type="checkbox" class="widefat" id="p2p_widget-debug" name="p2p_widget-debug" 
		value="1" <?php if( $debug ) echo ' checked="checked" ' ?> /></label></p>

	<input type="hidden" name="p2p_widget-submit" id="p2p_widget-submit" value="1" />
<?php
}

function yd_p2p_widget_init() {
	// Check for the required API functions
	if ( !function_exists('register_sidebar_widget') || !function_exists('register_widget_control') )
	return;
	register_sidebar_widget( __('Post2Peer', 'yd-p2p-widget'), 'yd_p2p_widget' );
	register_widget_control( __('Post2Peer', 'yd-p2p-widget'), 'yd_p2p_widget_control' );
}

// Tell Dynamic Sidebar about our new widget and its control
add_action('plugins_loaded', 'yd_p2p_widget_init');

// ======================================== MAIN ====================================

function yd_p2p_widget_main() {
	global $post;
	$html = '';
	$options = get_option( 'yd_p2p_widget' );
	//get language
	$lang = $options['language'];
	//get categories and tags
	$id = $post->ID;
	if( is_category() ) {
		$categories[] = single_cat_title( "", false );
		$mytags = Array();
	} elseif( is_tag() ) {
		$mytags[] = single_tag_title( "", false );
		$categories = Array();
	} else {
		$categories = get_the_category();
		$mytags = get_the_tags( $id );
	} 
	$tags = Array();
	if( is_array( $categories ) ) foreach( $categories as $cat ) $tags[] = $cat->cat_name;
	if( is_array( $mytags ) ) foreach( $mytags as $tag ) $tags[] = $tag->name;
	//if( count( $tags ) == 1 ) $tags[0] = $tags[0] . "+" . $tags[0]; // double the tag to avoid bug
	//construct rss url
	$url = 'http://post2peer.com' .
		'/l/' . $lang . 
		'?tag=' . urlencode( join( ",", $tags ) ) .
		'&feed=rss2' .
		'&e=' . get_bloginfo( 'home' ) .
		'&max=' . $options['max'] .
		'&min=' . $options['min'];
	if( $options['porn'] ) $url .= '&pornlover=1';
	$rss = yd_fetch_rss( $url );
	if( $h = yd_p2p_walk_rss( $rss ) ) $html .= $h;
	if( $options['debug'] ) {
		// debug output
		$html .= '<pre>';
		$html .= "-- debug: --\n";
		$html .= "<a href=$url>$url</a>\n";
		$html .= 'id: ' . $id . "\n";
		$html .= 'language: ' . $lang . "\n";
		$html .= "categories:\n";
		foreach( $categories as $cat ) $html .= "- " . $cat->cat_name . "\n";
		$html .= 'mytags: ' . '' . "\n";
		foreach( $mytags as $tag ) $html .= "- " . $tag->name . "\n";
		$html .= 'tags: ' . '' . "\n";
		foreach( $tags as $tag ) $html .= "- " . $tag . "\n";
		$html .= '</pre>';
	}
	return Array( $html, $url );
}

function yd_p2p_walk_rss( $rss ) {
	$html = '';
	$options = get_option( 'yd_p2p_widget' );
	if ( is_array( $rss->items ) && !empty( $rss->items ) ) {
		if( $options['max'] && $options['max'] > 0 ) $rss->items = array_slice( $rss->items, 0, $options['max'] );
		$html .= '<div class="yd_rp_widget">';
		$html .= '<ul>';
		foreach ( (array) $rss->items as $item ) {	
			list( $link, $title, $desc, $img ) = Array();
			while ( strstr($item['link'], 'http') != $item['link'] )
				$item['link'] = substr($item['link'], 1);
			$link = clean_url(strip_tags($item['link']));
			$title = attribute_escape(strip_tags($item['title']));
			$desc = str_replace(array("\n", "\r"), ' ', attribute_escape(strip_tags(html_entity_decode($item['description'], ENT_QUOTES))));
			$desc = yd_clean_cut( $desc, 128 );
			if( preg_match( "/<img[^>]+>/", $item['content']['encoded'], $matches ) ) $img = $matches[0];
			if( preg_match( "|<a[^>]+href=\"([^\"]+)\"[^>]*>[^<]+</a></p>$|", $item['content']['encoded'], $matches ) ) $link = $matches[1];
			if( !$options['load_css'] && $options['style'] ) 
				$img = preg_replace( "|style=\"[^\"]+\"|", ' style="' . $options['style'] . '"', $img );
				
			$html .= '<li><h4><a href="' . $link . '" rel="bookmark" title="Permanent link to: ' . $title . '">' . $img . $title . '</a></h4>';
			$html .= '<div class="yd_rp_excerpt">' . $desc;
			$html .= $direct_link;
			$html .= '<a href="' . $link . '">...&nbsp;&raquo;</a></div>';
			$html .= "</li>";
		}
		$html .= '</ul>';
		$html .= '</div>';
		return $html;
	} else {
		return false;
	}
}

function yd_get_p2p_language_list() {
	$lang = Array(
		'en' => 'English',
		'ar' => 'العربية',
		'cs' => 'Česky',
		'da' => 'Dansk',
		'de' => 'Deutsch',
		'es' => 'Español',
		'fr' => 'Français',
		'he' => 'עברית',
		'hu' => 'Magyar',
		'it' => 'Italiano',
		'ja' => '日本語',
		'nl' => 'Nederlands',
		'pl' => 'Polski',
		'pt' => 'Português',
		'ro' => 'Română',
		'ru' => 'русский',
		'sl' => 'Slovenščina',
		'sv' => 'Svenska',
		'tr' => 'Türkçe',
		'zh' => '中文'
	);
	return $lang;
}

include( 'yd-rss-lib.inc.php' );
include( 'yd-wp-lib.inc.php' );
?>