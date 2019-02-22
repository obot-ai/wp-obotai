<?php
/*
Plugin Name: ObotAI
Plugin URI: 
Description: ObotAIのWebChatを設置するプラグイン
Version: 1.0.0
Author: Mariana Ocean Japan
Author URI: https://obot-ai.com/
License: GPL2
*/

/*  Copyright 2019 Mariana Ocean Japan Co., Ltd. (email : obotai@marianaocean.com)
 
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
     published by the Free Software Foundation.
 
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
 
    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class ObotAISetting {
	public $obotai_db_version = '1.0';

	function __construct() {
		register_activation_hook( __FILE__, array($this, 'obotai_install'));
		// 管理メニューに追加するフック
		add_action('admin_menu', array($this, 'add_obotai_page'));
		add_shortcode( 'obotai_code', array( 'ObotAISettingCord', 'obotai_shortcode' ) );
		add_action('wp_footer', array($this, 'obotai_footer_function'));
	}

	function obotai_install() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'obotai_setting';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			obotai_key text NOT NULL,
			url text NOT NULL,
			valid text NOT NULL,
			UNIQUE KEY id (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
		
		update_option('obotai_db_version', $obotai_db_version);
	}

	function add_obotai_page() {
		add_menu_page('webchat設定', 'webchat設定', 'level_8', __FILE__, array($this,'obotai_option_page'), '');
	}

	public function obotai_option_page() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'obotai_setting';

		if ( isset($_POST['obotai_options'])) {
			check_admin_referer('shoptions');
?>
			<div id="message" class="updated notice is-dismissible">
				<p><strong><?php _e('保存しました'); ?></strong></p>
			</div>
<?php
		}else if ( isset($_POST['add'])) {
			check_admin_referer('shoptions');
			$wpdb->insert(
				$table_name,
				array( 'valid' => 'true' )
			);
?>
			<div id="message" class="updated notice is-dismissible">
				<p><strong><?php _e('Webchatを設置しました'); ?></strong></p>
			</div>
<?php
		}else if ( isset($_POST['remove'])) {
			check_admin_referer('shoptions');
			$wpdb->insert(
				$table_name,
				array( 'valid' => 'false' )
			);
?>
			<div id="message" class="updated notice is-dismissible">
				<p><strong><?php _e('Webchatを解除しました'); ?></strong></p>
			</div>
<?php
		}
?>
		<head><link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css"></head>
		<div class="wrap">
			<div id="icon-edit-comments" class="icon32"><br /></div>
			<h2>Webchat設定</h2>
			<table class="table">
				<form action="" method="post">
<?php
					wp_nonce_field('shoptions');
					// テーブルに格納
					$wpdb->insert(
						$table_name,
						array( 'obotai_key' => $_POST['obotai_options']['key'] )
					);
					// シークレットキー及びプラグイン判定検索用（降順）
					$sql_desc = "SELECT obotai_key,valid FROM ".$table_name." ORDER BY id DESC";
					$results_desc = $wpdb->get_results($sql_desc);
?>
					<tr valign="top">
						<th scope="row">
							<label for="inputtext">シークレットキー</label>
						</th>
						<td>
							<input
								   name="obotai_options[key]"
								   type="text"
								   class="regular-text"
							/>
						</td>
						<td>
							<input
						   		type="submit"
						   		name="Submit"
						   		class="btn btn-success btn-sm"
						   		value="登録"
							/>
						</td>
					</tr>
				</form>
				<form action="" method="post">
<?php
					wp_nonce_field('shoptions');
					// テーブルに格納
					$wpdb->insert(
						$table_name,
						array( 'url' => $_POST['obotai_options']['url'] )
					);
					// 登録URL検索用（昇順）
					$sql_asc = "SELECT url FROM ".$table_name;
					$results_asc = $wpdb->get_results($sql_asc);
?>
					<tr valign="top">
						<th scope="row">
							<label for="inputtext">非表示ページ</label>
						</th>
						<td>
							<input
								   name="obotai_options[url]" 
								   type="text"
								   class="regular-text"
							/>
						</td>
						<td>
							<input
								type="submit"
								name="Submit"
						   		class="btn btn-success btn-sm"
						   		value="登録"
							/>
							<input
								type="submit"
								name="Submit"
						   		class="btn btn-danger btn-sm"
						   		value="削除"
							/>
						</td>
					</tr>
				</form>
				<tr valign="top">
					<th scope="row">設定</th>
					<td><ul style="list-style:none;">
						<li>【シークレットキー】</li>
						<li>
<?php
							if(count($results_desc)){
								for($i=0; $i<count($results_desc); $i++){
									if($results_desc[$i]->obotai_key){
										// 最新のものだけ表示する
										echo $results_desc[$i]->obotai_key;
										break;
									}else if($i == count($results_desc)-1){
										// 要素全てが空のとき
										echo "未設定";
									}
								}
							}else{
								// 初期表示
								echo "未設定";
							}
?>
						</li>
						<li>【非表示ページ】</li>
<?php
						if(count($results_asc)){
							$k=0;
							for($i=0; $i<count($results_asc); $i++){
								if($results_asc[$i]->url){
									// 存在する時だけ表示する
									echo "<li>".urldecode($results_asc[$i]->url)."</li>";
									$k ++;
								}
							}
							if($k == 0){
								// 要素全てが空のとき
								echo "未設定";
							}
						}else{
							// 初期表示
							echo "未設定";
						}
?>
					</ul></td>
				</tr>
				<form action="" method="post">
					<p class="submit">
<?php
						wp_nonce_field('shoptions');
						// プラグイン有効判定
						for($i=0; $i<count($results_desc); $i++){
							if($results_desc[$i]->valid){
								// 最新の設定を反映
								$valid = $results_desc[$i]->valid;
								break;
							}
						}
						if($valid == 'true'){
							// プラグイン有効時
?>
							<input
								   name="remove"
								   type="submit"
								   class="btn btn-danger btn-sm"
								   value="Webchatを解除"
							/>
<?php
						}else{
							// プラグイン無効時
?>
							<input
								   name="add"
								   type="submit"
								   class="btn btn-primary btn-sm"
								   value="Webchatを設置"
							/>
<?php
						}
?>
					</p>
				</form>	
			</table>
		<!-- /.wrap --></div>
<?php
	}

	public function obotai_footer_function() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'obotai_setting';

		// シークレットキー及びプラグイン判定検索用（降順）
		$sql_desc = "SELECT obotai_key,valid FROM ".$table_name." ORDER BY id DESC";
		$results_desc = $wpdb->get_results($sql_desc);
		// 登録URL検索用（昇順）
		$sql_asc = "SELECT url FROM ".$table_name;
		$results_asc = $wpdb->get_results($sql_asc);
		
		// 現在地
		$now_url = get_permalink();
		$now_url = urldecode($now_url);
		// URL登録
		$url_list = [];
		foreach ($results_asc as $value) {
			$url_list[] = urldecode($value->url);
		}
		// シークレットキー登録
		foreach ($results_desc as $value) {
			if($value->obotai_key){
				// 最新のものだけ登録する
				$addition_cord = $value->obotai_key;
				break;
			}
		}
		// プラグイン有効判定
		for($i=0; $i<count($results_desc); $i++){
			if($results_desc[$i]->valid){
				// 最新の設定を反映
				$valid = $results_desc[$i]->valid;
				break;
			}
		}
		
		if($valid == 'true'){
			// プラグイン有効時
			if(in_array($now_url, $url_list)){
				// 現在地が登録URLに含まれる場合チャットは非表示
				exit;
			}else{
				// チャット表示
				$short_cord = '[obotai_code obotai_code_id='.$addition_cord.']';
				echo do_shortcode($short_cord);
			}
		}else{
			// プラグイン無効時
			exit;
		}
	}
}

class ObotAISettingCord {
	function obotai_shortcode($atts){
		$atts = shortcode_atts(
			array(
				'obotai_code_id' => '未設定'	//初期値
			),
			$atts,
			'obotai_code'	//ショートコード名
		);
		
		if( $atts['obotai_code_id'] == '未設定'){
			$msg = "IDが未設定です";
		} else {
			$arr = [
				'<!DOCTYPE html>',
				'<html><head><meta charset="UTF-8">',
				'<style>',
				'* {margin: 0px; box-sizing: border-box;}',
				'#webchat,',
				'#webchat > * {border: 1px solid #cccccc; height: 400px; max-width: 100%;}',
				'</style></head>',
				'<body><div id="webchat" >',
				'<script src="//cdn.botframework.com/botframework-webchat/latest/webchat.js"></script>',
				'<script>',
				'window.WebChat.renderWebChat({',
				"directLine: window.WebChat.createDirectLine({ secret: '".$atts['obotai_code_id']."' }),",
				"user: { id: 'userid' }",
				"}, document.getElementById('webchat'));",
				'</script></div></body></html>',
			];
			$msg = implode('', $arr);
		}
		return $msg;
	}
}

$obotaisetting = new ObotAISetting;
$obotaisettingcord = new ObotAISettingCord;

?>