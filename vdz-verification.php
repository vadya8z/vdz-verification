<?php
/**
Plugin Name: VDZ Verification Plugin
Plugin URI:  http://online-services.org.ua
Description: Simple add site verification code for Google, Bing, Yandex. Add on site other CUSTOM Meta Tags
Version:     1.4.9
Author:      VadimZ
Author URI:  http://online-services.org.ua#vdz-verification
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'VDZ_VERIFICATION_API', 'vdz_info_verification' );

require_once 'api.php';
require_once 'updated_plugin_admin_notices.php';

// Код активации плагина
register_activation_hook( __FILE__, 'vdz_verification_activate_plugin' );
function vdz_verification_activate_plugin() {
	global $wp_version;
	if ( version_compare( $wp_version, '3.8', '<' ) ) {
		// Деактивируем плагин
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die( 'This plugin required WordPress version 3.8 or higher' );
	}
	do_action( VDZ_VERIFICATION_API, 'on', plugin_basename( __FILE__ ) );
}

// Код деактивации плагина
register_deactivation_hook( __FILE__, function () {
	$plugin_name = preg_replace( '|\/(.*)|', '', plugin_basename( __FILE__ ));
	$response = wp_remote_get( "http://api.online-services.org.ua/off/{$plugin_name}" );
	if ( ! is_wp_error( $response ) && isset( $response['body'] ) && ( json_decode( $response['body'] ) !== null ) ) {
		//TODO Вывод сообщения для пользователя
	}
} );
//Сообщение при отключении плагина
add_action( 'admin_init', function (){
	if(is_admin()){
		$plugin_data = get_plugin_data(__FILE__);
		$plugin_slug    = isset( $plugin_data['slug'] ) ? $plugin_data['slug'] : sanitize_title( $plugin_data['Name'] );
		$plugin_id_attr = $plugin_slug;
		$plugin_name = isset($plugin_data['Name']) ? $plugin_data['Name'] : ' us';
		$plugin_dir_name = preg_replace( '|\/(.*)|', '', plugin_basename( __FILE__ ));
		$handle = 'admin_'.$plugin_dir_name;
		wp_register_script( $handle, '', null, false, true );
		wp_enqueue_script( $handle );
		$msg = '';
		if ( function_exists( 'get_locale' ) && in_array( get_locale(), array( 'uk', 'ru_RU' ), true ) ) {
			$msg .= "Спасибо, что были с нами! ({$plugin_name}) Хорошего дня!";
		}else{
			$msg .= "Thanks for your time with us! ({$plugin_name}) Have a nice day!";
		}
		if(substr_count( $_SERVER['REQUEST_URI'], 'plugins.php')){
			wp_add_inline_script( $handle, "if(document.getElementById('deactivate-".esc_attr($plugin_id_attr)."')){document.getElementById('deactivate-".esc_attr($plugin_id_attr)."').onclick=function (e){alert('".esc_attr( $msg )."');}}" );
		}
	}
} );




if ( ! function_exists( 'vdz_get_allow_meta_tag' ) ) {
	function vdz_get_allow_meta_tag( $custom_allowed_tags = array() ) {
		$allowed_tags = array(
			'meta' => array(
				'name'       => array(),
				'content'    => array(),
				'charset'    => array(),
				'http-equiv' => array(),
				'scheme'     => array(),
				'itemprop'   => array(),
			),
		);
		if ( is_array( $custom_allowed_tags ) ) {
			$allowed_tags = array_merge( $allowed_tags, $custom_allowed_tags );
		}
		return $allowed_tags;
	}
}
if ( ! function_exists( 'vdz_sanitize_meta_tag' ) ) {
	function vdz_sanitize_meta_tag( $str = '' ) {
		if ( ! substr_count( $str, 'meta' ) ) {
			return '';
		}
		return wp_kses( $str, vdz_get_allow_meta_tag() );
	}
}

/*Добавляем новые поля для в настройках шаблона шаблона для верификации сайта*/
function vdz_verification_theme_customizer( $wp_customize ) {

	if ( ! class_exists( 'WP_Customize_Control' ) ) {
		exit;
	}

	/**Кастомный рендер для поля textarea*/
	class VDZ_Verification_Textarea_Control extends WP_Customize_Control {
		public $type = 'textarea';

		public function render_content() {
			?>
			<label>
				<?php if ( ! empty( $this->label ) ) : ?>
					<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
					<?php
				endif;
				if ( ! empty( $this->description ) ) :
					?>
					<span class="description customize-control-description"><?php echo $this->description; ?></span>
				<?php endif; ?>
				<textarea rows="3" <?php $this->link(); ?> <?php $this->input_attrs(); ?>><?php echo esc_textarea( $this->value() ); ?></textarea>
			</label>
			<?php
		}
	}

	// Добавляем логотип
	$wp_customize->add_section(
		'vdz_verification_section',
		array(
			'title'       => __( 'VDZ Verification' ),
			'priority'    => 10,
			'description' => __( 'Add verification code (or Custom Meta Tag) on site' ),
		)
	);
	// Добавляем настройки
	$wp_customize->add_setting(
		'vdz_verification_google',
		array(
			'type'              => 'option',
			'sanitize_callback' => 'vdz_sanitize_meta_tag',
		)
	);
	$wp_customize->add_setting(
		'vdz_verification_yandex',
		array(
			'type'              => 'option',
			'sanitize_callback' => 'vdz_sanitize_meta_tag',
		)
	);
	$wp_customize->add_setting(
		'vdz_verification_bing',
		array(
			'type'              => 'option',
			'sanitize_callback' => 'vdz_sanitize_meta_tag',
		)
	);
	$wp_customize->add_setting(
		'vdz_verification_custom',
		array(
			'type'              => 'option',
			'sanitize_callback' => 'vdz_sanitize_meta_tag',
		)
	);

	// Google
	$wp_customize->add_control(
		new VDZ_Verification_Textarea_Control(
			$wp_customize,
			'vdz_verification_google',
			array(
				'label'       => __( 'Google site verification' ),
				'section'     => 'vdz_verification_section',
				'settings'    => 'vdz_verification_google',
				'type'        => 'textarea',
				'description' => __( 'Add Google Meta Tag here:' ),
				'input_attrs' => array(
					'placeholder' => '<meta name="google-site-verification" content="m7TT80UfFSr_irxVkvMpFiHeWBsR8f1YdAisML7tOEk" />', // для примера
				),
			)
		)
	);
	// Yandex
	$wp_customize->add_control(
		new VDZ_Verification_Textarea_Control(
			$wp_customize,
			'vdz_verification_yandex',
			array(
				'label'       => __( 'Yandex site verification' ),
				'section'     => 'vdz_verification_section',
				'settings'    => 'vdz_verification_yandex',
				'description' => __( 'Add Yandex Meta Tag here:' ),
				'type'        => 'textarea',
				'input_attrs' => array(
					'placeholder' => '<meta name="yandex-verification" content="c878fa12b3c5522e" />', // для примера
				),
			)
		)
	);
	// Bing
	$wp_customize->add_control(
		new VDZ_Verification_Textarea_Control(
			$wp_customize,
			'vdz_verification_bing',
			array(
				'label'       => __( 'Bing site verification' ),
				'section'     => 'vdz_verification_section',
				'settings'    => 'vdz_verification_bing',
				'description' => __( 'Add Bing Meta Tag here:' ),
				'type'        => 'textarea',
				'input_attrs' => array(
					'placeholder' => '<meta name="msvalidate.01" content="80AD548D2B6E73D872F3786C22BE3327" />', // для примера
				),
			)
		)
	);

	// Other
	$wp_customize->add_control(
		new WP_Customize_Control(
			$wp_customize,
			'vdz_verification_custom',
			array(
				'label'       => __( 'Other custom Meta Tags' ),
				'section'     => 'vdz_verification_section',
				'settings'    => 'vdz_verification_custom',
				'description' => __( 'Add other Meta Tag here (1 tag in 1 line, second meta tag in new line): ' ),
				'type'        => 'textarea',
			)
		)
	);

	// Добавляем ссылку на сайт
	$wp_customize->add_setting(
		'vdz_verification_link',
		array(
			'type' => 'option',
		)
	);
	$wp_customize->add_control(
		new WP_Customize_Control(
			$wp_customize,
			'vdz_verification_link',
			array(
				// 'label'    => __( 'Link' ),
							'section' => 'vdz_verification_section',
				'settings'            => 'vdz_verification_link',
				'type'                => 'hidden',
				'description'         => '<br/><a href="//online-services.org.ua#vdz-verification" target="_blank">VadimZ</a>',
			)
		)
	);
}
add_action( 'customize_register', 'vdz_verification_theme_customizer', 1 );


// Добавляем допалнительную ссылку настроек на страницу всех плагинов
add_filter(
	'plugin_action_links_' . plugin_basename( __FILE__ ),
	function( $links ) {
		$settings_link = '<a href="' . get_admin_url() . 'customize.php?autofocus[section]=vdz_verification_section">' . __( 'Settings' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}
);

// Добавляем мета теги в head
add_action( 'wp_head', 'vdz_verification_add_meta_in_head', 1 );
function vdz_verification_add_meta_in_head() {

	$head_str = "\r\n" . '<!--Start VDZ Verification Plugin-->' . "\r\n";

	$vdz_verification_google = get_option( 'vdz_verification_google' );
	if ( ! empty( $vdz_verification_google ) ) {
		$head_str .= trim( $vdz_verification_google ) . "\r\n";
	}
	$vdz_verification_yandex = get_option( 'vdz_verification_yandex' );
	if ( ! empty( $vdz_verification_yandex ) ) {
		$head_str .= trim( $vdz_verification_yandex ) . "\r\n";
	}
	$vdz_verification_bing = get_option( 'vdz_verification_bing' );
	if ( ! empty( $vdz_verification_bing ) ) {
		$head_str .= trim( $vdz_verification_bing ) . "\r\n";
	}
	$vdz_verification_custom = get_option( 'vdz_verification_custom' );
	if ( ! empty( $vdz_verification_custom ) ) {
		$head_str .= trim( $vdz_verification_custom ) . "\r\n";
	}
	$head_str .= '<!--End VDZ Verification Plugin-->' . "\r\n\r\n";
	echo $head_str;
}

