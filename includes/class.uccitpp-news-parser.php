<?php

require plugin_dir_path(__FILE__) . '../vendor/autoload.php';
use Sunra\PhpSimple\HtmlDomParser;

if (!class_exists('UCCITPPNewsParser')) {
	class UCCITPPNewsParser {
		protected $loader;
		protected $plugin_slug;
		protected $version;

		public function __construct() {
			$this->plugin_slug = 'ucci-tpp-news-parser';
			$this->version = '1.1';
			add_shortcode('news', array('UCCITPPNewsParser', 'uccinews_news'));
		}

		public function uccinews_news() {
			static::uccinews_setNews();
			static::uccinews_getNews();
		}

		private function uccinews_setNews() {
			global $wpdb;

			$html = file_get_contents('https://www.ucci.org.ua/press-center/ucci-news/1');
			$dom = HtmlDomParser::str_get_html($html);
			$new = $dom->find('.tab_news', 0);
			$a = $new->find('a', 0);
			$date = $a->find('.news_date', 0)->innertext();
			$timestamp = strtotime($date);
			$date = date('Y-m-d', $timestamp);
			if ($result = $wpdb->get_results("SELECT `date` FROM `news` ORDER BY `date` DESC LIMIT 1")) {
				$lastDate = $result[0]->date;
				if (!($lastDate == $date)) {
					$news = $dom->find('.news_date');
					$i = 0;
					foreach ($news as $new) {
						$d = strtotime($new->innertext());
						$d = date('Y-m-d', $d);
						if ($d == $date) $i++;
					}
					$news = $dom->find('.tab_news');
					$newpost = array();
					for ($j = 0; $j < $i; $j++) {
						$a = $news[$j]->find('a', 0);
						$linkNews = 'https://www.ucci.org.ua' . $a->href;
						$title = $a->title;
						$img = $a->find('picture', 0)
						         ->find('source', 0)->srcset;
						$date = $a->find('.news_date', 0)->innertext();
						$date = strtotime($date);
						$date = date('Y-m-d', $date);
						$desc = $a->find('.news_desc', 0)->innertext();

						$arr = array('link' => $linkNews,
						             'title' => $title,
						             'img' => $img,
						             'date' => $date,
						             'desc' => $desc);

						array_push($newpost, $arr);
					}

					foreach ($newpost as $post) {
						$wpdb->insert('news', $post);
					}

				}
			}
		}

		private function uccinews_getNews() {
			global $wpdb;

			$result = $wpdb->get_results("SELECT * FROM `news` ORDER BY `date` DESC");
			foreach ($result as $new) {
				echo "<div class='new'>";
				echo "<img src='$new->img'>";
				echo "<a href='$new->link'>";
				echo "<h3>" . $new->title . "</h3>";
				echo "</a>";
				echo $new->date . "<br>";
				echo $new->desc;
				echo "</div>";
			}
		}

		public function get_version() {
			return $this->version;
		}
	}
}