<?php
/**
 * Holdonstranger-wordpress.
 *
 * @package Holdonstranger
 *
 * @file
 * Install the Javascript snippet in WordPress.
 */

/**
 * Install the javascript snippet on the page footer.
 */
function k2c_holdonstranger_popup_wrapper() {
	add_action( 'wp_footer', 'holdonstranger_snippet' );
}

/**
 * Write the holdonstranger snippet.
 */
function holdonstranger_snippet() {
	?>
<script data-cfasync='false' type='text/javascript'>
	/* WooCommerce is NOT active */
	<?php $k2c_options = get_option('k2c_holdonstranger'); ?>
		window._hos_||(function(w,d,S){
	  var t=(new Date).getTime(),
	      k='<?php echo esc_attr( $k2c_options["public_key"] )?>',
	      e=d.getElementsByTagName(S)[0],
		  h=window.location.host.toLowerCase(),
	      s=d.createElement(S);
	  w._hos_={k:k,t:t,x:[],do:function(a,o){w._hos_.x.push([a,o]);} };
	  s.type='text/javascript';
	  s.async=true;
	  s.src='https://cdn.holdonstranger.com/l/v3/'+k+'/'+h+'/s/'+h+'.js';
	  e.parentNode.insertBefore(s,e);
	})(window,document,'script');
</script>
<?php
}
?>
