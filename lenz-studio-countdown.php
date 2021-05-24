<?php
/**
 * Plugin Name: LZ Count down timer
 * Description: カウントダウンタイマーです。ショートコードで追加できます。
 * Author: 銀ねこアトリエ
 * Version: 1.0
 * Author URI: https://ginneko-atelier.com
 *
 * @package Count Down Timer
 * @version 1.0
 */

/*
 * プラグインパス
*/
define( 'CD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
add_action( 'template_redirect', 'lz_redirect' );

/*
 * Redirectキャンペーンが終わったらリダイレクト
 * @return void
*/
function lz_redirect() {
	$end_date = strtotime( get_option( 'lzcd-date' ) );
	// $now      = strtotime( wp_date( 'Y-m-d H:i:s', strtotime( '-1 hour' ) ) );
	$now      = strtotime(wp_date(  "Y-m-d H:i:s" ));

	global $post;
	if ( has_shortcode( $post->post_content, 'show_timer' ) && $end_date <= $now ) {
		wp_redirect( esc_url( get_option( 'lzcd-redirecturl' ) ? get_option( 'lzcd-redirecturl' ) : home_url( '/' ) ) );
		exit;
	}
}

/**
 * Fonts
 *
 * @return $root
 */
function google_font() {
	$font = array(
		''                      => '使わない',
		'Montserrat'            => 'Montserrat:wght@600',
		'Montserrat Alternates' => 'Montserrat+Alternates:wght@600',
		'Roboto Mono'           => 'Roboto+Mono:wght@600',
		'Rubik Mono One'        => 'Rubik+Mono+One',
		'Numans'                => 'Numans',
	);
	return $font;
}

/**
 * Add menu
 *
 * @return void
 */
function lz_count_down_menu_page() {
	add_menu_page(
		'Count Down Timer',
		'Count Down Timer',
		'manage_options',
		'lz_count_down_menu_page',
		'add_lz_count_down_menu_page',
		'dashicons-calendar-alt',
		20
	);
}
add_action( 'admin_menu', 'lz_count_down_menu_page' );

$page = 'lz_count_down_menu_page';

function lz_value() {
	return array(
		date         => 'lzcd-date',
		label        => 'name="lzcd-label-first',
		redirect_url => 'lzcd-redirecturl',
		font         => 'lzcd-font',
		unit         => 'lzcd-unit',
		num_color    => 'lzcd-num-color',
		label_color  => 'lzcd-label-color',
		bg_color     => 'lzcd-bg-color',
		css_code     => 'lzcd-code',
	);
}
function lz_unit() {
	return array(
		jp       => '日本語',
		en_lower => '英語（小文字）',
		en_upper => '英語（大文字）',
	);

}

/**
 * Add style for admin page.
 */
add_action(
	'admin_head-toplevel_page_lz_count_down_menu_page',
	function() {
		?>
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
	<style>
	.code {
		display: flex;
		align-items: center;
		margin-bottom: 30px;
		margin-top: 20px;
	}
	.code input{
		text-align: center;
		background: #fff;
		border: 2px solid #ccc;
		display: block;
		margin-right: 20px;
	}
	.lg.button-primary {
		font-size: 18px;
		width: 200px;
	}
	.editor {
		margin-top: 10px;
		border: 2px solid #ccc;
	}
	.ace_print-margin {
		display: none;
	}
	.form-table td p {
		margin-bottom: 15px;
	}
	</style>
		<?php
	}
);

add_action(
	'admin_footer-toplevel_page_lz_count_down_menu_page',
	function() {
		?>
	<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.2.0/ace.js"></script>
	<script>
	window.addEventListener('DOMContentLoaded', (event) => {
		flatpickr(".anotherSelector",{
			enableTime: true,
			dateFormat: "Y-m-d H:i",
			minDate: "today"
		});
		const editor = ace.edit("editor");
		const csscode = document.querySelector('[name=lzcd-code]');
		csscode.value = editor.getSession().getValue();
		editor.session.setMode("ace/mode/css");
		editor.getSession().setTabSize(2);
		editor.session.setUseSoftTabs(true);
		editor.getSession().setUseWrapMode(true);
		editor.getSession().on('change', function(){
			csscode.value = editor.getSession().getValue();
		});
		editor.setValue(csscode.textContent);

		document.getElementById('copyBtn').addEventListener('click', function(){

			const copyTarget = document.getElementById("shortcode");
			copyTarget.select();
			document.execCommand("Copy");

			// コピーをお知らせする
			alert("ショートコードをコピー : "+ copyTarget.value);
		});

	});
	jQuery(document).ready(function($) {
		$('body').on('click','.status .notice-dismiss', function() {
			$('.status div').remove();
			return false;
		})
		$('#saveData').submit(function(event){
			event.preventDefault();
			const fd = new FormData( this );
			fd.append('action'  , 'save_data' );

			$.ajax({
				type: "POST",
				url: "<?php echo admin_url( 'admin-ajax.php' ); ?>",
				dataType: 'json',
				data: fd,
				processData: false,
				contentType: false,
			}).done(function(data, textStatus, jqXHR) {
				$('.status').html('<div class="notice notice-success is-dismissible"><p><strong>設定を保存しました。</strong></p><button type="button" class="notice-dismiss"></button></div>');
			}).fail(function() {
				$('.status').html('<div class="notice notice-error is-dismissible"><p><strong>設定の保存を失敗しました。</strong></p><button type="button" class="notice-dismiss"></button></div>');
			});

		return false;
		})
	})
	</script>
		<?php

	}
);

/**
 * Save data.
 *
 * @return void
 */
function save_data() {
	if ( isset( $_POST['action'] ) && $_POST['action'] === 'save_data' ) {
		foreach ( $_POST as $key => $value ) {
			if ( isset( $_POST[ $key ] ) ) {
				if ( $key !== 'action' || $key !== 'page' ) {
					update_option( esc_html( $key ), esc_html( $value ) );
				}
			} else {
				delete_option( $key );
			}
		}
	}

	echo json_encode( 'Succsess' );
	die();
}
add_action( 'wp_ajax_save_data', 'save_data' );
/**
 * Add admin page
 *
 * @return void
 */
function add_lz_count_down_menu_page() {
	$google_font = google_font();
	$unit        = lz_unit();
	?>

	<div class="wrap">
		<div class="status"></div>
		<h2>カウントダウンタイマー(試作品)</h2>
		<div class="code">
			<input id="shortcode" type="text" value="[show_timer]" readonly>
			<button type="button" class="button-secondary" id="copyBtn">ショートコードをコピー</button>
		</div>
		<form action="<?php echo esc_url( home_url( 'wp-admin' ) ); ?>/admin.php?page=<?php echo esc_html( $page ); ?>" method="POST" id="saveData">
		<button class="button-primary lg" data-save="saveBtn">保存する</button>
		<input type="hidden" name="ls-count-down1" value="<?php echo esc_html( $page ); ?>">
			<section class="section">
				<table class="form-table" role="presentation">
					<tr class="row">
						<th>カウントダウン終了日</th>
						<td>
							<input type="text" class="anotherSelector" name="lzcd-date" placeholder="2021-12-12 00:00:00" value="<?php echo esc_html( get_option( 'lzcd-date' ) ); ?>">
						</td>
					</tr>
					<tr class="row">
						<th>ラベル（カウントダウン中）</th>
						<td>
							<input type="text" style="min-width:80%" name="lzcd-label-first" value="<?php echo esc_html( get_option( 'lzcd-label-first' ) ); ?>">
						</td>
					</tr>
					<tr class="row">
						<th>ラベル（カウントダウン終了後）</th>
						<td>
							<input type="text" style="min-width:80%" name="lzcd-label-last" value="<?php echo esc_html( get_option( 'lzcd-label-last' ) ); ?>">
						</td>
					</tr>
					<tr class="row">
						<th>リダイレクト先</th>
						<td>
							<input type="text" placeholder="https://example.com/contact/" style="min-width:80%" name="lzcd-redirecturl" value="<?php echo esc_html( get_option( 'lzcd-redirecturl' ) ); ?>">
						</td>
					</tr>
					<tr class="row">
						<th>フォント</th>
						<td>
							<select type="text" name="lzcd-font">
							<?php
							$selected = ' selected';
							foreach ( $google_font as $key => $value ) {
								if ( get_option( 'lzcd-font' ) == $key ) {
									$selected = ' selected';
								} else {
									$selected = '';
								}
								?>
									<option value="<?php echo esc_html( $key ); ?>"<?php echo esc_html( $selected ); ?>><?php echo esc_html( $key ); ?></option>
									<?php
							}
							?>
							</select>
						</td>
					</tr>
					<tr class="row">
						<th>単位</th>
						<td>
							<select type="text" name="lzcd-unit">
							<?php
							foreach ( $unit as $key => $value ) {
								if ( get_option( 'lzcd-unit' ) == $key ) {
									$selected = ' selected';
								} else {
									$selected = '';
								}
								?>
									<option value="<?php echo esc_html( $key ); ?>"<?php echo esc_html( $selected ); ?>><?php echo esc_html( $value ); ?></option>
								<?php
							}
							?>
							</select>
						</td>
					</tr>
					<tr class="row">
						<th>基本の文字色</th>
						<td>
							<input type="color" name="lzcd-color" value="<?php echo ( get_option( 'lzcd-color' ) ? esc_html( get_option( 'lzcd-color' ) ) : '#3333333' ); ?>">
						</td>
					</tr>
					<tr class="row">
						<th>カウントダウンの文字色</th>
						<td>
							<input type="color" name="lzcd-num-color" value="<?php echo ( get_option( 'lzcd-num-color' ) ? esc_html( get_option( 'lzcd-num-color' ) ) : '#333333' ); ?>">
						</td>
					</tr>
					<tr class="row">
						<th>ラベルの文字色</th>
						<td>
							<input type="color" name="lzcd-label-color" value="<?php echo ( get_option( 'lzcd-label-color' ) ? esc_html( get_option( 'lzcd-label-color' ) ) : '#333333' ); ?>">
						</td>
					</tr>
					<tr class="row">
						<th>背景色</th>
						<td>
							<input type="color" name="lzcd-bg-color" value="<?php echo ( get_option( 'lzcd-bg-color' ) ? esc_html( get_option( 'lzcd-bg-color' ) ) : '#ffffff' ); ?>">
						</td>
					</tr>
					<tr class="row">
						<th>高度な設定</th>
						<td>
							<p>CSSを追加して、デザインを編集できます。</p>
							<div id="editor" style="min-height: 100px" class="editor"></div>
							<textarea name="lzcd-code" style="display:none"><?php echo esc_html( get_option( 'lzcd-code' ) ); ?></textarea>
						</td>
					</tr>
					<tr class="row">
						<td colspan="2">
							<p>出力する要素の構造は次の通りです。</p>
							<p><img src="<?php echo plugin_dir_url( __FILE__ ); ?>/img/countdown.jpg" alt="" width="500px" height=""></p>
						</td>
					</tr>
				</table>

			</section>
		</form>
	</div>
		<?php
}

/**
 * Short code for couwnt down timer
 *
 * @param date $atts is date left.
 * @return $tag
 */
function lz_show_timer( $atts ) {

		$value = array(
			jp       => array(
				days  => '日',
				hours => '時間',
				min   => '分',
				sec   => '秒',
			),
			en_lower => array(
				days  => 'days',
				hours => 'hours',
				min   => 'min',
				sec   => 'sec',
			),
			en_upper => array(
				days  => 'Days',
				hours => 'Hours',
				min   => 'Min',
				sec   => 'Sec',
			),

		);
		$unit = $value['jp'];
		if ( get_option( 'lzcd-unit' ) !== '' ) {
			$unit = $value[ esc_html( get_option( 'lzcd-unit' ) ) ];
		}
		$tag = '<div class="c-timer">' . "\n";

		if ( get_option( 'lzcd-label-first' ) !== '' ) {
			$tag .= '<p class="c-timer__label" id="label">' . get_option( 'lzcd-label-first' ) . '</p>' . "\n";
		}
		$tag .= '<div class="c-timer__main">' . "\n";
		$tag .= '<span id="days"></span>' . $unit['days'] . '<span id="hours"></span>' . esc_html( $unit['hours'] ) . '<span id="min"></span
>' . esc_html( $unit['min'] ) . '<span id="sec"></span>' . esc_html( $unit['sec'] ) . "\n";
		$tag .= '</div>' . "\n";
		$tag .= '</div>' . "\n";
		return $tag;
}
add_shortcode( 'show_timer', 'lz_show_timer' );

/**
 * Counter_js_add
 *
 * @return void
 */
add_filter(
	'wp_footer',
	function() {
		global $post;
		if ( has_shortcode( $post->post_content, 'show_timer' ) && $end_date >= $now ) {
			?>
		<script>
			const goal = new Date("<?php echo get_option( 'lzcd-date' ); ?>");
			let count;
			function countDown(date) {
			const now = new Date();
			const left = goal.getTime() - now.getTime();
			if (left > 0) {
				const sec = Math.floor(left / 1000) % 60;
				const min = Math.floor(left / 1000 / 60) % 60;
				const hours = Math.floor(left / 1000 / 60 / 60) % 24;
				const days = Math.floor(left / 1000 / 60 / 60 / 24);
				count = { days: days, hours: hours, min: min, sec: sec };
			} else {
				count = { days: 0, hours: 0, min: 0, sec: 0 };
			}
			return count;
		}

		function setCountDown() {
			let counter = countDown(goal);
			let end = 0;
			const countDownTimer = setTimeout(setCountDown, 1000);

			for (let item in counter) {
				document.getElementById(item).textContent = counter[item];
				end += parseInt(counter[item]);
			}
			if (end === 0) {
				clearTimeout(countDownTimer);
				<?php
				if ( get_option( 'lzcd-label-last' ) !== '' && get_option( 'lzcd-label-first' ) !== '' ) {
					?>
				document.getElementById("label").textContent =
				"<?php echo esc_html( get_option( 'lzcd-label-last' ) ); ?>";
					<?php
				}
				?>
				setTimeout(function(){
					location.href= '<?php echo esc_url( get_option( 'lzcd-redirecturl' ) ? get_option( 'lzcd-redirecturl' ) : home_url( '/' ) ); ?>';
				},1000);
			}
		}

		setCountDown();
		</script>
			<?php
		}
	}
);

add_filter(
	'wp_head',
	function() {
		global $post;
		if ( has_shortcode( $post->post_content, 'show_timer' ) ) {
			$fonts       = google_font();
			$font_family = '';
			if ( get_option( 'lzcd-font' ) !== '' ) {
				?>
			<link rel=preconnect href=https://fonts.gstatic.com>
			<link href=https://fonts.googleapis.com/css2?family=<?php echo esc_html( $fonts[ get_option( 'lzcd-font' ) ] ); ?>&display=swap rel=stylesheet>
				<?php
				$font_family = 'font-family: "' . get_option( 'lzcd-font' ) . '";';
			}
			?>
		<style>
		.c-timer {
			margin: 20px auto;
			max-width: 600px;
			text-align: center;
			padding: 15px;
			background: <?php echo esc_html( get_option( 'lzcd-bg-color' ) ); ?>;
			color: <?php echo esc_html( get_option( 'lzcd-color' ) ); ?>;
		}
		.c-timer__label {
			font-size: 20px;
			font-weight: bold;
			color: <?php echo esc_html( get_option( 'lzcd-label-color' ) ); ?>;
		}
		.c-timer__main {
			font-weight: bold;
			font-size: 20px;
		}
		.c-timer__main span{
			text-align: right;
			display: inline-block;
			min-width: 1.5em;
			font-size: 40px;
			color: <?php echo esc_html( get_option( 'lzcd-num-color' ) ); ?>;
			<?php echo $font_family; ?>
		}
		</style>
			<?php
		}
	}
);
