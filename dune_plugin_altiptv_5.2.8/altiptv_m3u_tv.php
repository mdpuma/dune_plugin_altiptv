<?php
///////////////////////////////////////////////////////////////////////////

require_once 'lib/hashed_array.php';
require_once 'lib/tv/abstract_tv.php';
require_once 'lib/tv/default_epg_item.php';
require_once 'altiptv_channel.php';
require_once 'lib/tv/epg_iterator.php';
require_once 'lib/user_input_handler_registry.php';

///////////////////////////////////////////////////////////////////////////

class DemoM3uTv extends AbstractTv implements UserInputHandler
{	private $chid2num;
    private $parsed_schedule = null;
    private $ndx_map;
    private $pdt_map;
    private $schedule_age = 0;
	private $currentUrl = 0;
	private $finalUrl = 0;
	private $currentId = false;
	public function get_handler_id() { return "tv"; }
    public function __construct()
    {
        UserInputHandlerRegistry::get_instance()->register_handler($this);
		parent::__construct(
            AbstractTv::MODE_CHANNELS_N_TO_M,
            self::favorites_support(),
            false);
    }

    public function get_fav_icon_url(&$plugin_cookies)
    {
        $altiptv_data_path = DemoConfig::get_altiptv_data_path(&$plugin_cookies);
		$fico=$altiptv_data_path . '/icons/fav.png';
		return $fico;
    }
	public function favorites_support()
    {
        $link = DuneSystem::$properties['data_dir_path'].'/altiptv_data/data_file/fav';
		$fav_show = file_exists($link) ? file_get_contents ($link): 'yes';
		if ($fav_show == 'yes')
		$favorites_support = true;
		else
		$favorites_support = false;
		return $favorites_support;
    }
    public function get_tv_stream_url($playback_url, &$plugin_cookies)
    {
	$this->finalUrl = $playback_url;
	$use_proxy = isset($plugin_cookies->use_proxy) ?
            $plugin_cookies->use_proxy : 'no';	
	$playback_url = preg_replace("/^udp:\/\//", "udp://@", $playback_url);
	$playback_url = str_replace('@@', '@', $playback_url);
	if (($use_proxy == 'yes')&&(preg_match("/udp:\/\/@/i",$playback_url)))
		$url = str_replace('udp://@', 'http://ts://'.$plugin_cookies->proxy_ip.':'.$plugin_cookies->proxy_port.'/udp/', $playback_url);
	else if (($use_proxy == 'yes')&&(preg_match("/rtp:\/\/@/i",$playback_url)))
		$url = str_replace('rtp://@', 'http://ts://'.$plugin_cookies->proxy_ip.':'.$plugin_cookies->proxy_port.'/rtp/', $playback_url);
	else if (substr(strtolower($playback_url), 0, 12) == 'http://ts://')
	    $url = $playback_url;
	else if (substr(strtolower($playback_url), 0, 22) == 'http://audio_stream://')
	    $url = $playback_url;
	else if (substr(strtolower($playback_url), 0, 11) == 'http://ts//')
	    $url = str_replace('http://ts//', 'http://ts://', $playback_url);
	else if (substr(strtolower($playback_url), 0, 13) == 'http://mp4://')
	     $url = $playback_url;
	else if (substr(strtolower($playback_url), 0, 12) == 'http://mp4//')
	     $url = str_replace('http://mp4//', 'http://mp4://', $playback_url);
	else if (substr(strtolower($playback_url), 0, 4) == 'rtmp') {
	    $f = array('rtmp://$OPT:rtmp-raw=', 'rtmp://', 'rtmpt://','rtmpe://','rtmpte://', ' --', '<playpath>', '<swfUrl>', '<pageUrl>');
	    $t = array('', "http://ts://127.0.0.1:81/cgi-bin/rtmp.sh?rtmp://", "http://ts://127.0.0.1:81/cgi-bin/rtmp.sh?rtmpt://","http://ts://127.0.0.1:81/cgi-bin/rtmp.sh?rtmpe://","http://ts://127.0.0.1:81/cgi-bin/rtmp.sh?rtmpte://", ' ',' playpath=', 'swfUrl=', 'pageUrl=');
	    $url = str_ireplace($f, $t, $playback_url);
	}
	else if (substr(strtolower($playback_url), 0, 7) == 'rtsp://')
	     $url = str_replace('rtsp://', 'http://ts://127.0.0.1:81/cgi-bin/rtsp.sh?rtsp://', $playback_url);
	else if (substr(strtolower($playback_url), -3) == 'flv')
	     $url = str_replace('http://', 'http://ts://127.0.0.1:81/cgi-bin/flv.sh?http://', $playback_url);
	else if(substr($playback_url, -1, 1) == ':')
	     $url = $playback_url;
	else if (substr(strtolower($playback_url), 0, 7) == 'http://')
	     $url = str_replace('http://', 'http://ts://', $playback_url);
	else 
	     $url = $playback_url;
	if (preg_match("|127.0.0.1:81\/cgi-bin|",$url))
		HD::get_codec_start_info();
	return $url;
    }

    public function parse_playlist($file, &$plugin_cookies) 
    {
	if (preg_match("/xspf/i", $file))
		return self::parse_xspf($file, &$plugin_cookies);
	else {
		return self::parse_m3u($file, &$plugin_cookies);
	}
    }
	
    public function parse_m3u($m3u_file, &$plugin_cookies)
    {
	$dload_http = isset($plugin_cookies->dload_http) ?
		$plugin_cookies->dload_http : 0;

	$playlist = array();
	if ((preg_match('|http:\/\/|', $m3u_file))&& ($dload_http == 1)){
		$web_pls = HD::http_get_document($m3u_file);
		if ($web_pls == false)
			$web_pls = 'Плейлист не доступен';
		if (!preg_match('|#EXTINF:|', $web_pls))
			$web_pls = "По ссылке\n $m3u_file \n m3u плейлиста нет.";
		$web_pls = str_replace("\r", "", $web_pls);
		$m3u_lines = explode ("\n", $web_pls);
	}
	else if (preg_match('|https:\/\/|', $m3u_file)){
		$web_pls = HD::http_get_document($m3u_file);
		if ($web_pls == false)
			$web_pls = 'Плейлист не доступен';
		if (!preg_match('|#EXTINF:|', $web_pls))
			$web_pls = "По ссылке\n $m3u_file \n m3u плейлиста нет.";
		$web_pls = str_replace("\r", "", $web_pls);
		$m3u_lines = explode ("\n", $web_pls);
	}
	else {
		if (!($m3u_lines = file($m3u_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES))) {
		hd_print("Error opening $m3u_file");
		return $playlist;
		}
	}
	$cod_type = isset($plugin_cookies->cod_type) ?
        $plugin_cookies->cod_type : '0';
	$ico_show = isset($plugin_cookies->ico_show) ?
        $plugin_cookies->ico_show : 'yes';
	
	$caption = ''; 	$url = ''; $logo = ''; $tvg_name = ''; 	$id = '';  $epg_shift = 0; $group_title = ''; $i = 0; $parental = 0;
	$user_agent = 
	$dune_zoom = false;
	foreach ($m3u_lines as $line) {
		$group_id = 0;

		if ($cod_type == 0)	{
		if (!preg_match('//u', $line))
		$line = iconv('WINDOWS-1251', 'UTF-8', $line);}
		if ($cod_type == 2)	{
		$line = iconv('WINDOWS-1251', 'UTF-8', $line);}
		global $url_tvg;
		$line = trim($line);	
	 	if (preg_match('/#EXTM3U/', strtoupper($line), $matches)){
		if (preg_match('/url-tvg=(.*?zip)/', $m3u_lines[0], $matches)){
		$url_tvg = $matches[1];
		$url_tvg = str_replace(array("\"", "'"), '', $url_tvg);}
			continue;
		}
		else if (preg_match('/^#EXTVLCOPT:|^#EXTGRP:|^#EXT-INETRA/', $line)){
			if (preg_match('/^#EXTGRP:(.*)/', $line, $matches))
			    $group_title = preg_replace('/\"/', '', $matches[1]);
			continue;
		}
		else if (preg_match('/^#EXTINF:[^,]*,(.+)$/', $line, $matches)) {
			$caption = trim($matches[1]);
			$parental = 0; 
			$group_title = '';
			$line = str_replace('groups=', 'group-title=', $line);
			if ($ico_show=='yes'){
			if (preg_match('/^#EXTINF:.*logo=([^ ^,]+)/', $line, $matches)) {
			    if ((preg_match('|\/\/|', $matches[1]))
				||(preg_match('|\/D\/|', $matches[1]))
				||(preg_match('|\/flashdata\/|', $matches[1]))
				||(preg_match('|\/path_m\/|', $matches[1]))
				||(preg_match('|\/persistfs\/|', $matches[1])))
				$logo = preg_replace('/\"/', '', $matches[1]);
			}}
			if (preg_match('/^#EXTINF:.*id=vsetv_([^ ^,]+)/', $line, $matches)) {
			    $id = preg_replace('/\"/', '', $matches[1]);			
			}
			if (preg_match('/^#EXTINF:.*tvg-name=(".*?")/', $line, $matches)) {
			    $tvg_name = preg_replace('/\"/', '', $matches[1]);
			}
			if (preg_match('/^#EXTINF:.*group-title=(".*?")/', $line, $matches)) {
			    $group_title = preg_replace('/\"/', '', $matches[1]);
			}
			if (preg_match('/^#EXTINF:.*shift=([^ ^,]+)/', $line, $matches)) {
			    $epg_shift = preg_replace('/\"/', '', $matches[1]);			
			}
			if (preg_match('/^#EXTINF:.*parental/', $line, $matches)) {
			    $parental = 1;
			$pin = isset($plugin_cookies->pin) ? $plugin_cookies->pin : '0000';
				if ($pin == '1111')
				{
				$parental = 0;
				}
			}
			if (preg_match('/^#EXTINF:.*group_id=([^ ^,]+)/', $line, $matches)) {
			    $group_id = preg_replace('/\"/', '', $matches[1]);			
			}
			if (preg_match('/^#EXTINF:.*user_agent=([^ ^,]+)/', $line, $matches)) {
			    $user_agent = preg_replace('/\"/', '', $matches[1]);
			}
			if (preg_match('/^#EXTINF:.*dune_zoom=([^ ^,]+)/', $line, $matches)) {
			    $dune_zoom = preg_replace('/\"/', '', $matches[1]);
			}
			continue;
		}
		
		$url = $line;
		$playlist[$i]['url'] = $url;
		$playlist[$i]['caption'] = ($caption == '') ? $url : $caption;		
		$playlist[$i]['logo'] = $logo;
		$playlist[$i]['group'] = $group_id;
		$playlist[$i]['tvg_name'] = $tvg_name;
		$playlist[$i]['user_agent'] = $user_agent;
		$playlist[$i]['dune_zoom'] = $dune_zoom;
		$playlist[$i]['group_title'] = $group_title;
		$playlist[$i]['id'] = trim($id);
		$playlist[$i]['parental'] = $parental;
		$playlist[$i]['epg_shift'] = ($epg_shift == '') ? 0 : floatval($epg_shift);
		$playlist[$i]['epg_shift'] = (($playlist[$i]['epg_shift'] < -24) || ($playlist[$i]['epg_shift'] < -24)) ? 0 : $playlist[$i]['epg_shift'] + 24;
		$caption = '';
		$logo = '';
		$id = '';
		$i++;
		$epg_shift = 0;
		if (preg_match('/shift=([^ ^,]+)/', $m3u_lines[0], $matches))
		$epg_shift = preg_replace('/\"/', '', $matches[1]);
		$group_title = '';
	}

    	return $playlist;

    }
    public function parse_xspf($xspf_file, &$plugin_cookies)
    {
    	libxml_use_internal_errors(true);
	$ico_show = isset($plugin_cookies->ico_show) ?
        $plugin_cookies->ico_show : 'yes';
		
	$i = 0;	$playlist = array();
	$caption = ''; 	$url = ''; $logo = ''; $tvg_name = ''; 	$id = '';  $epg_shift = 0; $group_title = ''; $i = 0; $parental = 0;
	$user_agent = 
	$dune_zoom = false;

	if (!($test = simplexml_load_string(file_get_contents($xspf_file)))) {
		hd_print("Error parsing $xspf_file");
		return $playlist;
	}

	else {
	foreach ($test->tracklist->children() as $track) {
	    $caption = trim($track->title);
	    $url = trim(preg_replace("/\/manualDetectVideoInfo.+/", "", $track->location));
	    $id_t = $track->extension->xpath('altiptv:id');
	    $id = isset($id_t[0]) ? substr(trim($id_t[0]), 6) : ''; 
	    $epg_shift_t = $track->extension->xpath('altiptv:epg_shift');
	    $epg_shift = isset($epg_shift_t[0]) ? floatval(trim($epg_shift_t[0])) : 0; 
	    if ($ico_show=='yes')
		$logo_t = $track->thumb;
	    $logo = isset($logo_t[0]) ? trim($logo_t[0]) : '';
	    $playlist[$i]['url'] = $url;
	    $playlist[$i]['caption'] = ($caption == '') ? $url : $caption;		
	    $playlist[$i]['logo'] = $logo;		
	    $playlist[$i]['id'] = $id;
		$playlist[$i]['tvg_name'] = $tvg_name;
		$playlist[$i]['user_agent'] = $user_agent;
		$playlist[$i]['dune_zoom'] = $dune_zoom;
		$playlist[$i]['parental'] = $parental;
	    $playlist[$i]['epg_shift'] = ($epg_shift == '') ? 0 : floatval($epg_shift);
	    $playlist[$i]['epg_shift'] = (($playlist[$i]['epg_shift'] < -24) || ($playlist[$i]['epg_shift'] < -24)) ? 0 : $playlist[$i]['epg_shift'] + 24;
	    $caption = ''; $logo = ''; 	$id = ''; $epg_shift = 0; $group_title = '';
	    $i++;
	}
	return $playlist;
	}
    }


    ///////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////
	
    public function folder_entered(MediaURL $media_url, &$plugin_cookies) {
	if ($media_url->get_raw_string() == 'tv_group_list')
                $this->load_channels($plugin_cookies);            
    }

    private static function get_icon_path($channel_id,&$plugin_cookies)
    { 
	if(preg_match("/%/", $channel_id)){
	$tmp = explode('%', $channel_id);
	$channel_id = $tmp[0];}
	$channel_id = ($channel_id < 40000) ? $channel_id : 0;
	$altiptv_data_path = DemoConfig::get_altiptv_data_path(&$plugin_cookies);
	if (file_exists($altiptv_data_path . "/logo/$channel_id.png"))
	$icon_url = $altiptv_data_path."/logo/$channel_id.png";
	else
	$icon_url = DuneSystem::$properties['data_dir_path'] . "/altiptv_data/logo/$channel_id.png";
	return $icon_url;
    }

    private static function get_future_epg_days($channel_id)
    { 
	$days = ($channel_id < 20000) ? 3 : 0;
	return $days;
    }

    protected function load_channels(&$plugin_cookies)
    {
        $sort_channels = isset($plugin_cookies->sort_channels) ? $plugin_cookies->sort_channels : '1';
		$altiptv_data_path = DemoConfig::get_altiptv_data_path(&$plugin_cookies);
		$this->channels = new HashedArray();
        $this->groups = new HashedArray();

        if ($this->is_favorites_supported())
        {
            $this->groups->put(
                new FavoritesGroup(
                    $this,
                    '__favorites',
                    DemoConfig::FAV_CHANNEL_GROUP_CAPTION,
                    $altiptv_data_path . '/icons/fav.png'));
        }

        $all_channels_group = 
            new AllChannelsGroup(
                $this,
                DemoConfig::ALL_CHANNEL_GROUP_CAPTION,
                $altiptv_data_path . '/icons/all.png');

        $this->groups->put($all_channels_group);

	$out_lines = "";
	$channels_id_parsed = array();
	$group_defs = array();
	$parental_defs = array();
	$m3u_lines = array();
	$name_pl_defs = array();
	$hide_ch_defs = array();
	
    $array1 = unserialize(file_get_contents($altiptv_data_path.'/data_file/myiptv_channels_id'));
    $array2 = unserialize(file_get_contents($altiptv_data_path.'/data_file/other_channels_id'));
    $array3 = unserialize(file_get_contents($altiptv_data_path.'/data_file/my_channels_id'));
	$channels_id_parsed = array_merge($array1, $array2, $array3);
	$group_defs = HD::get_items('grups_id', &$plugin_cookies);
	$parental_defs = HD::get_items('parental', &$plugin_cookies);
	$name_pl_defs = HD::get_items('name_pl', &$plugin_cookies);
	$hide_ch_defs = HD::get_items('hide_ch', &$plugin_cookies);
	$chnmbr_list = HD::get_items('chnmbr_list', &$plugin_cookies);
	$pin = isset($plugin_cookies->pin) ? 
			$plugin_cookies->pin : '0000';
    $m3u = isset($plugin_cookies->m3u) ? 
			$plugin_cookies->m3u : '';
	$m3u_type = isset($plugin_cookies->m3u_type) ?
            $plugin_cookies->m3u_type : '1';
	$m3u_dir = isset($plugin_cookies->m3u_dir) ?
            $plugin_cookies->m3u_dir : '/D';
	$ip_path = isset($plugin_cookies->ip_path) ? 
			$plugin_cookies->ip_path : '';
	$smb_user = isset($plugin_cookies->smb_user) ? 
			$plugin_cookies->smb_user : '';
	$prov_pl = isset($plugin_cookies->prov_pl) ?
			$plugin_cookies->prov_pl : '0';
	$smb_pass = isset($plugin_cookies->smb_pass) ? 
		$plugin_cookies->smb_pass : '';
	$double_tv = isset($plugin_cookies->double_tv) ?
        $plugin_cookies->double_tv : '1';
	$group_tv = isset($plugin_cookies->group_tv) ?
        $plugin_cookies->group_tv : '1';
	$start_tv = isset($plugin_cookies->start_tv) ?
        $plugin_cookies->start_tv : 0;
	$buf_time = isset($plugin_cookies->buf_time) ? 
		$plugin_cookies->buf_time : 0;
	$show_hd = isset($plugin_cookies->show_hd) ?
        $plugin_cookies->show_hd : 'no';
	$group_p = isset($plugin_cookies->group_p) ?
        $plugin_cookies->group_p : '0';
	$arc = isset($plugin_cookies->arc) ?
		$plugin_cookies->arc : 'arc_itm';
	$m3u_files = array();
	if ($prov_pl !== '0'){
	$tmp = explode('|', $prov_pl);
	$plist = $tmp[1];
	$plist_n = $tmp[0];
	$m3u_files[]=$plist;
	}

	if ($m3u_type == 1) {
		if (preg_match("/\/path_m\//i",$altiptv_data_path)){
		$files = scandir($altiptv_data_path.'/playlists/');
		foreach($files as $file)
		{
		if (preg_match('/.[mM]3[uU]$/i', $file))
		$m3u_files[]= $altiptv_data_path. '/playlists/' . $file;
		}	
		}
		else{
		foreach (glob('{'.$altiptv_data_path.'/playlists/*.xspf,'.$altiptv_data_path.'/playlists/*.[mM]3[uU]}', GLOB_BRACE) as $file) 
    			if (is_file($file)) $m3u_files[]=$file;
		}
		if (file_exists( $altiptv_data_path. '/playlists/pls.txt'))
			{
			$m3u_list = file_get_contents($altiptv_data_path. '/playlists/pls.txt');
			$m3u_list = str_replace(array ("\n","\r"), "", $m3u_list);
			$m3u_arr = explode("http", $m3u_list);
			unset($m3u_arr[0]);
			$m3u_arr = array_values($m3u_arr);
			foreach($m3u_arr as $m3u_s)
			$m3u_files[]='http'. $m3u_s;
			}
	}
	else if ($m3u_type == 2) {
		foreach (glob('{'.$m3u_dir.'/*.[mM]3[uU],'.$m3u_dir.'/*.xspf}', GLOB_BRACE) as $file) 
    			if (is_file($file)) $m3u_files[]=$file;
		if (file_exists( $m3u_dir . '/pls.txt'))
			{
			$m3u_list = file_get_contents($m3u_dir . '/pls.txt');
			$m3u_list = str_replace(array ("\n","\r"), "", $m3u_list);
			$m3u_arr = explode("http", $m3u_list);
			unset($m3u_arr[0]);
			$m3u_arr = array_values($m3u_arr);
			foreach($m3u_arr as $m3u_s)
			$m3u_files[]='http'. $m3u_s;
			}
	}
	else if ($m3u_type == 4) {
		foreach (glob('{'.$m3u_dir.'/*.[mM]3[uU],'.$m3u_dir.'/*.xspf}', GLOB_BRACE) as $file) 
    			if (is_file($file)) $m3u_files[]=$file;
		if (file_exists( $m3u_dir . '/pls.txt'))
			{
			$m3u_list = file_get_contents($m3u_dir . '/pls.txt');
			$m3u_list = str_replace(array ("\n","\r"), "", $m3u_list);
			$m3u_arr = explode("http", $m3u_list);
			unset($m3u_arr[0]);
			$m3u_arr = array_values($m3u_arr);
			foreach($m3u_arr as $m3u_s)
			$m3u_files[]='http'. $m3u_s;
			}
	}
	else if ($m3u_type == 5) {
		foreach (glob('{'.$m3u_dir.'/*.[mM]3[uU],'.$m3u_dir.'/*.xspf}', GLOB_BRACE) as $file) 
    			if (is_file($file)) $m3u_files[]=$file;
		if (file_exists( $m3u_dir . '/pls.txt'))
			{
			$m3u_list = file_get_contents($m3u_dir . '/pls.txt');
			$m3u_list = str_replace(array ("\n","\r"), "", $m3u_list);
			$m3u_arr = explode("http", $m3u_list);
			unset($m3u_arr[0]);
			$m3u_arr = array_values($m3u_arr);
			foreach($m3u_arr as $m3u_s)
			$m3u_files[]='http'. $m3u_s;
			}
	}
	else if ($m3u_type == 7) {
		$m3u_dir = HD::get_mount_smb_path($ip_path, $smb_user, $smb_pass, 'm3u_dir');
		$files = scandir($m3u_dir);
		foreach($files as $file)
		{
		if (preg_match('/.[mM]3[uU]$/i', $file))
		$m3u_files[]= $m3u_dir . $file;
		}
		if (file_exists( $m3u_dir . '/pls.txt'))
		{
			$m3u_list = file_get_contents($m3u_dir . '/pls.txt');
			$m3u_list = str_replace(array ("\n","\r"), "", $m3u_list);
			$m3u_arr = explode("http", $m3u_list);
			unset($m3u_arr[0]);
			$m3u_arr = array_values($m3u_arr);
			foreach($m3u_arr as $m3u_s)
			$m3u_files[]='http'. $m3u_s;
		}
	}
	if ($m3u_type == 8) {
		if (file_exists($m3u_dir))
			{
		foreach (glob('{'.$m3u_dir.'/*.[mM]3[uU],'.$m3u_dir.'/*.xspf}', GLOB_BRACE) as $file) 
    			if (is_file($file)) $m3u_files[]=$file;
			}
		if (file_exists( $m3u_dir . '/pls.txt'))
			{
			$m3u_list = file_get_contents($m3u_dir . '/pls.txt');
			$m3u_list = str_replace(array ("\n","\r"), "", $m3u_list);
			$m3u_arr = explode("http", $m3u_list);
			unset($m3u_arr[0]);
			$m3u_arr = array_values($m3u_arr);
			foreach($m3u_arr as $m3u_s)
			$m3u_files[]='http'. $m3u_s;
			}
		if (!$m3u == '') {
		if(preg_match("/-pd-/", $m3u))
		{
		$plists = explode('-pd-', $m3u);
		$plists = array_values($plists);
		foreach($plists as $plist)
				$m3u_files[]=$plist;
		}
		else{
		$m3u_files[]=$m3u;
		}
		}
		if (!$ip_path == '') {
		$m3u_dir = HD::get_mount_smb_path($ip_path, $smb_user, $smb_pass, 'm3u_dir');
		if (file_exists( $m3u_dir . '/pls.txt'))
			{
			$m3u_list = file_get_contents($m3u_dir . '/pls.txt');
			$m3u_list = str_replace(array ("\n","\r"), "", $m3u_list);
			$m3u_arr = explode("http", $m3u_list);
			unset($m3u_arr[0]);
			$m3u_arr = array_values($m3u_arr);
			foreach($m3u_arr as $m3u_s)
			$m3u_files[]='http'. $m3u_s;
			}
		$files = scandir($m3u_dir);
		foreach($files as $file)
		{
		if (preg_match('/.m3u$|.m3u8$/i', $file))
		$m3u_files[]= $m3u_dir . $file;
		}
		}
		
	}
	else if ($m3u_type == 6) {
		if (file_exists( $m3u_dir . '/pls.txt'))
			{
			$m3u_list = file_get_contents($m3u_dir . '/pls.txt');
			$m3u_list = str_replace(array ("\n","\r"), "", $m3u_list);
			$m3u_arr = explode("http", $m3u_list);
			unset($m3u_arr[0]);
			$m3u_arr = array_values($m3u_arr);
			foreach($m3u_arr as $m3u_s)
			$m3u_files[]='http'. $m3u_s;
			}}
	else if ($m3u_type == 3) {
		if(preg_match("/-pd-/", $m3u))
		{
		$plists = explode('-pd-', $m3u);
		$plists = array_values($plists);
		foreach($plists as $plist)
				$m3u_files[]=$plist;
		}
		else{
		$m3u_files[]=$m3u;
		}
	}
	
	$gid = 0;
	
	
	if (($group_tv == 1)||($group_tv == 4)){
		foreach ($m3u_files as $m3u_file) {
			$chnmbr =-1;
			$icon_file = $altiptv_data_path.'/icons/tv.png';
			$gname = basename($m3u_file);
			$is_renemed = (array_key_exists($gname, $name_pl_defs)) ?  $name_pl_defs[$gname] : '';
			if ($is_renemed != '')
				$gname = $is_renemed;
			if (($prov_pl !== '0') && ($plist == $m3u_file))
				$gname = $plist_n;
			if (file_exists($altiptv_data_path."/icons/$gname.png"))
				$icon_file = $altiptv_data_path."/icons/$gname.png";
			if (file_exists(str_ireplace('.m3u', '.png', $m3u_file)))
				$icon_file = str_ireplace('.m3u', '.png', $m3u_file);
			$i = 0;
			foreach (self::parse_playlist($m3u_file, &$plugin_cookies) as $playlist_line) {
				$archive = 0;
				$caption = $playlist_line['caption'];
				if ($caption == '')
					continue;
				if (file_exists($altiptv_data_path . '/data_file/hide_ch')){
					$hide_key = $gname."|$caption";
					$is_hide = (array_key_exists($hide_key, $hide_ch_defs)) ? 1 : 0;
					if ($is_hide == 1)
						continue;
				}
				$media_url = $playlist_line['url']; 
				$tvg_name = $playlist_line['tvg_name'];
				$user_agent = $playlist_line['user_agent'];
				$dune_zoom = $playlist_line['dune_zoom'];
				$captions = mb_strtolower($caption, 'UTF-8');
				$captions = str_replace(array(" ", "-", ".", "\r", "\n", "\"", " "), '', $captions);
				$id_key = md5($captions);
				//hd_print("$caption => $id_key");
				if ($playlist_line['id'] == '')
					$id = array_key_exists($id_key,$channels_id_parsed) ? $channels_id_parsed[$id_key] : 40000 + $i;
				else
					$id = $playlist_line['id'];
				if (($sort_channels == 3)||($sort_channels == 4)){
					$chnmbr = array_search($id, $chnmbr_list);
					if ($chnmbr =='')
						$chnmbr =-1;
				}
				$arc_itm = HD::get_items($arc, &$plugin_cookies);
				if (isset($arc_itm[$id]))
					$archive=1;
				$pl_id = str_replace('-', '', crc32($gname));
				$n_id = str_replace('-', '', crc32($caption));
				$id = $id . '%' . $pl_id . $n_id;
				if ($double_tv == 2)
					$id = $id . '%' . $pl_id . str_replace('-', '', crc32($media_url)). $n_id;
				if ($pin == '1111')
					$is_protected = 0;
				else{
					$is_protected = $playlist_line['parental'];
					if ($is_protected == 0)
						$is_protected = (array_key_exists($id_key, $parental_defs)) ? 1 : 0;
				}
				$cid = "alt_".$playlist_line['epg_shift']."_".$id;
				
				$logo = ($playlist_line['logo'] == '') ? self::get_icon_path($id,&$plugin_cookies) : $playlist_line['logo'];
				
					$channel =
						new DemoChannel(
							$cid,
							$caption,
							$logo,
							$media_url,
							$chnmbr,
							self::get_future_epg_days($id),
							self::get_future_epg_days($id),
							$is_protected,
							$buf_time,
							$tvg_name,
							basename($m3u_file),
							intval($archive),
							$user_agent,
							$dune_zoom);
							
				if ((!($this->groups->has($gid)))&&($group_tv == 1)) {
					$this->groups->put(
							new DefaultGroup(
								strval($gid),
								strval($gname),
								$icon_file));
				}
				
				$this->channels->put($channel);
				if ($group_tv == 1){
					$group = $this->groups->get($gid);
					$channel->add_group($group);
					$group->add_channel($channel);
				}
			$i++;
		  }
		$gid++;        
		}
	}
	else if ($group_tv == 2){
		foreach ($m3u_files as $m3u_file) {
			$chnmbr =-1;
		$pname = basename($m3u_file);
		$i = 0;
		foreach (self::parse_playlist($m3u_file, &$plugin_cookies) as $playlist_line) {
			$archive = 0;
			$caption = $playlist_line['caption'];
			if ($caption == '')
				continue;
			if (file_exists($altiptv_data_path . '/data_file/hide_ch')){
				$hide_key = $pname."|$caption";
				$is_hide = (array_key_exists($hide_key, $hide_ch_defs)) ? 1 : 0;
				if ($is_hide == 1)
					continue;
			}
			$media_url = $playlist_line['url']; 
			$tvg_name = $playlist_line['tvg_name'];
			$user_agent = $playlist_line['user_agent'];
			$dune_zoom = $playlist_line['dune_zoom'];
			$captions = mb_strtolower($caption, 'UTF-8');
			$captions = str_replace(array(" ", "-", ".", "\r", "\n", "\"", " "), '', $captions);
			$id_key = md5($captions);
			$group_id = $playlist_line['group'];
			if ($group_id > 0) {
				list ($gid, $gname) = DemoConfig::get_group_name($group_id,$plugin_cookies);
				$this->global_channels[$id_key]['group'] = $gid;
			}
			if ($playlist_line['id'] == '')
				$id = array_key_exists($id_key,$channels_id_parsed) ? $channels_id_parsed[$id_key] : 40000 + $i;
			else
				$id = $playlist_line['id'];
			if (($sort_channels == 3)||($sort_channels == 4)){
					$chnmbr = array_search($id, $chnmbr_list);
					if ($chnmbr =='')
						$chnmbr =-1;
				}
			$arc_itm = HD::get_items($arc, &$plugin_cookies);
			if (isset($arc_itm[$id]))
				$archive=1;
			if ($group_id == 0) {
				$group_id = (array_key_exists($id, $group_defs)) ? $group_defs[$id] : 0;
				list ($gid, $gname) = DemoConfig::get_group_name($group_id,$plugin_cookies);
			}
			if (file_exists($altiptv_data_path . "/icons/$gid.png"))
				$icon_file = $altiptv_data_path."/icons/$gid.png";
			else if (file_exists($altiptv_data_path . "/icons/$group_id.png"))
				$icon_file = $altiptv_data_path."/icons/$group_id.png";
			else if (file_exists($altiptv_data_path . "/icons/$gname.png"))
				$icon_file = $altiptv_data_path ."/altiptv_data/icons/$gname.png";
			else
				$icon_file = $altiptv_data_path.'/icons/tv.png';
			$pl_id = str_replace('-', '', crc32($pname));
			$n_id = str_replace('-', '', crc32($caption));
			$id = $id . '%' . $pl_id . $n_id;
			if ($double_tv == 2)
				$id = $id . '%' . $pl_id . str_replace('-', '', crc32($media_url)). $n_id;
			if ($pin == '1111')
				$is_protected = 0;
			else{
				$is_protected = $playlist_line['parental'];
				if ($is_protected == 0)
					$is_protected = (array_key_exists($id_key, $parental_defs)) ? 1 : 0;
			}
			$cid = "alt_".$playlist_line['epg_shift']."_".$id;
			$logo = ($playlist_line['logo'] == '') ? self::get_icon_path($id,&$plugin_cookies) : $playlist_line['logo'];
			$is_renemed = (array_key_exists($gname, $name_pl_defs)) ?  $name_pl_defs[$gname] : '';
			if ($is_renemed != '')
				$gname = $is_renemed;
				$channel =
					new DemoChannel(
						$cid,
						$caption,
						$logo,
						$media_url,
						$chnmbr,
						self::get_future_epg_days($id),
						self::get_future_epg_days($id),
						$is_protected,
						$buf_time,
						$tvg_name,
						$pname,
						intval($archive),
						$user_agent,
						$dune_zoom);
			
			if (!($this->groups->has($gid))) {
				$this->groups->put(
						new DefaultGroup(
							strval($gid),
							strval($gname),
							$icon_file));
			}	
			$this->channels->put($channel);
			$group = $this->groups->get($gid);
			$channel->add_group($group);
			$group->add_channel($channel);
			$i++;

		if ((preg_match("/HD/",$caption)) && ($show_hd === 'yes') && ($gid != 13)) {
			$group_id = 13;
			list ($gid, $gname) = DemoConfig::get_group_name($group_id,$plugin_cookies);
			if (file_exists($altiptv_data_path . "/icons/$group_id.png"))
				$icon_file = $altiptv_data_path."/icons/$group_id.png";
			else
				$icon_file = DuneSystem::$properties['data_dir_path']."/altiptv_data/icons/$group_id.png";
			$cid = "alt_".$playlist_line['epg_shift']."_".$id;
			$is_renemed = (array_key_exists($gname, $name_pl_defs)) ?  $name_pl_defs[$gname] : '';
			if ($is_renemed != '')
				$gname = $is_renemed;
			if (!($this->groups->has($gid))) {
				$this->groups->put(
						new DefaultGroup(
							strval($gid),
							strval($gname),
							$icon_file));
			}
			$group = $this->groups->get($gid);
			$channel->add_group($group);
			$group->add_channel($channel);}
		  }
			$gid++;        
		}
	}
	else if ($group_tv == 3){
		$gids =0;
		foreach ($m3u_files as $m3u_file) {
			$chnmbr =-1;
			$icon_file = $altiptv_data_path.'/icons/tv.png';
			$gname = basename($m3u_file);
			if (($prov_pl !== '0') && ($plist == $m3u_file))
				$gname = $plist_n;
			$pl_name = $gname;
			if (file_exists($altiptv_data_path."/icons/$gname.png"))
				$icon_file = $altiptv_data_path."/icons/$gname.png";
			if (file_exists(str_ireplace('.m3u', '.png', $m3u_file)))
				$icon_file = str_ireplace('.m3u', '.png', $m3u_file);
		 $i = 0;
		 
		 foreach (self::parse_playlist($m3u_file, &$plugin_cookies) as $playlist_line) {
			$archive = 0;
			if ($group_p == '0')
				$gid = $gids;
			$gname = $pl_name;
			$caption = $playlist_line['caption'];
			if ($caption == '')
				continue;
			if (file_exists($altiptv_data_path . '/data_file/hide_ch')){
				$hide_key = $gname."|$caption";
				$is_hide = (array_key_exists($hide_key, $hide_ch_defs)) ? 1 : 0;
				if ($is_hide == 1)
					continue;
			}
			$media_url = $playlist_line['url'];
			$tvg_name = $playlist_line['tvg_name'];
			$user_agent = $playlist_line['user_agent'];
			$dune_zoom = $playlist_line['dune_zoom'];
			$group_title = $playlist_line['group_title'];
			$captions = mb_strtolower($caption, 'UTF-8');
			$captions = str_replace(array(" ", "-", ".", "\r", "\n", "\"", " "), '', $captions);
			$id_key = md5($captions);
				//hd_print("tvg_name--->>> $tvg_name");
				//hd_print("id_key--->>> $id_key");
			if ($playlist_line['id'] == '')
				$id = array_key_exists($id_key,$channels_id_parsed) ? $channels_id_parsed[$id_key] : 40000 + $i;
			else
				$id = $playlist_line['id'];
			if (($sort_channels == 3)||($sort_channels == 4)){
					$chnmbr = array_search($id, $chnmbr_list);
					if ($chnmbr =='')
						$chnmbr =-1;
				}
			$arc_itm = HD::get_items($arc, &$plugin_cookies);
			if (isset($arc_itm[$id]))
				$archive=1;
			$pl_id = str_replace('-', '', crc32($gname));
			$n_id = str_replace('-', '', crc32($caption));
			$id = $id . '%' . $pl_id . $n_id;
			if ($double_tv == 2)
				$id = $id . '%' . $pl_id . str_replace('-', '', crc32($media_url)). $n_id;
			if ($pin == '1111')
				$is_protected = 0;
			else{
			$is_protected = $playlist_line['parental'];
			if ($is_protected == 0)
				$is_protected = (array_key_exists($id_key, $parental_defs)) ? 1 : 0;
			}
			$cid = "alt_".$playlist_line['epg_shift']."_".$id;
			$logo = ($playlist_line['logo'] == '') ? self::get_icon_path($id,&$plugin_cookies) : $playlist_line['logo'];
			if (!$group_title === false){
				if (preg_match("/\|/",$group_title)){
					$group_titles = explode('|', $group_title);
					$group_titles = array_values($group_titles);
					$i=0;
					foreach($group_titles as $group_title)
					{
						$gid = crc32($group_title);
						$gid = str_replace('-', '',$gid);
						$gname = $group_title;
						$is_renemed = (array_key_exists($gname, $name_pl_defs)) ?  $name_pl_defs[$gname] : '';
						if ($is_renemed != '')
							$gname = $is_renemed;
						if (file_exists($altiptv_data_path . "/icons/$gname.png"))
							$icon_file = $altiptv_data_path."/icons/$gname.png";
						else
							$icon_file = $altiptv_data_path.'/icons/tv.png';
						$cid = "alt_".$playlist_line['epg_shift']."_".$id;
						if ($i<1) {
							$channel =
								new DemoChannel(
									$cid,
									$caption,
									$logo,
									$media_url,
									$chnmbr,
									self::get_future_epg_days($id),
									self::get_future_epg_days($id),
									$is_protected,
									$buf_time,
									$tvg_name,
									$pl_name,
									intval($archive),
									$user_agent,
									$dune_zoom);
							$this->channels->put($channel);
						}
						if (!($this->groups->has($gid))) {
							$this->groups->put(
									new DefaultGroup(
										strval($gid),
										strval($gname),
										$icon_file));
						}
						$group = $this->groups->get($gid);
						$channel->add_group($group);
						$group->add_channel($channel);
						$i++;
					}
					continue;
				}
				$gid = crc32($group_title);
				$gid = str_replace('-', '',$gid);
				$cid = "alt_".$playlist_line['epg_shift']."_".$id;
				$gname = $group_title;
				$is_renemed = (array_key_exists($gname, $name_pl_defs)) ?  $name_pl_defs[$gname] : '';
				if ($is_renemed != '')
				$gname = $is_renemed;
				if (($prov_pl !== '0') && ($plist == $m3u_file))
				$gname = $plist_n;
				if (file_exists($altiptv_data_path . "/icons/$gname.png")){
				$icon_file = $altiptv_data_path."/icons/$gname.png";
				}else{
				$icon_file = $altiptv_data_path.'/icons/tv.png';}
			}
			if (file_exists($altiptv_data_path . "/icons/$gname.png"))
			$icon_file = $altiptv_data_path."/icons/$gname.png";
			
				$channel =
					new DemoChannel(
						$cid,
						$caption,
						$logo,
						$media_url,
						$chnmbr,
						self::get_future_epg_days($id),
						self::get_future_epg_days($id),
						$is_protected,
						$buf_time,
						$tvg_name,
						$pl_name,
						intval($archive),
						$user_agent,
						$dune_zoom);
			
			if (!($this->groups->has($gid))) {
				$this->groups->put(
						new DefaultGroup(
							strval($gid),
							strval($gname),
							$icon_file));
			}
			
			$this->channels->put($channel);
			$group = $this->groups->get($gid);
			$channel->add_group($group);
			$group->add_channel($channel);
			$i++;
		  }
		$gids++;        
		}
	}
	else if ($group_tv == 5){
	$this->global_channels=null;
	$i = 0;
	foreach ($m3u_files as $m3u_file) 
		{
			$chnmbr =-1;
			$pname = basename($m3u_file);
			if (($prov_pl !== '0') && ($plist == $m3u_file))
				$pname = $plist_n;
			foreach (self::parse_playlist($m3u_file, &$plugin_cookies) as $playlist_line) {
				$archive = 0;
				$caption = $playlist_line['caption'];
				if ($caption == '')
					continue;
				if (file_exists($altiptv_data_path . '/data_file/hide_ch')){
					$hide_key = "all|$caption";
					$is_hide = (array_key_exists($hide_key, $hide_ch_defs)) ? 1 : 0;
					if ($is_hide == 1)
					continue;
				}
				$media_url = $playlist_line['url']; 
				$tvg_name = $playlist_line['tvg_name'];
				$user_agent = $playlist_line['user_agent'];
				$dune_zoom = $playlist_line['dune_zoom'];
				$captions = mb_strtolower($caption, 'UTF-8');
				$captions = str_replace(array(" ", "-", ".", "\r", "\n", "\"", " "), '', $captions);
				$id_key = md5($captions);
				$group_id = $playlist_line['group'];
				if ($group_id > 0) {
					list ($gid, $gname) = DemoConfig::get_group_name($group_id,$plugin_cookies);
					$this->global_channels[$id_key]['group'] = $gid;
				}
				if ($playlist_line['id'] == '')
					$id = array_key_exists($id_key,$channels_id_parsed) ? $channels_id_parsed[$id_key] : 40000 + $i;
				else
					$id = $playlist_line['id'];
				if (($sort_channels == 3)||($sort_channels == 4)){
					$chnmbr = array_search($id, $chnmbr_list);
					if ($chnmbr =='')
						$chnmbr =-1;
				}
				$arc_itm = HD::get_items($arc, &$plugin_cookies);
				if (isset($arc_itm[$id]))
					$archive=1;
				$gid_key = $id;
				if ($group_id == 0) {
					$group_id = (array_key_exists($id, $group_defs)) ? $group_defs[$id] : 0;
					list ($gid, $gname) = DemoConfig::get_group_name($group_id,$plugin_cookies);
				}
				if (file_exists($altiptv_data_path . "/icons/$gid.png"))
					$icon_file = $altiptv_data_path."/icons/$gid.png";
				else if (file_exists($altiptv_data_path . "/icons/$group_id.png"))
					$icon_file = $altiptv_data_path."/icons/$group_id.png";
				else if (file_exists($altiptv_data_path . "/icons/$gname.png"))
					$icon_file = $altiptv_data_path ."/altiptv_data/icons/$gname.png";
				else
					$icon_file = $altiptv_data_path.'/icons/tv.png';
				$pl_id = str_replace('-', '', crc32($pname));
				$n_id = str_replace('-', '', crc32($caption));
				$id = $id . '%' . $pl_id . $n_id;
				if ($double_tv == 2)
					$id = $id . '%' . $pl_id . str_replace('-', '', crc32($media_url)). $n_id;
				if ($pin == '1111')
					$is_protected = 0;
				else{
					$is_protected = $playlist_line['parental'];
					if ($is_protected == 0)
						$is_protected = (array_key_exists($id_key, $parental_defs)) ? 1 : 0;
				}
				$cid = "alt_".$playlist_line['epg_shift']."_".$id;
				$logo = ($playlist_line['logo'] == '') ? self::get_icon_path($id,&$plugin_cookies) : $playlist_line['logo'];
				$is_renemed = (array_key_exists($gname, $name_pl_defs)) ?  $name_pl_defs[$gname] : '';
				if ($is_renemed != '')
					$gname = $is_renemed;
				// $vsetv_list = HD::get_items('vsetv_list', &$plugin_cookies);
				// $key = array_search($gid_key, $vsetv_list);
				// if ($key == true)
					// $caption = $key;
				$new=false;
				if (!isset($this->global_channels["alt_".$playlist_line['epg_shift']."_".$gid_key]))
						$new=true;
				$this->global_channels["alt_".$playlist_line['epg_shift']."_".$gid_key]['urls'][$cid] = $media_url;
				$this->global_channels["alt_".$playlist_line['epg_shift']."_".$gid_key]['pname'][$cid] = $pname;
				$this->global_channels["alt_".$playlist_line['epg_shift']."_".$gid_key]['user_agent'][$cid] = $user_agent;
				$this->global_channels["alt_".$playlist_line['epg_shift']."_".$gid_key]['dune_zoom'][$cid] = $dune_zoom;
				if ($new==true){
					$channel =
							new DemoChannel(
								"alt_".$playlist_line['epg_shift']."_".$gid_key,
								$caption,
								$logo,
								"alt_".$playlist_line['epg_shift']."_".$gid_key,
								$chnmbr,
								self::get_future_epg_days($gid_key),
								self::get_future_epg_days($gid_key),
								$is_protected,
								$buf_time,
								$tvg_name,
								'all',
								intval($archive),
								false,
								false);

					if (!($this->groups->has($gid))) {
						$this->groups->put(
								new DefaultGroup(
									strval($gid),
									strval($gname),
									$icon_file));
					}	
					$this->channels->put($channel);
					$group = $this->groups->get($gid);
					$channel->add_group($group);
					$group->add_channel($channel);
					if ((preg_match("/HD/",$caption)) && ($show_hd === 'yes') && ($gid != 13)) {
						$group_id = 13;
						list ($gid, $gname) = DemoConfig::get_group_name($group_id,$plugin_cookies);
						if (file_exists($altiptv_data_path . "/icons/$group_id.png"))
							$icon_file = $altiptv_data_path."/icons/$group_id.png";
						else
							$icon_file = DuneSystem::$properties['data_dir_path']."/altiptv_data/icons/$group_id.png";
						$is_renemed = (array_key_exists($gname, $name_pl_defs)) ?  $name_pl_defs[$gname] : '';
						if ($is_renemed != '')
							$gname = $is_renemed;
						if (!($this->groups->has($gid))) {
							$this->groups->put(
									new DefaultGroup(
										strval($gid),
										strval($gname),
										$icon_file));
						}
						$group = $this->groups->get($gid);
						$channel->add_group($group);
						$group->add_channel($channel);
					}
				}
				++$i;
			}
		}
		reset($this->global_channels);	
	}
		
		if ($sort_channels > 0) {
			if ($sort_channels == 1)
				$this->channels->usort(DemoConfig::CHANNEL_SORT_FUNC_CB);
			else if ($sort_channels < 5) {
				$sort_cb = ($sort_channels == 4) ? DemoConfig::CHANNEL_SORT_FUNC_BA :
						(($sort_channels == 3) ? DemoConfig::CHANNEL_SORT_FUNC_BC :
						 DemoConfig::CHANNEL_SORT_FUNC_CB);
				$this->channels->usort($sort_cb);
				foreach ($this->groups as $g) {
					if ((isset($g->id))&&($g->id == '__favorites')) continue;
					$g->sort_channels($sort_cb);
				}
			}
		}  // sort > 0
    }

    ///////////////////////////////////////////////////////////////////////////
	public function get_tv_playback_url($channel_id, $archive_ts, $protect_code, &$plugin_cookies)
    {
		$cid = $channel_id;
		$group_tv = isset($plugin_cookies->group_tv) ?
        $plugin_cookies->group_tv : '1';
		$arc = isset($plugin_cookies->arc) ?
		$plugin_cookies->arc : 'arc_itm';
		$moyo_user_agent = HD::get_item('moyo_user_agent', &$plugin_cookies);
		$pl_name = $this->get_channel($channel_id)->pl_name();
		$this->currentUrlcid = null;
		if (intval($archive_ts) > 0){
			list($garb, $epg_shift_pl, $chid) = preg_split('/_/', $channel_id);
			if(preg_match("/%/", $chid)){
			$tmp = explode('%', $chid);
			$chid = $tmp[0];}
			$arc_itm = HD::get_items($arc, &$plugin_cookies);
			$npvr_id = $arc_itm[$chid];
			$now_ts = intval(time());
			$u_start = intval($archive_ts);
			$u_tcend = (($now_ts - $u_start) > 35999)? $u_start + 35999 : $now_ts;
			$url = $npvr_id . "?utcstart=$u_start&utcend=$u_tcend";
			if (preg_match("|m3u9|",$url))
				$url .='&user_agent='.HD::get_item('moyo_user_agent', &$plugin_cookies);
			if (preg_match("|zamena|",$url)){
				$arc_server = trim(HD::get_item('arc_server', &$plugin_cookies));
				if ($arc_server != '')
					$url = str_replace('zamena', $arc_server, $url);
				else
					$url = $this->get_channel($channel_id)->get_streaming_url();
			}
			$dune_zoom = $this->get_channel($channel_id)->dune_zoom();
			$user_agent = $this->get_channel($channel_id)->user_agent();
		}
		else if ($group_tv==5)
		{
			$ch_select = HD::get_items('ch_select', &$plugin_cookies);
			$ch_select_all = HD::get_item('ch_select_all', &$plugin_cookies);
			$key_all = false;
			if ($ch_select_all!='')
			$key_all = array_search($ch_select_all, $this->global_channels[$channel_id]['pname']);
			if ((isset($ch_select[$channel_id]))&&(isset($this->global_channels[$channel_id]['urls'][$ch_select[$channel_id]]))){
				$cid = $ch_select[$channel_id];
				$url = $this->global_channels[$channel_id]['urls'][$cid];
				$pl_name = $this->global_channels[$channel_id]['pname'][$cid];
			} else if (($key_all==true)&&(isset($this->global_channels[$channel_id]['urls'][$key_all]))){
				$cid = $key_all;
				$url = $this->global_channels[$channel_id]['urls'][$cid];
				$pl_name = $this->global_channels[$channel_id]['pname'][$cid];
			}else{
				if ($this->currentId != $channel_id)
					$this->currentUrl = 0;
				foreach ($this->global_channels[$channel_id]['urls'] as $k => $v){
					$url_arr[] = $v;
					$cid_arr[] = $k;
				}
				$c = count($url_arr);
				$url = $url_arr[$this->currentUrl];
				$cid = $cid_arr[$this->currentUrl];
				$this->currentUrlcid = $cid;
				$pl_name = $this->global_channels[$channel_id]['pname'][$cid];
				if ($this->currentUrl<$c-1)
					$this->currentUrl = $this->currentUrl +1;
				else
					$this->currentUrl = 0;
				$this->currentId = $channel_id;
			}
			$dune_zoom = $this->global_channels[$channel_id]['dune_zoom'][$cid];
			$user_agent = $this->global_channels[$channel_id]['user_agent'][$cid];
			
		}
		else{
			$url = $this->get_channel($channel_id)->get_streaming_url();
			$dune_zoom = $this->get_channel($channel_id)->dune_zoom();
			$user_agent = $this->get_channel($channel_id)->user_agent();
		}
		
		
		$channel_user_agents = HD::get_items('channel_user_agent', &$plugin_cookies);
		$user_agents = HD::get_items('user_agent', &$plugin_cookies);
		if ((isset($channel_user_agents[$cid]))&&(intval($archive_ts) == -1))
				$url = 'http://ts://127.0.0.1:81/cgi-bin/hls.sh?'.str_ireplace(array('.m3u8','&offset=0'), array('.m3u9',''),$url).'&user_agent='. $channel_user_agents[$cid];
		else if ((isset($user_agents[$pl_name]))&&(intval($archive_ts) == -1))
				$url = 'http://ts://127.0.0.1:81/cgi-bin/hls.sh?'.str_ireplace(array('.m3u8','&offset=0'), array('.m3u9',''),$url).'&user_agent='. $user_agents[$pl_name];
		else if (($user_agent==true)&&(intval($archive_ts) == -1))
				$url = 'http://ts://127.0.0.1:81/cgi-bin/hls.sh?'.str_ireplace(array('.m3u8','&offset=0'), array('.m3u9',''),$url).'&user_agent='. $user_agent;
		if (preg_match('|(.*?)\$OPT:http-user-agent=(.*)|', $url, $matches))
				$url = 'http://ts://127.0.0.1:81/cgi-bin/hls.sh?'.str_ireplace(array('.m3u8','&offset=0'), array('.m3u9','') , $matches[1]).'&user_agent='. $matches[2];
		list($garb, $epg_shift_pl, $chid) = preg_split('/_/', $channel_id);
			if(preg_match("/%/", $chid)){
			$tmp = explode('%', $chid);
			$chid = $tmp[0];}
		$zoom = HD::get_items('zoom', &$plugin_cookies);
		if (isset($zoom[$chid]))
			$url = $url . '|||dune_params|||zoom:'.$zoom[$chid];
		else if ($dune_zoom==true)//????
			$url = $url . '|||dune_params|||zoom:'.$dune_zoom;
			
		$pin = isset($plugin_cookies->pin) ? $plugin_cookies->pin : '0000';
        $nado = $this->get_channel($channel_id)->is_protected();
        if ($nado)
			if ($protect_code !== $pin)  $url=false;
        return $url;
    }
    ///////////////////////////////////////////////////////////////////////////

    public function get_day_epg_iterator($channel_id, $day_start_ts, &$plugin_cookies)
    {
	$epg_type = false;
	$jtv_type = isset($plugin_cookies->epg_type) ?
        $plugin_cookies->epg_type : '1';
	$channel_jedy = $channel_id;
	list($garb, $epg_shift_pl, $channel_id) = preg_split('/_/', $channel_id);
	if(preg_match("/%/", $channel_id)){
	$tmp = explode('%', $channel_id);
	$channel_id = $tmp[0];}
	
	if ($jtv_type == 2)	{
		global $url_tvg;
		$tvg_name = $this->get_channel($channel_jedy)->tvg_name();
		if ((!$tvg_name == '') && (!$url_tvg == ''))
		$epg_type = 2;
	}

	if (($channel_id > 0) && ($channel_id < 3000)&&(!$epg_type == 2))
		$epg_type = 1;
	else if (($channel_id > 2999) && ($channel_id < 5000)&&(!$epg_type == 2))
		$epg_type = 3;
	else if (($channel_id > 4999) && ($channel_id < 6000)&&(!$epg_type == 2))
		$epg_type = 4;
	else if (($channel_id > 5999) && ($channel_id < 7000)&&(!$epg_type == 2))
		$epg_type = 5;
	else if (($channel_id > 6999) && ($channel_id < 8000)&&(!$epg_type == 2))
		$epg_type = 6;
	else if (($channel_id > 7999) && ($channel_id < 9000)&&(!$epg_type == 2))
		$epg_type = 7;
	else if (($channel_id > 8999) && ($channel_id < 10000)&&(!$epg_type == 2))
		$epg_type = 8;
	else if (($channel_id > 9999) && ($channel_id < 11000)&&(!$epg_type == 2))
		$epg_type = 9;
	else if (($channel_id > 10999) && ($channel_id < 12000)&&(!$epg_type == 2))
		$epg_type = 10;
	else if (($channel_id > 19999)&&(!$epg_type == 2))
	return array();
	if ($epg_type == 10)
	{
	$teleman_id = HD::get_epg_ids($plugin_cookies,'vsetv_teleman', $channel_id);
		if ($teleman_id == false)
			return array();
    $epg_shiftteleman = isset($plugin_cookies->epg_shiftteleman) ?
		$plugin_cookies->epg_shiftteleman : '0';
	$epg_shift_pl = ($epg_shift_pl - 24) * 3600;
	$epg_shift = $epg_shiftteleman + $epg_shift_pl;
    $q_date = date("Y-m-d", $day_start_ts);
	$epg_date = date("Ymd", $day_start_ts);
    $epg = array();

    if (file_exists(DuneSystem::$properties['tmp_dir_path']."/channel_".$channel_id."_".$day_start_ts)) {
	$epg = unserialize(file_get_contents(DuneSystem::$properties['tmp_dir_path']."/channel_".$channel_id."_".$day_start_ts));
    }
    else {
	try {
            $doc = HD::http_get_document("http://www.teleman.pl/program-tv/stacje/".$teleman_id.'?date='.$q_date.'&hour=-1');
	}
	catch (Exception $e) {
	    hd_print("Can't fetch EPG ID:$ntvplus_id DATE:$epg_date");
	    return array();
	}
	$e_time = strtotime("$epg_date, 0500 EEST");
	preg_match_all('|<em>(.*?)</em><div class="detail">(.*?)</a>(.*?)</li>|', $doc, $keywords);
	foreach ($keywords[1] as $key => $time){
	$time = strip_tags($time);
	$u_time = strtotime("$epg_date $time EEST");
	$last_time = ($u_time < $e_time) ? $u_time + 86400  : $u_time ;
	$epg[$last_time]["name"] = strip_tags($keywords[2][$key]);
	$epg[$last_time]["desc"] = strip_tags($keywords[3][$key]);
	}
	file_put_contents(DuneSystem::$properties['tmp_dir_path']."/channel_".$channel_id."_".$day_start_ts, serialize($epg));}
	$epg_result = array();
	ksort($epg, SORT_NUMERIC);
	foreach ($epg as $time => $value) {
	    $epg_result[] =
                new DefaultEpgItem(
                    strval($value["name"]),
                    strval($value["desc"]),
                    intval($time + $epg_shift),
                    intval(-1));
	}
	
	return
            new EpgIterator(
                $epg_result,
                $day_start_ts,
                $day_start_ts + 100400);
    }
	if ($epg_type == 9)
	{
	$tvprogrlt_id = HD::get_epg_ids($plugin_cookies,'vsetv_tvprogrlt', $channel_id);
		if ($tvprogrlt_id == false)
			return array();
    $epg_shifttvprogrlt = isset($plugin_cookies->epg_shifttvprogrlt) ?
		$plugin_cookies->epg_shifttvprogrlt : '0';
	$epg_shift_pl = ($epg_shift_pl - 24) * 3600;
	$epg_shift = $epg_shifttvprogrlt + $epg_shift_pl;
    $q_date = date("Y_m_d", $day_start_ts);
	$epg_date = date("Ymd", $day_start_ts);
    $epg = array();

    if (file_exists(DuneSystem::$properties['tmp_dir_path']."/channel_".$channel_id."_".$day_start_ts)) {
	$epg = unserialize(file_get_contents(DuneSystem::$properties['tmp_dir_path']."/channel_".$channel_id."_".$day_start_ts));
    }
    else {
	try {
            $doc = HD::http_get_document("http://www.tvprograma.lt/tv-programa/televizija/".$tvprogrlt_id.'/'.$q_date);
	}
	catch (Exception $e) {
	    hd_print("Can't fetch EPG ID:$ntvplus_id DATE:$epg_date");
	    return array();
	}
	$e_time = strtotime("$epg_date, 0500 EEST");
	$tmp = explode('<div class="span channel">', $doc);
	$c1 = preg_replace('/\s{2,}/', ' ', $tmp[1]);
	$c1 = preg_replace('|<div class="description show-full-description">.*?</div>|', '', $c1);
	$c1 = str_replace('<div class="full-description" style="display:none;">', '<div class="description">', $c1);
	preg_match_all('|<span>(.*?)</span>(.*?)</div>|',  $c1, $keywords);
	foreach ($keywords[1] as $key => $time){
	$u_time = strtotime("$epg_date $time EEST");
	$last_time = ($u_time < $e_time) ? $u_time + 86400  : $u_time ;
	$epg[$last_time]["name"] = strip_tags($keywords[2][$key]);
	$epg[$last_time]["desc"] = '';
	if (preg_match('/(.*)<div class="description">(.*)/', $keywords[2][$key], $matches)) {
		$epg[$last_time]["name"] = strip_tags($matches[1]);
	$epg[$last_time]["desc"] = strip_tags($matches[2]);
	}
	}
	file_put_contents(DuneSystem::$properties['tmp_dir_path']."/channel_".$channel_id."_".$day_start_ts, serialize($epg));}
	$epg_result = array();
	ksort($epg, SORT_NUMERIC);
	foreach ($epg as $time => $value) {
	    $epg_result[] =
                new DefaultEpgItem(
                    strval($value["name"]),
                    strval($value["desc"]),
                    intval($time + $epg_shift),
                    intval(-1));
	}
	
	return
            new EpgIterator(
                $epg_result,
                $day_start_ts,
                $day_start_ts + 100400);
    }
	if ($epg_type == 8)
	{
	$teleguide_id = HD::get_epg_ids($plugin_cookies,'vsetv_teleguide', $channel_id);
		if ($teleguide_id == false)
			return array();
    $epg_shiftteleguide = isset($plugin_cookies->epg_shiftteleguide) ?
		$plugin_cookies->epg_shiftteleguide : '0';
	$epg_shift_pl = ($epg_shift_pl - 24) * 3600;
	$epg_shift = $epg_shiftteleguide + $epg_shift_pl;
    $epg_date = date("Ymd", $day_start_ts);
    $epg = array();

    if (file_exists(DuneSystem::$properties['tmp_dir_path']."/channel_".$channel_id."_".$day_start_ts)) {
	$epg = unserialize(file_get_contents(DuneSystem::$properties['tmp_dir_path']."/channel_".$channel_id."_".$day_start_ts));
    }
    else {
	try {
            $doc = HD::http_get_document("http://www.teleguide.info/kanal".$teleguide_id."_".$epg_date.".html");
	}
	catch (Exception $e) {
	    hd_print("Can't fetch EPG ID:$ntvplus_id DATE:$epg_date");
	    return array();
	}
	$e_time = strtotime("$epg_date, 0500 EEST");
	preg_match_all('|<div id="programm_text">(.*?)</div>|',  $doc, $keywords);
	foreach ($keywords[1] as $key => $qid){
	$qq = strip_tags($qid);
	preg_match_all('|(\d\d:\d\d)&nbsp;(.*?)&nbsp;(.*)|', $qq, $keyw);
	$time = $keyw[1][0];
	$u_time = strtotime("$epg_date $time EEST");
	$last_time = ($u_time < $e_time) ? $u_time + 86400  : $u_time ;
	$epg[$last_time]["name"] = str_replace("&nbsp;", " ",$keyw[2][0]);
	$epg[$last_time]["desc"] = str_replace("&nbsp;", " ", $keyw[3][0]);
	}
	file_put_contents(DuneSystem::$properties['tmp_dir_path']."/channel_".$channel_id."_".$day_start_ts, serialize($epg));}
	$epg_result = array();
	ksort($epg, SORT_NUMERIC);
	foreach ($epg as $time => $value) {
	    $epg_result[] =
                new DefaultEpgItem(
                    strval($value["name"]),
                    strval($value["desc"]),
                    intval($time + $epg_shift),
                    intval(-1));
	}
	
	return
            new EpgIterator(
                $epg_result,
                $day_start_ts,
                $day_start_ts + 100400);
    }
	if ($epg_type == 7)
	{
	$tvlistingsuk_id = HD::get_epg_ids($plugin_cookies,'vsetv_tvlistingsuk', $channel_id);
	if ($tvlistingsuk_id == false)
			return array();
	$epg_shifttvlistingsuk = isset($plugin_cookies->epg_shifttvlistingsuk) ?
	$plugin_cookies->epg_shifttvlistingsuk : '0';
	$epg_shift_pl = ($epg_shift_pl - 24) * 3600;
	$epg_shift = $epg_shifttvlistingsuk + $epg_shift_pl;
	    $epg_date = date("Y-m-d", $day_start_ts);
    $epg = array();

    if (file_exists(DuneSystem::$properties['tmp_dir_path']."/channel_".$channel_id."_".$day_start_ts)) {
	$epg = unserialize(file_get_contents(DuneSystem::$properties['tmp_dir_path']."/channel_".$channel_id."_".$day_start_ts));
    }
	else {
	try {
            $doc = HD::http_get_document("http://www.tvlistings.eu.pn/index.php/tvlisting/channel/1/0/$tvlistingsuk_id");
	}
	catch (Exception $e) {
	    hd_print("Can't fetch EPG ID:$akado_id DATE:$epg_date");
	    return array();
	}
	
	$patterns = array(
		"/\n/", "/.*<\/h3><br\/>/", "/<div id=\"gadsvch\">.*/", "/<article class=\"back\">/", "/<\/span><\/strong><br\/>/", "/<\/p><\/article>/");
		$replace = array("", "", "", "\n", "|", "");
	$doc = strip_tags(preg_replace($patterns, $replace, $doc));
	
    preg_match_all('@(\d\d\s\d\d)-(\d\d\s\d\d)(.*)\|(.*)@i', $doc, $matches);
	$last_time = 0;
	
        foreach ($matches[1] as $key => $time) {
	    $name = $matches[3][$key];
	    $desc = $matches[4][$key];
		$time = str_replace(' ',':',$time);
	    $u_time = strtotime("$epg_date $time EEST");
	    $last_time = ($u_time < $last_time) ? $u_time + 86400  : $u_time ;
	    $epg[$last_time]["name"] = $name;
	    $epg[$last_time]["desc"] = $desc;
	    }
	file_put_contents(DuneSystem::$properties['tmp_dir_path']."/channel_".$channel_id."_".$day_start_ts, serialize($epg));
	}
	$epg_result = array();

	ksort($epg, SORT_NUMERIC);

	foreach ($epg as $time => $value) {
	    $epg_result[] =
                new DefaultEpgItem(
                    strval($value["name"]),
                    strval($value["desc"]),
                    intval($time + $epg_shift),
                    intval(-1));
	}
	
	return
            new EpgIterator(
                $epg_result,
                $day_start_ts,
                $day_start_ts + 100400);
	}
	if ($epg_type == 6)
	{
	$tvspielfilm_id = HD::get_epg_ids($plugin_cookies,'tvspielfilm', $channel_id);
	if ($tvspielfilm_id == false)
			return array();
	$epg_shiftvspielfilm = isset($plugin_cookies->epg_shiftvspielfilm) ?
		$plugin_cookies->epg_shiftvspielfilm : '0';
	$epg_shift_pl = ($epg_shift_pl - 24) * 3600;
	$epg_shift = $epg_shiftvspielfilm + $epg_shift_pl;
    $epg_date = date("Y-m-d", $day_start_ts);
    $epg = array();

    if (file_exists(DuneSystem::$properties['tmp_dir_path']."/channel_".$channel_id."_".$day_start_ts)) {
	$epg = unserialize(file_get_contents(DuneSystem::$properties['tmp_dir_path']."/channel_".$channel_id."_".$day_start_ts));
    }
    else {
	try {
		  $opt[CURLOPT_USERAGENT] = 'Apache-HttpClient/UNAVAILABLE (java 1.4)';
          	  $doc = HD::http_get_document('http://tvsapi.cellmp.de/getProgram_1_3.php?date='.$epg_date.'&channel=["'.$tvspielfilm_id.'"]&time=05:00', $opt);
		}
		catch (Exception $e) {
		    hd_print("Can't fetch EPG ID:$id DATE:$epg_date (tvspielfilm)");
	 	   return array();
		}
		
		if (substr($doc, 0,3) == pack("CCC",0xef,0xbb,0xbf))
		    $doc=substr($doc, 3);

		if ($matches = json_decode($doc, true)) 
			foreach ($matches as $item) {
				$time = strtotime($item['anfangsdatum']." CEST");
				$epg[$time]['name'] = 	$item['titel'];
				$epg[$time]['desc'] = "{$item['genre']} {$item['jahr']} {$item['land']}";
			}
	file_put_contents(DuneSystem::$properties['tmp_dir_path']."/channel_".$channel_id."_".$day_start_ts, serialize($epg));
	}
	$epg_result = array();

	ksort($epg, SORT_NUMERIC);

	foreach ($epg as $time => $value) {
	    $epg_result[] =
                new DefaultEpgItem(
                    strval($value["name"]),
                    strval($value["desc"]),
                    intval($time + $epg_shift),
                    intval(-1));
	}
	
	return
            new EpgIterator(
                $epg_result,
                $day_start_ts,
                $day_start_ts + 100400);
    
	}
	if ($epg_type == 2)
	{
	$channel_id = $tvg_name;
	if (time() - $this->schedule_age > 3600) {
            $this->parsed_schedule = null;
        }
        if (is_null($this->parsed_schedule)) {
            $ts1 = microtime(true);

            hd_print("first time load/reload schedule");

            $this->parsed_schedule = array();
			
            file_put_contents("/tmp/schedule.zip", file_get_contents($url_tvg));
            $zip = zip_open("/tmp/schedule.zip");

            while(($zip_entry = zip_read($zip)) !== false) {
                $entry_name = zip_entry_name($zip_entry);
				$entry_name = iconv('CP866', 'UTF-8', $entry_name);
                $entry_ext = substr($entry_name, strrpos($entry_name, '.') + 1);
                $entry_partname = substr($entry_name, 0, strrpos($entry_name, '.'));
    
                if ($entry_ext == "ndx") {
                    $this->ndx_map[$entry_partname] = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
                } else if ($entry_ext == "pdt") {
                    $this->pdt_map[$entry_partname] = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
                } else {
                    hd_print("unexpected ext ".$entry_ext);
                }

            }

            $this->schedule_age = time();

            $ts2 = microtime(true);
            hd_print("schedule downloaded for ".($ts2-$ts1));
        }

		
        $num = $channel_id;
		$epg_shiftjtv = isset($plugin_cookies->epg_shiftjtv) ? $plugin_cookies->epg_shiftjtv : '0';
		$epg_shift_pl = ($epg_shift_pl - 24) * 3600;
		$epg_shift = $epg_shiftjtv + $epg_shift_pl;
		
        $epg = array();

        if (array_key_exists($num, $this->ndx_map)) {
            if (!array_key_exists($num, $this->parsed_schedule)) {
                $ts3 = microtime(true);

                $ndx_data = $this->ndx_map[$num];
                $parsed_ndx = unpack("v", substr($ndx_data, 0, 2));
                $prog_count = $parsed_ndx[1];
                $pdt_data = $this->pdt_map[$num];
                $ch_schedule = array();

                for ($i = 0; $i < $prog_count; $i++) {
                    $parsed_ndx = unpack("x2/V2time/voffset/", substr($ndx_data, 2 + $i * 12, 12)); 
                    $time1 = $parsed_ndx["time1"];
                    $time2 = $parsed_ndx["time2"];

                    $time_unix = ($time2 - 27111902.832985)  * 429.4967296 + ($time1 / 10000000.0) + ($time1 < 0 ? 429.4967296 : 0);
                    $time_unix = round($time_unix / 10) * 10;
                    $offset = $parsed_ndx["offset"];
                    $title_length_arr = unpack("v", substr($pdt_data, $offset, 2));
                    $title = substr($pdt_data, $offset + 2, $title_length_arr[1]);

                    $title_utf = iconv("CP1251", "UTF-8", $title);

                    $ch_schedule[] = array(intval($time_unix + $epg_shift), $title_utf);

                }
                for ($i = 0; $i < count($ch_schedule); $i++) {
                    $start_time = $ch_schedule[$i][0];

                    if ($i < count($ch_schedule) - 1) {
                        $stop_time = $ch_schedule[$i + 1][0];
                    } else {
                        $stop_time = $start_time + 3600;
                    }

                    $epg[] = new DefaultEpgItem($ch_schedule[$i][1], "", $start_time, $stop_time, intval(-1));
                }

                $this->parsed_schedule[$num] = $epg;

                $ts4 = microtime(true);
            }

            $epg = $this->parsed_schedule[$num];
        }

        $epg_result = array();

        foreach($epg as $epg_item) {
            if ($epg_item->get_start_time() >= $day_start_ts && $epg_item->get_start_time() <= $day_start_ts + 86400) {
                $epg_result[] = $epg_item;
            }
        }

        return 
			new EpgIterator(
				$epg_result, 
				$day_start_ts, 
				$day_start_ts + 100400);
	}
	if ($epg_type == 3)
	{
	$mail_id = HD::get_epg_ids($plugin_cookies,'vsetv_mail', $channel_id);
	if ($mail_id == false)
			return array();
    $epg_shiftmail = isset($plugin_cookies->epg_shiftmail) ?
		$plugin_cookies->epg_shiftmail : '0';
	$epg_shift_pl = ($epg_shift_pl - 24) * 3600;
	$epg_shift = $epg_shiftmail + $epg_shift_pl;
    $epg_date = date("Y-m-d", $day_start_ts);
    $epg = array();

    if (file_exists(DuneSystem::$properties['tmp_dir_path']."/channel_".$channel_id."_".$day_start_ts)) {
	$epg = unserialize(file_get_contents(DuneSystem::$properties['tmp_dir_path']."/channel_".$channel_id."_".$day_start_ts));
    }
    else {
	try {
            $doc = HD::http_get_document("http://tv.mail.ru/ajax/channel/?channel_id=$mail_id&period=all&date=$epg_date" );
	}
	catch (Exception $e) {
	    hd_print("Can't fetch EPG ID:$mail_id DATE:$epg_date");
	    return array();
	}
	
	$last_time = 0;
    $jsd = json_decode($doc, true);
		foreach (array_merge($jsd['schedule'][0]['event']['past'], $jsd['schedule'][0]['event']['current']) as $value) 
		{
		$last_time =$jsd['form']['date']['value'] ." ". $value["start"];
		list($year, $month, $day, $hour, $minute) = preg_split('/[- :]/', $last_time);
		$timestamp = mktime($hour, $minute, 0, $month, $day, $year);
		$name = $value['name'];
		if ($value['episode_title'] == true)
		$name = $name .' - '. $value['episode_title'];
		$desc = '';
		if (array_key_exists( 'episode_num', $value) == true)
		$desc = $desc .'Эпизод: '. $value['episode_num'];
		$epg[$timestamp]["name"] = $name;
		$epg[$timestamp]["desc"] = $desc;
		}

	file_put_contents(DuneSystem::$properties['tmp_dir_path']."/channel_".$channel_id."_".$day_start_ts, serialize($epg));
	}
	$epg_result = array();

	ksort($epg, SORT_NUMERIC);

	foreach ($epg as $time => $value) {
	    $epg_result[] =
                new DefaultEpgItem(
                    strval($value["name"]),
                    strval($value["desc"]),
                    intval($time + $epg_shift),
                    intval(-1));
	}
	
	return
            new EpgIterator(
                $epg_result,
                $day_start_ts,
                $day_start_ts + 100400);
    }
	if ($epg_type == 4)
	{
	$akado_id = HD::get_epg_ids($plugin_cookies,'vsetv_akado', $channel_id);
	if ($akado_id == false)
			return array();
    $epg_shiftakado = isset($plugin_cookies->epg_shiftakado) ?
		$plugin_cookies->epg_shiftakado : '0';
	$epg_shift_pl = ($epg_shift_pl - 24) * 3600;
	$epg_shift = $epg_shiftakado + $epg_shift_pl;
    $epg_date = date("Y-m-d", $day_start_ts);
    $epg = array();

    if (file_exists(DuneSystem::$properties['tmp_dir_path']."/channel_".$channel_id."_".$day_start_ts)) {
	$epg = unserialize(file_get_contents(DuneSystem::$properties['tmp_dir_path']."/channel_".$channel_id."_".$day_start_ts));
    }
    else {
	try {
            $doc = HD::http_get_document("http://tv.akado.ru/channels/$akado_id.html?date=$epg_date");
	}
	catch (Exception $e) {
	    hd_print("Can't fetch EPG ID:$akado_id DATE:$epg_date");
	    return array();
	}
	
	$patterns = array(
		"/\n/", "/.*<table class=\"tv-channel-full\">/", "/<\/table><\/div>.*/", "/<tr.*?tv-time.>/", "/<\/[ap].*?p>/", "/<\/span>/");
		$replace = array("", "", "", "\n", "|", "");
	$doc = strip_tags(preg_replace($patterns, $replace, $doc));
    preg_match_all("/([0-2][0-9]:[0-5][0-9])([^\n]+)\n/", $doc, $matches);

	$last_time = 0;
	
        foreach ($matches[1] as $key => $time) {
	    $str = preg_split("/\|/", $matches[2][$key], 2);
	    $name = $str[0];
	    $desc = array_key_exists(1, $str) ? $str[1] : "";
	    $u_time = strtotime("$epg_date $time EEST");
	    $last_time = ($u_time < $last_time) ? $u_time + 86400  : $u_time ;
	    $epg[$last_time]["name"] = $name;
	    $epg[$last_time]["desc"] = $desc;
	    }
	file_put_contents(DuneSystem::$properties['tmp_dir_path']."/channel_".$channel_id."_".$day_start_ts, serialize($epg));
	}
	$epg_result = array();

	ksort($epg, SORT_NUMERIC);

	foreach ($epg as $time => $value) {
	    $epg_result[] =
                new DefaultEpgItem(
                    strval($value["name"]),
                    strval($value["desc"]),
                    intval($time + $epg_shift),
                    intval(-1));
	}
	
	return
            new EpgIterator(
                $epg_result,
                $day_start_ts,
                $day_start_ts + 100400);
    }
	if ($epg_type == 5)
	{
	$ntvplus_id = HD::get_epg_ids($plugin_cookies,'vsetv_ntv', $channel_id);
		if ($ntvplus_id == false)
			return array();
    $epg_shiftntvplus = isset($plugin_cookies->epg_shiftntvplus) ?
		$plugin_cookies->epg_shiftntvplus : '0';
	$epg_shift_pl = ($epg_shift_pl - 24) * 3600;
	$epg_shift = $epg_shiftntvplus + $epg_shift_pl;
    $epg_date = date("d.m.Y", $day_start_ts);
    $epg = array();

    if (file_exists(DuneSystem::$properties['tmp_dir_path']."/channel_".$channel_id."_".$day_start_ts)) {
	$epg = unserialize(file_get_contents(DuneSystem::$properties['tmp_dir_path']."/channel_".$channel_id."_".$day_start_ts));
    }
    else {
	try {
            $doc = HD::http_get_document("http://www.ntvplus.ru/tv/schedule.xl?&channel=$ntvplus_id&date=$epg_date&time=day");
	}
	catch (Exception $e) {
	    hd_print("Can't fetch EPG ID:$ntvplus_id DATE:$epg_date");
	    return array();
	}
	$pattern = '|<div class=\"time\">(.*?)<\/div>.*?<h5>(.*?)<\/h5>(.*?)<\/div>|';
	preg_match_all($pattern, $doc , $matches);
	$e_time = strtotime("$epg_date, 0500 EEST");
    for ($i=0; $i< count($matches[0]); $i++){
	$time = $matches[1][$i];
	$name = strip_tags($matches[2][$i]);
	$desc = $matches[3][$i];
	$u_time = strtotime("$epg_date $time EEST");
	$last_time = ($u_time < $e_time) ? $u_time + 86400  : $u_time ;
	$epg[$last_time]["name"] = $name;
	$epg[$last_time]["desc"] = $desc;}
	file_put_contents(DuneSystem::$properties['tmp_dir_path']."/channel_".$channel_id."_".$day_start_ts, serialize($epg));}
	$epg_result = array();
	ksort($epg, SORT_NUMERIC);
	foreach ($epg as $time => $value) {
	    $epg_result[] =
                new DefaultEpgItem(
                    strval($value["name"]),
                    strval($value["desc"]),
                    intval($time + $epg_shift),
                    intval(-1));
	}
	
	return
            new EpgIterator(
                $epg_result,
                $day_start_ts,
                $day_start_ts + 100400);
    }
	if ($epg_type == 1)
	{    
    $epg_shift = isset($plugin_cookies->epg_shift) ? $plugin_cookies->epg_shift : '0';
	$epg_shift_pl = ($epg_shift_pl - 24) * 3600;
	$epg_shift = $epg_shift + $epg_shift_pl;
    $epg_date = date("Y-m-d", $day_start_ts);
    $epg = array();

    if (file_exists(DuneSystem::$properties['tmp_dir_path']."/channel_".$channel_id."_".$day_start_ts)) {
	$epg = unserialize(file_get_contents(DuneSystem::$properties['tmp_dir_path']."/channel_".$channel_id."_".$day_start_ts));
    }
    else {
	try {
				$doc = iconv('WINDOWS-1251', 'UTF-8', HD::http_get_document("http://www.vsetv.com/schedule_channel_".$channel_id."_day_".$epg_date."_nsc_1.html"));
				static $arr = null;
				if (is_null($arr))
				{
					preg_match('|url: "(.*?)"|', $doc, $matches);
					$opts [CURLOPT_HTTPHEADER] = array
					(
						'Accept: */*', 
						'Accept-Encoding: gzip, deflate',
						'X-Requested-With: XMLHttpRequest',
					);
					$jsds = json_decode(HD::http_post_document("http://www.vsetv.com/".$matches[1],"",$opts));
					if (preg_match_all('|(.*?)=myData\.(.*?);|', $doc, $match)){
						foreach($match[2] as $k => $v)
						$jsd[trim ($match[1][$k])] = $jsds->$v;
					}
					preg_match_all('|\((.*)\)\.replaceWith\("(.*)"\);|', $doc, $matches);
					foreach ($matches[1] as $k => $v){
						$doc = str_replace(str_replace('.', "class=", $jsd[$v]).">", ">".$matches[2][$k], $doc);
						$arr[str_replace('.', "class=", $jsd[$v]).">"] = ">".$matches[2][$k];
					}
						
				}else{
					foreach ($arr as $k => $v)
						$doc = str_replace($k, $v, $doc);
				}
	}
	catch (Exception $e) {
	    hd_print("Can't fetch EPG ID:$channel_id DATE:$epg_date");
	    return array();
	}
	
	$patterns = array("/<div class=\"desc\">/", "/<div class=\"onair\">/", "/<div class=\"pasttime\">/", "/<div class=\"time\">/", "/<br><br>/", "/<br>/", "/&nbsp;/");
        $replace = array("|", "\n", "\n", "\n", ". ", ". ", "");

	$doc = strip_tags(preg_replace($patterns, $replace, $doc));
	
    preg_match_all("/([0-2][0-9]:[0-5][0-9])([^\n]+)\n/", $doc, $matches);

	$last_time = 0;
	
        foreach ($matches[1] as $key => $time) {
	    $str = preg_split("/\|/", $matches[2][$key], 2);
	    $name = $str[0];
	    $desc = array_key_exists(1, $str) ? $str[1] : "";
	    $u_time = strtotime("$epg_date $time EEST");
	    $last_time = ($u_time < $last_time) ? $u_time + 86400  : $u_time ;
	    $epg[$last_time]["name"] = $name;
	    $epg[$last_time]["desc"] = $desc;
	    }
	file_put_contents(DuneSystem::$properties['tmp_dir_path']."/channel_".$channel_id."_".$day_start_ts, serialize($epg));
	}
	$epg_result = array();

	ksort($epg, SORT_NUMERIC);

	foreach ($epg as $time => $value) {
	    $epg_result[] =
                new DefaultEpgItem(
                    strval($value["name"]),
                    strval($value["desc"]),
                    intval($time + $epg_shift),
                    intval(-1));
	}
	
	return
            new EpgIterator(
                $epg_result,
                $day_start_ts,
                $day_start_ts + 100400);
    }
	}
	public function handle_user_input(&$user_input, &$plugin_cookies)
    {
		if ($user_input->control_id == 'dialog')
        {
			$defs = array();
			$zoom = HD::get_items('zoom', &$plugin_cookies);
			$channel_id = $user_input->plugin_tv_channel_id;
			list($garb, $epg_shift_pl, $channel_id) = preg_split('/_/', $channel_id);
			if(preg_match("/%/", $channel_id)){
			$tmp = explode('%', $channel_id);
			$channel_id = $tmp[0];}
			$group_tv = isset($plugin_cookies->group_tv) ?
			$plugin_cookies->group_tv : '1';
			// if ($group_tv==5)
			// {
				// $ch_select = HD::get_items('ch_select', &$plugin_cookies);
				// if (isset($ch_select[$channel_id]))
					// $channel_id = $ch_select[$channel_id];
				// else
					// $channel_id = key($this->global_channels[$channel_id]['urls']);
			// }
			$dune_zoom = isset($zoom[$channel_id]) ?
            $zoom[$channel_id] : 'v';
			$dune_zoom_ops['v'] = 'Не выбрано';
			$dune_zoom_ops[0] = 'Обычный [0]';
			$dune_zoom_ops[1] = 'Увеличение [1]';
			$dune_zoom_ops[2] = 'Увеличение ширины [2]';
			$dune_zoom_ops[3] = 'Нелинейное растяжение [3]';
			$dune_zoom_ops[4] = 'Нелинейное растяжение на весь экран [4]';
			$dune_zoom_ops[5] = 'Увеличение высоты [5]';
			$dune_zoom_ops[6] = 'Обрезка краев [6]';
			$dune_zoom_ops[8] = 'Полный экран [8]';
			$dune_zoom_ops[9] = 'Растяжение на весь экран [9]';
			ControlFactory::add_combobox($defs, $this, null,
            'zoom_select', '',
            $dune_zoom, $dune_zoom_ops, 1000, $need_confirm = false, $need_apply = false
			);
			$add_params['cid']= $channel_id;
			ControlFactory::add_close_dialog_and_apply_button($defs,
				$this, $add_params,
				'zoom_apply', 'Сохранить', 300, $params=null);
			$attrs['dialog_params'] = array('frame_style' => DIALOG_FRAME_STYLE_GLASS);
			$attrs['actions'] = null;
			return ActionFactory::show_dialog('Настройки зума для канала', $defs,true,0,$attrs);
			return $defs;
        }
		if ($user_input->control_id == 'ch_select')
        {
			$defs = array();
			$channel_id = $user_input->plugin_tv_channel_id;
			$channel_urls = $this->global_channels[$channel_id]['urls'];
			$ch_select = HD::get_items('ch_select', &$plugin_cookies);
			$ch_select_all = HD::get_item('ch_select_all', &$plugin_cookies);
			$key_all = array_search($ch_select_all, $this->global_channels[$channel_id]['pname']);
			$name_pl_defs = HD::get_items('name_pl', &$plugin_cookies);
			$channell_select_ops['v'] = 'Удалить';
			foreach ($channel_urls as $k => $v){
				$gname = $this->global_channels[$channel_id]['pname'][$k];
				$is_renemed = (array_key_exists($gname, $name_pl_defs)) ?  $name_pl_defs[$gname] : '';
				if ($is_renemed != '')
					$gname = $is_renemed;
				$gname .= ': '. substr($this->global_channels[$channel_id]['urls'][$k],0,50);
				$channell_select_ops[$k] = $gname;
				
			}
			if ($this->currentUrlcid == true){
				$dune_ch_select = $this->currentUrlcid;
				ControlFactory::add_label($defs, '', 'Автовыбор стрима');
			}
			else if (isset($ch_select[$channel_id])){
				$dune_ch_select = $ch_select[$channel_id];
				ControlFactory::add_label($defs, '', 'Стрим указан для канала');
			}
			else if ($key_all==true){
				$dune_ch_select = $key_all;
				ControlFactory::add_label($defs, '', 'Стрим из плейлиста по умолчанию');
			}
			else
				$dune_ch_select = reset($channel_urls);
			ControlFactory::add_combobox($defs, $this, null,
            'channell_select', '',
            $dune_ch_select, $channell_select_ops, 750, $need_confirm = false, $need_apply = false
			);
			ControlFactory::add_close_dialog_and_apply_button($defs,
				$this, null,
				'ch_select_apply', 'Сохранить', 450, $params=null);
			ControlFactory::add_close_dialog_and_apply_button($defs,
				$this, null,
				'ch_select_apply_all', 'Сохранить для всех', 450, $params=null);
			ControlFactory::add_close_dialog_and_apply_button($defs,
				$this, null,
				'ch_select_clear_all', 'Очистить все', 450, $params=null);
			$attrs['dialog_params'] = array('frame_style' => DIALOG_FRAME_STYLE_GLASS);
			$attrs['actions'] = null;
			return ActionFactory::show_dialog('Выбор стрима канала', $defs,true,0,$attrs);
			return $defs;
        }
		if ($user_input->control_id == 'ch_select_apply')
        {
			$ch_select = HD::get_items('ch_select', &$plugin_cookies);
			if (($user_input->channell_select == 'v')&&(isset($ch_select[$user_input->plugin_tv_channel_id])))
				unset ($ch_select[$user_input->plugin_tv_channel_id]);
			else
				$ch_select[$user_input->plugin_tv_channel_id] = $user_input->channell_select;
			HD::save_items('ch_select', $ch_select,&$plugin_cookies);
			shell_exec ('echo EA15BF00 > /proc/ir/button');
			shell_exec ('echo E916BF00 > /proc/ir/button');
		}
		if ($user_input->control_id == 'ch_select_apply_all')
        {
			if ($user_input->channell_select == 'v')
				$ch_select_all = '';
			else
				$ch_select_all = $this->global_channels[$user_input->plugin_tv_channel_id]['pname'][$user_input->channell_select];
			HD::save_item('ch_select_all', $ch_select_all,&$plugin_cookies);
			shell_exec ('echo EA15BF00 > /proc/ir/button');
			shell_exec ('echo E916BF00 > /proc/ir/button');
		}
		if ($user_input->control_id == 'ch_select_clear_all')
        {
			$ch_select=array();
			$this->currentUrl = 0;
			HD::save_item('ch_select_all', '',&$plugin_cookies);
			HD::save_items('ch_select', $ch_select,&$plugin_cookies);
			shell_exec ('echo EA15BF00 > /proc/ir/button');
			shell_exec ('echo E916BF00 > /proc/ir/button');
		}
		if ($user_input->control_id == 'sleep')
        {
			$defs = array();
			
			$sleep_time = '';
			$cron_file = '/tmp/cron/crontabs/root';
			$doc = file_get_contents($cron_file);
			$tmp = explode('Выключение в:', $doc);
			if (count($tmp) > 1)
            $sleep_time = strstr($tmp[1], '+', true);
			if (preg_match('/\[(\d\d)\:(\d\d)\] \[(\d\d)\-(\d\d)\]/', $sleep_time, $matches)){
			$docs = file_get_contents('/config/settings.properties');
			if (preg_match('/time_zone =(.*)\s/', $docs, $match)) {
			$tmp = explode(':', $match[1]);
			$rec_shift =($tmp[0] * 3600 ) + ($tmp[1] * 60 );}
			$year =  date("y");
			$timestamp = mktime($matches[1], $matches[2], 0, $matches[4], $matches[3], $year);
			$unix_time = time() - $rec_shift;
			if($timestamp < $unix_time){
			if (preg_match('/Выключение/i', $doc))
				{
				$tmp = explode('#*#*#', $doc);
				if (count($tmp) > 1)
				$sleep_old = strstr($tmp[1], '#-#-#', true);
				$sleep_old = "\n#*#*#" . $sleep_old . "#-#-#";
				$save_cron = "";
				$data = str_replace($sleep_old, $save_cron, $doc);
				$cron_data = fopen($cron_file,"w");
				if (!$cron_data)
				hd_print("НЕ МОГУ ЗАПИСАТЬ cron");
				fwrite($cron_data, $data);
				@fclose($cron_data);
				chmod($cron_file, 0575);
				}
				shell_exec('crontab -e');
				$sleep_time = '';
			}
			}
			
			$sleep_time_hour = 0;
			$sleep_time_ops = array();
			$sleep_time_ops ["0"] = 'Установите';
			$sleep_time_ops ["0.25"] = '15 минут';
			$sleep_time_ops ["0.5"] = '30 минут';
			$sleep_time_ops ["0.75"] = '45 минут';
			$sleep_time_ops ["1"] = '1 час';
			$sleep_time_ops ["1.25"] = '1 час 15 минут';
			$sleep_time_ops ["1.5"] = '1 час 30 минут';
			$sleep_time_ops ["1.75"] = '1 час 45 минут';
			$sleep_time_ops ["2"] = '2 часа';
			$sleep_time_ops ["2.5"] = '2 часа 30 минут';
			$sleep_time_ops ["3"] = '3 часа';
			$sleep_time_ops ["3.5"] = '3 часа 30 минут';
			$sleep_time_ops ["4"] = '4 часа';
			$sleep_time_ops ["4.5"] = '4 часа 30 минут';
			$sleep_time_ops ["5"] = '5 часов';
			$sleep_time_ops ["5.5"] = '5 часов 30 минут';
			$sleep_time_ops ["6"] = '6 часов';
			
			ControlFactory::add_combobox($defs, $this, null,
				'sleep_time_hour', 'Выключение через:',
				$sleep_time_hour, $sleep_time_ops, 0, $need_confirm = false, $need_apply = false
			);
			ControlFactory::add_button_close ($defs, $this, $add_params=null,'cleer_sleep',
			"Сброс Sleep таймера:", 'Очистить таймеры', 0);	
			
			ControlFactory::add_close_dialog_and_apply_button($defs,
				$this, null,
				'sleep_time', 'Применить', 300, $params=null);
			$attrs['dialog_params'] = array('frame_style' => DIALOG_FRAME_STYLE_GLASS);
			$attrs['actions'] = null;
			return ActionFactory::show_dialog("Sleep таймер $sleep_time", $defs,true,0,$attrs);
        }
		if ($user_input->control_id == 'sleep_time')
        {
			$sleep_time_hour = $user_input->sleep_time_hour;
					$sleep_time_hour = $sleep_time_hour * 3600;
					$settings_file = '/config/settings.properties';
					$doc = file_get_contents($settings_file);
					$tmp = explode('time_zone =', $doc);
						if (count($tmp) > 1)
					$sleep_shift = (strstr($tmp[1], ':', true));
					$sleep_shift = $sleep_shift * 3600;
					$seconds = '00';
					$year = date("Y");
					$cron_file = '/tmp/cron/crontabs/root';
					$doc = file_get_contents($cron_file);
					
					$unix_time = time() - $sleep_shift + $sleep_time_hour;
					$date = date("m-d H:i:s" , $unix_time);
					$day_s = date("d", $unix_time);
					$mns_s = date("m", $unix_time);
					$hrs_s = date("H", $unix_time);
					$min_s = date("i", $unix_time);
					
					$unix_time = time() + $sleep_time_hour;
					$date = date("m-d H:i:s" , $unix_time);
					$day_s1 = date("d", $unix_time);
					$mns_s1 = date("m", $unix_time);
					$hrs_s1 = date("H", $unix_time);
					$min_s1 = date("i", $unix_time);
					if (preg_match('/Выключение/i', $doc))
					{
					$tmp = explode('#*#*#', $doc);
					if (count($tmp) > 1)
					$sleep_old = strstr($tmp[1], '#-#-#', true);
					$sleep_old = "\n#*#*#" . $sleep_old . "#-#-#";
					$save_cron = "\n#*#*# Выключение в: [$hrs_s:$min_s] [$day_s-$mns_s] + \n$min_s1 $hrs_s1 $day_s1 $mns_s1 * wget --quiet -O - \"http://127.0.0.1/cgi-bin/do?cmd=standby\"\n#-#-#";
					$data = str_replace($sleep_old, $save_cron, $doc);
					$cron_data = fopen($cron_file,"w");
					if (!$cron_data)
					hd_print("НЕ МОГУ ЗАПИСАТЬ cron");
					fwrite($cron_data, $data);
					@fclose($cron_data);
					chmod($cron_file, 0575);
					}
					else
					{
					$save_cron = "\n#*#*# Выключение в: [$hrs_s:$min_s] [$day_s-$mns_s] + \n$min_s $hrs_s1 $day_s1 $mns_s1 * wget --quiet -O - \"http://127.0.0.1/cgi-bin/do?cmd=standby\"\n#-#-#";
					$cron_data = fopen($cron_file,"a");
					if (!$cron_data)
					hd_print("НЕ МОГУ ЗАПИСАТЬ cron");
					fwrite($cron_data, $save_cron);
					@fclose($cron_data);
					chmod($cron_file, 0575);
					}
					shell_exec('crontab -e');
					return null;
		}
		if ($user_input->control_id == 'cleer_sleep')
        {
			$cron_file = '/tmp/cron/crontabs/root';
			$doc = file_get_contents($cron_file);

			if (preg_match('/Выключение/i', $doc))
			{
			$tmp = explode('#*#*#', $doc);
			if (count($tmp) > 1)
            $sleep_old = strstr($tmp[1], '#-#-#', true);
			$sleep_old = "\n#*#*#" . $sleep_old . "#-#-#";
			$save_cron = "";
			$data = str_replace($sleep_old, $save_cron, $doc);
			$cron_data = fopen($cron_file,"w");
			if (!$cron_data)
			hd_print("НЕ МОГУ ЗАПИСАТЬ cron");
			fwrite($cron_data, $data);
			@fclose($cron_data);
			chmod($cron_file, 0575);
			}
			else
			{
			return ActionFactory::show_title_dialog_gl("Таймер выключениня не был задан!");
			}
			shell_exec('crontab -e');
			$perform_new_action = null;
			return UserInputHandlerRegistry::create_action($this,
            'sleep', $params=null);
		}
		if ($user_input->control_id == 'oth_select'){
			$bgr_rs = DuneSystem::$properties['tmp_dir_path'] . '/background_rec_stop';
			if (file_exists($bgr_rs)) {
			$name = trim(file_get_contents($bgr_rs));
			$dd = "/tmp/".$name."_iptvrecord.sh";
			if (!file_exists($dd))
				unlink($bgr_rs);
			}
			ControlFactory::add_button_close ($defs, $this, $add_params=null,'sleep',
			"", 'Sleep таймер', 600);	
			ControlFactory::add_button_close ($defs, $this, $add_params=null,'background_rec',
			"", 'Фоновая запись', 600);	
			if (file_exists($bgr_rs))
				ControlFactory::add_button_close ($defs, $this, $add_params=null,'background_stoprec',
			$name, "Остановить запись", 600);	
			ControlFactory::add_button_close ($defs, $this, $add_params=null,'info',
			"", 'Время записи по таймеру', 600);
			ControlFactory::add_button_close ($defs, $this, $add_params=null,'new_rec',
			"", 'Указать время записи', 600);
			ControlFactory::add_button_close ($defs, $this, $add_params=null,'one_rec',
			"", 'Расписание записи каналов', 600);
			$attrs['dialog_params'] = array('frame_style' => DIALOG_FRAME_STYLE_GLASS);
			$attrs['actions'] = null;
			return ActionFactory::show_dialog("Действия", $defs,true,0,$attrs);
		}
		else if ($user_input->control_id == 'one_rec')
		{	
			$doc = file_get_contents('/tmp/run/storage_list.xml');
			if (is_null($doc))
			throw new Exception('Can not fetch storage_list');
			$xml = simplexml_load_string($doc);
			$uuid = $xml->storages->storage[0]->uuid;
			if ($xml === false)
				{
					$tmp = file('/tmp/run/storages.txt');
					$uuid = $tmp[2];
				}

			$defs = array();
			$cron_file = '/tmp/cron/crontabs/root';
			$doc = file_get_contents($cron_file);
			$texts = explode('###', $doc);
			unset($texts[0]);
			$texts = array_values($texts);
			$ndx_rec = 1;
			foreach($texts as $text){
			$tmp = explode('*', $text);
			$time =$tmp[0];
			$pattern = '|\/tmp/(.*?)_iptvrecord.sh|';
			preg_match( $pattern, $text , $matches);
			$file_rec = $matches[1];
			$rec_path = HD::get_rec_path($plugin_cookies);
			if (!file_exists("/D/IPTV_recordings/$file_rec.ts")){
			ControlFactory::add_label($defs, "$ndx_rec", $time);}
			else{
			$rec_hdd = "main_storage://IPTV_recordings/$file_rec.ts";
			$add_params ['rec_hdd'] = $rec_hdd;
			ControlFactory::add_button ($defs, $this, $add_params,'rec_cool', $ndx_rec, $time, 500);}
			++$ndx_rec;
			}
			if ($ndx_rec == 1){
			ControlFactory::add_label($defs, "", 'Запись каналов не задана.');
			$attrs['dialog_params'] = array('frame_style' => DIALOG_FRAME_STYLE_GLASS);
			$attrs['actions'] = null;
			ControlFactory::add_close_dialog_button($defs,'Ок', 350);
			return ActionFactory::show_dialog('Расписание записи каналов', $defs,true,0,$attrs);
			}
			$rec_del = '0';
			$rec_ops[0] = 'Выбор';
			foreach($texts as $text)
				{
				$tmp = explode('*', $text);
				$rec_ops[] = $tmp[0];
				}
			ControlFactory::add_label($defs, "", 'Удалить задание:');
			ControlFactory::add_combobox($defs, $this, null,
				'rec_del', '',
				$rec_del, $rec_ops, 0, $need_confirm = false, $need_apply = false
			);
			$do_rec_del = UserInputHandlerRegistry::create_action($this, 'rec_del');
			ControlFactory::add_custom_close_dialog_and_apply_buffon($defs,
			'_do_rec_del', 'Удалить', 350, $do_rec_del);
			$rec_del_menu = UserInputHandlerRegistry::create_action($this, 'rec_del_menu');
			ControlFactory::add_custom_close_dialog_and_apply_buffon($defs, '_del', 'Удалить все', 350,  $rec_del_menu);
			ControlFactory::add_close_dialog_button($defs,
				'Отмена', 350);
			$attrs['dialog_params'] = array('frame_style' => DIALOG_FRAME_STYLE_GLASS);
			$attrs['actions'] = null;
			return  ActionFactory::show_dialog("Расписание записи каналов",$defs,true,0,$attrs);
		}
		else if ($user_input->control_id === 'rec_del_menu')
		{
			$defs = array();
			$cron_file = '/tmp/cron/crontabs/root';
			$doc = file_get_contents($cron_file);
			$texts = explode('###', $doc);
			foreach ($texts as $text){
			$one_del = "###" . strstr($text, 'iptvrecord.sh', true) .'iptvrecord.sh';
			$doc = str_replace($one_del, '', $doc);
			}

			$date_cron = fopen($cron_file,"w");
			if (!$date_cron)
				{
				ActionFactory::show_title_dialog("Не могу записать. Что-то здесь не так!!!");
				}
			fwrite($date_cron, $doc);
			@fclose($date_cron);
			chmod($cron_file, 0575);
			shell_exec('crontab -e');
				ControlFactory::add_close_dialog_button($defs,
					'Ок', 350);
			$attrs['dialog_params'] = array('frame_style' => DIALOG_FRAME_STYLE_GLASS);
			$attrs['actions'] = null;
			return  ActionFactory::show_dialog("Список записи очищен!!!",$defs,true,0,$attrs);
		}
		else if ($user_input->control_id === 'rec_del')
		{
                $control_id = $user_input->control_id;
				$new_value = $user_input->{$control_id};
				$cron_file = '/tmp/cron/crontabs/root';
				$doc = file_get_contents($cron_file);
				$texts = explode('###', $doc);
				$one_del = "###" . strstr($texts[$new_value], 'iptvrecord.sh', true) .'iptvrecord.sh';
				$data = str_replace($one_del, '', $doc);
				$cron_edit = fopen($cron_file,"w");
			if (!$cron_file)
			hd_print("НЕ МОГУ ЗАПИСАТЬ НА USB/HDD");
			fwrite($cron_edit, $data);
			@fclose($cron_edit);
			chmod($cron_file, 0575);
			shell_exec('crontab -e');
			$perform_new_action = UserInputHandlerRegistry::create_action(
                    $this, 'one_rec');
			return ActionFactory::invalidate_folders(array('one_rec'), $perform_new_action);
		}
		else if ($user_input->control_id === 'rec_cool')
		{
			if (isset($user_input->rec_hdd))
				$rec_hdd = $user_input->rec_hdd;
			return ActionFactory::launch_media_url(
					$rec_hdd);
		}
		else if ($user_input->control_id === 'background_stoprec')
		{
			$rec_file = DuneSystem::$properties['tmp_dir_path'] . '/background_rec_stop';
			if (!file_exists($rec_file))
			return ActionFactory::show_title_dialog_gl("Активная фоновая запись не найдена.");
			$background_rec_stop = trim(file_get_contents($rec_file));
			unlink($rec_file);
			$cmd_stoprec = '/tmp/' .$background_rec_stop.'_iptvrecord.sh';
			shell_exec($cmd_stoprec);
			return ActionFactory::show_title_dialog_gl("Запись остановлена.");
		}
		else if ($user_input->control_id == 'info')
        {
			$rec_type = isset($plugin_cookies->rec_type) ?
			$plugin_cookies->rec_type : 0;
			$doc = file_get_contents('/config/settings.properties');
			if (preg_match('/time_zone =(.*)\s/', $doc, $matches)) {
			$tmp = explode(':', $matches[1]);
			$rec_shift =($tmp[0] * 3600 ) + ($tmp[1] * 60 );
			}
			$channel_id = $user_input->plugin_tv_channel_id;
			$channels = $this->channels;
			$c = $channels->get($channel_id);
			$title = $c->get_title();
			$streaming_url = $this->finalUrl;
			$chid = $channel_id;
			$epg_date = date("Y-m-d");
			$stream_url = $epg_date;
            $post_action = null;
			list($garb, $epg_shift_pl, $channel_id) = preg_split('/_/', $channel_id);
			if(preg_match("/%/", $channel_id)){
				$tmp = explode('%', $channel_id);
				$channel_id = $tmp[0];}
			$tvg_rec_day = 0;
			if (isset($user_input->epg_date)){
			$epg_date = $user_input->epg_date;
			$stream_url = $epg_date;
			$tvg_rec_day = $epg_date;
			}
			if(preg_match("/%/", $channel_id)){
			$tmp = explode('%', $channel_id);
			$channel_id = $tmp[0];}
			if ($channel_id < 20000){
			$day_start_ts = strtotime($epg_date);
			$texts = HD::get_day_epg($chid, $day_start_ts, &$plugin_cookies);

			$times = array();
			foreach($texts as $time => $value)
			{
			$time = $time - $rec_shift;
			$times[] =$time;
			}
			$i = 0;
			foreach($texts as $time => $value)
			{
			$inf = $value["name"];
			$time_n = $time - $rec_shift;
			$time = date("H:i" , $time_n);
			$result = count($times)-1;
			$add_params ['start_tvg_times'] = $time;
			$cnt = $i+1;
			if ($cnt > $result){
			$add_params ['stop_tvg_times'] = $time;
			}
			else{
			$time_n = $times[$cnt];
			$time_end = date("H:i" , ($times[$cnt]));
			$add_params ['stop_tvg_times'] = $time_end;}
			$add_params ['tvg_rec_day'] = $tvg_rec_day;
			$add_params ['inf'] = $inf;
			$unix_time = time() - $rec_shift;
			if ($time_n < $unix_time){
			ControlFactory::add_label($defs, $time, $inf);
			}else{
			if (($rec_type >= 0) && ($add_params ['stop_tvg_times'] != $add_params ['start_tvg_times'])){
			$rec_start_do = date("dm", $unix_time);
			$tmp = explode(':', $add_params ['start_tvg_times']);
			$shift = (mktime($tmp[0], $tmp[1], 0,0, 0, 0)) - $rec_type;
			$rec_start_to = date("Hi" , $shift);
			$rec_stop_do = date("dm", $unix_time);
			$tmp = explode(':', $add_params ['stop_tvg_times']);
			$shift = (mktime($tmp[0], $tmp[1], 0,0, 0, 0)) + $rec_type;
			$rec_stop_to  = date("Hi" , $shift);
			$year =  date("y");

			if (!$tvg_rec_day == '0'){
			$rec_day =  explode('-', $tvg_rec_day);
			$rec_start_do = $rec_day[2] . $rec_day[1];
			$rec_stop_do = $rec_start_do;
			}
			$c_time= intval(date("Hi", $unix_time));
			$c_day_start = intval($rec_start_to);
			$c_day_stop = intval($rec_stop_to);

			if(($c_day_start <= 500) && ($c_time > 500)){
			$unix_t = time() + $rec_shift + 86400;
			$rec_start_do = date("dm", $unix_t);
			}
			if(($c_day_stop <= 500)&& ($c_time > 500)){
			$unix_t = time() + $rec_shift + 86400;
			$rec_stop_do = date("dm", $unix_t);
			}
			if ((!$tvg_rec_day == '0') && ($c_day_start <= 500)){
			$date = strtotime($tvg_rec_day);
			$date = strtotime("+1 day", $date);
			$rec_start_do = date('dm', $date);
			}
			if ((!$tvg_rec_day == '0') && ($c_day_stop <= 500)){
			$date = strtotime($tvg_rec_day);
			$date = strtotime("+1 day", $date);
			$rec_stop_do = date('dm', $date);
			}
			$day = substr($rec_start_do, 0, 2);
			$mns = substr($rec_start_do, -2);
			$hrs = substr($rec_start_to, 0, 2);
			$min = substr($rec_start_to, -2);
			$timestamp = mktime($hrs, $min, 0, $mns, $day, $year);
			if($timestamp < $unix_time)
			$rec_start_to = date("Hi", $unix_time + 60);

			$add_params ['rec_start_t'] = $rec_start_to;
			$add_params ['rec_start_d'] = $rec_start_do;
			$add_params ['rec_stop_t'] = $rec_stop_to;
			$add_params ['rec_stop_d'] = $rec_stop_do;
			$add_params ['inf'] = $inf;
			ControlFactory::add_button ($defs, $this, $add_params,'new_rec_conf', $time, $inf, 900);
			}else{
			ControlFactory::add_button ($defs, $this, $add_params,'new_rec', $time, $inf, 900);}}
			$i++;}
			$date = strtotime($epg_date);
			$date = strtotime("+1 day", $date);
			$date = date('Y-m-d', $date);
			$add_params ['epg_date'] = $date;
			$time = '';
			$inf = "Следующий день";
			ControlFactory::add_button_close ($defs, $this, $add_params,'info', $time, $inf, 500);
			}
			else{
			$time =' ';
			ControlFactory::add_button ($defs, $this, $add_params=null,'popup_menu', $time, $streaming_url, 500);
			}
			$attrs['dialog_params'] = array('frame_style' => DIALOG_FRAME_STYLE_GLASS);
			$attrs['actions'] = null;
			return ActionFactory::show_dialog("ТВ программа: $title [$stream_url] ", $defs, true,0,$attrs);
        }
		else if ($user_input->control_id === 'new_rec')
		{
			
			if (!file_exists('/codecpack/script/iptv_record.sh')){
			$defs = $this->do_install_codecpack_defs($plugin_cookies);
			$attrs['dialog_params'] = array('frame_style' => DIALOG_FRAME_STYLE_GLASS);
			$attrs['actions'] = null;
			return  ActionFactory::show_dialog("Кодеки не установлены или установленна старая версия.",$defs, true,0,$attrs);
			}
			$start_tvg_times = 0;
			$stop_tvg_times = 0;
			$tvg_rec_day = 0;
			$inf = '';
			if (isset($user_input->start_tvg_times))
			$start_tvg_times = $user_input->start_tvg_times;
			if (isset($user_input->stop_tvg_times))
			$stop_tvg_times = $user_input->stop_tvg_times;
			if (isset($user_input->tvg_rec_day))
			$tvg_rec_day = $user_input->tvg_rec_day;
			if (isset($user_input->inf))
			$inf = $user_input->inf;
			$start_tvg_times = str_replace(":", "", $start_tvg_times);
			$stop_tvg_times = str_replace(":", "", $stop_tvg_times);
			$doc = file_get_contents('/config/settings.properties');
			if (preg_match('/time_zone =(.*)\s/', $doc, $matches)) {
			$tmp = explode(':', $matches[1]);
			$rec_shift =($tmp[0] * 3600 ) + ($tmp[1] * 60 );}
			$defs = array();
			$add_params['inf'] = $inf;
			if ($start_tvg_times == '0'){
			$unix_time = time() - $rec_shift;
			$date = date("m-d H:i:s" , $unix_time);
			$rec_start_do = date("dm", $unix_time);
			$rec_start_to = date("Hi", $unix_time);
			$rec_stop_do = date("dm", $unix_time);
			$rec_stop_to = date("Hi", $unix_time);}
			else{
			$unix_time = time() - $rec_shift;
			$year =  date("y");
			$rec_start_do = date("dm", $unix_time);
			$rec_start_to = $start_tvg_times;
			$rec_stop_do = date("dm", $unix_time);
			$rec_stop_to = $stop_tvg_times;
			if (!$tvg_rec_day == '0'){
			$rec_day =  explode('-', $tvg_rec_day);
			$rec_start_do = $rec_day[2] . $rec_day[1];
			$rec_stop_do = $rec_start_do;
			}
			$c_time= intval(date("Hi", $unix_time));
			$c_day_start = intval($start_tvg_times);
			$c_day_stop = intval($stop_tvg_times);

			if(($c_day_start <= 500) && ($c_time > 500)){
			$unix_t = time() + $rec_shift + 86400;
			$rec_start_do = date("dm", $unix_t);
			}
			if(($c_day_stop <= 500)&& ($c_time > 500)){
			$unix_t = time() + $rec_shift + 86400;
			$rec_stop_do = date("dm", $unix_t);
			}
			if ((!$tvg_rec_day == '0') && ($c_day_start <= 500)){
			$date = strtotime($tvg_rec_day);
			$date = strtotime("+1 day", $date);
			$rec_start_do = date('dm', $date);
			}
			if ((!$tvg_rec_day == '0') && ($c_day_stop <= 500)){
			$date = strtotime($tvg_rec_day);
			$date = strtotime("+1 day", $date);
			$rec_stop_do = date('dm', $date);
			}
			$day = substr($rec_start_do, 0, 2);
			$mns = substr($rec_start_do, -2);
			$hrs = substr($rec_start_to, 0, 2);
			$min = substr($rec_start_to, -2);
			$timestamp = mktime($hrs, $min, 0, $mns, $day, $year);
			if($timestamp < $unix_time)
			$rec_start_to = date("Hi", $unix_time + 60);
			}
			ControlFactory::add_text_field($defs,0,0,
				'rec_start_t', 'Время начала записи [ЧЧММ]:',
				$rec_start_to, 1, 0, 0, 1, 250, 0, false);
			ControlFactory::add_text_field($defs,0,0,
				'rec_start_d', 'Дата начала записи [ДДMM]:',
				$rec_start_do, 1, 0, 0, 1, 250, 0, false);
			ControlFactory::add_text_field($defs,0,0,
				'rec_stop_t', 'Время окончания записи [ЧЧММ]:',
				$rec_stop_to, 1, 0, 0, 1, 250, 0, false);
			ControlFactory::add_text_field($defs,0,0,
				'rec_stop_d', 'Дата окончания записи [ДДMM]:',
				$rec_stop_do, 1, 0, 0, 1, 250, 0, false);

			$do_rec_apply = UserInputHandlerRegistry::create_action($this, 'new_rec_apply', $add_params);
			ControlFactory::add_custom_close_dialog_and_apply_buffon($defs,
			'_do_rec_apply', 'ОК', 250, $do_rec_apply);
			ControlFactory::add_close_dialog_button($defs,
				'Отмена', 250);
			$attrs['dialog_params'] = array('frame_style' => DIALOG_FRAME_STYLE_GLASS);
			$attrs['actions'] = null;
			return  ActionFactory::show_dialog("Задать время записи:",$defs,true,0,$attrs);
		}
		else if ($user_input->control_id === 'new_rec_conf')
		{
			$defs = array();
			$rec_start_t = $user_input->start_tvg_times;
			$rec_stop_t = $user_input->stop_tvg_times;
			$inf = $user_input->inf;
			ControlFactory::add_label($defs, "$rec_start_t - $rec_stop_t", "$inf");
			$add_params ['rec_start_t'] = $user_input->rec_start_t;
			$add_params ['rec_start_d'] = $user_input->rec_start_d;
			$add_params ['rec_stop_t'] = $user_input->rec_stop_t;
			$add_params ['rec_stop_d'] = $user_input->rec_stop_d;
			$add_params ['inf'] = $user_input->inf;
			$new_rec_apply = UserInputHandlerRegistry::create_action($this, 'new_rec_apply', $add_params);
			ControlFactory::add_custom_close_dialog_and_apply_buffon($defs,
			'_add_new_rec_apply', 'Да', 250, $new_rec_apply);
			ControlFactory::add_close_dialog_button($defs,
				'Нет', 250);
			$attrs['dialog_params'] = array('frame_style' => DIALOG_FRAME_STYLE_GLASS);
			$attrs['actions'] = null;
			return  ActionFactory::show_dialog("Добавить задание записи?",$defs,true,0,$attrs);
		}
		else if ($user_input->control_id === 'new_rec_apply')
		{
			$doc = file_get_contents('/config/settings.properties');
			if (preg_match('/time_zone =(.*)\s/', $doc, $matches)) {
			$tmp = explode(':', $matches[1]);
			$rec_shift =($tmp[0] * 3600 ) + ($tmp[1] * 60 );}
			$rec_shift = $rec_shift / 3600;
			$seconds = '00';
			$year = date("Y");
			$channel_id = $user_input->plugin_tv_channel_id;
			$channels = $this->channels;
			$c = $channels->get($channel_id);
			$title = $c->get_title();
			$streaming_url = $this->finalUrl;///$c->streaming_url();
			$rec_start_t = $user_input->rec_start_t;
			$rec_start_d = $user_input->rec_start_d;
			$rec_stop_t = $user_input->rec_stop_t;
			$rec_stop_d = $user_input->rec_stop_d;
			$day_e = substr($rec_stop_d, 0, 2);
			$mns_e = substr($rec_stop_d, -2);
			$day_s = substr($rec_start_d, 0, 2);
			$mns_s = substr($rec_start_d, -2);
			$year =  date("y");
			$inf = $user_input->inf;
			if (($rec_start_t >= $rec_stop_t) && ((strtotime ("$day_s.$mns_s.$year")) >= strtotime (("$day_e.$mns_e.$year"))))
			return ActionFactory::show_title_dialog_gl("Время окончания записи указанно не верно.");
			$cron_file = '/tmp/cron/crontabs/root';
			$hrs_s = substr($rec_start_t, 0, 2);
			$min_s = substr($rec_start_t, -2);
			$time_s = $hrs_s .":".$min_s;
			if (!preg_match('/^([0-1][0-9]|[2][0-3]):([0-5][0-9])$/', $time_s))
			return ActionFactory::show_title_dialog_gl("Время начала записи указанно не верно.");

			$data_s = $day_s .":".$mns_s;
			if (!preg_match('/^([0-2][0-9]|[3][0-1]):([0-1][0-9])$/', $data_s))
			return ActionFactory::show_title_dialog_gl("Дата начала записи указанно не верно.");
			$timestamp = mktime($hrs_s + $rec_shift, $min_s , $seconds, $mns_s, $day_s, $year);
			$unix_time = time();
			if ($unix_time > $timestamp)
			return ActionFactory::show_title_dialog_gl("Время начала записи указанно не верно.");
			$hrs_s1 = strftime('%H',$timestamp);
			$min_s1 = strftime('%M',$timestamp);
			$day_s1 = strftime('%d',$timestamp);
			$mns_s1 = strftime('%m',$timestamp);
			$hrs_e = substr($rec_stop_t, 0, 2);
			$min_e = substr($rec_stop_t, -2);
			$time_e = $hrs_e .":".$min_e;
			if (!preg_match('/^([0-1][0-9]|[2][0-3]):([0-5][0-9])$/', $time_e))
			return ActionFactory::show_title_dialog_gl("Время начала записи указанно не верно.");

			$data_e = $day_e .":".$mns_e;
			if (!preg_match('/^([0-2][0-9]|[3][0-1]):([0-1][0-9])$/', $data_e))
			return ActionFactory::show_title_dialog_gl("Дата окончания записи указанно не верно.");
			$timestamp = mktime($hrs_e + $rec_shift, $min_e, $seconds, $mns_e, $day_e, $year);
			$hrs_e1 = strftime('%H',$timestamp);
			$min_e1 = strftime('%M',$timestamp);
			$day_e1 = strftime('%d',$timestamp);
			$mns_e1 = strftime('%m',$timestamp);
			$rec_path = HD::get_rec_path($plugin_cookies);
			$tr_title = HD::translit($title);
			$date_name = $hrs_s . $min_s . $day_s . $mns_s ."-". $hrs_e . $min_e . $day_e . $mns_e;
			$rec_name = $tr_title .'_'. $date_name;
			$streaming_url = str_replace(array('http://ts://','m3u9'), array('http://','m3u8'), $streaming_url);
			$ptl = "http";
			if (preg_match("/udp:\/\/@/i",$streaming_url))
				$ptl = "udp";
			if (preg_match("/rtp:\/\/@/i",$streaming_url))
				$ptl = "rtp";
			if (preg_match("/rtsp:\/\//i",$streaming_url))
				$ptl = "rtsp";
			if (preg_match("/\.m3u8/i",$streaming_url))
				$ptl = "hls";
			if (preg_match("/\.flv/i",$streaming_url))
				$ptl = "flv";
			if (preg_match("/rtmp:\/\//i",$streaming_url))
				$ptl = "rtmp";
			$cmd_rec = "/codecpack/script/iptv_record.sh --$ptl \"$streaming_url\" \"$rec_name\" \"$rec_path\"";

			if (preg_match("|dvb:\/\/\/(.*?):|",$streaming_url, $matches))
				$cmd_rec = "/codecpack/script/iptv_record.sh --dvbt \"".$matches[1]."\" $rec_name \"$rec_path\" no";
				if (preg_match("|\?(.*?)&user_agent=(.*)|",$streaming_url, $matches)){
				if (preg_match("/\.m3u8/i",$streaming_url))
				$ptl = "hls";
				$ua = $matches[2];
				$cmd_rec = "/codecpack/script/iptv_record.sh --$ptl \"".$matches[1]."\" \"$rec_name\" \"$rec_path\" \"$ua\"";
			}

			if (!file_exists($rec_path))
			return ActionFactory::show_title_dialog_gl("Накопитель для записи не найден!!! Подключите к плееру накопитель.");
			if (preg_match("|\/D\/|",$rec_path)){
			$bytes = disk_free_space ('/D/');
			$si_prefix = array( 'B', 'KB', 'MB', 'GB', 'TB', 'EB', 'ZB', 'YB' );
			$base = 1024;
			$class = min((int)log($bytes , $base) , count($si_prefix) - 1);
			$free = sprintf('%1.2f' , $bytes / pow($base,$class)) . ' ' . $si_prefix[$class];
			if ($bytes < 1000000000)
			return ActionFactory::show_title_dialog("Свободного места на диске меньше 1ГБ ($free). Запись не началась!!!");
			}
			$background_rec_stop = DuneSystem::$properties['tmp_dir_path'] . '/background_rec_stop';
			$save_cron = "\n###$title $inf [$hrs_s:$min_s] [$day_s-$mns_s] по [$hrs_e:$min_e] [$day_e-$mns_e]* \n$min_s1 $hrs_s1 $day_s1 $mns_s1 * $cmd_rec\n$min_s1 $hrs_s1 $day_s1 $mns_s1 * echo \"$rec_name\" > $background_rec_stop\n$min_e1 $hrs_e1 $day_e1 $mns_e1 * /tmp/". $rec_name ."_iptvrecord.sh";
			$cron_data = fopen($cron_file,"a");
			if (!$cron_data)
			hd_print("НЕ МОГУ ЗАПИСАТЬ cron");
			fwrite($cron_data, $save_cron);
			@fclose($cron_data);
			chmod($cron_file, 0575);
			shell_exec('crontab -e');
			return ActionFactory::show_title_dialog_gl("Добавлено $title Старт:$hrs_s:$min_s $day_s-$mns_s Стоп:$hrs_e:$min_e $day_e-$mns_e");
			break;
		}
		else if ($user_input->control_id === 'background_rec')
		{
			if (!file_exists('/codecpack/script/iptv_record.sh')){
			$defs = $this->do_install_codecpack_defs($plugin_cookies);
			$attrs['dialog_params'] = array('frame_style' => DIALOG_FRAME_STYLE_GLASS);
			$attrs['actions'] = null;
			return  ActionFactory::show_dialog("Кодеки не установлены или установленна старая версия.",$defs, true,0,$attrs);
			}
		$channel_id = $user_input->plugin_tv_channel_id;
        $channels = $this->channels;
        $c = $channels->get($channel_id);
        $title = $c->get_title();
		$tr_title = HD::translit($title);
		$rec_path = HD::get_rec_path($plugin_cookies);
		$streaming_url = $this->finalUrl;
		$doc = file_get_contents('/config/settings.properties');
		if (preg_match('/time_zone =(.*)\s/', $doc, $matches)) {
		$tmp = explode(':', $matches[1]);
		$rec_shift =($tmp[0] * 3600 ) + ($tmp[1] * 60 );}
        $unix_time = time() - $rec_shift;
		$date = date("HidmY" , $unix_time);
		$rec_name = $tr_title .'_'. $date;
		$streaming_url = str_replace(array('http://ts://','m3u9'), array('http://','m3u8'), $streaming_url);
		$ptl = "http";
		if (preg_match("/udp:\/\/@/i",$streaming_url))
			$ptl = "udp";
		if (preg_match("/rtp:\/\/@/i",$streaming_url))
			$ptl = "rtp";
		if (preg_match("/rtsp:\/\//i",$streaming_url))
			$ptl = "rtsp";
		if (preg_match("/\.m3u8/i",$streaming_url))
			$ptl = "hls";
		if (preg_match("/\.flv/i",$streaming_url))
			$ptl = "flv";
		if (preg_match("/rtmp:\/\//i",$streaming_url))
			$ptl = "rtmp";
		$cmd_rec = "/codecpack/script/iptv_record.sh --$ptl \"$streaming_url\" \"$rec_name\" \"$rec_path\"";
		if (preg_match("|dvb:\/\/\/(.*?):|",$streaming_url, $matches))
			$cmd_rec = "/codecpack/script/iptv_record.sh --dvbt \"".$matches[1]."\" \"$rec_name\" \"$rec_path\" no";
		if (preg_match("|\?(.*?)&user_agent=(.*)|",$streaming_url, $matches)){
			if (preg_match("/\.m3u8/i",$streaming_url))
			$ptl = "hls";
			$ua = $matches[2];
			$cmd_rec = "/codecpack/script/iptv_record.sh --$ptl \"".$matches[1]."\" \"$rec_name\" \"$rec_path\" \"$ua\"";
		}	
		$free = "$rec_path";
		if (!file_exists($rec_path))
		return ActionFactory::show_title_dialog_gl("Накопитель для записи не найден!!! Подключите к плееру накопитель.");
		if (preg_match("|\/D\/|",$rec_path)){
		$bytes = disk_free_space ('/D/');
		$si_prefix = array( 'B', 'KB', 'MB', 'GB', 'TB', 'EB', 'ZB', 'YB' );
		$base = 1024;
		$class = min((int)log($bytes , $base) , count($si_prefix) - 1);
		$free = sprintf('%1.2f' , $bytes / pow($base,$class)) . ' ' . $si_prefix[$class];
		if ($bytes < 1000000000)
		return ActionFactory::show_title_dialog_gl("Свободного места на диске меньше 1ГБ ($free). Запись не началась!!!");
		}
		$rec_file = DuneSystem::$properties['tmp_dir_path'] . '/background_rec_stop';
		$date_count = fopen($rec_file,"w");
		if (!$date_count)
		return ActionFactory::show_title_dialog_gl("Не могу записать в tmp Что-то здесь не так!!!");
		fwrite($date_count, $rec_name);
		@fclose($date_count);
		shell_exec($cmd_rec);
		ControlFactory::add_label($defs, "Запись канала:", "$title");
		ControlFactory::add_label($defs, "Свободно на диске:", "$free");
		ControlFactory::add_label($defs, "", "Не забудьте Выключить запись!!!");
		$do_br_apply = UserInputHandlerRegistry::create_action($this, 'new_br_apply');
        ControlFactory::add_custom_close_dialog_and_apply_buffon($defs,
		'_do_br_apply', 'ОК', 250, $do_br_apply);
		$attrs['dialog_params'] = array('frame_style' => DIALOG_FRAME_STYLE_GLASS);
		$attrs['actions'] = null;
		return ActionFactory::show_dialog('Фоновая запись канала', $defs,true,0,$attrs);
		}
		if ($user_input->control_id == 'zoom_apply'){
			$zoom = HD::get_items('zoom', &$plugin_cookies);
			if ($user_input->zoom_select == 'v')
				unset ($zoom[$user_input->cid]);
			else
				$zoom[$user_input->cid] = $user_input->zoom_select;
			HD::save_items('zoom', $zoom,&$plugin_cookies);
			shell_exec ('echo EA15BF00 > /proc/ir/button');
			shell_exec ('echo E916BF00 > /proc/ir/button');
		}
		if ($user_input->control_id == 'refresh'){
			
			shell_exec ('echo EA15BF00 > /proc/ir/button');
			shell_exec ('echo E916BF00 > /proc/ir/button');
		}
		if ($user_input->control_id == 'rec_repeat'){
			
			shell_exec ('echo 9F60BF00 > /proc/ir/button');
		}
		else if ($user_input->control_id === 'do_new_install')
		{
			$add_params = array('name' => 'Codec_install');
            $url = 'plugin_installer://http://dune-club.info/plugins/update/Codec_install/dune_plugin_Codec_install_v4.0.3.tar.gz:::name=Codec_install&space_needed=2000000&need_confirm_replace=0';
			return ActionFactory::launch_media_url($url,UserInputHandlerRegistry::create_action($this, 'refresh', $add_params));
		}
        return null;
    }

    public function get_tv_info(MediaURL $media_url, &$plugin_cookies)
    {
        $group_tv = isset($plugin_cookies->group_tv) ?
        $plugin_cookies->group_tv : '1';
		
		$dialog = UserInputHandlerRegistry::create_action($this,
            'dialog', $params=null);
		$rec_repeat = UserInputHandlerRegistry::create_action($this,
            'rec_repeat',  $params=null);
		$oth_select = UserInputHandlerRegistry::create_action($this,
            'oth_select',  $params=null);
		$actions = array(
			GUI_EVENT_KEY_B_GREEN => $dialog,
			GUI_EVENT_KEY_C_YELLOW => $oth_select,
			GUI_EVENT_KEY_REPEAT => $rec_repeat,
			//GUI_EVENT_KEY_MODE => $time_shift,
        );
		if ($group_tv == 5){
			$ch_select = UserInputHandlerRegistry::create_action($this,
            'ch_select',  $params=null);
			$actions_plus = array(
			GUI_EVENT_KEY_CLEAR => $ch_select
			);
			$actions = array_merge ($actions, $actions_plus);
		}
		$info = parent::get_tv_info($media_url, &$plugin_cookies);
        $info[PluginTvInfo::actions] = $actions;
        return $info;
    }
	private function get_set_default_behaviour_action()
    {
		$gda = UserInputHandlerRegistry::create_action($this,
            'dialog', $params=null);
		$zoom = UserInputHandlerRegistry::create_action($this,
            'zoom',  $params=null);
        return ActionFactory::change_behaviour(
            array(
                GUI_EVENT_KEY_SETUP => $gda,
				GUI_EVENT_KEY_ZOOM => $zoom,
            ));
    }
	private function get_sel_item_update_action(&$user_input, &$plugin_cookies)
    {
        $parent_media_url = MediaURL::decode($user_input->parent_media_url);
        $sel_ndx = $user_input->sel_ndx;
        $group = $this->tv->get_group($parent_media_url->group_id);
        $channels = $group->get_channels($plugin_cookies);

        $items[] = $this->get_regular_folder_item($group,
            $channels->get_by_ndx($sel_ndx), $plugin_cookies);
        $range = HD::create_regular_folder_range($items,
            $sel_ndx, $channels->size());

        return ActionFactory::update_regular_folder($range, false);
    }
	public function do_install_codecpack_defs(&$plugin_cookies)
    {
		$do_new_install_action = UserInputHandlerRegistry::create_action($this, 'do_new_install');
		ControlFactory::add_multiline_label($defs, '', 'Codec_install_v5 устанавливается в несколько этапов,
		скорость установки зависит от скорости интернета.
		После установки Codec_install_v5 зайдите в плагин и нажмите на кнопку обновить.', 6);
		ControlFactory::add_custom_close_dialog_and_apply_buffon($defs,'apply_subscription',
                'Установить Codec_install',
                600, $do_new_install_action);
        ControlFactory::add_close_dialog_button($defs,
                'Отмена', 600);
        return $defs;
	}
}

///////////////////////////////////////////////////////////////////////////
?>
