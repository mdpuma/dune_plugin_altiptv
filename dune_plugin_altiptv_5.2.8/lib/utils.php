<?php
///////////////////////////////////////////////////////////////////////////

class HD
{
    public static function is_map($a)
    {
        return is_array($a) &&
            array_diff_key($a, array_keys(array_keys($a)));
    }

    ///////////////////////////////////////////////////////////////////////

    public static function has_attribute($obj, $n)
    {
        $arr = (array) $obj;
        return isset($arr[$n]);
    }
    ///////////////////////////////////////////////////////////////////////

    public static function get_map_element($map, $key)
    {
        return isset($map[$key]) ? $map[$key] : null;
    }

    ///////////////////////////////////////////////////////////////////////

    public static function starts_with($str, $pattern)
    {
        return strpos($str, $pattern) === 0;
    }

    ///////////////////////////////////////////////////////////////////////

    public static function format_timestamp($ts, $fmt = null)
    {
        // NOTE: for some reason, explicit timezone is required for PHP
        // on Dune (no builtin timezone info?).

        if (is_null($fmt))
            $fmt = 'Y:m:d H:i:s';

        $dt = new DateTime('@' . $ts);
        return $dt->format($fmt);
    }

    ///////////////////////////////////////////////////////////////////////

    public static function format_duration($msecs)
    {
        $n = intval($msecs);

        if (strlen($msecs) <= 0 || $n <= 0)
            return "--:--";

        $n = $n / 1000;
        $hours = $n / 3600;
        $remainder = $n % 3600;
        $minutes = $remainder / 60;
        $seconds = $remainder % 60;

        if (intval($hours) > 0)
        {
            return sprintf("%d:%02d:%02d", $hours, $minutes, $seconds);
        }
        else
        {
            return sprintf("%02d:%02d", $minutes, $seconds);
        }
    }

    ///////////////////////////////////////////////////////////////////////

    public static function encode_user_data($a, $b = null)
    {
        $media_url = null;
        $user_data = null;

        if (is_array($a) && is_null($b))
        {
            $media_url = '';
            $user_data = $a;
        }
        else
        {
            $media_url = $a;
            $user_data = $b;
        }

        if (!is_null($user_data))
            $media_url .= '||' . json_encode($user_data);

        return $media_url;
    }
	
	public static function get_epg_ids($plugin_cookies,$path, $channel_id)
    {	
		$altiptv_data_path = DemoConfig::get_altiptv_data_path(&$plugin_cookies);
        $channels_parsed = array();
		$channels_parsed = unserialize(file_get_contents($altiptv_data_path.'/data_file/'.$path));
		$id = (array_key_exists($channel_id, $channels_parsed)) ? $channels_parsed[$channel_id] : false;
		return $id;
    }
    ///////////////////////////////////////////////////////////////////////

    public static function decode_user_data($media_url_str, &$media_url, &$user_data)
    {
        $idx = strpos($media_url_str, '||');

        if ($idx === false)
        {
            $media_url = $media_url_str;
            $user_data = null;
            return;
        }

        $media_url = substr($media_url_str, 0, $idx);
        $user_data = json_decode(substr($media_url_str, $idx + 2));
    }

    ///////////////////////////////////////////////////////////////////////

    public static function create_regular_folder_range($items,
        $from_ndx = 0, $total = -1, $more_items_available = false)
    {
        if ($total === -1)
            $total = $from_ndx + count($items);

        if ($from_ndx >= $total)
        {
            $from_ndx = $total;
            $items = array();
        }
        else if ($from_ndx + count($items) > $total)
        {
            array_splice($items, $total - $from_ndx);
        }

        return array
        (
            PluginRegularFolderRange::total => intval($total),
            PluginRegularFolderRange::more_items_available => $more_items_available,
            PluginRegularFolderRange::from_ndx => intval($from_ndx),
            PluginRegularFolderRange::count => count($items),
            PluginRegularFolderRange::items => $items
        );
    }

    ///////////////////////////////////////////////////////////////////////

    public static function http_get_document($url, $opts = null)
    {
        $ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 	FALSE);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,    25);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION,    1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,    true);
        curl_setopt($ch, CURLOPT_TIMEOUT,           25);
        curl_setopt($ch, CURLOPT_USERAGENT,         "Mozilla/5.0 (Windows NT 6.1; rv:25.0) Gecko/20100101 Firefox/25.0");
		curl_setopt($ch, CURLOPT_ENCODING,          1);
        curl_setopt($ch, CURLOPT_URL,               $url);

        if (isset($opts))
        {
            foreach ($opts as $k => $v)
                curl_setopt($ch, $k, $v);
        }

        hd_print("HTTP fetching '$url'...");

        $content = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if($content === false)
        {
            $err_msg = "HTTP error: $http_code (" . curl_error($ch) . ')';
            hd_print($err_msg);
            // throw new Exception($err_msg);
        }

        if ($http_code != 200)
        {
            $err_msg = "HTTP request failed ($http_code)";
            hd_print($err_msg);
            // throw new Exception($err_msg);
        }

        hd_print("HTTP OK ($http_code)");

        curl_close($ch);

        return $content;
    }

    ///////////////////////////////////////////////////////////////////////

    public static function http_post_document($url, $post_data, $opts=null)
    {
        $arr [CURLOPT_POST] = true;
        $arr [CURLOPT_POSTFIELDS] = $post_data;
		if (isset($opts))
        {
            foreach ($opts as $k => $v)
               $arr[$k] = $v;
        }
		return self::http_get_document($url, $arr);
    }

    ///////////////////////////////////////////////////////////////////////

    public static function parse_xml_document($doc)
    {
        $xml = simplexml_load_string($doc);

        if ($xml === false)
        {
            hd_print("Error: can not parse XML document.");
            hd_print("XML-text: $doc.");
            throw new Exception('Illegal XML document');
        }

        return $xml;
    }

    ///////////////////////////////////////////////////////////////////////

    public static function make_json_rpc_request($op_name, $params)
    {
        static $request_id = 0;

        $request = array
        (
            'jsonrpc' => '2.0',
            'id' => ++$request_id,
            'method' => $op_name,
            'params' => $params
        );

        return $request;
    }
	public static function get_mount_smb_path($ip_path, $smb_user, $smb_pass, $mount_path)
    {
		$tmp = explode('/', $ip_path);
		$ip_lan = $tmp[0].'/'.$tmp[1];
		$tmp = explode($ip_lan, $ip_path);
		$folder_lan = $tmp[1] . '/';
		if (!file_exists("/tmp/mnt/smb/$mount_path"))
		shell_exec("mkdir /tmp/mnt/smb/$mount_path");
		//$q = DuneSystem::$properties['tmp_dir_path'];
		//shell_exec("mount > $q/mount");
		//$doc = file_get_contents("$q/mount");
		//if (!preg_match("|\/\/$ip_lan on /tmp/mnt/smb/$mount_path type|", $doc))
		shell_exec("mount -t cifs -o rw,username=$smb_user,password=$smb_pass \"//$ip_lan\" \"/tmp/mnt/smb/$mount_path\"");
		$mount_path = str_replace('//','/',"/tmp/mnt/smb/$mount_path/$folder_lan");
		return $mount_path;
	}
    ///////////////////////////////////////////////////////////////////////////
	public static function load_prov_info()
    {
		$country = self::load_location_info();
		$path = 'http://dune-club.info/plugins/update/altiptv3/altiptv_prov_country.xml';
		$doc = file_get_contents($path);
		if ($doc === false)
			return false;
		$xml = simplexml_load_string($doc);
		if ($xml === false){
            hd_print("Error: can not parse XML document. XML-text: $doc.");
            return false;
		}
		if (isset($xml->$country))
		foreach ($xml->$country->children() as $xml_tv_playlist){	
			$caption = (strval($xml_tv_playlist->caption));
			$desc = (strval($xml_tv_playlist->desc));
			$playlist_url =	(strval($xml_tv_playlist->playlist_url));
			$ch_ops["$caption|$playlist_url"] = "$caption [$desc]";
		}
		foreach ($xml->tv_items->children() as $xml_tv_playlist){	
			$caption = (strval($xml_tv_playlist->caption));
			$desc = (strval($xml_tv_playlist->desc));
			$playlist_url =	(strval($xml_tv_playlist->playlist_url));
			$ch_ops["$caption|$playlist_url"] = "$caption [$desc]";
		}
		return $ch_ops;
	}
	public static function load_location_info()
    {
        $country = "";
        if (is_file("/tmp/location_info.properties"))
            $location_info_path = "/tmp/location_info.properties";
        else if (is_file("/config/location_info.properties"))
            $location_info_path = "/config/location_info.properties";
        else
            return;
        $lines = file($location_info_path,
            FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line)
        {
            if (preg_match('/^country =\s*(\S.*\S)\s*$/', $line, $matches))
            {
                $country = $matches[1];
                if (preg_match('/(\S[^,]*)\s*,/', $country, $matches))
                    $country = $matches[1];
				$country = str_replace(" ", "_", $country);
                hd_print("Using country: " . $country);
                return $country;
            }
        }
    }
    public static function get_mac_addr()
    {
        static $mac_addr = null;

        if (is_null($mac_addr))
        {
            $mac_addr = shell_exec(
                'ifconfig  eth0 | head -1 | sed "s/^.*HWaddr //"');

            $mac_addr = trim($mac_addr);

            hd_print("MAC Address: '$mac_addr'");
        }

        return $mac_addr;
    }

    ///////////////////////////////////////////////////////////////////////////

    // TODO: localization
    private static $MONTHS = array(
        'January',
        'February',
        'March',
        'April',
        'May',
        'June',
        'July',
        'August',
        'September',
        'October',
        'November',
        'December',
    );

    public static function format_date_time_date($tm)
    {
        $lt = localtime($tm);
        $mon = self::$MONTHS[$lt[4]];
        return sprintf("%02d %s %04d", $lt[3], $mon, $lt[5] + 1900);
    }

    public static function format_date_time_time($tm, $with_sec = false)
    {
        $format = '%H:%M';
        if ($with_sec)
            $format .= ':%S';
        return strftime($format, $tm);
    }

    public static function print_backtrace()
    {
        hd_print('Back trace:');
        foreach (debug_backtrace() as $f)
        {
            hd_print(
                '  - ' . $f['function'] . 
                ' at ' . $f['file'] . ':' . $f['line']);
        }
    }
	public static function get_items($path,&$plugin_cookies) {
	$items = array();
	$data_path = DemoConfig::get_altiptv_data_path(&$plugin_cookies);
	$link = $data_path . '/data_file/'. $path;
			if (file_exists($link)){
			$doc = file_get_contents($link);
			$items = unserialize($doc);}
	return $items;
	}
	public static function save_items($path, $items, &$plugin_cookies) {
	$data_path = DemoConfig::get_altiptv_data_path(&$plugin_cookies);
	$link = $data_path . '/data_file/'. $path;
	$skey = serialize($items);
						$data = fopen($link,"w");
						if (!$data)
							{
							return ActionFactory::show_title_dialog("Не могу записать items Что-то здесь не так!!!");
							}
						fwrite($data, $skey);
						@fclose($data);
	}
	
	public static function save_items_tmp($path, $items) {
	$data_path = DuneSystem::$properties['tmp_dir_path']. '/' .$path;
	$skey = serialize($items);
						$data = fopen($data_path,"w");
						if (!$data)
							{
							return ActionFactory::show_title_dialog("Не могу записать items Что-то здесь не так!!!");
							}
						fwrite($data, $skey);
						@fclose($data);
	}
	
	public static function get_items_tmp($path) {
	$item = '';
	$data_path = DuneSystem::$properties['tmp_dir_path']. '/' .$path;
			if (file_exists($data_path))
			$item = unserialize(file_get_contents($data_path));
	return $item;
	}
	
	public static function save_item($path, $item, &$plugin_cookies) {
	$data_path = DemoConfig::get_altiptv_data_path(&$plugin_cookies);
	$link = $data_path . '/data_file/'. $path;
						$data = fopen($link,"w");
						if (!$data)
							{
							return ActionFactory::show_title_dialog("Не могу записать items Что-то здесь не так!!!");
							}
						fwrite($data, $item);
						@fclose($data);
	}
	public static function get_item($path,&$plugin_cookies) {
	$item = '';
	$data_path = DemoConfig::get_altiptv_data_path(&$plugin_cookies);
	$link = $data_path . '/data_file/'. $path;
			if (file_exists($link))
			$item = file_get_contents($link);
	return $item;
	}
	public static function alphabet()
    {
        return array(
		'0' => '0', '1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6', '7' => '7', '8' => '8', '9' => '9', 
		'А' => 'А', 'Б' => 'Б', 'В' => 'В', 'Г' => 'Г', 'Д' => 'Д', 'Е' => 'Е', 'Ё' => 'Ё', 'Ж' => 'Ж', 'З' => 'З', 'И' => 'И', 'Й' => 'Й', 'К' => 'К', 'Л' => 'Л', 'М' => 'М', 'Н' => 'Н', 'О' => 'О', 'П' => 'П', 'Р' => 'Р', 'С' => 'С', 'Т' => 'Т', 'У' => 'У', 'Ф' => 'Ф', 'Х' => 'Х', 'Ц' => 'Ц', 'Ч' => 'Ч', 'Ш' => 'Ш', 'Щ' => 'Щ', 'Ъ' => 'Ъ', 'Ы' => 'Ы', 'Ь' => 'Ь', 'Э' => 'Э', 'Ю' => 'Ю', 'Я' => 'Я', 
		'A' => 'A', 'B' => 'B', 'C' => 'C', 'D' => 'D', 'E' => 'E', 'F' => 'F', 'G' => 'G', 'H' => 'H', 'I' => 'I', 'J' => 'J', 'K' => 'K', 'L' => 'L', 'M' => 'M', 'N' => 'N', 'O' => 'O', 'P' => 'P', 'Q' => 'Q', 'R' => 'R', 'S' => 'S', 'T' => 'T', 'U' => 'U', 'V' => 'V', 'W' => 'W', 'X' => 'X', 'Y' => 'Y', 'Z' => 'Z',
		);
    }
	public static function translit($str) {
    $str = preg_replace('/[-`~!#$%^&*()_=+\\\\|\\/\\[\\]{};:"\',<>?]+/','',$str);
    $rus = array(' ','А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ъ', 'Ы', 'Ь', 'Э', 'Ю', 'Я', 'а', 'б', 'в', 'г', 'д', 'е', 'ё', 'ж', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ч', 'ш', 'щ', 'ъ', 'ы', 'ь', 'э', 'ю', 'я');
    $lat = array('_','A', 'B', 'V', 'G', 'D', 'E', 'E', 'Gh', 'Z', 'I', 'Y', 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'F', 'H', 'C', 'Ch', 'Sh', 'Sch', 'Y', 'Y', 'Y', 'E', 'Yu', 'Ya', 'a', 'b', 'v', 'g', 'd', 'e', 'e', 'gh', 'z', 'i', 'y', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'h', 'c', 'ch', 'sh', 'sch', 'y', 'y', 'y', 'e', 'yu', 'ya');
    return str_replace($rus, $lat, $str);
	}
    public static function get_rec_path(&$plugin_cookies) {
		$recdata = isset($plugin_cookies->recdata) ?
		$plugin_cookies->recdata : '/D';
		$recdata_dir = isset($plugin_cookies->recdata_dir) ?
		$plugin_cookies->recdata_dir : '/';
		if ($recdata !== '1')
		$rec_path = $recdata.$recdata_dir;
		elseif ($recdata == '1') {
			$recdata_smb_user = isset($plugin_cookies->recdata_smb_user) ?
			$plugin_cookies->recdata_smb_user : 'guest';
			$recdata_smb_pass = isset($plugin_cookies->recdata_smb_pass) ?
			$plugin_cookies->recdata_smb_pass : 'guest';
			$recdata_ip_path = isset($plugin_cookies->recdata_ip_path) ?
			$plugin_cookies->recdata_ip_path : '';
			if ($recdata_ip_path == '')
				$rec_path = '/D/';
			else
				$rec_path = self::get_mount_smb_path($recdata_ip_path, $recdata_smb_user, $recdata_smb_pass, 'recdata_path');
		}
		return $rec_path;
	}
	public static function get_day_epg($channel_id, $day_start_ts, &$plugin_cookies)
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
	ksort($epg, SORT_NUMERIC);
	return $epg;
    }}
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
	ksort($epg, SORT_NUMERIC);
	return $epg;
    }}
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
	preg_match_all('|<div id="programm_text">(.*?)</div>|', $doc, $keywords);
	foreach ($keywords[1] as $key => $qid){
	$qq = strip_tags($qid);
	preg_match_all('|(\d\d:\d\d)&nbsp;(.*?)&nbsp;(.*)|', $qq, $keyw);
	$time = $keyw[1][0];
	$u_time = strtotime("$epg_date $time EEST");
	$last_time = ($u_time < $e_time) ? $u_time + 86400  : $u_time ;
	$epg[$last_time]["name"] = str_replace("&nbsp;", " ",$keyw[2][0]);
	$epg[$last_time]["desc"] = str_replace("&nbsp;", " ", $keyw[3][0]);
	}
	ksort($epg, SORT_NUMERIC);
	return $epg;
    }}
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
				$time = (strtotime($item['anfangsdatum']." CEST"))+$epg_shift;
				$epg[$time]['name'] = 	$item['titel'];
				$epg[$time]['desc'] = "{$item['genre']} {$item['jahr']} {$item['land']}";
			}
	ksort($epg, SORT_NUMERIC);
	return $epg;
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
	try {
            $doc = HD::http_get_document("http://tv.mail.ru/ajax/channel/?channel_id=$mail_id&period=all&date=$epg_date" );
	}
	catch (Exception $e) {
	    hd_print("Can't fetch EPG ID:$mail_id DATE:$epg_date");
	    return array();
	}

	$last_time = 0;
    $jsd = json_decode($doc, true);
		foreach ($jsd['schedule'][0]['event'] as $value)
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
	ksort($epg, SORT_NUMERIC);
	return $epg;
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
		$e_time = strtotime("$epg_date, 0500 EEST");
		if ($e_time > $u_time)
		$u_time = $u_time + 86400;
		$last_time = $u_time + $epg_shift;
	    $epg[$last_time]["name"] = $name;
	    $epg[$last_time]["desc"] = $desc;
	    }
	ksort($epg, SORT_NUMERIC);
	return $epg;
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
	$e_time = strtotime("$epg_date, 0500 EEST");
	if ($e_time > $u_time)
	$u_time = $u_time + 86400;
	$last_time = $u_time + $epg_shift;
	$epg[$last_time]["name"] = $name;
	$epg[$last_time]["desc"] = $desc;}
	ksort($epg, SORT_NUMERIC);
	return $epg;
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
                    $parsed_ndx = unpack("x2/V2time/voffset/", substr($ndx_data, 2 + $i * 12, 12));  //time1, time2, offset
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

                //fill epg
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
                //hd_print("schedule parsed for ".($ts4-$ts3));
            }

            $epg = $this->parsed_schedule[$num];
        }
	ksort($epg, SORT_NUMERIC);
	return $epg;
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
	}
	ksort($epg, SORT_NUMERIC);

	return $epg;
	}
	if ($epg_type == 1)
	{

    $epg_shift = isset($plugin_cookies->epg_shift) ? $plugin_cookies->epg_shift : '0';
	$epg_shift_pl = ($epg_shift_pl - 24) * 3600;
	$epg_shift = $epg_shift + $epg_shift_pl;
    $epg_date = date("Y-m-d", $day_start_ts);
    $epg = array();
	try {
				$doc = iconv('WINDOWS-1251', 'UTF-8', self::http_get_document("http://www.vsetv.com/schedule_channel_".$channel_id."_day_".$epg_date."_nsc_1.html"));
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
		$e_time = strtotime("$epg_date, 0500 EEST");
		if ($e_time > $u_time)
		$u_time = $u_time + 86400;
		$last_time = $u_time + $epg_shift;
	    $epg[$last_time]["name"] = $name;
	    $epg[$last_time]["desc"] = $desc;
	    }
	ksort($epg, SORT_NUMERIC);
	return $epg;
    }
	}
	public static function get_codec_start_info()
		{	
			$check = shell_exec('ps | grep httpd | grep -c 81');
			if ( $check <= 1){
				shell_exec("httpd -h /codecpack/WWW -p 81");
				usleep(500000);
			}
		}
}

///////////////////////////////////////////////////////////////////////////
?>
