<?php

    if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
        exit();
    }

    function obotai_delete_plugin() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'obotai_setting';
        //データベースのテーブル削除
        $sql = "DROP TABLE ".$table_name;
        $wpdb->query($sql);
    }

    obotai_delete_plugin();
