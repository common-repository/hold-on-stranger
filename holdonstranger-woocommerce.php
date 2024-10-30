<?php
/**
 * Holdonstranger-woocommerce.
 *
 * @package Holdonstranger
 *
 * @file
 * Install the Javascript snippet for the Woocommerce plugin.
 */

/**
 * Install the javascript snippet on the page footer.
 */
function k2c_holdonstranger_popup_wrapper() {
	add_filter( 'nonce_life', function() { return HOUR_IN_SECONDS;
	} );
	add_action( 'wp_footer', 'holdonstranger_snippet' ); // Write our JS below here.
	add_action( 'wp_ajax_create_coupon', 'holdonstranger_create_coupon' );
	add_action( 'wp_ajax_nopriv_create_coupon', 'holdonstranger_create_coupon' );
}

/**
 * Write the holdonstranger snippet.
 */
function holdonstranger_snippet() {
	$hos_ajax_nonce = wp_create_nonce( 'k2c_holdonstranger-create_coupon' );
	?>
    <script data-cfasync='false' type="text/javascript" >
		var ajaxurl = '<?php echo admin_url( 'admin-ajax.php' ); ?>';
		function gc(params, cb) {
			var data = params || {};
			data.hos_nonce = '<?php echo $hos_ajax_nonce; ?>';
			data.action = 'create_coupon';
			jQuery.post(ajaxurl, data, cb);
		}

		/* WooCommerce is active */
		<?php $k2c_options = get_option( 'k2c_holdonstranger' ); ?>
		
				window._hos_||(function(w,d,S){
		  var t=(new Date).getTime(),
		      k='<?php echo esc_html( $k2c_options["public_key"] )?>',
		      e=d.getElementsByTagName(S)[0],
			  h=window.location.host.toLowerCase(),
		      s=d.createElement(S);
		  w._hos_={gc:gc,k:k,t:t,x:[],do:function(a,o){w._hos_.x.push([a,o]);} };
		  s.type='text/javascript';
		  s.async=true;
		  s.src='https://cdn.holdonstranger.com/l/v3/'+k+'/'+h+'/s/'+h+'.js';
		  e.parentNode.insertBefore(s,e);
		})(window,document,'script');
    </script> <?php
}

/**
 * Function used to create the new coupon based on the given coupon code.
 */
function holdonstranger_create_coupon() {
	global $wpdb; // This is how you get access to the database.
	global $woocommerce;
	$response = array( 'success' => false );

	if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) ) {
		wp_send_json( $response );
		return;
	}

	check_ajax_referer( 'k2c_holdonstranger-create_coupon', 'hos_nonce' );

	$post = $_POST; // Input var okay.

	if ( empty( $post['rule_id'] ) ) {
		$response['message'] = "Missing required parameter: 'rule_id'";
		wp_send_json( $response );
		return false;
	}

	// Sanitize coupon rule_id.
	$coupon_code = apply_filters( 'woocommerce_coupon_code', $post['rule_id'] );
	if ( empty( $coupon_code ) ) {
		$response['message'] = 'Could not create the coupon code!';
		wp_send_json( $response );
		return false;
	}

	// Get the coupon.
	$the_coupon = new WC_Coupon( $coupon_code );
	if ( ! $the_coupon->exists ) {
		$response['message'] = $the_coupon->get_generic_coupon_error( WC_Coupon::E_WC_COUPON_NOT_EXIST );
		wp_send_json( $response );
		return false;
	}

	// Check if can be used with cart.
	if ( ! $the_coupon->is_valid() ) {
		$response['message'] = $the_coupon->get_error_message();
		wp_send_json( $response );
		return false;
	}

	// Get or create the coupon code.
	try {
		$coupon = holdonstranger_get_coupon_code( $the_coupon, $post );

		$response['success'] = true;
		$response['coupon'] = $coupon;
		wp_send_json( $response ); // Terminate immediately and return a proper Json response.

	} catch ( Exception $e ) {
		$response['message'] = $e->getMessage();
		wp_send_json( $response );
	}
}

/**
 * Get the created coupon code fro the currente session.
 * @param string $the_coupon The coupon code.
 * @param array  $post Coupon generation parameters.
 * @throws Exception InvalidPostId.
 */
function holdonstranger_get_coupon_code( $the_coupon, $post ) {

	// Verify if already created.
	$created_coupons = get_option( 'k2c_holdonstranger_coupons' );
	$nonce = $post['hos_nonce'];
	$time = time();

	if ( ! isset( $created_coupons[ $nonce ] ) ) {
		$code = holdontranger_clone_coupon( $the_coupon, $post );
		$expire = $time + HOUR_IN_SECONDS;
		$created_coupons[ $nonce ] = array( $code, $expire );
	} else {
		$parts = $created_coupons[ $nonce ];
		$code = $parts[0];
	}

	// Cleanup expired coupons.
	foreach ( $created_coupons as $nonce => $parts ) {
		if ( $parts[1] > $time ) {
			break; }

		// This code has expired.
		unset( $created_coupons[ $nonce ] );
	}
	asort( $created_coupons );
	update_option( 'k2c_holdonstranger_coupons', $created_coupons );
	return $code;
}

/**
 * Create a new coupon has a copy of the previous coupon.
 * @param string $the_coupon The coupon code.
 * @param array  $params Coupon generation parameters.
 * @throws Exception InvalidPostId.
 */
function holdontranger_clone_coupon( $the_coupon, $params ) {
	$coupon_code = holdonstranger_generate_code( $params );

	$post = get_post( $the_coupon->id );
	if ( is_null( $post ) ) {
		throw new Exception( __( 'Invalid post ID.' ) );
	}

	$meta = get_post_meta( $post->ID );
	if ( empty( $meta ) ) {
		 throw new Exception( __( 'Invalid post ID.' ) );
	}

	$new_coupon = array(
		'post_title'   => $coupon_code,
		'post_content' => '',
		'post_status'  => 'publish',
		'post_author'  => 1,
		'post_type'    => 'shop_coupon',
	);

	$wp_error = true;
	$new_coupon_id = wp_insert_post( $new_coupon, $wp_error );
	if ( is_wp_error( $new_coupon_id ) ) {
		throw new Exception( $new_coupon_id->get_error_messages() );
	}

	foreach ( $meta as $key => $value ) {
		if ( count( $value ) > 1 ) {
			foreach ( $value as $v ) {
				add_post_meta( $new_coupon_id, $key, $v );
			}
		} else {
			if ( ! ('usage_limit' === $key || 'usage_limit_per_user' === $key) ) {
				update_post_meta( $new_coupon_id, $key, $value[0] );
			} else {
				update_post_meta( $new_coupon_id, $key, 1 );
			}
		}
	}
	return $new_coupon['post_title'];
}

/**
 * Generate code.
 * @param array $params The code generation parameters.
 */
function holdonstranger_generate_code( $params ) {
	$alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
	$lengthMin = 6;
	$lengthMax = 16;
	$length = 0;
	if ( is_array( $params )  && array_key_exists( 'length', $params ) ) {
		$length = $params['length'];
	}

	if ( $length <= 0 ) {
		$length = rand( $lengthMin, $lengthMax );
	}

	$result = '';
	$indexMax = strlen( $alphabet ) - 1;

	for ( $i = 0; $i < $length; $i++ ) {
		$index = rand( 0, $indexMax );
		$result .= $alphabet{$index};
	}
	return $result;
}
?>
