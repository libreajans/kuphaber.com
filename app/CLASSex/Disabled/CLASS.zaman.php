<?php

	class zaman
	{
		public function __construct()
		{
			$this->conn = $GLOBALS['conn'];
		}

		public function rss_fetch($url, $channel = 1)
		{
			global $_cache, $list_cat;

			$data_curl = curl_get_data($url);

			//nesneye kolay erişim için küçük bir değişiklik yapıyoruz
			$data_curl = str_replace('news:', 'news_', $data_curl);
			$data = simplexml_load_string($data_curl);
			$adet = count($data->url);
			if($adet > 0)
			{
				for($i = 0; $i < $adet; $i++)
				{
					$list[$i]['link']	= trim(strip_tags($data->url[$i]->loc));
					$list[$i]['time']	= date('Y-m-d H:i:s', strtotime(strip_tags($data->url[$i]->news_news->news_publication_date)));

					//kategori seçimi yapıyoruz
					$tlink = str_replace('http://www.zaman.com.tr/','', $list[$i]['link']);
					$tlink = explode('_', $tlink);

					$list[$i]['cat']	= intval($list_cat[$t = format_url($tlink[0])]);
				}
			}

			//rss'i parse ettik, artık veritabanına alabiliriz
 			//print_pre($list);
			if($adet > 0)
			{
				for($i = 0; $i < $adet; $i++)
				{
					//kayıttın önce bu içerik belleklenmiş mi kontrol edelim
					if($_cache->content_cached($list[$i]['link']) == false)
					{
						//hata sayısını sıfırlayalım ki break edip hepsi hata diye kırılmasın
						$hata = 0;

						//parser seçimi yapıyoruz
						if(strpos_array($list[$i]['link'], array('http://www.zaman.com.tr/')) == true)
						{
							$parser	= 'zaman_www';
						}

						//belli url mantıklarına izin vermiyoruz
						//galeri içerik veya video dönüyorlar
						$array_url  = array(
							'http://www.zaman.com.tr/yazarlar_',
						);
						if(strpos_array($list[$i]['link'], $array_url) == true)
						{
							$hata = 1;
						}

						if($hata <> 1)
						{
							//içeriği belleğe alalım
							$record = array(
								'cache_link'	=> $list[$i]['link'],
								'cache_time'	=> $list[$i]['time'],
								'cache_cat'		=> $list[$i]['cat'],
								'cache_object'	=> 'zaman',		//hangi objeyi kullanacağı
								'cache_parser'	=> $parser,		//hangi parser kullanacağız
								'cache_channel'	=> $channel,	//hangi rss sağlayıcıdan geldi
								'cache_status'	=> 0,
							);
							//parser var ise
							if($parser <> '')
							{
								$rs = $this->conn->AutoExecute(T_CACHE, $record, 'INSERT');
								if($rs == false)
								{
									//print_pre($record);
									throw new Exception($this->conn->ErrorMsg());
								}
								echo '.';
							}
						}
					}
					else
					{
						echo '!';
					}
				}
			}
		}

		public function zaman_www($data, $type = 'text')
		{
			if($data == '') return '';

			//gelen datadan ihtiyacımız olan kısmı alıyoruz
			//bu içerik türü için sadece metine ihtiyacımız olduğundan metin alıp geri dönüyoruz
			//html datayı çıkartırken simple_html_dom.php dosyasını kullanıyoruz
			if($type == 'text')
			{
				$html = str_get_html($data);

				//tüm linkleri temizleyelim
				//js scriptlerini temizleyelim
				//other-news benzer yazılarını temizleyelim
				foreach($html->find('a') as $e)							$e->href = '';
				foreach($html->find('script') as $e)					$e->outertext = '';
				foreach($html->find('img') as $e) 						$e->width = '';
				foreach($html->find('img') as $e) 						$e->height = '';

				//istenmeyen kimi alanları silelim

				//en son metni alalım
				foreach($html->find('span[itemprop=\'articleBody\']') as $e)			$text = $e->innertext;

				//alt ve title değerlerini temizleyelim
				$text = preg_replace('/alt="([\w\W]*?)"/', '', $text);
				$text = preg_replace('/title="([\w\W]*?)"/', '', $text);

				//kapanmayan etiketlerin önündeki boşlukları silelim
				$text = str_replace(array('  >', ' >'), '>', $text);

				//metin çok kısa ise boş verelim,
				//bunun için de tagları temizleyip metni ölçelim
				if( strlen(strip_tags($text)) < 200)
				{
					$text = '';
				}

				//bellek boşaltıyoruz
				$html->clear();
				unset($html);

				//sonucu dönüyoruz
				return trim(html_entity_decode($text));
			}

			if($type == 'title')
			{
				if($text == '')
				{
					$html = str_get_html($data);

					foreach($html->find('h1[itemprop=\'headline\']') as $e)	$text = $e->plaintext;

					//bellek boşaltıyoruz
					$html->clear();
					unset($html);
				}
				return trim(strip_tags($text));
			}

			if($type == 'desc')
			{
				if($text == '')
				{
					$html = str_get_html($data);

					foreach($html->find('.detaySpot') as $e) $text = $e->plaintext;

					//bellek boşaltıyoruz
					$html->clear();

					unset($html);
				}
				return trim(strip_tags(html_entity_decode($text)));
			}

			if($type == 'image')
			{
				if($text == '')
				{
					$html = str_get_html($data);

					foreach($html->find('img.detayImg') as $e) $text = $e->src;

					//bellek boşaltıyoruz
					$html->clear();
					unset($html);
				}

				return trim(strip_tags($text));
			}
		}
	}
