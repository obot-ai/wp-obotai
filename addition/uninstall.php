<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

function addition_delete_plugin() {
	global $wpdb;
	
	$table_name = $wpdb->prefix . 'moj_table';
//データベースのテーブル削除
      $sql = "DROP TABLE ".$table_name;
      $wpdb->query($sql);
}

addition_delete_plugin();
