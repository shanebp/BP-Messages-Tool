<?php

/**
 * Messages Loop
 * Custom template for BuddyPress Messages Tool
 * Cannot be overloaded
 *
 */


if( !isset( $_GET['user'] ) )
	$bpmt_get_member = '&user_id=' . $bpmt_user_data->ID;
else
	$bpmt_get_member = '&user_id=' . $_GET['user'];


if( isset( $_GET['mpage'] ) )
	$bpmt_get_member .= '&mpage=' . $_GET['mpage'];

$bpmt_get_member .= '&box=' . $bpmt_user_data->box;


?>

<?php if ( bp_has_message_threads( bp_ajax_querystring( 'messages' ) . $bpmt_get_member ) ) : ?>

	<div class="pagination no-ajax" id="user-pag">

		<div class="pag-count" id="messages-dir-count">
			<?php bp_messages_pagination_count(); ?>
		</div>

		<div class="pagination-links" id="messages-dir-pag">
			<?php bp_messages_pagination(); ?>
		</div>

	</div><!-- .pagination -->

	<br/>

	<table id="message-threads" cellspacing="15">

			<tr>
				<td style="width:20%;vertical-align:top;"><strong><?php _e( 'Participants / Meta', 'bpmt' ); ?></strong></td>
				<td style="width:70%;vertical-align:top;"><strong><?php _e( 'Threads', 'bpmt' ); ?></strong></td>
				<td style="width:10%;vertical-align:top;"><strong><?php _e( 'Delete', 'bpmt' ); ?></strong></td>
			</tr>

			<?php while ( bp_message_threads() ) : bp_message_thread(); ?>

				<tr id="m-<?php bp_message_thread_id(); ?>" class="<?php bp_message_css_class(); ?><?php if ( bp_message_thread_has_unread() ) : ?> unread<?php else: ?> read<?php endif; ?>">

					<td style="width:20%;vertical-align:top;">
						<p>
						<?php bp_message_thread_to(); ?>
						<br/>
						<?php  _e( 'Message Count: ', 'bpmt' ); echo bp_get_message_thread_total_count(); ?>
						<br/><?php bp_message_thread_last_post_date_raw(); ?>
						</p>
					</td>

					<td style="width:70%;vertical-align:top;">
						<p><a href="<?php echo bpmt_view_delete_back_link('view'); ?>" title="<?php _e( "View Thread", "bpmt" ); ?>"><?php echo stripslashes( bp_get_message_thread_subject() ); ?></a></p>
						<p class="thread-excerpt"><?php echo stripslashes( bp_get_message_thread_content() ); ?></p>
					</td>

					<td style="width:10%;vertical-align:top;">
						<p>
						<a class="submitdelete" href="<?php echo bpmt_view_delete_back_link('delete'); ?>" onclick="return confirm('<?php _e( "Are you sure you want to Delete this Message Thread?", "bpmt" ); ?>');" title="<?php _e( "Delete Thread", "bpmt" ); ?>"><?php _e( 'Delete', 'bpmt' ); ?></a>
						</p>
					</td>
				</tr>

			<?php endwhile; ?>

	</table><!-- #message-threads -->

	<br/>

	<div class="pagination no-ajax" id="user-pag">

		<div class="pag-count" id="messages-dir-count">
			<?php bp_messages_pagination_count(); ?>
		</div>

		<div class="pagination-links" id="messages-dir-pag">
			<?php bp_messages_pagination(); ?>
		</div>

	</div><!-- .pagination -->


<?php else: ?>

	<div id="message" class="info">
		<p><?php _e( 'Sorry, no messages were found for that member.', 'bpmt' ); ?></p>
	</div>

<?php endif;?>
