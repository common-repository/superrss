<?php
/*
Plugin Name: SuperRSS by Leo Balter
Plugin URI: http://leobalter.net
Description: Adiciona um Widget para RSS Feed, com muitas configura&ccedil;&otilde;es a mais. Exemplos: exibir hora e data em diversos formatos (definidos pelo objeto date() do php); exibir hora e/ou data antes ou depois do t&iacute;tulo do feed, temporiza&ccedil;&atilde;o do cache dos feeds (o padr&atilde;o &eacute; de 12 horas no widget comum de RSS). Totalmente em portugu&ecirc;s do Brasil. Em breve estarei lan&ccedil;ando outros idiomas.
Author: Leo Balter (leonardo.balter@gmail.com)
Version: 1.0
Author URI: http://leobalter.net
*/

/**
 * Super RSS widget class
 *
 */
class Super_RSS extends WP_Widget {

	function Super_RSS() {
		$widget_ops = array( 'description' => __('RSS ou Atom Feed do seu jeito') );
		$control_ops = array( 'width' => 400, 'height' => 200 );
		$this->WP_Widget( 'superrss', __('SuperRSS'), $widget_ops, $control_ops );
	}

	function widget($args, $instance) {

		if ( isset($instance['error']) && $instance['error'] )
			return;

		extract($args, EXTR_SKIP);

		$url = $instance['url'];
		while ( stristr($url, 'http') != $url )
			$url = substr($url, 1);

		if ( empty($url) )
			return;

		$cache_time = @ $instance['set_rss_cache_time'];
		
		if (!$cache_time) {
			$cache_time = 30;
		}
			
		$rss = superRss_fetch_feed($url, $cache_time);
		$title = $instance['title'];
		$desc = '';
		$link = '';

		if ( ! is_wp_error($rss) ) {
			$desc = esc_attr(strip_tags(@html_entity_decode($rss->get_description(), ENT_QUOTES, get_option('blog_charset'))));
			if ( empty($title) )
				$title = esc_html(strip_tags($rss->get_title()));
			$link = esc_url(strip_tags($rss->get_permalink()));
			while ( stristr($link, 'http') != $link )
				$link = substr($link, 1);
		}

		if ( empty($title) )
			$title = empty($desc) ? __('Feed RSS') : $desc;

		$title = apply_filters('widget_title', $title );
		$url = esc_url(strip_tags($url));
		$icon = includes_url('images/rss.png');
		if ( $title )
			$title = "<a class='rsswidget' href='$url' title='" . esc_attr(__('Assinar este conte&uacute;do')) ."'><img style='background:orange;color:white;border:none;' width='14' height='14' src='$icon' alt='RSS' /></a> <a class='rsswidget' href='$link' title='$desc'>$title</a>";

		echo $before_widget;
		if ( $title )
			echo $before_title . $title . $after_title;
		superRss_output( $rss, $instance );
		echo $after_widget;
	}

	function update($new_instance, $old_instance) {
		$testurl = $new_instance['url'] != $old_instance['url'];
		return superRss_process( $new_instance, $testurl );
	}

	function form($instance) {

		if ( empty($instance) )
 			$instance = array( 'title' => '', 'url' => '', 'items' => 10, 'error' => false, 'show_summary' => 0, 'show_author' => 0, 'show_date' => 0, 'set_rss_cache_time' => 300, 'show_time' => 0, 'show_before' => 0, 'dateStamp' => 'j/n/Y', 'timeStamp' => 'H:i');
		$instance['number'] = $this->number;

		superRss_form( $instance );
	}
}

/**
 * Faz o output do RSS Feed.
 *
 * @param string|array|object $rss RSS url.
 * @param array $args Widget arguments.
 */
function superRss_output( $rss, $args = array() ) {
	if ( is_string( $rss ) ) {
		$rss = superRss_fetch_feed($rss);
	} elseif ( is_array($rss) && isset($rss['url']) ) {
		if (!isset($rss['set_rss_cache_time'])) { $set_rss_cache_time = 1; }
		$set_rss_cache_time = intval($set_rss_cache_time);
		$args = $rss;
		$rss = superRss_fetch_feed($rss['url'], $set_rss_cache_time);
	} elseif ( !is_object($rss) ) {
		return;
	}

	if ( is_wp_error($rss) ) {
		if ( is_admin() || current_user_can('manage_options') )
			echo '<p>' . sprintf( __('<strong>Erro no RSS</strong>: %s'), $rss->get_error_message() ) . '</p>';

		return;
	}
	
	$default_args = array( 'show_author' => 0, 'show_date' => 0, 'show_summary' => 0, 'show_time' => 0, 'show_before' => 0, 'dateStamp' => 'j/n/Y', 'timeStamp' => 'H:i');
	$args = wp_parse_args( $args, $default_args );
	extract( $args, EXTR_SKIP );

	$items = (int) $items;
	if ( $items < 1 || 20 < $items )
		$items = 10;
	$show_summary	= (int) $show_summary;
	$show_author	= (int) $show_author;
	$show_date		= (int) $show_date;
	$show_time		= (int) $show_time;
	$show_before	= (int) $show_before;

	if ( !$rss->get_item_quantity() ) {
		echo '<ul><li>' . __( 'Ocorreu um erro; N&atilde;o foi poss&iacute;vel acessar essa feed.' ) . '</li></ul>';
		return;
	}

	echo '<ul>';
	foreach ( $rss->get_items(0, $items) as $item ) {
		$link = $item->get_link();
		while ( stristr($link, 'http') != $link )
			$link = substr($link, 1);
		$link = esc_url(strip_tags($link));
		$dateStamp = esc_attr(strip_tags($dateStamp));
		$timeStamp = esc_attr(strip_tags($timeStamp));
		$title = esc_attr(strip_tags($item->get_title()));
		if ( empty($title) )
			$title = __('Super RSS');

		$desc = str_replace(array("\n", "\r"), ' ', esc_attr(strip_tags(@html_entity_decode($item->get_description(), ENT_QUOTES, get_option('blog_charset')))));
		$desc = wp_html_excerpt( $desc, 360 ) . ' [&hellip;]';
		$desc = esc_html( $desc );

		if ( $show_summary ) {
			$summary = "<div class='rssSummary'>$desc</div>";
		} else {
			$summary = '';
		}

		$date = '';
		$preDate = '';
		if ( $show_date  && $show_time) {
			$date = $item->get_date($dateStamp . ' ' . $timeStamp); // default 'j/n/Y H:i'
		} else if ($show_date) {
			$date = $item->get_date($dateStamp);
		} else if ($show_time) {
			$date = $item->get_date($timeStamp);
		}
		
		if ( $date ) {
			$date = '<span class="rss-date">' . $date . '</span>';
			$preDate = ' '; // gambiarra da porra
		}

		$author = '';
		if ( $show_author ) {
			$author = $item->get_author();
			if ( is_object($author) ) {
				$author = $author->get_name();
				$author = ' <cite>' . esc_html( strip_tags( $author ) ) . '</cite>';
			}
		}

		if ( $link == '' ) {
			echo "<li>$title{$date}{$summary}{$author}</li>";
		} else {
			if ($show_before) {
				echo "<li>{$date} <a class='rsswidget' href='$link' title='$desc'>$title</a>{$summary}{$author}</li>";
			} else {
				echo "<li><a class='rsswidget' href='$link' title='$desc'>$title</a>{$preDate}{$date}{$summary}{$author}</li>";
			}
		}
	}
	echo '</ul>';
}



/**
 * Monta o form de backend desse feed.
 *
 * As opcoes marcadas no início como true são as que devem aparecer disponíveis no form
 *
 * @param array|string $args Values for input fields.
 * @param array $inputs Override default display options.
 */
function superRss_form( $args, $inputs = null ) {

	$default_inputs = array( 'url' => true, 'title' => true, 'items' => true, 'show_summary' => true, 'show_author' => true, 'show_date' => true, 'set_rss_cache_time' => true, 'show_time' => true, 'show_before' => true, 'dateStamp' => true, 'timeStamp' => true );
	$inputs = wp_parse_args( $inputs, $default_inputs );
	extract( $args );
	extract( $inputs, EXTR_SKIP);

	$number = esc_attr( $number );
	$title  = esc_attr( $title );
	$url    = esc_url( $url );
	$items  = (int) $items;
	if ( $items < 1 || 20 < $items )
		$items  = 10;
	$show_summary   = (int) $show_summary;
	$show_author    = (int) $show_author;
	$show_date      = (int) $show_date;
	$show_time      = (int) $show_time;
	$show_before    = (int) $show_before;
	
	$set_rss_cache_time = esc_attr( $set_rss_cache_time );
	$set_rss_cache_time = (is_numeric($set_rss_cache_time) ? $set_rss_cache_time : 1) ;
	$set_rss_cache_time = intval($set_rss_cache_time, 10);
	
	$dateStamp = esc_attr($dateStamp);
	$timeStamp = esc_attr($timeStamp);

	if ( !empty($error) )
		echo '<p class="widget-error"><strong>' . sprintf( __('Erro no RSS: %s'), $error) . '</strong></p>';

	if ( $inputs['url'] ) :
?>
	<p><label for="sRss-url-<?php echo $number; ?>"><?php _e('Insira a URL do Feed de RSS:'); ?></label>
	<input class="widefat" id="sRss-url-<?php echo $number; ?>" name="widget-superrss[<?php echo $number; ?>][url]" type="text" value="<?php echo $url; ?>" /></p>
<?php endif; if ( $inputs['title'] ) : ?>
	<p><label for="sRss-title-<?php echo $number; ?>"><?php _e('T&iacute;tulo (opcional):'); ?></label>
	<input class="widefat" id="sRss-title-<?php echo $number; ?>" name="widget-superrss[<?php echo $number; ?>][title]" type="text" value="<?php echo $title; ?>" /></p>
<?php endif; if ( $inputs['items'] ) : ?>
	<p><label for="sRss-items-<?php echo $number; ?>"><?php _e('Mostrar quantos itens?'); ?></label>
	<select id="sRss-items-<?php echo $number; ?>" name="widget-superrss[<?php echo $number; ?>][items]">
<?php
		for ( $i = 1; $i <= 20; ++$i )
			echo "<option value='$i' " . ( $items == $i ? "selected='selected'" : '' ) . ">$i</option>";
?>
	</select></p>
<?php endif; if ( $inputs['show_summary'] ) : ?>
	<p><input id="sRss-show-summary-<?php echo $number; ?>" name="widget-superrss[<?php echo $number; ?>][show_summary]" type="checkbox" value="1" <?php if ( $show_summary ) echo 'checked="checked"'; ?>/>
	<label for="sRss-show-summary-<?php echo $number; ?>"><?php _e('Exibir conte&uacute;do?'); ?></label></p>
<?php endif; if ( $inputs['show_author'] ) : ?>
	<p><input id="sRss-show-author-<?php echo $number; ?>" name="widget-superrss[<?php echo $number; ?>][show_author]" type="checkbox" value="1" <?php if ( $show_author ) echo 'checked="checked"'; ?>/>
	<label for="sRss-show-author-<?php echo $number; ?>"><?php _e('Exibir nome do autor?'); ?></label></p>
<?php endif; if ( $inputs['show_date'] ) : ?>
	<p><input id="sRss-show-date-<?php echo $number; ?>" name="widget-superrss[<?php echo $number; ?>][show_date]" type="checkbox" value="1" <?php if ( $show_date ) echo 'checked="checked"'; ?>/>
	<label for="sRss-show-date-<?php echo $number; ?>"><?php _e('Exibir data?'); ?></label></p>
<?php endif; if ( $inputs['dateStamp'] ) : ?>
	<p><label for="sRss-dateStamp-<?php echo $number; ?>" title="Padr&atilde;o: j/n/Y = 13/03/2010"><?php _e('Formato da data:'); ?></label>
	<input class="widefat" id="sRss-dateStamp-<?php echo $number; ?>" name="widget-superrss[<?php echo $number; ?>][dateStamp]" type="text" value="<?php echo $dateStamp; ?>"  title="Padr&atilde;o: j/n/Y = 13/03/2010" /></p>
<?php endif; if ( $inputs['show_time'] ) : ?>
	<p><input id="sRss-show-time-<?php echo $number; ?>" name="widget-superrss[<?php echo $number; ?>][show_time]" type="checkbox" value="1" <?php if ( $show_time ) echo 'checked="checked"'; ?>/>
	<label for="sRss-show-time-<?php echo $number; ?>"><?php _e('Exibir hora?'); ?></label></p>
<?php endif; if ( $inputs['timeStamp'] ) : ?>
	<p><label for="sRss-timeStamp-<?php echo $number; ?>" title="Padr&atilde;o: H:i = 13:37"><?php _e('Formato da hora:'); ?></label>
	<input class="widefat" id="sRss-timeStamp-<?php echo $number; ?>" name="widget-superrss[<?php echo $number; ?>][timeStamp]" type="text" value="<?php echo $timeStamp; ?>"  title="Padr&atilde;o: H:i = 13:37" /></p>
	<p><a href="http://codex.wordpress.org/Formatting_Date_and_Time" target="_blank">Informa&ccedil;&otilde;es sobre formata&ccedil;&atilde;o de data e hora</a></p>
<?php endif; if ( $inputs['show_before'] ) : ?>
	<p><input id="sRss-show-before-<?php echo $number; ?>" name="widget-superrss[<?php echo $number; ?>][show_before]" type="checkbox" value="1" <?php if ( $show_before ) echo 'checked="checked"'; ?>/>
	<label for="sRss-show-before-<?php echo $number; ?>"><?php _e('Exibir data e/ou hora antes do item?'); ?></label></p>
<?php endif; if ( $inputs['set_rss_cache_time'] ) : ?>
	<p><label for="sRss-cache-<?php echo $number; ?>"><?php _e('Tempo de cache do widget:'); ?></label>
	<input class="widefat" id="sRss-cache-<?php echo $number; ?>" name="widget-superrss[<?php echo $number; ?>][set_rss_cache_time]" type="text" value="<?php echo $set_rss_cache_time; ?>" /></p>
<?php
	endif;
	foreach ( array_keys($default_inputs) as $input ) :
		if ( 'hidden' === $inputs[$input] ) :
			$id = str_replace( '_', '-', $input );
?>
	<input type="hidden" id="rss-<?php echo $id; ?>-<?php echo $number; ?>" name="widget-superrss[<?php echo $number; ?>][<?php echo $input; ?>]" value="<?php echo $$input; ?>" />
<?php
		endif;
	endforeach;
}

/**
 * Processa o Feed RSS e obtém os itens do mesmo
 *
 * Por padrão o widget não pode ter mais de 20 itens ou o mesmo é resetado de volta
 * para o padrão de 10 itens.
 *
 * A array retornada contém o título do feed, a url, o link (marcada no canal),
 * itens do feed, error (caso ocorra), tempo de cache, e demais opções padrões.
 *
 * @param array $widget_rss RSS widget feed data. Expects unescaped data.
 * @param bool $check_feed Optional, default is true. Whether to check feed for errors.
 * @return array
 */
function superRss_process( $widget_rss, $check_feed = true ) {
	$items = (int) $widget_rss['items'];
	if ( $items < 1 || 20 < $items )
		$items = 10;
	$url           		= esc_url_raw(strip_tags( $widget_rss['url'] ));
	$title         		= trim(strip_tags( $widget_rss['title'] ));
	$show_summary  		= (int) $widget_rss['show_summary'];
	$show_author   		= (int) $widget_rss['show_author'];
	$show_date     		= (int) $widget_rss['show_date'];
	$show_time     		= (int) $widget_rss['show_time'];
	$show_before     	= (int) $widget_rss['show_before'];
	$set_rss_cache_time = $widget_rss['set_rss_cache_time'];
	$set_rss_cache_time = (is_numeric($set_rss_cache_time)) ? $set_rss_cache_time : 300 ;
	$set_rss_cache_time = intval($set_rss_cache_time, 10);
	$dateStamp			= strip_tags( $widget_rss['dateStamp'] );
	$timeStamp			= strip_tags( $widget_rss['timeStamp'] );

	if ( $check_feed ) {
		$rss = superRss_fetch_feed($url, $set_rss_cache_time);
		$error = false;
		$link = '';
		if ( is_wp_error($rss) ) {
			$error = $rss->get_error_message();
		} else {
			$link = esc_url(strip_tags($rss->get_permalink()));
			while ( stristr($link, 'http') != $link )
				$link = substr($link, 1);
		}
	}

	return compact( 'title', 'url', 'link', 'items', 'error', 'show_summary', 'show_author', 'show_date', 'set_rss_cache_time', 'show_time', 'show_before', 'dateStamp', 'timeStamp' );
}

/**
 * Cria um objeto SimplePie baseado no Feed RSS ou Atom indicado pela URL.
 *
 * @param string $url URL to retrieve feed
 * @return WP_Error|SimplePie WP_Error object on failure or SimplePie object on success
 */
function superRss_fetch_feed($url, $set_rss_cache_time = 300) {
	require_once (ABSPATH . WPINC . '/class-feed.php');

	$feed = new SimplePie();
	$feed->set_feed_url($url);
	$feed->set_cache_class('WP_Feed_Cache');
	$feed->set_file_class('WP_SimplePie_File');
	$feed->set_cache_duration(apply_filters('wp_feed_cache_transient_lifetime', $set_rss_cache_time));
	$feed->init();
	$feed->handle_content_type();

	if ( $feed->error() )
		return new WP_Error('simplepie-error', $feed->error());

	return $feed;
}

add_action('widgets_init', create_function('', 'return register_widget("Super_RSS");'));

?>