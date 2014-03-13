<?php
/*------------------------------------------------------------------------
# plg_videobox - Videobox
# ------------------------------------------------------------------------
# author    HitkoDev
# copyright Copyright (C) 2012 hitko.si. All Rights Reserved.
# @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
# Websites: http://hitko.eu/software/videobox
-------------------------------------------------------------------------*/
// no direct access
defined( '_JEXEC' ) or die( 'Restricted Access' );

jimport( 'joomla.plugin.plugin' );

class plgSystemVideobox extends JPlugin
{

	public function onAfterDispatch(){
		$app = JFactory::getApplication();
		$document = JFactory::getDocument();
		if($app->isSite() && method_exists($document, 'addCustomTag')){
			$document->addCustomTag('<link rel="stylesheet" href="'.JURI::root().'/plugins/system/videobox/css/videobox.css" type="text/css" media="screen" />');
			if($this->params->get('loadjq')=='1'){
				$document->addCustomTag('<script type="text/javascript" src="'.JURI::root().'/plugins/system/videobox/js/jquery.min.js"></script><script type="text/javascript">jQuery.noConflict();</script>');
			}
			$document->addCustomTag('<script type="text/javascript" src="'.JURI::root().'/plugins/system/videobox/js/videobox.js"></script>');
		}
	}

	public function onAfterRender(){
		$app = JFactory::getApplication();
		$document = JFactory::getDocument();
		if($app->isSite() && method_exists($document, 'addCustomTag')){
			$vb_path = str_replace('//', '/', JPATH_BASE . '/plugins/system/videobox/');
			
			// Clear cache and unset the clear parameter
			if($this->params->get('clear')){
				$this->params->set('clear', '0');
				$db = JFactory::getDBO();
				$query = $db->getQuery(true);
				$query->update('#__extensions AS a');
				$query->set('a.params = ' . $db->quote((string)$this->params));
				$query->where('a.element = "videobox"');
				$db->setQuery($query);
				$db->query();
				foreach(new DirectoryIterator($vb_path.'cache') as $fileInfo) {
					if(!$fileInfo->isDot() && $fileInfo->getFilename() != 'index.html') unlink($vb_path.'cache/'.$fileInfo->getFilename());
				}
			}
			
			// Get content
			$pageload = JResponse::getBody();
			preg_match('/<(\s*)body(.*)\/(\s*)body(\s*)>/s', $pageload, $buffer);
			$buffer = $buffer[2];
			$old_buffer = $buffer;
			$buffer = str_replace(array("&#123;", "&#125;"), array("{", "}"), $buffer);
			
			// Avoid rendering Videobox inside texteditors
			preg_match_all('/<(\s*)textarea([^>]*)>(.*?)<\/(\s*)textarea(\s*)>/s', $buffer, $areas);
			foreach($areas[0] as $area){
				$area1 = str_replace(array("{", "}"), array("&#123;", "&#125;"), $area);
				$buffer = str_replace($area, $area1, $buffer);
			}
			
			// Avoid rendering Videobox when {raw} is used
			preg_match_all('/{raw}([\s\S]*){\/raw}/isU', $buffer, $matches);			
			foreach($matches[1] as $match) {
				$raw_text = str_replace(array("{", "}"), array("&#123;", "&#125;"), $match);
				$buffer = preg_replace('/{raw}([\s\S]*){\/raw}/isU', $raw_text, $buffer, 1);
			}
			
			// Match Videobox calls
			$regex = '/{videobox}(.*){\/videobox}/isU';			
			preg_match_all($regex, $buffer, $matches);
			
			// Get pages for the gallery display
			$url2 = explode(',', JRequest::getVar('vblimitstart', ''));
			
			$co = 0;
			foreach($matches[1] as $match){
				
				$co++;
				$match = strip_tags($match);				
				$parametri = explode('||', $match);
				$videos = explode('|,', $parametri[0]);
				$count = count($videos);
				
				// Match parameters in code
				$para_match = array(array());
				if(isset($parametri[1])){
					preg_match_all('/([^=, ]*)=(([^,]*)|"([^"]*)")/i', $parametri[1], $para_match);
				}
				foreach($para_match[0] as $para){
					if(preg_match('/([^=]+)="{0,1}([^"]*)"{0,1}/i', $para, $parameter) > 0){
						if(isset($parameter[1]) && isset($parameter[2])){
							if((trim($parameter[1])!='') && (trim($parameter[2])!='')) $parametri[trim($parameter[1])] = trim($parameter[2]);
						}
					}
				}
				if(isset($parametri['alternative']) && ($parametri['alternative']==1)) $parametri['alternative'] = 99;
				if(isset($parametri['display'])){
					$parametri['display'] = strtolower($parametri['display']);
					if($parametri['display']=='links' || $parametri['display']=='link' || $parametri['display']==1){
						$parametri['display'] = 1;
					} else if($parametri['display']=='box' || $parametri['display']==2) {
						$parametri['display'] = 2;
					} else {
						$parametri['display'] = 0;
					}
				}
				if(isset($parametri['player'])){
					$parametri['player'] = strtolower($parametri['player']);
					if($parametri['player']=='inline' || $parametri['player']==0){
						$parametri['player'] = 0;
					} else {
						$parametri['player'] = 1;
					}
				}
				
				// Get the required parameters		
				if(!isset($parametri['cache'])) $parametri['cache'] = $this->params->get('cache');
				if($count>1){
					if(!isset($parametri['display']) || $parametri['display']>1) $parametri['display'] = $this->params->get('links_g');
					if($parametri['display']==1){
						$parametri['player'] = 1;
						if(!isset($parametri['class'])) $parametri['class'] = $this->params->get('class_lg');
						if(!isset($parametri['style'])) $parametri['style'] = $this->params->get('style_lg');
						if(!isset($parametri['separator'])) $parametri['separator'] = $this->params->get('separator');
						if(!isset($parametri['width'])) $parametri['width'] = $this->params->get('width_lg');
						if(!isset($parametri['height'])) $parametri['height'] = $this->params->get('height_lg');
						if(!isset($parametri['sc_visual'])) $parametri['sc_visual'] = $this->params->get('sc_visual_lg');
					} else {
						if(!isset($parametri['player'])) $parametri['player'] = $this->params->get('player_g');
						if(!isset($parametri['class'])) $parametri['class'] = $this->params->get('class_g');
						if(!isset($parametri['style'])) $parametri['style'] = $this->params->get('style_g');
						if(!isset($parametri['alternative'])) $parametri['alternative'] = $this->params->get('cs_g');
						if(($parametri['player']!=$this->params->get('player_g') && $parametri['alternative']==1) || $parametri['alternative']==99){
							if(!isset($parametri['width'])) $parametri['width'] = $this->params->get('width_nlb_g');
							if(!isset($parametri['height'])) $parametri['height'] = $this->params->get('height_nlb_g');
							if(!isset($parametri['t_width'])) $parametri['t_width'] = $this->params->get('width_nlb_gt');
							if(!isset($parametri['t_height'])) $parametri['t_height'] = $this->params->get('height_nlb_gt');
							if(!isset($parametri['button'])) $parametri['button'] = $this->params->get('play_nlb_g');	
							if(!isset($parametri['break'])) $parametri['break'] = $this->params->get('break_nlb');
							if(!isset($parametri['pages'])) $parametri['pages'] = $this->params->get('pages_nlb');
							if(!isset($parametri['sc_visual'])) $parametri['sc_visual'] = $this->params->get('sc_visual_nlb_g');
						} else {
							if(!isset($parametri['width'])) $parametri['width'] = $this->params->get('width_g');
							if(!isset($parametri['height'])) $parametri['height'] = $this->params->get('height_g');
							if(!isset($parametri['t_width'])) $parametri['t_width'] = $this->params->get('width_gt');
							if(!isset($parametri['t_height'])) $parametri['t_height'] = $this->params->get('height_gt');
							if(!isset($parametri['button'])) $parametri['button'] = $this->params->get('play_g');	
							if(!isset($parametri['break'])) $parametri['break'] = $this->params->get('break');
							if(!isset($parametri['pages'])) $parametri['pages'] = $this->params->get('pages');
							if(!isset($parametri['pages_results'])) $parametri['pages_results'] = $this->params->get('pages_results');
							if(!isset($parametri['sc_visual'])) $parametri['sc_visual'] = $this->params->get('sc_visual_g');
						}
					}
				} else {
					if(!isset($parametri['display'])) $parametri['display'] = $this->params->get('display');
					if($parametri['display']==2){
						if(!isset($parametri['player'])) $parametri['player'] = $this->params->get('player_b');
						if(!isset($parametri['class'])) $parametri['class'] = $this->params->get('class_l');
						if(!isset($parametri['style'])) $parametri['style'] = $this->params->get('style_l');
						if(!isset($parametri['alternative'])) $parametri['alternative'] = $this->params->get('cs_b');
						if(($parametri['player']!=$this->params->get('player_b') && $parametri['alternative']==1) || $parametri['alternative']==99){
							if(!isset($parametri['t_width'])) $parametri['t_width'] = $this->params->get('width_nlb_bt');
							if(!isset($parametri['t_height'])) $parametri['t_height'] = $this->params->get('height_nlb_bt');
							if(!isset($parametri['width'])) $parametri['width'] = $this->params->get('width_nlb_b');
							if(!isset($parametri['height'])) $parametri['height'] = $this->params->get('height_nlb_b');	
							if(!isset($parametri['button'])) $parametri['button'] = $this->params->get('play_nlb_b');
							if(!isset($parametri['sc_visual'])) $parametri['sc_visual'] = $this->params->get('sc_visual_nlb_b');
						} else {
							if(!isset($parametri['t_width'])) $parametri['t_width'] = $this->params->get('width_bt');
							if(!isset($parametri['t_height'])) $parametri['t_height'] = $this->params->get('height_bt');
							if(!isset($parametri['width'])) $parametri['width'] = $this->params->get('width_b');
							if(!isset($parametri['height'])) $parametri['height'] = $this->params->get('height_b');
							if(!isset($parametri['button'])) $parametri['button'] = $this->params->get('play_b');	
							if(!isset($parametri['sc_visual'])) $parametri['sc_visual'] = $this->params->get('sc_visual_b');					
						}
					} else {
						if($parametri['display']==1){
							$parametri['player'] = 1;
							if(!isset($parametri['class'])) $parametri['class'] = $this->params->get('class_l');
							if(!isset($parametri['style'])) $parametri['style'] = $this->params->get('style_l');
							if(!isset($parametri['width'])) $parametri['width'] = $this->params->get('width_l');
							if(!isset($parametri['height'])) $parametri['height'] = $this->params->get('height_l');
							if(!isset($parametri['sc_visual'])) $parametri['sc_visual'] = $this->params->get('sc_visual_l');
						} else {
							if(!isset($parametri['width'])) $parametri['width'] = $this->params->get('width');
							if(!isset($parametri['height'])) $parametri['height'] = $this->params->get('height');
							if(!isset($parametri['class'])) $parametri['class'] = $this->params->get('class');
							if(!isset($parametri['style'])) $parametri['style'] = $this->params->get('style');
							if(!isset($parametri['play'])) $parametri['play'] = $this->params->get('autoplay');
							if(!isset($parametri['sc_visual'])) $parametri['sc_visual'] = $this->params->get('sc_visual_s');
						}
					}
				}
				
				$parametri['path'] = $vb_path;
				if(!isset($parametri['sc_visual']) || $parametri['sc_visual']==2) $parametri['sc_visual'] = $this->params->get('sc_visual');
				
				if(!isset($parametri['pages']) || $parametri['pages']==0) $parametri['pages'] = 99999999;
				
				// Create video adapter object for each video
				$video_objects = array();
				foreach($videos as $video){
					$video = $this->_getVideo($video, $parametri);
					if($video) $video_objects[] = $video;
				}
				$videos = $video_objects;
				
				$count = count($videos);
				
				if($count){
				
					// Create pagination if needed
					$start = 0;
					$pagination = '';
					if(($count>$parametri['pages']) && ($parametri['pages']!=0) && ($parametri['display']==0)){
						
						if(isset($url2[$co-1])) $start = (int)$url2[$co-1];
						$path = '';
						for($h = 0; $h<$co-1; $h++){
							if(!isset($url2[$h])) $url2[$h] = '';
							if($url2[$h]=='') $url2[$h]='0';
							$path .= ','.$url2[$h];
						}
						$after = '';
						for($h = $co; $h<count($url2); $h++){
							if($url2[$h]=='') $url2[$h]='0';
							$after .= ','.$url2[$h];
						}
						
						jimport('joomla.html.pagination');
						$pg = new JPagination($count, $start, $parametri['pages']);
						$pg->prefix = 'vb';
						$pagination = '<div class="pagination">';
						if($parametri['pages_results']) $pagination .= '<p class="counter">'.$pg->getPagesCounter().'</p>';
						$pagination .= $pg->getPagesLinks().'</div>';
						$pagination = str_replace(array('vblimitstart=,', ',,'), array('vblimitstart=', ','), preg_replace('/vblimitstart=(\d+)/', 'vblimitstart='.$path.',$1'.$after, $pagination));
					}
					
					// Create the appropriate display method code
					$thumbnails = '';
					if($count==1){
						
						if($parametri['display']==2){
							$thumbnails .= $this->_videoBox($videos[0], $parametri, $co);
						} elseif($parametri['display']==1){
							$thumbnails .= $this->_videoLink($videos[0], $parametri, $co);
						} else {
							$thumbnails .= $this->_videoCode($videos[0], $parametri);
						}
						
					} elseif($count > 1){
						
						if($parametri['display']==1){
						
							foreach($videos as $n => $video){
								if($n < ($count-1)){
									$thumbnails .= $this->_videoLink($video, $parametri, $co, $n, $parametri['separator']);
								} else {
									$thumbnails .= $this->_videoLink($video, $parametri, $co, $n);
								}
							}
							
						} else {
							$thumbnails .= '<div style="'.$parametri['style'];
							if($parametri['break']!=0) $thumbnails.= ' max-width: '.(($parametri['t_width'] + 30) * $parametri['break'] - 20).'px;';
							$thumbnails.= '" class="vb_gallery_frame '.$parametri['class'].'"><ul class="vb_video">';
							for($n = $start; $n < ($start + $parametri['pages']); $n++){
								if(isset($videos[$n])){
									$thumbnails .= $this->_videoThumb($videos[$n], $parametri, $co, $n);
								} else {
									break;
								}
							}
							$thumbnails .= '</ul></div>';
						}
						
					}
					
					// Insert the code into the content
					$buffer = preg_replace($regex, str_replace('&', '&amp;', $thumbnails).$pagination, $buffer, 1);
				}
			}
			
			$pageload = str_replace($old_buffer, $buffer, $pageload);
			JResponse::setBody($pageload);
			return true;
		}
	}
	
	protected function _getVideo($video, $params){
		// Break the $video into the $id, $title, and $offset
		$video = explode('|', $video);
		$title = '';
		if(isset($video[1])) $title = trim($video[1]);
		$title = $this->htmldec($title);
		$title = $this->htmlenc($title);
		$video = explode('#', $video[0]);
		$id = trim($video[0]);
		$offset = 0;
		if(count($video) > 1 && is_numeric(str_replace(':', '', trim($video[count($video) - 1])))){
			$off = explode (':', trim($video[count($video) - 1]));
			foreach($off as $off1){
				$offset = $offset*60 + $off1;
			}
		}
		
		// Adapter switch
		if(in_array(pathinfo($id, PATHINFO_EXTENSION), array('mp4', 'ogv', 'webm', 'm4v', 'oga', 'mp3', 'm4a', 'webma', 'wav'))){
			include_once('adapters/h5video.php');
			$video = new h5Video(rawurldecode($id), $title, $offset);
			$video->poster = $this->_videoThumbnail($video, $params, true);
			return $video;
		}
		if(strlen($id)==11 && preg_match('/([a-zA-Z0-9_-]{11})/', $id)==1){
			include_once('adapters/ytvideo.php');
			return new ytVideo($id, $title, $offset);
		}
		if(is_numeric($id)){
			include_once('adapters/vmvideo.php');
			return new vmVideo($id, $title, $offset);
		}
		if(strpos($id, 'youtube')!==false){
			preg_match('/v=([a-zA-Z0-9_-]{11}?)/isU', $id, $v_urls);
			include_once('adapters/ytvideo.php');
			return new ytVideo($v_urls[1], $title, $offset);
		}
		if(strpos($id, 'youtu.be')!==false){
			preg_match('/youtu\.be\/([a-zA-Z0-9_-]{11}?)/isU', $id, $v_urls);
			include_once('adapters/ytvideo.php');
			return new ytVideo($v_urls[1], $title, $offset);
		}
		if(strpos($id, 'vimeo')!==false){
			preg_match('/vimeo.com\/([0-9]*?)/isU', $id, $v_urls);
			include_once('adapters/vmvideo.php');
			return new vmVideo($v_urls[1], $title, $offset);
		}
		if(strpos($id, 'soundcloud')!==false){
			include_once('adapters/scvideo.php');
			return new scVideo($id, $title, $offset, $params['sc_visual']);
		}
		
		// No appropriate adapter
		return false;
	}
	
	protected function _videoCode($video, $params) {
		$src = $video->getPlayerLink($params['play']);
		$responsive = '';
		$html  = '<div class="'.$responsive.'"><div owidth="'.$params['width'].'" oheight="'.$params['height'].'" style="width: '.$params['width'].'px; height: '.$params['height'].'px; max-width: 100%; '.$params['style'].'" class="vb_videoFrame '.$params['class'].'"><iframe src="'.$src.'" frameborder="0" allowfullscreen oallowfullscreen msallowfullscreen webkitallowfullscreen mozallowfullscreen style="display: block; width: 100%; height: 100%;"></iframe></div></div>';
		return $html;
	}
	
	protected function _videoLink($video, $params, $i, $n = 1, $separator = ''){
		$src = $video->getPlayerLink(true);
		$rel = 'videobox';
		if($params['player']=='0') $rel = 'vbinline';
		$html = '<a class="'.$params['class'].' vb_video_link_a" style="'.$params['style'].'" href="'.$src.'" rel="'.$rel.'.sig'.$i.'" title="' . $video->getTitle() . '" videowidth="'.$params['width'].'" videoheight="'.$params['height'].'">' . $video->getTitle(true) . '</a>'.$separator;
		return $html;
	}

	protected function _videoThumb($video, $params, $i, $n) {
		$src = $video->getPlayerLink(true);
		$img = JURI::root().'/plugins/system/videobox/'.$this->_videoThumbnail($video, $params);
		$rel = 'videobox';
		$v_width = $params['width'];
		$v_height = $params['height'];
		if($params['player']=='0'){
			$rel = 'vbinline';
			$v_width = $params['t_width'];
			$v_height = $params['t_height'];
		}
		$thumb  = '<li class="vb_video_cont vb_gal" style="width:'.($params['t_width'] + 10).'px;">
			<a href="'.$src.'" rel="'.$rel.'.sig'.$i.'" title="' . $video->getTitle() . '" videowidth="'.$v_width.'" videoheight="'.$v_height.'">
				<img src="'.$img.'" id="vb_thumb_'.$i.'_'.$n.'" alt="' . $video->getTitle() . '" />
				<span class="vb_video_title" >' . $video->getTitle() . '</span>
			</a>
		</li>';
		return $thumb;
	}
	
	protected function _videoBox($video, $params, $i, $n = 1) {
		$src = $video->getPlayerLink(true);
		$img = JURI::root().'/plugins/system/videobox/'.$this->_videoThumbnail($video, $params);
		$rel = 'videobox';
		if($params['player']=='0') $rel = 'vbinline';		
		$thumb  = '<span class="vb_video_cont vb_box '.$params['class'].'" style="'.$params['style'].'">
			<a href="'.$src.'" rel="'.$rel.'.sib'.$i.'" title="' . $video->getTitle() . '" videowidth="'.$params['width'].'" videoheight="'.$params['height'].'">
				<img src="'.$img.'" id="vb_thumb_'.$i.'_'.$n.'" alt="' . $video->getTitle() . '" />
				<span class="vb_video_title" style="max-width:'.$params['t_width'].'px;" >' . $video->getTitle() . '</span>
			</a>
		</span>';
		return $thumb;
	}
	
	protected function _videoThumbnail($video, $params, $no_border = false, $n = 0) {
		// Prevent infinite loop
		if($n > 1) return '';
		
		// Get name suffixes
		$name = '';
		if($no_border){
			$name .= '-no_border';
		} else {
			$name .= '-'.$params['t_width'].'-'.$params['t_height'];
			if($params['button']) $name .= '-button';
		}
		
		// If $video is a Video object, get its data, otherwise get nobg data
		if($video instanceof Video){
			$nobg = 'nobg_v';
			if($video->type == 'a') $nobg = 'nobg_a';
			$hash = md5($video->id . $name);
			if($params['cache'] && is_file($params['path'].'cache/'.$hash.'.jpg')){
				return 'cache/'.$hash.'.jpg';
			}
			$img = $video->getThumb();
		} else {
			$nobg = $video;
			$hash = md5($video . $name);
			if($params['cache'] && is_file($params['path'].'cache/'.$hash.'.jpg')){
				return 'cache/'.$hash.'.jpg';
			}
			$img = array($params['path'].'css/'.$nobg.'.png', IMAGETYPE_PNG);
		}
		
		try {
			switch($img[1]){
				case IMAGETYPE_JPEG: 
					$src_img = imagecreatefromjpeg($img[0]);
					break;
				case IMAGETYPE_PNG: 
					$src_img = imagecreatefrompng($img[0]);
					break;
				case IMAGETYPE_GIF: 
					$src_img = imagecreatefromgif($img[0]);
					break;
				default:
					return $this->_videoThumbnail($nobg, $params, $no_border, $n + 1);
			}
		} catch (Exception $e) {
			return $this->_videoThumbnail($nobg, $params, $no_border, $n + 1);
		}
		if(!$src_img) return $this->_videoThumbnail($nobg, $params, $no_border, $n + 1);
		
		$imagedata[0] = imagesx($src_img);
		$imagedata[1] = imagesy($src_img);

		// Remove border added by video provider
		if($imagedata[0] && $imagedata[1]){
		
			$b_t = 0;
			$b_b = 0;
			$b_l = 0;
			$b_r = 0;

			if($imagedata[0]<=1920 && $imagedata[1]<=1080){
			
				for($y = 3; $y < $imagedata[1]; $y++) {
					for($x = 3; $x < $imagedata[0]; $x++) {
						if($this->_chkB($src_img, $x, $y)) break 2;
					}
					$b_t = $y;
				}

				for($y = $imagedata[1]-4; $y >= 0; $y--) {
					for($x = 3; $x < $imagedata[0]; $x++) {
						if($this->_chkB($src_img, $x, $y)) break 2;
					}
					$b_b = $imagedata[1] - 1 - $y;
				}

				for($x = 3; $x < $imagedata[0]; $x++) {
					for($y = 3; $y < $imagedata[1]; $y++) {
						if($this->_chkB($src_img, $x, $y)) break 2;
					}
					$b_l = $x;
				}

				for($x = $imagedata[0]-4; $x >= 0; $x--) {
					for($y = 3; $y < $imagedata[1]; $y++) {
						if($this->_chkB($src_img, $x, $y)) break 2;
					}
					$b_r = $imagedata[0] - 1 - $x;
				}
			
			}

		} else {
			return $this->_videoThumbnail($nobg, $params, $no_border, $n + 1);
		}
		
		$imagedata[0] -= $b_l + $b_r;
		$imagedata[1] -= $b_t + $b_b;
		
		if($no_border){	
			
			// Just crop the border
			$newimg = imagecreatetruecolor($imagedata[0], $imagedata[1]);
			imagecopy($newimg, $src_img, 0, 0, $b_l, $b_t, $imagedata[0], $imagedata[1]);
			
		} else {
		
			// Calculate new size and offset
			$new_w = $imagedata[0];
			$new_h = $imagedata[1];		
			
			if($new_h > $params['t_height']){
				$new_w = ($params['t_height']*$new_w) / $new_h;
				$new_h = $params['t_height'];
			}
			if($new_w > $params['t_width']){
				$new_h = ($params['t_width']*$new_h) / $new_w;
				$new_w = $params['t_width'];
			}		
			
			$new_w = (int)$new_w;
			$new_h = (int)$new_h;
			$off_w = (int)(($params['t_width'] - $new_w)/2);
			$off_h = (int)(($params['t_height'] - $new_h)/2);
			
			// Copy and crop
			$newimg = imagecreatetruecolor($params['t_width'], $params['t_height']);
			$black = imagecolorallocate($newimg, 0, 0, 0);
			imagefilledrectangle($newimg, 0, 0, $params['t_width'], $params['t_height'], $black);
			imagecopyresampled($newimg, $src_img, $off_w, $off_h, $b_l, $b_t, $new_w, $new_h, $imagedata[0], $imagedata[1]);
			
			// Add button
			if($params['button']){
				$play = imagecreatefrompng($params['path'].'css/play.png');
				if($play && $params['t_width'] >= (imagesx($play) + 50) && $params['t_height'] >= (imagesy($play) + 50)){
					imagealphablending($newimg, true);
					imagecopyresampled($newimg, $play, ($params['t_width']-imagesx($play))/2, ($params['t_height']-imagesy($play))/2, 0, 0, imagesx($play), imagesy($play), imagesx($play), imagesy($play));
				}
			}
		}
		
		// Save the image and return
		imagejpeg($newimg, $params['path'].'cache/'.$hash.'.jpg', 100);
		return 'cache/'.$hash.'.jpg';
		
	}
	
	protected function _chkB($img, $x, $y){
		$rgb = imagecolorat($img, $x, $y);
		$r = ($rgb >> 16) & 0xFF;
		$g = ($rgb >> 8) & 0xFF;
		$b = $rgb & 0xFF;
		return (($r > 31)||($g > 31)||($b > 31));
	}
	
	protected function htmldec($string){
		return str_replace(array('&lt;', '&gt;', '&quot;'), array('<', '>', '"'), $string);
	}
	
	protected function htmlenc($string){
		return str_replace(array('<', '>', '"'), array('&lt;', '&gt;', '&quot;'), $string);
	}
}