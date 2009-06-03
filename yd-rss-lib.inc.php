<?php
function yd_fetch_rss( $rss_url ) {
	if ( !defined('MAGPIE_FETCH_TIME_OUT') ) {
		define('MAGPIE_FETCH_TIME_OUT', 30);
	} else {
		//echo 'Timeout: ' . MAGPIE_FETCH_TIME_OUT . '<br/>';
	}
	require_once( ABSPATH . WPINC . '/rss.php' );
	$rss = fetch_rss( $rss_url);
	return $rss;
}
?>