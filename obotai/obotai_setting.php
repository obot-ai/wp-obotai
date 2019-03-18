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
        // 管理メニューに追加するフックChat Bot
        add_action('admin_menu', array($this, 'add_obotai_page'));
        add_shortcode( 'obotai_code', array( 'ObotAISettingCord', 'obotai_shortcode' ) );
        add_action('wp_head', array($this, 'obotai_head_function'));
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
            css text NOT NULL,
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
                    // テーブルに格納（url,css以外）
                    $wpdb->insert(
                        $table_name,
                        array(
                            'obotai_key' => $_POST['obotai_options']['key'],
                            'valid' => $_POST['obotai_options']['valid']
                        )
                    );
                    // 登録url,cssを行に分割
                    $array['url'] = explode("\n", $_POST['obotai_options']['url']);
                    $array['css'] = explode("\n", $_POST['obotai_options']['css']);
                    // 各行から空白を削除
                    $array['url'] = array_map('trim', $array['url']);
                    $array['css'] = array_map('trim', $array['css']);
                    // 文字数が0の行を取り除く
                    $array['url'] = array_filter($array['url'], 'strlen');
                    $array['css'] = array_filter($array['css'], 'strlen');
                    // indexを詰める
                    $array['url'] = array_values($array['url']);
                    $array['css'] = array_values($array['css']);
                    // テーブルに格納（url,css）
                    for($i=0, $j=0; ; $i++, $j++){
                        if($i<count($array['url']) || $j<count($array['css'])){
                            if(!$array['url'][$i]){
                                $array['url'][$i] = '';
                            }else{
                                // エンコード（urlのみ）
                                $array['url'][$i] = urlencode($array['url'][$i]);
                                // 記号を元に戻す
                                $array['url'][$i] = str_ireplace('%3a', ':', $array['url'][$i]);
                                $array['url'][$i] = str_ireplace('%2f', '/', $array['url'][$i]);
                                $array['url'][$i] = str_ireplace('%25', '%', $array['url'][$i]);
                            }
                            if(!$array['css'][$j]){
                                $array['css'][$j] = '';
                            }
                            $wpdb->insert(
                                $table_name,
                                array(
                                    'url' => $array['url'][$i],
                                    'css' => $array['css'][$j]
                                )
                            );
                        }else{
                            break;
                        }
                    }
                    // データベース昇順出力
                    $sql = "SELECT obotai_key,url,css,valid FROM ".$table_name;
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
                                value="<?php echo $results[0]->obotai_key ?>"
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
                                for($i=1; $i<count($results); $i++){
                                    if($results[$i]->url != ''){
                                        if($i>1){
                                            echo "\n";
                                        }
                                        echo urldecode($results[$i]->url);
                                    }
                                } 
                            ?></textarea>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="inputtext">CSS設定</label>
                        </th>
                        <td>
                            <textarea
                                    name="obotai_options[css]" 
                                    rows="5"
                                    cols="100"
                            ><?php
                                for($i=1; $i<count($results); $i++){
                                    if($results[$i]->css != ''){
                                        if($i>1){
                                            echo "\n";
                                        }
                                        echo $results[$i]->css;
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
                                if( $results[0]->valid == 'valid' ){
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
                                if( $results[0]->valid != 'valid' ){
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
                    </p>
                </form>
            </table>
        <!-- /.wrap --></div>
<?php
    }

    public function obotai_head_function() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'obotai_setting';

        // データベース昇順出力
        $sql = "SELECT css FROM ".$table_name;
        $results = $wpdb->get_results($sql);

        $arr_head = [
            '<script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>',
            '<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>',
            '<style type="text/css">',
            'html {overflow-y: scroll; }',
            'body {padding: 0; margin: 0; }',
            '#bot {border: 1px solid #000000; height: 600px; width: 460px; background-color: #FFFFFF; position: fixed; top: 100% - 100px);',
            'bottom: calc(0% + 50px); right: 40px; visibility: hidden; opacity: 0; max-height: calc(97% - 50px); max-width: 100%;}',
            '#bot_toggle {width: 80px; height: auto; position: fixed; bottom: calc(0%); right: 0.2vw; display: block; }',
            '.wc-message-groups {transform: translateY(0); outline: 0; overflow-x: hidden; overflow-y: scroll; padding: 10px; position: absolute; bottom: 50px; left: 0; right: 0; top: 40px; transition: transform 0.2s cubic-bezier(0, 0, 0.5, 1); }',
            '.wc-message-group-content {overflow: hidden; }',
            '.wc-message svg.wc-message-callout {height: 22px; position: absolute; stroke: none; top: 12px; width: 6px; }',
            '.wc-chatview-panel {overflow: hidden; position: absolute; right: 0; left: 0; top: 0; bottom: 0; }',
            '.wc-console {
  bottom: 0;
  box-sizing: border-box;
  height: 50px;
  left: 0;
  position: absolute;
  right: 0;
 }
  
  
  .wc-console > .wc-upload,
  .wc-console > .wc-textbox,
  .wc-console > .wc-send,
  .wc-console > .wc-mic {
    position: absolute;
    top: 0;
    vertical-align: middle; }
    
  .wc-console label, .wc-console button {
    cursor: pointer;
    display: inline-block;
    height: 40px; }
    
  .wc-console svg {
    fill: #8a8a8a;
    margin: 11px; }
    
  .wc-console input[type=text],
  .wc-console textarea {
    border: none;
    height: 100%;
    outline: none;
    padding: 0;
    resize: none;
    width: 100%; }
    
  .wc-console.has-text .wc-send svg {
    fill: #0078d7; }
    
  .wc-console .wc-upload {
    cursor: pointer;
    position: relative; }
    
    .wc-console .wc-upload svg {
      height: 18px;
      width: 26px; }
      
  .wc-console #wc-upload-input {
    font-size: 0;
    height: 0;
    left: 0;
    opacity: 0;
    outline: 0;
    position: absolute;
    top: 0;
    width: 0; }
    
  .wc-console .wc-send {
    right: 0; }
    
  .wc-console .wc-send.hidden {
    visibility: hidden; }
    
  .wc-console.has-upload-button .wc-textbox {
    left: 48px; }
    
  .wc-console .wc-textbox {
    bottom: 0;
    left: 11px;
    right: 49px; }
    
    .wc-console .wc-textbox input {
      background-color: transparent; }
      
  .wc-console .wc-mic,
  .wc-console .wc-send {
    background-color: transparent;
    border: 0;
    padding: 0;
    right: 0; }
    
    .wc-console .wc-mic.hidden,
    .wc-console .wc-send.hidden {
      visibility: hidden; }
      
  .wc-console .wc-send svg {
    height: 18px;
    width: 27px; }
    
  .wc-console .wc-mic.active path#micFilling {
    fill: #4e3787; }
    
  .wc-console .wc-mic.inactive path#micFilling {
    visibility: hidden; }

.wc-console.has-text .wc-send svg {
  fill: #0078d7; }',
            '</style>',
        ];
        if( $results[1]->css){
            for($i=1; $i<count($results); $i++) {
                $arr_css[] = '<link href="'.$results[$i]->css.'" rel="stylesheet" />';
            }
        }

        if(!empty($arr_css)){
            $arr_head = array_merge($arr_head, $arr_css);
        }
        $arr_head = implode('', $arr_head);
        echo $arr_head;
    }

    public function obotai_footer_function() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'obotai_setting';

        // データベース昇順出力
        $sql = "SELECT obotai_key,url,valid FROM ".$table_name;
        $results = $wpdb->get_results($sql);

        // 現在地
        if(is_front_page() || is_home()){
            $now_url = home_url();
        }else{
            $now_url = get_permalink();
        }
        $now_url = "/".preg_quote($now_url, '/')."/i";        // 大文字小文字区別しないようにしておく
        // URL登録
        $url_list = [];
        foreach ($results as $value) {
            $url_list[] = $value->url;
        }

        if( $results[0]->valid == 'valid' ){
            // ウェブチャット表示設定時
            if(preg_grep($now_url, $url_list)){
                // 現在地が登録URLに含まれる場合チャットは非表示
                return;
            }else{
                // チャット表示
                $short_cord = '[obotai_code obotai_code_id='.$results[0]->obotai_key.']';
                echo do_shortcode($short_cord);
            }
        }else{
            // ウェブチャット非表示設定時
            return;
        }
    }
}

class ObotAISettingCord {
    function obotai_shortcode($atts){
        $atts = shortcode_atts(
            array(
                'obotai_code_id' => '未設定'    //初期値
            ),
            $atts,
            'obotai_code'    //ショートコード名
        );

        if( $atts['obotai_code_id'] == '未設定'){
            $msg = "IDが未設定です";
        } else {
            $arr_footer = [
                '<div id="bot_toggle">',
                '<img src="http://demo.obot-ai.com/demo_apparel/images/bot_icon_sp.svg"></div>',
                '<div id="bot" >',
                '<script src="//cdn.botframework.com/botframework-webchat/latest/botchat.js"></script>',
                '<script>',
                'BotChat.App({',
                "directLine: { secret: '".$atts['obotai_code_id']."' },",
                "user: { id: 'userid' }, bot: { id: 'botid' }, resize: 'window', chatTitle: 'Consult with the coordinator', showUploadButton: false",
                "}, document.getElementById('bot'));",
                '/* トグル表示 */',
                '$(function(){',
                "$('#bot').draggable({ handle: '.wc-header' });",
                "$('#bot_toggle').on('click', function(){",
                "$('#bot').css('visibility')=='hidden' ? $('#bot').css({visibility:'visible'}).animate({opacity: 1}, 500) : $('#bot').css({visibility:'hidden'}).animate({opacity: 0}, 500);",
                "});",
                "})",
                '</script></div>',
            ];
            $arr_footer = implode('', $arr_footer);
        }
        return $arr_footer;
    }
}

$obotaisetting = new ObotAISetting;
$obotaisettingcord = new ObotAISettingCord;

?>