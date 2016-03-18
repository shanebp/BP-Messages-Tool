<?php

if ( !defined( 'ABSPATH' ) ) exit;

function bpmt_menu() {

	add_submenu_page( 'tools.php', 'BP Messages', 'BP Messages', 'manage_options', 'bp-messages-tool', 'bpmt_screen', '', 23.52 );
}
add_action( 'admin_menu', 'bpmt_menu' );


function bpmt_screen() {
?>
	<div class="wrap">
		<h2><?php _e( 'BP Messages Tool', 'bpmt' )?></h2>

		<?php bpmt_form(); ?>

		<?php
		if ( is_super_admin() && isset( $_GET['action'] ) ) {

			switch( $_GET['action'] ) {

				case 'select-member':
					bpmt_get_member();
					break;

				case 'member-threads':
					bpmt_get_member_page();
					break;

				case 'view-thread':
					bpmt_get_thread_view();
					break;

				case 'delete-thread':
					bpmt_delete_thread();
					break;

			}
		}
		?>
	</div>
<?php
}


function bpmt_form() {
?>
	<p>
	<div class="wrap">
		<form action="<?php echo site_url(); ?>/wp-admin/tools.php?page=bp-messages-tool&action=select-member" name="bpmt-form" id="bpmt-form"  method="post" class="standard-form">

			<?php wp_nonce_field('bpmt-member-action', 'bpmt-member-field'); ?>

			<?php _e("Enter a Member's login name or user id: ", 'bpmt'); ?>

			<br/><br/>

			<input type="text" name="bpmt-user" id="bpmt" maxlength="50" />

			<br/><br/>

			<input type="radio" name="bpmt-box" value="inbox" checked><?php _e("Inbox", 'bpmt'); ?> &nbsp; <input type="radio" name="bpmt-box" value="sentbox"><?php _e("Sent", 'bpmt'); ?><br/><br/>

			<input type="submit" name="bpmt-submit"  id=""bpmt-submit" class="button button-primary" value="<?php _e('Go', 'bpmt'); ?>">

		</form>
	</p>
<?php
}


function bpmt_get_member() {
	global $bpmt_user_data;

	if( isset( $_POST['bpmt-user'] ) ) {

		if( !wp_verify_nonce($_POST['bpmt-member-field'],'bpmt-member-action') )
			die('Security Check - Failed');

		if( ! empty( $_POST['bpmt-user'] ) )
			$bpmt_user = $_POST['bpmt-user'];
		else {
			_e("<div class='error below-h2'>ERROR -  Please enter a Member's login name or user id.</div>", 'bpmt');
			return;
		}
	}

	elseif( isset( $_GET['user'] ) )
		$bpmt_user = $_GET['user'];

	else {
		_e("<div class='error below-h2'>ERROR - There was a problem.</div>", 'bpmt');
		return;
	}

	$bpmt_user_data = bpmt_get_user_data( $bpmt_user );


	if( $bpmt_user_data != NULL ) {

		if( ( isset( $_POST['bpmt-box'] ) && $_POST['bpmt-box'] == 'sentbox' ) || ( isset( $_GET['box'] ) && $_GET['box'] == 'sentbox' ) )
			$bpmt_user_data->box = 'sentbox';
		else
			$bpmt_user_data->box = 'inbox';

		bpmt_display_user_info();

		include_once( dirname( 	__FILE__ ) . '/templates/bpmt-messages-loop.php' );

	}
	else
		echo sprintf( _x( '<div class="error below-h2">ERROR - Member could not be found for: %s </div>', 'bpmt'),  $bpmt_user );

}


// if clicking on pagination
function bpmt_get_member_page() {
	global $bpmt_user_data;

	$bpmt_user_data = bpmt_get_user_data( $_GET['user'] );

	if( $bpmt_user_data != NULL ) {

		if( $_GET['box'] == 'sentbox' )
			$bpmt_user_data->box = 'sentbox';
		else
			$bpmt_user_data->box = 'inbox';

		bpmt_display_user_info();

		include_once( dirname( 	__FILE__ ) . '/templates/bpmt-messages-loop.php' );
	}
	else
		_e("<div class='error below-h2'>ERROR -  Member could not be found via pagination.</div>", 'bpmt');

}


function bpmt_get_thread_view() {
	global $bpmt_user_data;

	$bpmt_user_data = bpmt_get_user_data( $_GET['user'] );

	if( $bpmt_user_data != NULL ) {

		if( $_GET['box'] == 'sentbox' )
			$bpmt_user_data->box = 'sentbox';
		else
			$bpmt_user_data->box = 'inbox';

		$thread_id = $_GET['thread_id'];

		bpmt_display_user_info();

		include_once( dirname( 	__FILE__ ) . '/templates/bpmt-messages-thread.php' );

	}
	else
		_e("<div class='error below-h2'>ERROR - Message Thread could not be found.</div>", 'bpmt');

}


function bpmt_delete_thread() {
	global $wpdb;

	if( ! is_super_admin() )
		return false;

	if( ! check_admin_referer( 'bpmt_delete_thread' ) )
		return false;

	/**
	  * Unfortunately, we can't use messages_delete_thread( $id )
	  * because BP_Messages_Thread::delete  is hardcoded to bp_loggedin_user_id()
	  * Core devs made a todo note in BP_Messages_Thread::delete
	  * So we have to roll our own delete calls
	  */

	$thread_id = intval( $_GET['thread_id'] );

	$bp = buddypress();

	$wpdb->query( $wpdb->prepare( "UPDATE {$bp->messages->table_name_recipients} SET is_deleted = 1 WHERE thread_id = %d", $thread_id ) );

	$message_ids = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$bp->messages->table_name_messages} WHERE thread_id = %d", $thread_id ) );

	$recipients = $wpdb->get_results( $wpdb->prepare( "SELECT id FROM {$bp->messages->table_name_recipients} WHERE thread_id = %d AND is_deleted = 0", $thread_id ) );

	if ( empty( $recipients ) ) {

		$wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->messages->table_name_messages} WHERE thread_id = %d", $thread_id ) );

		foreach ( $message_ids as $message_id ) {

			bp_messages_delete_meta( $message_id );

		}

		$wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->messages->table_name_recipients} WHERE thread_id = %d", $thread_id ) );

		_e("<div class='updated below-h2'>Message Thread was deleted.</div>", 'bpmt');
	}
	else
		_e("<div class='error below-h2'>ERROR - There was a problem deleting that Message Thread.</div>", 'bpmt');

	do_action( 'messages_delete_thread', $_GET['thread_id'] );

	bpmt_get_member();
}


function bpmt_get_user_data( $user ) {
	global $wpdb;

	if( is_numeric( $user ) )
		$sql = "SELECT * FROM {$wpdb->prefix}users WHERE ID = $user ";
	else
		$sql = "SELECT * FROM {$wpdb->prefix}users WHERE user_login = '$user' ";

	$bpmt_user_data = $wpdb->get_row( $sql );

	return $bpmt_user_data;

}


function bpmt_display_user_info() {
	global $bpmt_user_data;

	echo '<p><span class="highlight">Display Name: ' . $bpmt_user_data->display_name
	. ' | Login Name: ' . $bpmt_user_data->user_login
	. ' | ID: ' . $bpmt_user_data->ID
	. ' | Box: ' . ucfirst( $bpmt_user_data->box )
	. '</span><p/>';

}

// create links for View Thread, Delete Thread, Back to Messages Loop
function bpmt_view_delete_back_link( $type ) {
	global $messages_template, $bpmt_user_data;

	$mpage = '';
	if( isset( $_GET['mpage'] ) )
		$mpage = '&mpage=' . $_GET['mpage'];
	else
		$mpage = '&mpage=1';

	$user_id = '&user=' . $bpmt_user_data->ID;

	$box = '&box=' . $bpmt_user_data->box;

	switch( $type ) {

		case 'view':
			$thread_id = '&thread_id=' . $messages_template->thread->thread_id;
			$link = site_url() . '/wp-admin/tools.php?page=bp-messages-tool&action=view-thread' . $mpage . $user_id . $thread_id . $box;
			break;

		case 'delete':
			$thread_id = '&thread_id=' . $messages_template->thread->thread_id;
			$link = wp_nonce_url( site_url() . '/wp-admin/tools.php?page=bp-messages-tool&action=delete-thread' . $mpage . $user_id . $thread_id . $box, 'bpmt_delete_thread' );
			break;

		case 'back':
			$link = site_url() . '/wp-admin/tools.php?page=bp-messages-tool&action=member-threads' . $mpage . $user_id . $box;
			break;

		default:
			$link = '';
			break;
	}

	return $link;
}


function bpmt_pagination ( $pag_links ) {
	global $bpmt_user_data;

	if( $_GET['page'] = 'bp-messages-tool' ) {

		$rep = 'member-threads&user=' . $bpmt_user_data->ID . '&box=' . $bpmt_user_data->box;

		$pag_links = str_replace( 'select-member', $rep, $pag_links );

	}

	return $pag_links;
}
add_filter( 'bp_get_messages_pagination', 'bpmt_pagination', 25 );


function bpmt_message_thread_last_post_date_raw( $date ){

	if( $_GET['page'] = 'bp-messages-tool' )
		$date = current( explode( " ", $date) );

	return $date;
}
add_filter('bp_get_message_thread_last_message_date', 'bpmt_message_thread_last_post_date_raw', 25 );

