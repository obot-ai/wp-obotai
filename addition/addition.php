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
		global $obotai_db_version;

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
		add_menu_page('ObotAI ウェブチャット設定', 'ObotAI', 'level_8', __FILE__, array($this,'obotai_option_page'), '');
	}

	public function obotai_option_page() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'obotai_setting';

		if ( isset($_POST['obotai_options'])) {
			check_admin_referer('shoptions');
			// テーブル初期化
			$sql = "DELETE FROM ".$table_name;
			$wpdb->query($sql);
			// タイムゾーン設定
			date_default_timezone_set('Asia/Tokyo');
			// 更新時刻をテーブルに格納
					$wpdb->insert(
						$table_name,
						array(
							'date' => date('Y-m-d H:i:s')
						)
					);
?>
			<div id="message" class="updated notice is-dismissible">
				<p><strong><?php _e('保存しました'); ?></strong></p>
			</div>
<?php
		}
?>
		<div class="wrap">
			<div id="icon-edit-comments" class="icon32"><br /></div>
			<h2>ObotAI ウェブチャット設定</h2>
			<table class="table">
				<form action="" method="post">
<?php
					wp_nonce_field('shoptions');
					// テーブルに格納（url以外）
					$wpdb->insert(
						$table_name,
						array(
							'obotai_key' => $_POST['obotai_options']['key'],
							'valid' => $_POST['obotai_options']['valid']
						)
					);
					// 登録urlを行に分割
					$array = explode("\n", $_POST['obotai_options']['url']);
					// 各行から空白を削除
					$array = array_map('trim', $array);
					// 文字数が0の行を取り除く
					$array = array_filter($array, 'strlen');
					// テーブルに格納（urlのみ）
					foreach ($array as $value) {
						// エンコード
						$value = urlencode($value);
						// 記号を元に戻す
						$value = str_ireplace('%3a', ':', $value);
						$value = str_ireplace('%2f', '/', $value);
						$wpdb->insert(
							$table_name,
							array(
								'url' => $value
							)
						);
					}
					// データベース昇順出力
					$sql = "SELECT obotai_key,url,valid,date FROM ".$table_name;
					$results = $wpdb->get_results($sql);	
?>
					<tr valign="top">
						<th scope="row">
							<label for="inputtext">シークレットキー</label>
						</th>
						<td>
							<input
								   name="obotai_options[key]"
								   type="text"
								   size="100"
								   value="<?php echo $results[1]->obotai_key ?>"
							/>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="inputtext">非表示ページ</label>
						</th>
						<td>
							<textarea
									  name="obotai_options[url]" 
									  rows="10"
									  cols="100"
							><?php
								for($i=2; $i<count($results); $i++){
									echo urldecode($results[$i]->url);
									if($i<count($results)-1){
										echo "\n";
									}
								} 
							?></textarea>
						</td>
					</tr>
				<tr valign="top">
					<th scope="row">表示設定</th>
					<td>
						<input
							   name="obotai_options[valid]"
							   type="radio"
							   value="valid"
<?php
								if( $results[1]->valid == 'valid' ){
?>
							   		checked
<?php	
								}
?>
						>表示
						<input
							   name="obotai_options[valid]"
							   type="radio"
							   value="unvalid"
<?php
								if( $results[1]->valid != 'valid' ){
?>
							   		checked
<?php	
								}
?>
						>非表示
					</td>
				</tr>
					<p class="submit">
							<input
								   name="save"
								   type="submit"
								   class="button-primary"
								   value="保存"
							/>
<?php
							if($results[0]->date){
								echo "最終更新：".$results[0]->date;
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

		// データベース昇順出力
		$sql = "SELECT obotai_key,url,valid FROM ".$table_name;
		$results = $wpdb->get_results($sql);
		
		// 現在地
		$now_url = get_permalink();
		$now_url = "/". preg_quote($now_url, '/')."/i";		// 大文字小文字区別しないようにしておく
		// URL登録
		$url_list = [];
		foreach ($results as $value) {
			$url_list[] = $value->url;
		}
		
		if( $results[1]->valid == 'valid' ){
			// ウェブチャット表示設定時
			if(preg_grep($now_url, $url_list)){
				// 現在地が登録URLに含まれる場合チャットは非表示
				exit;
			}else{
				// チャット表示
				$short_cord = '[obotai_code obotai_code_id='.$results[1]->obotai_key.']';
				echo do_shortcode($short_cord);
			}
		}else{
			// ウェブチャット非表示設定時
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
				'</style><link href="file:///Users/suzukianna/Desktop/addition_759/add_css.css" rel="stylesheet"></head>',
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