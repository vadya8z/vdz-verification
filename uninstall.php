<?php
/**
 *
 *  * @ author ( Zikiy Vadim )
 *  * @ site http://online-services.org.ua
 *  * @ name
 *  * @ copyright Copyright (C) 2016 All rights reserved.
 */

// if uninstall/delete not called from WordPress exit
if ( ! defined( 'ABSPATH' ) && ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

// Удаляем все опции сохраненные плагином
delete_option( 'vdz_verification_google' );
delete_option( 'vdz_verification_yandex' );
delete_option( 'vdz_verification_bing' );
delete_option( 'vdz_verification_custom' );
