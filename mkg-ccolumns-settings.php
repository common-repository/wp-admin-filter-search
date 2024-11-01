		
<style>
	.mkg-ccolumns .card{
		max-height: 250px;
		overflow-y: auto;
	}
</style>

<?php if($result):?>
	<div id="message" class="updated notice is-dismissible">
		<p><?php _e('Settings updated.')?></p>
		<button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php _e('Dismiss this notice.')?></span></button>
	</div>
<?php endif;?>

<div class="mkg-ccolumns wrap">
	<h2><?php _e('MKG CColumns Settings')?></h2>
	<form action="" method="post">
		<?php wp_nonce_field( 'mkg-ccolumns-settings' );?>
		<input type="hidden" name="action" value="settings">
		
		<?php if($this->post_types){
			
			global $wpdb;
			$query = "SELECT DISTINCT pm.meta_key FROM {$wpdb->postmeta} pm
				LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id AND pm.meta_key NOT LIKE '%s'
				WHERE p.post_type = '%s'";
			
			foreach($this->post_types as $pt){
				$object = get_post_type_object($pt);?>
				<h4><?= $object->labels->name?></h4>
				<div class="card pressthis">
					<table class="form-table">
						<tbody>
							<?php if($r = $wpdb->get_results( $wpdb->prepare($query, "\_%", $pt))){
								foreach($r as $val){
									$check = ('on' == $this->options['keys'][$pt][$val->meta_key]) ? 'checked' : '';?>
									<tr>
										<td><label for="<?= $val->meta_key?>"><?= $val->meta_key?></label></td>
										<td><input type="checkbox" id="<?= $val->meta_key?>" name="keys[<?= $pt?>][<?= $val->meta_key?>]" <?= $check?>/></td>
									</tr>
								<?php }
							}else{?>
								<tr><td colspan="2"><?php _e('Custom fields not found!')?></td></tr>
							<?php }?>
						</tbody>
					</table>
				</div>
			<?php }
		}?>
		<p><input type="submit" name="settings_btn" class="button button-primary" value="<?php _e('Update Settings')?>"></p>
	</form>
</div>