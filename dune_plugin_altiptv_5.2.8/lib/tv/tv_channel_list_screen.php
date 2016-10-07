<?php
///////////////////////////////////////////////////////////////////////////

require_once 'lib/abstract_preloaded_regular_screen.php';
require_once 'lib/abstract_controls_screen.php';
///////////////////////////////////////////////////////////////////////////

class TvChannelListScreen extends AbstractPreloadedRegularScreen
    implements UserInputHandler
{
    const ID = 'tv_channel_list';

    public static function get_media_url_str($group_id)
    {
        return MediaURL::encode(
            array
            (
                'screen_id' => self::ID,
                'group_id'  => $group_id,
            ));
    }

    ///////////////////////////////////////////////////////////////////////

    protected $tv;

    ///////////////////////////////////////////////////////////////////////

    public function __construct(Tv $tv, $folder_views)
    {
        parent::__construct(self::ID, $folder_views);

        $this->tv = $tv;

        UserInputHandlerRegistry::get_instance()->register_handler($this);
    }

    ///////////////////////////////////////////////////////////////////////

    public function get_action_map(MediaURL $media_url, &$plugin_cookies)
    {
        $actions = array();
        $actions[GUI_EVENT_KEY_ENTER] = ActionFactory::tv_play();
        $actions[GUI_EVENT_KEY_PLAY] = ActionFactory::tv_play();

        if ($this->tv->is_favorites_supported())
        {
            $add_favorite_action =
                UserInputHandlerRegistry::create_action(
                    $this, 'add_favorite');
            $add_favorite_action['caption'] = 'Добавить в Избранное'; }
            $popup_menu_action =
                UserInputHandlerRegistry::create_action(
                    $this, 'popup_menu');
			$open_settings =
				ActionFactory::open_folder(
					DemoSetupScreen::get_media_url_str());
			$open_settings['caption'] = 'Настройки';
			$add_proxy_action =
                UserInputHandlerRegistry::create_action(
                    $this, 'add_proxy');
            $add_proxy_action['caption'] = 'Поиск прокси (ренд.)';
			$add_proxy_count_action =
                UserInputHandlerRegistry::create_action(
                    $this, 'add_proxy_count');
            $add_proxy_count_action['caption'] = 'Поиск прокси (посл.)';
			$background_rec_action =
                UserInputHandlerRegistry::create_action(
                    $this, 'background_rec');
            $background_rec_action['caption'] = 'Фоновая Запись канала';
			$info_back_scan_action =
                UserInputHandlerRegistry::create_action(
                    $this, 'info_back_scan');
			$ad_vsetvid =
                UserInputHandlerRegistry::create_action(
                    $this, 'vsetvid');
			$cleer_action =
                UserInputHandlerRegistry::create_action(
                    $this, 'hide_ch');
			$actions[GUI_EVENT_KEY_B_GREEN] = $add_proxy_action;
			$actions[GUI_EVENT_KEY_C_YELLOW] = $add_proxy_count_action;
            $actions[GUI_EVENT_KEY_D_BLUE] = $open_settings;
			$actions[GUI_EVENT_KEY_SETUP] = $open_settings;
			$actions[GUI_EVENT_KEY_REC] = $background_rec_action;
            $actions[GUI_EVENT_KEY_POPUP_MENU] = $popup_menu_action;
			$actions[GUI_EVENT_KEY_CLEAR] = $cleer_action;
			$actions[GUI_EVENT_KEY_SEARCH] = $info_back_scan_action;
			$actions[GUI_EVENT_KEY_SELECT] = $ad_vsetvid;
			$actions[GUI_EVENT_KEY_INFO] = UserInputHandlerRegistry::create_action(
                $this, 'info');

        return $actions;
    }

    public function get_handler_id()
    { return self::ID; }

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

    public function handle_user_input(&$user_input, &$plugin_cookies)
    {
        $altiptv_data_path = DemoConfig::get_altiptv_data_path(&$plugin_cookies);

		$m3u_dir = isset($plugin_cookies->m3u_dir) ?
			$plugin_cookies->m3u_dir : '/D';
		$m3u_type = isset($plugin_cookies->m3u_type) ?
            $plugin_cookies->m3u_type : '1';
		$ip_path = isset($plugin_cookies->ip_path) ?
			$plugin_cookies->ip_path : '';
		$smb_user = isset($plugin_cookies->smb_user) ?
			$plugin_cookies->smb_user : 'guest';
		$smb_pass = isset($plugin_cookies->smb_pass) ?
			$plugin_cookies->smb_pass : 'guest';
		$group_tv = isset($plugin_cookies->group_tv) ?
			$plugin_cookies->group_tv : '1';

        if ($user_input->control_id == 'info')
        {
			if (!isset($user_input->selected_media_url))
                return null;
			$rec_type = isset($plugin_cookies->rec_type) ?
			$plugin_cookies->rec_type : 0;
			$doc = file_get_contents('/config/settings.properties');
			if (preg_match('/time_zone =(.*)\s/', $doc, $matches)) {
			$tmp = explode(':', $matches[1]);
			$rec_shift =($tmp[0] * 3600 ) + ($tmp[1] * 60 );}
            $media_url = MediaURL::decode($user_input->selected_media_url);
            $channel_id = $media_url->channel_id;
			$chid = $channel_id;
            $channels = $this->tv->get_channels();
            $c = $channels->get($channel_id);
            $id = $c->get_id();
            $title = $c->get_title();
			$streaming_url = $c->streaming_url();
			$epg_date = date("Y-m-d");
			$stream_url = $epg_date; //substr($streaming_url,0,55);
            $post_action = null;
			list($garb, $epg_shift_pl, $channel_id) = preg_split('/_/', $channel_id);
			
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
			return ActionFactory::show_dialog("ТВ программа: $title [$stream_url] ", $defs, 1);
        }
        else if ($user_input->control_id == 'popup_menu')
        {
            if (!isset($user_input->selected_media_url))
                return null;

            $media_url = MediaURL::decode($user_input->selected_media_url);
            $channel_id = $media_url->channel_id;
			$bgr_rs = DuneSystem::$properties['tmp_dir_path'] . '/background_rec_stop';
			if (file_exists($bgr_rs)) {
			$name = trim(file_get_contents($bgr_rs));
			$dd = "/tmp/".$name."_iptvrecord.sh";
			if (!file_exists($dd))
				unlink($bgr_rs);
			}
            $is_favorite = $this->tv->is_favorite_channel_id($channel_id, $plugin_cookies);
            $add_favorite_action =
                UserInputHandlerRegistry::create_action(
                    $this, 'add_favorite');
            $caption = 'Добавить в Избранное';
			$new_count_action =
                UserInputHandlerRegistry::create_action(
                    $this, 'new_count');
            $new_count_caption = 'Сканировать этот плейлист с ...';
			$one_rec_action =
                UserInputHandlerRegistry::create_action(
                    $this, 'one_rec');
            $one_rec_caption = 'Расписание записи каналов';

			$new_port_action =
                UserInputHandlerRegistry::create_action(
                    $this, 'new_port');
            $new_port_caption = 'Сканировать порт ...';
			$back_scan_action =
                UserInputHandlerRegistry::create_action(
                    $this, 'back_scan');
            $back_scan_caption = 'Фоновое сканирование';
			$new_rec_action =
                UserInputHandlerRegistry::create_action(
                    $this, 'new_rec');
            $new_rec_caption = 'Запись канала по таймеру';
			$background_rec_action =
                UserInputHandlerRegistry::create_action(
                    $this, 'background_rec');
            $background_rec_caption = 'Фоновая Запись канала';
			$background_stoprec_action =
                UserInputHandlerRegistry::create_action(
                    $this, 'background_stoprec');
            $background_stoprec_caption = 'Остановить Запись';
			$new_vsetvid_action =
                UserInputHandlerRegistry::create_action(
                    $this, 'vsetvid');
            $new_vsetvid_caption = 'Задать каналу id vsetv';
			$kill_vsetvid_action =
                UserInputHandlerRegistry::create_action(
                    $this, 'killvsetvid');
            $kill_vsetvid_caption = 'Удалить id vsetv канала';
			$add_parental_action =
                UserInputHandlerRegistry::create_action(
                    $this, 'add_parental');
            $add_parental_caption = 'Закрыть канал (Parental)';
			$kill_parental_action =
                UserInputHandlerRegistry::create_action(
                    $this, 'kill_parental');
            $kill_parental_caption = 'Открыть канал (Parental)';
			$reboot_pl_action =
                UserInputHandlerRegistry::create_action(
                    $this, 'cleer_folder_key');
            $reboot_pl_caption = 'Обновить список каналов';
			$rename_ch_action =
                UserInputHandlerRegistry::create_action(
                    $this, 'rename_ch');
            $rename_ch_caption = 'Переименовать канал';
			$delete_ch_action =
                UserInputHandlerRegistry::create_action(
                    $this, 'delete_ch');
            $delete_ch_caption = 'Удалить канал';
			$hide_ch_action =
                UserInputHandlerRegistry::create_action(
                    $this, 'hide_ch');
            $hide_ch_caption = 'Скрыть канал';
			$hide_list_action =
                UserInputHandlerRegistry::create_action(
                    $this, 'hide_list');
            $hide_list_caption = 'Список скрытых каналов';
			$change_grup_action =
                UserInputHandlerRegistry::create_action(
                    $this, 'change_grup');
            $change_grup_caption = 'Изменить группу канала';
			$user_agent_action =
                UserInputHandlerRegistry::create_action(
                    $this, 'add_user_agent');
			$user_agent_caption = 'Задать каналу User Agent';

			$menu_items[] = array(
                GuiMenuItemDef::caption => $caption,
                GuiMenuItemDef::action => $add_favorite_action);
			$menu_items [] =  array(
				GuiMenuItemDef::is_separator => true,);
			if ($group_tv != 5){
			$menu_items[] = array(
                GuiMenuItemDef::caption => $background_rec_caption,
                GuiMenuItemDef::action => $background_rec_action);
			if (file_exists($bgr_rs)) {
			$menu_items[] = array(
                GuiMenuItemDef::caption => $background_stoprec_caption,
                GuiMenuItemDef::action => $background_stoprec_action);}
			$menu_items[] = array(
                GuiMenuItemDef::caption => $new_rec_caption,
                GuiMenuItemDef::action => $new_rec_action);
			$menu_items[] = array(
                GuiMenuItemDef::caption => $one_rec_caption,
                GuiMenuItemDef::action => $one_rec_action);
			$menu_items [] =  array(
				GuiMenuItemDef::is_separator => true,);
			$menu_items[] = array(
				GuiMenuItemDef::caption => $new_count_caption,
                GuiMenuItemDef::action => $new_count_action);
			$menu_items[] = array(
				GuiMenuItemDef::caption => $new_port_caption,
                GuiMenuItemDef::action => $new_port_action);
			$menu_items[] = array(
				GuiMenuItemDef::caption => $back_scan_caption,
                GuiMenuItemDef::action => $back_scan_action);
			$menu_items [] =  array(
				GuiMenuItemDef::is_separator => true,);
			}
			$menu_items [] =  array(
				GuiMenuItemDef::is_separator => true,);
			$menu_items[] = array(
                GuiMenuItemDef::caption => $new_vsetvid_caption,
                GuiMenuItemDef::action => $new_vsetvid_action);
			$menu_items[] = array(
                GuiMenuItemDef::caption => $kill_vsetvid_caption,
                GuiMenuItemDef::action => $kill_vsetvid_action);
			if (($group_tv == 2)||($group_tv == 5)){
			$menu_items[] = array(
                GuiMenuItemDef::caption => $change_grup_caption,
                GuiMenuItemDef::action => $change_grup_action);}
			$menu_items[] = array(
                GuiMenuItemDef::caption => $add_parental_caption,
                GuiMenuItemDef::action => $add_parental_action);
			$menu_items[] = array(
                GuiMenuItemDef::caption => $kill_parental_caption,
                GuiMenuItemDef::action => $kill_parental_action);
			$menu_items [] =  array(
				GuiMenuItemDef::is_separator => true,);
			if ($group_tv != 5){
			$menu_items[] = array(
                GuiMenuItemDef::caption => $rename_ch_caption,
                GuiMenuItemDef::action => $rename_ch_action);
			}
			$menu_items[] = array(
                GuiMenuItemDef::caption => $hide_ch_caption,
                GuiMenuItemDef::action => $hide_ch_action,
				//GuiMenuItemDef::icon_url => 'gui_skin://special_icons/off.aai'
				);
			$menu_items[] = array(
                GuiMenuItemDef::caption => $hide_list_caption,
                GuiMenuItemDef::action => $hide_list_action);
			if ($group_tv != 5){
			$menu_items[] = array(
                GuiMenuItemDef::caption => $delete_ch_caption,
                GuiMenuItemDef::action => $delete_ch_action);
			$menu_items [] =  array(
				GuiMenuItemDef::is_separator => true,);
			$menu_items[] = array(
				GuiMenuItemDef::caption => $user_agent_caption,
                GuiMenuItemDef::action => $user_agent_action);
			}
			$menu_items [] =  array(
				GuiMenuItemDef::is_separator => true,);
			$menu_items[] = array(
                GuiMenuItemDef::caption => $reboot_pl_caption,
                GuiMenuItemDef::action => $reboot_pl_action);
            return ActionFactory::show_popup_menu($menu_items);
        }
		else if ($user_input->control_id == 'new_count_apply')
			{
				if ($user_input->count == '')
				$new_count=0;
				else
				$new_count=$user_input->count;
			if (!isset($user_input->selected_media_url))
                return null;
            $media_url = MediaURL::decode($user_input->selected_media_url);
            $caption_g = $media_url->caption_g;
			if ($m3u_type==3)
			return ActionFactory::show_title_dialog("Для плейлистов по ссылке (http) поиска прокси нет!");
			else
			{
			if (file_exists(DuneSystem::$properties['tmp_dir_path'].'/' . $caption_g . '.cnt')) {
			$count_file = DuneSystem::$properties['tmp_dir_path'].'/' . $caption_g . '.cnt';
			$date_count = fopen($count_file,"w");
			if (!$date_count)
				return ActionFactory::show_title_dialog("Не могу записать в tmp Что-то здесь не так!!!");
			fwrite($date_count, $new_count);
			@fclose($date_count);
			return ActionFactory::show_title_dialog("Счетчик сканирования для этого плейлиста изменен!!!");
			}
			return ActionFactory::show_title_dialog("У этого плейлиста нет счетчика!!!");
			}
			}
		else if ($user_input->control_id == 'rename_ch_apply')
				{
			if (!isset($user_input->selected_media_url))
                return null;
			$newname=$user_input->newname;
			if ($newname == '')
			return ActionFactory::show_title_dialog("Новое имя канала не может быть пустым!");
            $media_url = MediaURL::decode($user_input->selected_media_url);
            $caption_g = $media_url->caption_g;
			$channel_id = $media_url->channel_id;
            $channels = $this->tv->get_channels();
            $c = $channels->get($channel_id);
            $id = $c->get_id();
            $title = $c->get_title();
			$streaming_url = $c->streaming_url();
			if ($m3u_type==7)
			$m3u_dir = HD::get_mount_smb_path($ip_path, $smb_user, $smb_pass, 'm3u_dir');
			if ($m3u_type==3)
			return ActionFactory::show_title_dialog("Для плейлистов по ссылке (http) изменить имя канала не возможно!");
			else
			{
			$m3u_file = $m3u_dir . '/' . $caption_g;
			if ($m3u_type==1)
			$m3u_file = $altiptv_data_path . '/playlists/' . $caption_g;
			$daten = file_get_contents($m3u_file);
			if (!$daten) {
			hd_print("НЕ МОГУ ОТКРЫТЬ PLAYLIST:$m3u_file");
			return ActionFactory::show_title_dialog("Плейлист не найден (перемещен или удален)!!!");
			}
			$order   = array("\r\n","\r");
			$replace = "\n";
			$daten = str_replace($order, $replace, $daten);
			$str_url = preg_quote($streaming_url);
			$tit = preg_quote($title);
			$old_capt = "|$tit\s*\n$str_url|";
			$new_capt = "$newname\n$streaming_url";
			$daten = preg_replace($old_capt, $new_capt, $daten);
			$dateihandle1 = fopen($m3u_file,"w");
			if (!$dateihandle1)
			hd_print("НЕ МОГУ ЗАПИСАТЬ НА USB/HDD");
			fwrite($dateihandle1, $daten);
			@fclose($dateihandle1);
			}
			$perform_new_action = UserInputHandlerRegistry::create_action(
                    $this, 'cleer_folder');
			return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);
				}
		else if ($user_input->control_id == 'delete_ch_apply')
				{
			if (!isset($user_input->selected_media_url))
                return null;
            $media_url = MediaURL::decode($user_input->selected_media_url);
            $caption_g = $media_url->caption_g;
			$channel_id = $media_url->channel_id;
            $channels = $this->tv->get_channels();
            $c = $channels->get($channel_id);
            $id = $c->get_id();
            $title = $c->get_title();
			$streaming_url = $c->streaming_url();
			if ($m3u_type==7)
			$m3u_dir = HD::get_mount_smb_path($ip_path, $smb_user, $smb_pass, 'm3u_dir');
			if ($m3u_type==3)
			return ActionFactory::show_title_dialog("Для плейлистов по ссылке (http) удалить канал не возможно!");
			else
			{
			$m3u_file = $m3u_dir . '/' . $caption_g;
			if ($m3u_type==1)
			$m3u_file = $altiptv_data_path . '/playlists/' . $caption_g;
			$daten = file_get_contents($m3u_file);
			if (!$daten) {
			hd_print("НЕ МОГУ ОТКРЫТЬ PLAYLIST:$m3u_file");
			return ActionFactory::show_title_dialog("Плейлист не найден (перемещен или удален или http)!!!");
			}
			$order   = array("\r\n","\r");
			$replace = "\n";
			$str_url = preg_quote($streaming_url);
			$tit = preg_quote($title);
			$q = preg_quote("name=\"$tit\"");
			if (preg_match("|$q|",$daten))
			$old_capt = "|#EXTINF.*name=\"$tit\".*$tit\s*\n$str_url|";
			else
			$old_capt = "|#EXTINF.*$tit\s*\n$str_url\s*\n?|";
			$daten = preg_replace($old_capt, '', $daten);
			$dateihandle1 = fopen($m3u_file,"w");
			if (!$dateihandle1)
			hd_print("НЕ МОГУ ЗАПИСАТЬ НА USB/HDD");
			fwrite($dateihandle1, $daten);
			@fclose($dateihandle1);
			}
			$perform_new_action = UserInputHandlerRegistry::create_action(
                    $this, 'cleer_folder');
			return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);
				}
		else if ($user_input->control_id == 'new_port_apply')
			{
				if ($user_input->port == '')
				$new_port=0;
				else
				$new_port=$user_input->port;
			if (!isset($user_input->selected_media_url))
                return null;
            $media_url = MediaURL::decode($user_input->selected_media_url);
            $caption_g = $media_url->caption_g;

			if ($m3u_type==7)
				$m3u_dir = HD::get_mount_smb_path($ip_path, $smb_user, $smb_pass, 'm3u_dir');
			if ($m3u_type==3)
				return ActionFactory::show_title_dialog("Для плейлистов по ссылке (http) поиска прокси нет!");
			else
			{
				$port_file = DuneSystem::$properties['tmp_dir_path'].'/'  . $caption_g . '.prt';
				$date_port = fopen($port_file,"w");
				if (!$date_port)
					{
						hd_print("НЕ МОГУ записать port_file:$port_file");
						return ActionFactory::show_title_dialog("Не могу записать в tmp Что-то здесь не так!!!");
					}
				fwrite($date_port, $new_port);
				@fclose($date_port);
				return ActionFactory::show_title_dialog("Порт сканирования для этого плейлиста изменен!!!");
			}
			}
		else if ($user_input->control_id == 'del_vsetvid_apply')
				{
					if (!isset($user_input->selected_media_url))
					return null;
					$vsetvid = $user_input->vsetvid3;
					if (($vsetvid == '') || ($vsetvid == 0) || ($vsetvid > 40000) || (!is_numeric($vsetvid))){
						ControlFactory::add_label($defs, "Внимание:", 'Удаляются только те каналы которым вы');
						ControlFactory::add_label($defs, "", 'присваивали id через popup задать id vsetv');
						ControlFactory::add_close_dialog_button($defs,'Ок', 350);
						return ActionFactory::show_dialog('Необходимо указать vsetv ID от 1 до 40000!!!', $defs, 1);
					}
					$my_channels_id = HD::get_items('my_channels_id', &$plugin_cookies);
					$key_id = array_search($vsetvid, $my_channels_id);
					if ($key_id == true){
						unset ($my_channels_id[$key_id]);
						HD::save_items('my_channels_id', $my_channels_id, &$plugin_cookies);
					}
					
					$texts = HD::get_items('vsetv_list', &$plugin_cookies);
					$key = array_search($vsetvid, $texts);
						if ($key == true){
							unset ($texts [$key]);
							HD::save_items('vsetv_list', $texts, &$plugin_cookies);
							if (file_exists($altiptv_data_path . "/logo/$vsetvid.png"))
								unlink($altiptv_data_path . "/logo/$vsetvid.png");
						}
					$perform_new_action = UserInputHandlerRegistry::create_action($this, 'cleer_folder');
					return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);
				}
		else if ($user_input->control_id == 'new_vsetvid_apply')
			{
				if (!isset($user_input->selected_media_url))
					return null;
				$media_url = MediaURL::decode($user_input->selected_media_url);
				$channel_id = $media_url->channel_id;
				$channels = $this->tv->get_channels();
				$c = $channels->get($channel_id);
				$title = $c->get_title();
				list($garb, $epg_shift_pl, $channel_id) = preg_split('/_/', $channel_id);
				if(preg_match("/%/", $channel_id)){
					$tmp = explode('%', $channel_id);
					$channel_id = $tmp[0];
				}
				$qb =
				$qb2 =
				$qb3 =
				$qb4 = 0;
				if (isset($user_input->vsetvid))
					$qb = $user_input->vsetvid;
				if (isset($user_input->vsetvid2))
					$qb2 = $user_input->vsetvid2;
				if (isset($user_input->vsetvid3))
					$qb3 = $user_input->vsetvid3;
				if (isset($user_input->vsetvid4))
					$qb4 = $user_input->vsetvid4;
				$qq = array($qb,$qb2, $qb3, $qb4);
				foreach($qq as $key => $q)
					if ($q == intval($channel_id))
						unset($qq[$key]);
				$vsetv_id = max($qq);
				if ((isset($user_input->chnmbr))&&($user_input->chnmbr != '')){
					$chnmbr_list = HD::get_items('chnmbr_list', &$plugin_cookies);
					if ($user_input->chnmbr==0){
						if ($vsetv_id == '')
							$chnmbr = array_search($channel_id, $chnmbr_list);
						else
							$chnmbr = array_search($vsetvid, $chnmbr_list);
					unset ($chnmbr_list[$chnmbr]);
					HD::save_items('chnmbr_list', $chnmbr_list, &$plugin_cookies);
					$perform_new_action = UserInputHandlerRegistry::create_action(
						$this, 'cleer_folder');
					return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);
					}
					if ($vsetv_id == '')
						$chnmbr_list[$user_input->chnmbr] = $channel_id;
					else
						$chnmbr_list[$user_input->chnmbr] = $vsetv_id;
					HD::save_items('chnmbr_list', $chnmbr_list, &$plugin_cookies);
					$perform_new_action = UserInputHandlerRegistry::create_action(
						$this, 'cleer_folder');
					return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);
				}
				if (($vsetv_id == '') || ($vsetv_id == 0) || ($vsetv_id > 40000) || (!is_numeric($vsetv_id))){
					ControlFactory::add_multiline_label($defs, '', 'vsetv ID от 1 до 20000 присваиваются  известным плагину каналам для получения ТВ программы (EPG) и присваивания иконок. vsetv ID от 20000 присваиваются  не известным плагину каналам для присваивания иконоки каналу Для добавления ID больше 20000 необходима иконка (вида vsetvid.png) в плагине или накопителе', 10);
					ControlFactory::add_close_dialog_button($defs,'Ок', 350);
					return ActionFactory::show_dialog('Необходимо указать vsetv ID от 20000!!!', $defs,true ,1100);
				}else
					$vsetvid=$vsetv_id;
				if ($vsetvid > 20000){
					if ((!file_exists("/D/$vsetvid.png")) && (!file_exists($altiptv_data_path  . "/logo/$vsetvid.png")))
					return ActionFactory::show_title_dialog("Для добавления ID больше 20000 необходима иконка в плагине или накопителе");
					if (file_exists("/D/$vsetvid.png"))
						shell_exec("cp -f /D/$vsetvid.png " . $altiptv_data_path . "/logo/$vsetvid.png");
					$texts = HD::get_items('vsetv_list', &$plugin_cookies);
					$key = array_search($vsetvid, $texts);
					if ($key == false){
						$texts [$title] = $vsetvid;
						HD::save_items('vsetv_list', $texts, &$plugin_cookies);
					}
				}
				$captions = mb_strtolower($title, 'UTF-8');
				$captions = str_replace(array(" ", "-", ".", "\r", "\n", "\"", " "), '', $captions);
				$id_key = md5($captions);
				$my_channels_id = HD::get_items('my_channels_id', &$plugin_cookies);
				$my_channels_id [$id_key] = $vsetvid;
				HD::save_items('my_channels_id', $my_channels_id, &$plugin_cookies);
				$perform_new_action = UserInputHandlerRegistry::create_action(
						$this, 'cleer_folder');
				return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);
			}
		else if ($user_input->control_id == 'alfavit_vsetvid_apply')
			{
			if (!isset($user_input->selected_media_url))
					return null;
			$alfavit = $user_input->alfavit;
			HD::save_item('alfavit_item', $alfavit,&$plugin_cookies);
			$defs = array();
			$vsetvid= 0;
			$altiptv_data_path = DemoConfig::get_altiptv_data_path(&$plugin_cookies);
			$altiptv_data_path = DemoConfig::get_altiptv_data_path(&$plugin_cookies);
			$texts = HD::get_items('vsetv_list', &$plugin_cookies);
			$ch_ops = array();
			$ch_ops[0] = 'Выбор';
			foreach($texts as $key => $value){
				if (preg_match("|^$alfavit|i", $key))
					$ch_ops[$value] = "$key => $value";
			}
        ControlFactory::add_combobox($defs, $this, null,
            'vsetvid2', 'Выбор канала:',
            $vsetvid, $ch_ops, 400, $need_confirm = false, $need_apply = false
        );
		$do_port_apply = UserInputHandlerRegistry::create_action($this, 'new_vsetvid_apply');
        ControlFactory::add_custom_close_dialog_and_apply_buffon($defs,
		'_do_vsetvid_apply', 'Применить', 350, $do_port_apply);

        return ActionFactory::show_dialog(
                            "Каналы на букву - \"$alfavit\"",
                            $defs,
								true);
			}
        else if ($user_input->control_id == 'add_favorite')
        {
            if (!isset($user_input->selected_media_url))
                return null;

            $media_url = MediaURL::decode($user_input->selected_media_url);
            $channel_id = $media_url->channel_id;

            $is_favorite = $this->tv->is_favorite_channel_id($channel_id, $plugin_cookies);
            if ($is_favorite)
            {
                return ActionFactory::show_title_dialog(
                    'Канал уже в Избранном',
                    $this->get_sel_item_update_action(
                        $user_input, $plugin_cookies));
            }
            else
            {
                $this->tv->change_tv_favorites(PLUGIN_FAVORITES_OP_ADD,
                    $channel_id, $plugin_cookies);

                return ActionFactory::show_title_dialog(
                    'Канал добавлен в Избранное',
                    $this->get_sel_item_update_action(
                        $user_input, $plugin_cookies));
            }
        }
		else if ($user_input->control_id == 'reset_count')
        {
            if (!isset($user_input->selected_media_url))
                return null;
            $media_url = MediaURL::decode($user_input->selected_media_url);
            $caption_g = $media_url->caption_g;
			if ($m3u_type==3)
			return ActionFactory::show_title_dialog("Для плейлистов по ссылке (http) поиска прокси нет!");
			else
			{
			if (file_exists(DuneSystem::$properties['tmp_dir_path'].'/'  . $caption_g . '.cnt')) {
			$count_file = DuneSystem::$properties['tmp_dir_path'].'/'  . $caption_g . '.cnt';
			$new_count = 0;
			$date_count = fopen($count_file,"w");
			if (!$date_count)
				return ActionFactory::show_title_dialog("Не могу записать в tmp Что-то здесь не так!!!");
			fwrite($date_count, $new_count);
			@fclose($date_count);
			return ActionFactory::show_title_dialog("Счетчик сканирования для этого плейлиста cброшен!!!");
			}
			return ActionFactory::show_title_dialog("У этого плейлиста нет счетчика!!!");
			}
		}
		else if ($user_input->control_id == 'reset_port')
        {
            if (!isset($user_input->selected_media_url))
                return null;
            $media_url = MediaURL::decode($user_input->selected_media_url);
            $caption_g = $media_url->caption_g;
			if ($m3u_type==3)
			return ActionFactory::show_title_dialog("Для плейлистов по ссылке (http) поиска прокси нет!");
			else
			{
			if (file_exists(DuneSystem::$properties['tmp_dir_path'].'/'  . $caption_g . '.prt')) {
			$count_file = DuneSystem::$properties['tmp_dir_path'].'/'  . $caption_g . '.prt';
			$new_count = 0;
			$date_count = fopen($count_file,"w");
			if (!$date_count)
				return ActionFactory::show_title_dialog("Не могу записать в tmp Что-то здесь не так!!!");
			fwrite($date_count, $new_count);
			@fclose($date_count);
			return ActionFactory::show_title_dialog("Порт сканирования для этого плейлиста cброшен!!!");
			}
			return ActionFactory::show_title_dialog("Для этого плейлиста порт не указан!!!");
			}
		}

		else if ($user_input->control_id == 'vsetvid')
			{
				if (!isset($user_input->selected_media_url))
					return null;
				$media_url = MediaURL::decode($user_input->selected_media_url);
				$channel_id = $media_url->channel_id;
				$channels = $this->tv->get_channels();
				$c = $channels->get($channel_id);
				$title = $c->get_title();
				list($garb, $epg_shift_pl, $channel_id) = preg_split('/_/', $channel_id);
				if(preg_match("/%/", $channel_id)){
					$tmp = explode('%', $channel_id);
					$channel_id = $tmp[0];
				}
				$defs = $this->do_get_new_vsetvid_defs($title, $channel_id, $plugin_cookies);
				return  ActionFactory::show_dialog ("Задать vsetv ID для: $title",$defs,true);
			}
		else if ($user_input->control_id == 'killvsetvid')
			{
			if (!isset($user_input->selected_media_url))
                return null;
            $media_url = MediaURL::decode($user_input->selected_media_url);
            $channel_id = $media_url->channel_id;
            $channels = $this->tv->get_channels();
            $c = $channels->get($channel_id);
            $title = $c->get_title();
			$captions = mb_strtolower($title, 'UTF-8');
			$captions = str_replace(array(" ", "-", ".", "\r", "\n", "\"", " "), '', $captions);
			$id_key = md5($captions);
			$channels_id_parsed = HD::get_items('my_channels_id', &$plugin_cookies);
			if (isset($channels_id_parsed [$id_key])){
				unset ($channels_id_parsed [$id_key]);
			HD::save_items('my_channels_id', $channels_id_parsed, &$plugin_cookies);
			}
			$perform_new_action = UserInputHandlerRegistry::create_action(
                    $this, 'cleer_folder');
			return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);
			}
		else if ($user_input->control_id == 'kill_parental')
				{
					if (!isset($user_input->selected_media_url))
						return null;
					$media_url = MediaURL::decode($user_input->selected_media_url);
					$channel_id = $media_url->channel_id;
					$channels = $this->tv->get_channels();
					$c = $channels->get($channel_id);
					$title = $c->get_title();
					$captions = mb_strtolower($title, 'UTF-8');
					$captions = str_replace(array(" ", "-", ".", "\r", "\n", "\"", " "), '', $captions);
					$id_key = md5($captions);
					$parental_defs = HD::get_items('parental', &$plugin_cookies);
					$is_protected = (array_key_exists($id_key, $parental_defs)) ? 1 : 0;
					if ($is_protected == 0){
					return ActionFactory::show_title_dialog("Канал $title не защищен!!!");
					}
					$defs = $this->do_kill_pin_control_defs($plugin_cookies);

					return  ActionFactory::show_dialog
							(
								"Открыть канал (Parental) - $title",
								$defs,
								true
							);
				}
		else if ($user_input->control_id == 'add_parental_apply')
			{
			if (!isset($user_input->selected_media_url))
                return null;
            $media_url = MediaURL::decode($user_input->selected_media_url);
            $channel_id = $media_url->channel_id;
            $channels = $this->tv->get_channels();
            $c = $channels->get($channel_id);
            $title = $c->get_title();
			$captions = mb_strtolower($title, 'UTF-8');
			$captions = str_replace(array(" ", "-", ".", "\r", "\n", "\"", " "), '', $captions);
			$id_key = md5($captions);
			$parental_defs = HD::get_items('parental', &$plugin_cookies);
			$is_protected = (array_key_exists($id_key, $parental_defs)) ? 1 : 0;
					if ($is_protected == 1){
					return ActionFactory::show_title_dialog("Канал $title уже защищен!!!");
					}
			$parental_defs [$id_key] = 1;
			HD::save_items('parental', $parental_defs, &$plugin_cookies);
			$perform_new_action = UserInputHandlerRegistry::create_action(
                    $this, 'cleer_folder');
		return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);
			}
		else if ($user_input->control_id == 'hide_ch_apply')
			{
			if (!isset($user_input->selected_media_url))
                return null;
            $media_url = MediaURL::decode($user_input->selected_media_url);
            $caption_g = $media_url->caption_g;
			$channel_id = $media_url->channel_id;
            $channels = $this->tv->get_channels();
            $c = $channels->get($channel_id);
            $id = $c->get_id();
            $title = $c->get_title();
			$id_key = "$caption_g|$title";
			$hide_ch_defs = HD::get_items('hide_ch', &$plugin_cookies);
			$is_hide = (array_key_exists($id_key, $hide_ch_defs)) ? 1 : 0;
					if ($is_hide == 1){
					return ActionFactory::show_title_dialog("Что-то тут не так. Канал $title уже скрыт!");
					}
			$hide_ch_defs [$id_key] = 1;
			HD::save_items('hide_ch', $hide_ch_defs, &$plugin_cookies);
			$perform_new_action = UserInputHandlerRegistry::create_action(
                    $this, 'cleer_folder');
			return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);
			}
		else if ($user_input->control_id == 'rename_ch')
			{
			if (!isset($user_input->selected_media_url))
                return null;
            $media_url = MediaURL::decode($user_input->selected_media_url);
            $caption_g = $media_url->caption_g;
			$channel_id = $media_url->channel_id;
            $channels = $this->tv->get_channels();
            $c = $channels->get($channel_id);
            $id = $c->get_id();
            $title = $c->get_title();
			if ($m3u_type==7)
			$m3u_dir = HD::get_mount_smb_path($ip_path, $smb_user, $smb_pass, 'm3u_dir');
			if ($m3u_type==3)
			return ActionFactory::show_title_dialog("Для плейлистов по ссылке (http) переименование не возможно!");
			else
			{
			$m3u_file = $m3u_dir . '/' . $caption_g;
			if ($m3u_type==1)
			$m3u_file = $altiptv_data_path . '/playlists/' . $caption_g;
			if (preg_match('|^http|', $m3u_file))
				return ActionFactory::show_title_dialog("Для плейлистов по ссылке (http) переименование не возможно!");
			$daten = file_get_contents($m3u_file);
			$pr=false;
			if (!$daten) {
			return ActionFactory::show_title_dialog("Плейлист не найден (перемещен, удален или по ссылке http)!!!");
			}
			}
			$defs = $this->do_get_rename_ch_defs($title, $plugin_cookies);
			return  ActionFactory::show_dialog
			("Переименовать канал: $title",
			$defs,
			true
			);
			}
		else if ($user_input->control_id == 'delete_ch')
			{
			if (!isset($user_input->selected_media_url))
                return null;
            $media_url = MediaURL::decode($user_input->selected_media_url);
            $caption_g = $media_url->caption_g;
			$channel_id = $media_url->channel_id;
            $channels = $this->tv->get_channels();
            $c = $channels->get($channel_id);
            $id = $c->get_id();
            $title = $c->get_title();
			if ($m3u_type==7)
			$m3u_dir = HD::get_mount_smb_path($ip_path, $smb_user, $smb_pass, 'm3u_dir');
			if ($m3u_type==3)
			return ActionFactory::show_title_dialog("Для плейлистов по ссылке (http) удаление не возможно!");
			else
			{
			$m3u_file = $m3u_dir . '/' . $caption_g;
			if ($m3u_type==1)
			$m3u_file = $altiptv_data_path . '/playlists/' . $caption_g;
			if (preg_match('|^http|', $m3u_file))
				return ActionFactory::show_title_dialog("Для плейлистов по ссылке (http) переименование не возможно!");
			$daten = file_get_contents($m3u_file);
			$pr=false;
			if (!$daten)
			return ActionFactory::show_title_dialog("Плейлист не найден (перемещен, удален или по ссылке http)!!!");
			}
			$defs = $this->do_get_delete_ch_defs($title, $caption_g, $plugin_cookies);
			return  ActionFactory::show_dialog
			("Удалить канал: $title из плейлиста $caption_g ?",
			$defs,
			true
			);
			}
		else if ($user_input->control_id == 'cleer_hide')
			{
			if (!isset($user_input->selected_media_url))
                return null;
			if (isset($user_input->ch_pl))
			$ch_pl = $user_input->ch_pl;
			if (!file_exists($altiptv_data_path . '/data_file/hide_ch'))
				return ActionFactory::show_title_dialog("Скрытых каналов нет");
			$hide_ch_defs = HD::get_items('hide_ch', &$plugin_cookies);
			$is_hide = (array_key_exists($ch_pl, $hide_ch_defs)) ? 1 : 0;
					if ($is_hide == 0){
					$tmp = explode('|', $ch_pl);
					return ActionFactory::show_title_dialog("Канал $tmp[1] уже востановлен!");
					}
			unset ($hide_ch_defs [$ch_pl]);
			HD::save_items('hide_ch', $hide_ch_defs, &$plugin_cookies);
			
			$perform_new_action = UserInputHandlerRegistry::create_action(
                    $this, 'hide_list');
			return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);
			}
		else if ($user_input->control_id == 'hide_list')
			{
				if (!isset($user_input->selected_media_url))
					return null;
				$prov_pl = isset($plugin_cookies->prov_pl) ?
				$plugin_cookies->prov_pl : 0;
				$hide_ch_defs = HD::get_items('hide_ch', &$plugin_cookies);
				$name_pl_defs = HD::get_items('name_pl', &$plugin_cookies);
				if (!file_exists($altiptv_data_path . '/data_file/hide_ch'))
					return ActionFactory::show_title_dialog("Скрытых каналов нет");
				if (count($hide_ch_defs) == 0)
					return ActionFactory::show_title_dialog("Скрытых каналов нет");
				$i=1;
				foreach($hide_ch_defs as $key => $value){
				$tmp = explode('|', $key);
				$rname = $tmp[0];
				$chmame = $tmp[1];
				if (($prov_pl !== '0') &&(preg_match("/$rname/u",$prov_pl))){
				$tmp = explode('|', $prov_pl);
				$rname = $tmp[0];}
				$tname = array_key_exists($rname,$name_pl_defs) ? $name_pl_defs[$rname] : false;
				if ($tname == true)
					$rname = $tname;
				$ch_pl = "$i. Канал: $chmame [$rname]";
				$add_params ['ch_pl'] = $key;
				ControlFactory::add_button_close ($defs, $this, $add_params,'cleer_hide', $ch_pl, 'Востановить', 0);
				$i++;}
				$cleer_hide_all = UserInputHandlerRegistry::create_action($this, 'cleer_hide_all');
				ControlFactory::add_custom_close_dialog_and_apply_buffon($defs, '_del', 'Востановить все', 350,  $cleer_hide_all);
				ControlFactory::add_close_dialog_button($defs,
				'Отмена', 350);
				return ActionFactory::show_dialog("Скрытые каналы:", $defs, 1);
			}
		else if ($user_input->control_id == 'cleer_hide_all')
			{
			if (!isset($user_input->selected_media_url))
                return null;
			$link = $altiptv_data_path . '/data_file/hide_ch';
			if (!file_exists($link)){
			return ActionFactory::show_title_dialog("Скрытых каналов нет");
			}
			unlink($link);
			$perform_new_action = UserInputHandlerRegistry::create_action(
                    $this, 'cleer_folder');
			return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);
			}
		else if ($user_input->control_id == 'del_back_scan')
			{
			if (!isset($user_input->selected_media_url))
                return null;
			$link = $altiptv_data_path . '/data_file/hide_ch';
			$pid_info = DuneSystem::$properties['tmp_dir_path'] . '/pid_inf';
			if (file_exists($pid_info)){
			$kill_pid = file_get_contents($pid_info);
			shell_exec("kill $kill_pid > /dev/null &");}
			unlink(DuneSystem::$properties['tmp_dir_path'] . '/scan_inf');
			return ActionFactory::show_title_dialog("Фоновое сканирование остановлено");
			}
		else if ($user_input->control_id == 'hide_ch')
			{
			if (!isset($user_input->selected_media_url))
                return null;
            $media_url = MediaURL::decode($user_input->selected_media_url);
            $caption_g = $media_url->caption_g;
			$channel_id = $media_url->channel_id;
            $channels = $this->tv->get_channels();
            $c = $channels->get($channel_id);
            $id = $c->get_id();
            $title = $c->get_title();
			$defs = $this->do_get_hide_ch_defs($title, $caption_g, $plugin_cookies);
			return  ActionFactory::show_dialog
			("Скрыть канал: $title из плейлиста $caption_g ?",
			$defs,
			true
			);
			}
		if ($user_input->control_id == 'new_sh_back_scan')
        {
		$defs = $this->do_get_new_sh_back_scan_defs($plugin_cookies);
					return  ActionFactory::show_dialog
							(
								"Фоновое сканирование",
								$defs,
								true
							);
		}
		if ($user_input->control_id == 'cleer_back_scan')
        {
			$defs = $this->do_get_new_shedl_back_scan_defs($altiptv_data_path, $plugin_cookies);
					return  ActionFactory::show_dialog
							(
								"Расписание фонового сканирования",
								$defs,
								true
							);
		}
		if ($user_input->control_id == 'del_bg_apply')
        {
		$inf = $user_input->inf;
		$scan_file = $altiptv_data_path . '/data_file/bg_scan';
		$cron_file = '/tmp/cron/crontabs/root';
		$line = file_get_contents($scan_file);
		$cline = file_get_contents($cron_file);
		$save_cron  = str_replace($inf,'',$cline);
		$cron_data = fopen($cron_file,"w");
		if (!$cron_data)
			hd_print("НЕ МОГУ ЗАПИСАТЬ cron");
			fwrite($cron_data, $save_cron);
			@fclose($cron_data);
			chmod($cron_file, 0575);
			shell_exec('crontab -e');
		$save_c  = str_replace($inf,'',$line);
		$scan_data = fopen($scan_file,"w");
			if (!$scan_data)
			hd_print("НЕ МОГУ ЗАПИСАТЬ cron");
			fwrite($scan_data, $save_c);
			@fclose($scan_data);
		return ActionFactory::show_title_dialog("Удалено");
		}
		if ($user_input->control_id == 'read_bg_apply')
        {
		$cron_file = '/tmp/cron/crontabs/root';
		$save_cron = $user_input->inf;
		$cron_data = fopen($cron_file,"a");
			if (!$cron_data)
			hd_print("НЕ МОГУ ЗАПИСАТЬ cron");
			fwrite($cron_data, $save_cron);
			@fclose($cron_data);
			chmod($cron_file, 0575);
			shell_exec('crontab -e');
		return ActionFactory::show_title_dialog("Востановлено");
		}
		if ($user_input->control_id == 'sh_back_scan')
        {
			if (!isset($user_input->selected_media_url))
                return null;
            $media_url = MediaURL::decode($user_input->selected_media_url);
			$sbs_min = $user_input->sbs_min;
			$sbs_hrs = $user_input->sbs_hrs;
			$sbs_day = $user_input->sbs_day;
			$sbs_mns = $user_input->sbs_mns;
			$sbs_wday = $user_input->sbs_wday;
			$cron_file = '/tmp/cron/crontabs/root';
			$caption_g = $media_url->caption_g;
			$channel_id = $media_url->channel_id;
			$channels = $this->tv->get_channels();
			$c = $channels->get($channel_id);
			$title = $c->get_title();
			$tid = str_replace('-', '', crc32($title));
			$streaming_url = $c->streaming_url();
			if ($m3u_type==7)
			$m3u_dir = HD::get_mount_smb_path($ip_path, $smb_user, $smb_pass, 'm3u_dir');
			if ($m3u_type==3)
			return ActionFactory::show_title_dialog("Для плейлистов по ссылке (http) поиска прокси нет!");
			$m3u_file = $m3u_dir . '/' . $caption_g;
			if ($m3u_type==1)
			{
			$m3u_file = $altiptv_data_path . '/playlists/' . $caption_g;
			$m3u_dir = $altiptv_data_path . '/playlists/';
			}
			if (preg_match('|^http|', $m3u_file))
				return ActionFactory::show_title_dialog("Для плейлистов по ссылке (http) переименование не возможно!");
			$daten = file_get_contents($m3u_file);
			$pr=false;
			if (!$daten) {
			return ActionFactory::show_title_dialog("Плейлист не найден (перемещен или удален)!!!");
			}
			if ((!preg_match('/\/udp\//i', $streaming_url)) && (!preg_match("/\/.*:.*:.*:.*:.*:.*/i", $streaming_url)))
			return ActionFactory::show_title_dialog("Этот канал без прокси!!!");
			if (preg_match('/http:\/\/ts:/i', $streaming_url))
			$streaming_url = str_replace('http://ts://', 'http://', $streaming_url);
			if (preg_match('/dream.sh?/i', $streaming_url))
    		{
		     $tmp = explode('dream.sh?',  $streaming_url);
    		 $streaming_url = $tmp[1];
			}
			preg_match_all('|http:\/\/(.*):(.*?)\/.*|', $streaming_url, $tmp);
			$ip = $tmp[1][0];
			$port = $tmp[2][0];
			$ip_port = $ip . ':' . $port;
			$pars = explode('.', $ip);
			$n1 = $pars[0];
			$n2 = $pars[1];
			$n3 = $pars[2];
			$n4 = $pars[3];
			if (preg_match('/^(25[0-5]|2[0-4][0-9]|[0-1][0-9]{2}|[0-9]{2}|[0-9])(\.(25[0-5]|2[0-4][0-9]|[0-1][0-9]{2}|[0-9]{2}|[0-9])){3}-(25[0-5]|2[0-4][0-9]|[0-1][0-9]{2}|[0-9]{2}|[0-9])(\.(25[0-5]|2[0-4][0-9]|[0-1][0-9]{2}|[0-9]{2}|[0-9])){3}$/i', $caption_g))
			{
			$ipds = explode('-', $caption_g);
			$ip1 = $ipds[0];
			$ip2 = $ipds[1];
			$an = explode('.', $ip1);
			$an = $an[2];
			$en = explode('.', $ip2);
			$en = $en[2];

			$ipd = $caption_g;
			$prov = "Диапазон из названия плейлиста";
			}
			elseif (file_exists(DuneSystem::$properties['tmp_dir_path'].'/'  . $caption_g . $tid . '.ipi'))
			{
			$ipi_file = DuneSystem::$properties['tmp_dir_path'].'/'  . $caption_g . $tid . '.ipi';
			$ipi_info = file_get_contents($ipi_file);
			$tmp = explode('prov:', $ipi_info);
			$ipd = $tmp[0];
			$prov = $tmp[1];
			$ipds = explode('-', $ipd);
			$ip1 = $ipds[0];
			$ip2 = $ipds[1];
			$an = explode('.', $ip1);
			$an = $an[2];
			$en = explode('.', $ip2);
			$en = $en[2];
			}
			else
			{
			$domain = $ip;
			$whis_url = 'http://1whois.ru/?url=' . $domain;
			$page = HD::http_get_document($whis_url);
			$page = iconv('windows-1251', 'UTF-8',$page);
			$page = str_replace("&nbsp;", "", $page);
			$ipd = explode('inetnum:', $page);
			$ipd = strstr($ipd[1], '<br />', true);
			$prov = explode('descr:', $page);
			$prov = strstr($prov[1], '<br />', true);
			$prov = preg_replace('|\s+|', '', $prov);
			$ipd = preg_replace('|\s+|', '', $ipd);
			$new_ip_inf = $ipd . "prov:" . $prov;
			$ip_inf = DuneSystem::$properties['tmp_dir_path'].'/'  . $caption_g . $tid . '.ipi';
			if (!$ipd===false)
			{
			$date_ip = fopen($ip_inf,"w");
			if (!$date_ip)
				hd_print("Не могу записать в tmp Что-то здесь не так!!!");
			fwrite($date_ip, $new_ip_inf);
			@fclose($date_ip);
			}
			$ipds = explode('-', $ipd);
			$ip1 = $ipds[0];
			$ip2 = $ipds[1];
			$an = explode('.', $ip1);
			$an = $an[2];
			$en = explode('.', $ip2);
			$en = $en[2];

			if (!$ip2)
			return ActionFactory::show_title_dialog("whois сервис не отвечает или не доступен!!!");
			}

			if (!$ip2)
			return ActionFactory::show_title_dialog("Что-то здесь не так!!!");

			$port_f = false;
			if (file_exists(DuneSystem::$properties['tmp_dir_path'].'/'  . $caption_g . '.prt'))
			{
			$prt_file = DuneSystem::$properties['tmp_dir_path'].'/'  . $caption_g . '.prt';
			$temp_port = file_get_contents($prt_file);
			if ($temp_port !== '0')
			{
			$port = $temp_port;
			$port_f = true;
			}
			}
			$proxy_list_file = $m3u_dir . '/' . $caption_g . '.txt';
			$proxy_list_file = urlencode($proxy_list_file);
			$prov = urlencode($prov);
			$link = DuneSystem::$properties['plugin_cgi_url'] . "do?n1=$n1&n2=$n2&an=$an&en=$en&port=$port&plf=$proxy_list_file&prov=$prov";
			$save_cron = "\n$sbs_min $sbs_hrs $sbs_day $sbs_mns $sbs_wday wget --quiet -O - \"$link\" > /dev/null & #bg_scanПлейлист:$caption_g($prov)";
			$cron_data = fopen($cron_file,"a");
			if (!$cron_data)
			hd_print("НЕ МОГУ ЗАПИСАТЬ cron");
			fwrite($cron_data, $save_cron);
			@fclose($cron_data);
			chmod($cron_file, 0575);
			shell_exec('crontab -e');
			$scan_file = $altiptv_data_path . '/data_file/bg_scan';
			$scan_data = fopen($scan_file,"a");
			if (!$scan_data)
			hd_print("НЕ МОГУ ЗАПИСАТЬ cron");
			fwrite($scan_data, $save_cron);
			@fclose($scan_data);
			$perform_new_action = null;
			return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);
		}
		else if ($user_input->control_id == 'new_back_scan')
        {
            if (!isset($user_input->selected_media_url))
                return null;
            $media_url = MediaURL::decode($user_input->selected_media_url);
            $caption_g = $media_url->caption_g;
			$channel_id = $media_url->channel_id;
			$channels = $this->tv->get_channels();
			$c = $channels->get($channel_id);
			$title = $c->get_title();
			$tid = str_replace('-', '', crc32($title));
			$streaming_url = $c->streaming_url();
			if ($m3u_type==7)
			$m3u_dir = HD::get_mount_smb_path($ip_path, $smb_user, $smb_pass, 'm3u_dir');
			if ($m3u_type==3)
			return ActionFactory::show_title_dialog("Для плейлистов по ссылке (http) поиска прокси нет!");
			$m3u_file = $m3u_dir . '/' . $caption_g;
			if ($m3u_type==1)
			{
			$m3u_file = $altiptv_data_path . '/playlists/' . $caption_g;
			$m3u_dir = $altiptv_data_path . '/playlists/';
			}
			if (preg_match('|^http|', $m3u_file))
				return ActionFactory::show_title_dialog("Для плейлистов по ссылке (http) переименование не возможно!");
			$daten = file_get_contents($m3u_file);
			$pr=false;
			if (!$daten) {
			return ActionFactory::show_title_dialog("Плейлист не найден (перемещен или удален)!!!");
			}

			if ((!preg_match('/\/udp\//i', $streaming_url)) && (!preg_match("/\/.*:.*:.*:.*:.*:.*/i", $streaming_url)))
			{
			return ActionFactory::show_title_dialog("Этот канал без прокси!!!");
			}
			if (preg_match('/http:\/\/ts:/i', $streaming_url))
			{
			$streaming_url = str_replace('http://ts://', 'http://', $streaming_url);
			}
			if (preg_match('/dream.sh?/i', $streaming_url))
    		{
		     $tmp = explode('dream.sh?',  $streaming_url);
    		 $streaming_url = $tmp[1];
			}
			preg_match_all('|http:\/\/(.*):(.*?)\/.*|', $streaming_url, $tmp);
			$ip = $tmp[1][0];
			$port = $tmp[2][0];
			$ip_port = $ip . ':' . $port;
			$pars = explode('.', $ip);
			$n1 = $pars[0];
			$n2 = $pars[1];
			$n3 = $pars[2];
			$n4 = $pars[3];
			if (preg_match('/^(25[0-5]|2[0-4][0-9]|[0-1][0-9]{2}|[0-9]{2}|[0-9])(\.(25[0-5]|2[0-4][0-9]|[0-1][0-9]{2}|[0-9]{2}|[0-9])){3}-(25[0-5]|2[0-4][0-9]|[0-1][0-9]{2}|[0-9]{2}|[0-9])(\.(25[0-5]|2[0-4][0-9]|[0-1][0-9]{2}|[0-9]{2}|[0-9])){3}$/i', $caption_g))
			{
			$ipds = explode('-', $caption_g);
			$ip1 = $ipds[0];
			$ip2 = $ipds[1];
			$an = explode('.', $ip1);
			$an = $an[2];
			$en = explode('.', $ip2);
			$en = $en[2];

			$ipd = $caption_g;
			$prov = "Диапазон из названия плейлиста";
			}
			elseif (file_exists(DuneSystem::$properties['tmp_dir_path'].'/'  . $caption_g . $tid . '.ipi'))
			{
			$ipi_file = DuneSystem::$properties['tmp_dir_path'].'/'  . $caption_g . $tid . '.ipi';
			$ipi_info = file_get_contents($ipi_file);
			$tmp = explode('prov:', $ipi_info);
			$ipd = $tmp[0];
			$prov = $tmp[1];
			$ipds = explode('-', $ipd);
			$ip1 = $ipds[0];
			$ip2 = $ipds[1];
			$an = explode('.', $ip1);
			$an = $an[2];
			$en = explode('.', $ip2);
			$en = $en[2];
			}
			else
			{
			$domain = $ip;
			$whis_url = 'http://1whois.ru/?url=' . $domain;
			$page = HD::http_get_document($whis_url);
			$page = iconv('windows-1251', 'UTF-8',$page);
			$page = str_replace("&nbsp;", "", $page);
			$ipd = explode('inetnum:', $page);
			$ipd = strstr($ipd[1], '<br />', true);
			$prov = explode('descr:', $page);
			$prov = strstr($prov[1], '<br />', true);
			$prov = preg_replace('|\s+|', '', $prov);
			$ipd = preg_replace('|\s+|', '', $ipd);
			$new_ip_inf = $ipd . "prov:" . $prov;
			$ip_inf = DuneSystem::$properties['tmp_dir_path'].'/'  . $caption_g . $tid . '.ipi';
			if (!$ipd===false)
			{
			$date_ip = fopen($ip_inf,"w");
			if (!$date_ip)
				{
				hd_print("Не могу записать в tmp Что-то здесь не так!!!");
				}
			fwrite($date_ip, $new_ip_inf);
			@fclose($date_ip);
			}
			$ipds = explode('-', $ipd);
			$ip1 = $ipds[0];
			$ip2 = $ipds[1];
			$an = explode('.', $ip1);
			$an = $an[2];
			$en = explode('.', $ip2);
			$en = $en[2];

			if (!$ip2)
			return ActionFactory::show_title_dialog("whois сервис не отвечает или не доступен!!!");
			}

			if (!$ip2)
			return ActionFactory::show_title_dialog("Что-то здесь не так!!!");

			$port_f = false;
			if (file_exists(DuneSystem::$properties['tmp_dir_path'].'/'  . $caption_g . '.prt'))
			{
			$prt_file = DuneSystem::$properties['tmp_dir_path'].'/'  . $caption_g . '.prt';
			$temp_port = file_get_contents($prt_file);
			if ($temp_port !== '0')
			{
			$port = $temp_port;
			$port_f = true;
			}
			}
			$proxy_list_file = $m3u_dir . '/' . $caption_g . '.txt';
			$proxy_list_file = urlencode($proxy_list_file);
			$prov = urlencode($prov);
			$link = DuneSystem::$properties['plugin_cgi_url'] . "do?n1=$n1&n2=$n2&an=$an&en=$en&port=$port&plf=$proxy_list_file&prov=$prov";
			shell_exec("wget --quiet -O - \"$link\" > /dev/null &");

			return UserInputHandlerRegistry::create_action($this, 'info_back_scan');
		}
		else if ($user_input->control_id == 'add_parental')
			{
			if (!isset($user_input->selected_media_url))
                return null;
            $media_url = MediaURL::decode($user_input->selected_media_url);
			$channel_id = $media_url->channel_id;
            $channels = $this->tv->get_channels();
            $c = $channels->get($channel_id);
            $id = $c->get_id();
            $title = $c->get_title();
			$defs = $this->do_get_add_parental_defs($title, $plugin_cookies);
			return  ActionFactory::show_dialog
			("Закрыть канал: $title (Parental Control)?",
			$defs,
			true
			);
			}
		else if ($user_input->control_id == 'kill_parent_apply')
			{
			$pin = isset($plugin_cookies->pin) ? $plugin_cookies->pin : '0000';
			if ($user_input->pin !== $pin)
				{
				return ActionFactory::show_title_dialog("Код не правильный!!!");
				}
			if (!isset($user_input->selected_media_url))
                return null;
            $media_url = MediaURL::decode($user_input->selected_media_url);
            $channel_id = $media_url->channel_id;
            $channels = $this->tv->get_channels();
            $c = $channels->get($channel_id);
            $title = $c->get_title();
			$captions = mb_strtolower($title, 'UTF-8');
			$captions = str_replace(array(" ", "-", ".", "\r", "\n", "\"", " "), '', $captions);
			$id_key = md5($captions);
			$parental_defs = HD::get_items('parental', &$plugin_cookies);
			unset ($parental_defs [$id_key]);
			HD::save_items('parental', $parental_defs, &$plugin_cookies);
			$perform_new_action = UserInputHandlerRegistry::create_action(
                    $this, 'cleer_folder');
		return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);
			}
		else if ($user_input->control_id == 'one_rec')
			{
				if (!isset($user_input->selected_media_url))
                return null;

            $media_url = MediaURL::decode($user_input->selected_media_url);

			$defs = $this->do_get_one_rec_defs($plugin_cookies);
					return  ActionFactory::show_dialog
							(
								"Расписание записи каналов",
								$defs,
								true
							);
			}
		else if ($user_input->control_id == 'info_back_scan')
			{
				if (!isset($user_input->selected_media_url))
                return null;

            $media_url = MediaURL::decode($user_input->selected_media_url);
			$do_proxy = UserInputHandlerRegistry::create_action($this, 'info_back_scan');
			$attrs['timer'] = ActionFactory::timer(1000);
			$attrs['actions'] = array(GUI_EVENT_TIMER => ActionFactory::close_dialog_and_run($do_proxy));
			$attrs['dialog_params'] = array('frame_style' => DIALOG_FRAME_STYLE_GLASS);
			$defs = $this->do_get_info_back_scan_defs($plugin_cookies);
					return  ActionFactory::show_dialog
							(
								"Фоновое сканирование",
								$defs,
								true,0,$attrs
							);
			}
		else if ($user_input->control_id === 'do_new_install')
		{
			$add_params = array('name' => 'Codec_install');
            $url = 'plugin_installer://http://dune-club.info/plugins/update/Codec_install/dune_plugin_Codec_install_v4.0.3.tar.gz:::name=Codec_install&space_needed=2000000&need_confirm_replace=0';
			return ActionFactory::launch_media_url($url,UserInputHandlerRegistry::create_action($this, 'refresh', $add_params));
		}
		else if ($user_input->control_id === 'rec_del_menu')
		{
			if (!isset($user_input->selected_media_url))
                return null;
            $media_url = MediaURL::decode($user_input->selected_media_url);
			$defs = $this->do_get_del_rec_defs($plugin_cookies);
			return  ActionFactory::show_dialog
			("Список записи очищен!!!",
			$defs,
			true
			);
		}
		else if ($user_input->control_id == 'new_port')
			{
			if (!isset($user_input->selected_media_url))
                return null;
            $media_url = MediaURL::decode($user_input->selected_media_url);
            $caption_g = $media_url->caption_g;
			$channel_id = $media_url->channel_id;
            $channels = $this->tv->get_channels();
            $c = $channels->get($channel_id);
			$streaming_url = $c->streaming_url();
			if ($m3u_type==7)
			$m3u_dir = HD::get_mount_smb_path($ip_path, $smb_user, $smb_pass, 'm3u_dir');
			if ($m3u_type==3)
			return ActionFactory::show_title_dialog("Для плейлистов по ссылке (http) поиска прокси нет!");
			else
			{
				$m3u_file = $m3u_dir . '/' . $caption_g;
				if ($m3u_type==1)
					$m3u_file = $altiptv_data_path . '/playlists/' . $caption_g;
				if (preg_match('|^http|', $m3u_file))
					return ActionFactory::show_title_dialog("Для плейлистов по ссылке (http) поиска прокси нет!");
				$daten = file_get_contents($m3u_file);
				$pr=false;
				if (!$daten) 
					return ActionFactory::show_title_dialog("Плейлист не найден (перемещен или удален)!!!");
				$port_doc = 0;
				if (file_exists(DuneSystem::$properties['tmp_dir_path'].'/'  . $caption_g . '.prt')) {
					$port_file = DuneSystem::$properties['tmp_dir_path'].'/'  . $caption_g . '.prt';
					$port_doc = file_get_contents($port_file);
				}
				if (preg_match('|http:\/\/(.*):(.*?)\/.*|', $streaming_url, $tmp))
					$port_doc = $tmp[2];
			}
			$defs = $this->do_get_new_port_defs($port_doc, $plugin_cookies);
			return  ActionFactory::show_dialog
			("Сканировать диапазон используя порт",
			$defs,
			true
			);
			}
		else if ($user_input->control_id == 'new_count')
				{
					if (!isset($user_input->selected_media_url))
					return null;
					$media_url = MediaURL::decode($user_input->selected_media_url);
					$caption_g = $media_url->caption_g;
					if ($m3u_type==7)
					$m3u_dir = HD::get_mount_smb_path($ip_path, $smb_user, $smb_pass, 'm3u_dir');
					if ($m3u_type==3)
					return ActionFactory::show_title_dialog("Для плейлистов по ссылке (http) поиска прокси нет!");
					else
					{
					$m3u_file = $m3u_dir . '/' . $caption_g;
					if ($m3u_type==1)
					$m3u_file = $altiptv_data_path . '/playlists/' . $caption_g;
					if (preg_match('|^http|', $m3u_file))
						return ActionFactory::show_title_dialog("Для плейлистов по ссылке (http) поиска прокси нет!");
					$daten = file_get_contents($m3u_file);
					$pr=false;
					if (!$daten) {
					return ActionFactory::show_title_dialog("Плейлист не найден (перемещен или удален)!!!");
					}
					$count_doc = 0;
					if (file_exists(DuneSystem::$properties['tmp_dir_path'].'/'  . $caption_g . '.cnt')) {
					$count_file = DuneSystem::$properties['tmp_dir_path'].'/'  . $caption_g . '.cnt';
					$count_doc = file_get_contents($count_file);
					}
					}

					$defs = $this->do_get_new_count_defs($count_doc, $plugin_cookies);

					return  ActionFactory::show_dialog
							(
								"Сканировать диапазон начиная с номера",
								$defs,
								true
							);
				}
		else if ($user_input->control_id == 'back_scan')
				{
				if (!isset($user_input->selected_media_url))
                return null;

            $media_url = MediaURL::decode($user_input->selected_media_url);
			$defs = $this->do_get_back_scan_defs($plugin_cookies);
					return  ActionFactory::show_dialog
							(
								"Фоновое сканирование",
								$defs,
								true
							);
				}
		else if ($user_input->control_id == 'add_user_agent')
			{
			if (!isset($user_input->selected_media_url))
                return null;
            $media_url = MediaURL::decode($user_input->selected_media_url);
			$channel_id = $media_url->channel_id;
			$channels = $this->tv->get_channels();
            $c = $channels->get($channel_id);
            $id = $c->get_id();
            $title = $c->get_title();
			$user_agents = HD::get_items('channel_user_agent', &$plugin_cookies);
			$user_agent = '';
			if (isset($user_agents[$channel_id]))
				$user_agent = $user_agents[$channel_id];
			$defs = array();
			$user_agent_list = 'v';
			$user_agent_list_ops = array();
			$user_agent_list_ops['v'] = "Выберите";
			$user_agent_list_ops['del'] = "Удалить User Agent";
			$ualo = HD::get_items('user_agent_list_ops', &$plugin_cookies);
			$user_agent_list_ops = array_merge ($user_agent_list_ops, $ualo);
			ControlFactory::add_combobox($defs, $this, null,
				'user_agent_list', 'Список User Agent:',
				$user_agent_list, $user_agent_list_ops, 750, $need_confirm = false, $need_apply = false
			);
			ControlFactory::add_text_field($defs,0,0,
				'new_user_agent', 'Задать:',
				$user_agent, 0, 0, 0, 1, 750, 0, false);
			$add_params ['channel_id'] = $channel_id;
			ControlFactory::add_close_dialog_and_apply_button(&$defs,
			$this, $add_params,
			'apply_user_agent', 'Применить', 0, $gui_params = null);
			ControlFactory::add_close_dialog_and_apply_button(&$defs,
			$this, $add_params,
			'del_user_agent', 'Очистить все User Agent', 0, $gui_params = null);
			$attrs['actions'] = null;
			return ActionFactory::show_dialog("Задать User Agent каналу $title", $defs,true,0,$attrs);
			}
		else if ($user_input->control_id == 'del_user_agent')
			{
				$user_agents = array();
				HD::save_items('channel_user_agent', $user_agents, &$plugin_cookies);
				return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);
			}
		else if ($user_input->control_id == 'apply_user_agent')
			{
				if (!isset($user_input->selected_media_url))
                return null;
				$channel_id = $user_input->channel_id;
				$user_agents = HD::get_items('channel_user_agent', &$plugin_cookies);
				$user_agent_list_ops = HD::get_items('user_agent_list_ops', &$plugin_cookies);
				if ((($user_input->user_agent_list == 'v')&&($user_input->new_user_agent == ''))||($user_input->user_agent_list == 'del'))
				{
					if (isset($user_agents[$channel_id])){
						unset ($user_agents[$channel_id]);
						HD::save_items('channel_user_agent', $user_agents, &$plugin_cookies);
						$perform_new_action = null;
						return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);
					}
					else
						return ActionFactory::show_title_dialog('User Agent не был задан.');
				}
				if (($user_input->user_agent_list == 'v')&&($user_input->new_user_agent !== '')){
					$u_a[$user_input->new_user_agent] = substr($user_input->new_user_agent, 0, 50) . '...';
					$user_agent_list_ops = array_merge ($u_a, $user_agent_list_ops);
					HD::save_items('user_agent_list_ops', $user_agent_list_ops, &$plugin_cookies);
					$user_agents[$channel_id] = $user_input->new_user_agent;
					HD::save_items('channel_user_agent', $user_agents, &$plugin_cookies);
					$perform_new_action = null;
					return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);
				}
				if (($user_input->user_agent_list !== 'v')&&($user_input->new_user_agent == '')){
					$user_agents[$channel_id] = $user_input->user_agent_list;
					HD::save_items('channel_user_agent', $user_agents, &$plugin_cookies);
					$perform_new_action = null;
					return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);
				}
			}
		else if ($user_input->control_id == 'change_grup')
				{
					if (!isset($user_input->selected_media_url))
					return null;
					$media_url = MediaURL::decode($user_input->selected_media_url);
					$channel_id = $media_url->channel_id;
					list($garb, $epg_shift_pl, $ch_id) = preg_split('/_/', $channel_id);
					if(preg_match("/%/", $ch_id)){
					$tmp = explode('%', $ch_id);
					$ch_id = $tmp[0];}
					if ($ch_id > 40000)
					return ActionFactory::show_title_dialog("Не указан ID vsetv группу изменить не возможно");
					$channels = $this->tv->get_channels();
					$c = $channels->get($channel_id);
					$title = $c->get_title();
					$media_url = MediaURL::decode($user_input->selected_media_url);
					$defs = $this->do_get_change_grup_defs($garb, $plugin_cookies);
							return  ActionFactory::show_dialog
									(
										"Изменить группу для $title",
										$defs,
										true
									);
				}

		else if ($user_input->control_id === 'garb_type')
		{
                if (!isset($user_input->selected_media_url))
                return null;
				$garb_type=$user_input->garb_type;
				$media_url = MediaURL::decode($user_input->selected_media_url);
				$channel_id = $media_url->channel_id;
				list($garb, $epg_shift_pl, $ch_id) = preg_split('/_/', $channel_id);
				if(preg_match("/%/", $ch_id)){
				$tmp = explode('%', $ch_id);
				$ch_id = $tmp[0];}
				if ($ch_id > 40000)
				return ActionFactory::show_title_dialog("Не указан ID vsetv группу изменить не возможно");
				$channels = $this->tv->get_channels();
				$c = $channels->get($channel_id);
				$title = $c->get_title();
				$group_defs = HD::get_items('grups_id', &$plugin_cookies);
				$group_defs[$ch_id] = $garb_type;
				HD::save_items('grups_id', $group_defs, &$plugin_cookies);
				$perform_new_action = UserInputHandlerRegistry::create_action(
                    $this, 'cleer_folder');
		return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);
		}
		else if ($user_input->control_id === 'rec_cool')
		{
		if (isset($user_input->rec_hdd))
			$rec_hdd = $user_input->rec_hdd;
		return ActionFactory::launch_media_url(
                $rec_hdd);
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
		else if ($user_input->control_id === 'cleer_folder')
		{
		if (!isset($user_input->parent_media_url))
                return null;
		$parent_media_url = MediaURL::decode($user_input->parent_media_url);
		$sel_ndx = $user_input->sel_ndx;
				if ($sel_ndx < 0)
					$sel_ndx = 0;

				$range = $this->get_folder_range($parent_media_url, 0, $plugin_cookies);
				return ActionFactory::update_regular_folder($range, true, $sel_ndx);
		}
		else if ($user_input->control_id === 'cleer_folder_key')
		{
		if (!isset($user_input->parent_media_url))
                return null;
		$perform_new_action = UserInputHandlerRegistry::create_action(
                    $this, 'cleer_folder');
		return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);
		}
		else if ($user_input->control_id === 'background_rec')
		{
		if (!isset($user_input->selected_media_url))
        return null;
		if (!file_exists('/codecpack/script/iptv_record.sh')){
		$defs = $this->do_install_codecpack_defs($plugin_cookies);
					return  ActionFactory::show_dialog
							(
								"Кодеки не установлены или установленна старая версия.",
								$defs,
								true
							);}
		$media_url = MediaURL::decode($user_input->selected_media_url);
        $channel_id = $media_url->channel_id;
        $channels = $this->tv->get_channels();
        $c = $channels->get($channel_id);
        $title = $c->get_title();
		$tr_title = HD::translit($title);
		$rec_path = HD::get_rec_path($plugin_cookies);
		$streaming_url = $c->streaming_url();
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
		return ActionFactory::show_title_dialog("Накопитель для записи не найден!!! Подключите к плееру накопитель.");
		if (preg_match("|\/D\/|",$rec_path)){
		$bytes = disk_free_space ('/D/');
		$si_prefix = array( 'B', 'KB', 'MB', 'GB', 'TB', 'EB', 'ZB', 'YB' );
		$base = 1024;
		$class = min((int)log($bytes , $base) , count($si_prefix) - 1);
		$free = sprintf('%1.2f' , $bytes / pow($base,$class)) . ' ' . $si_prefix[$class];
		if ($bytes < 1000000000)
		return ActionFactory::show_title_dialog("Свободного места на диске меньше 1ГБ ($free). Запись не началась!!!");
		}
		$rec_file = DuneSystem::$properties['tmp_dir_path'] . '/background_rec_stop';
		$date_count = fopen($rec_file,"w");
		if (!$date_count)
		return ActionFactory::show_title_dialog("Не могу записать в tmp Что-то здесь не так!!!");
		fwrite($date_count, $rec_name);
		@fclose($date_count);
		shell_exec($cmd_rec);
		ControlFactory::add_label($defs, "Запись канала:", "$title");
		ControlFactory::add_label($defs, "Свободно на диске:", "$free");
		ControlFactory::add_label($defs, "", "Не забудьте Выключить запись!!!");
		$do_br_apply = UserInputHandlerRegistry::create_action($this, 'new_br_apply');
        ControlFactory::add_custom_close_dialog_and_apply_buffon($defs,
		'_do_br_apply', 'ОК', 250, $do_br_apply);
		return ActionFactory::show_dialog('Фоновая запись канала', $defs, 1);
		}
		else if ($user_input->control_id === 'new_br_apply')
		{
		$perform_new_action = null;
		return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);
		}
		else if ($user_input->control_id === 'background_stoprec')
		{
		if (!isset($user_input->selected_media_url))
                return null;
		$rec_file = DuneSystem::$properties['tmp_dir_path'] . '/background_rec_stop';
		if (!file_exists($rec_file))
		return ActionFactory::show_title_dialog("Активная фоновая запись не найдена.");
		$background_rec_stop = trim(file_get_contents($rec_file));
		unlink($rec_file);
		$cmd_stoprec = '/tmp/' .$background_rec_stop.'_iptvrecord.sh';
		shell_exec($cmd_stoprec);
		$perform_new_action = UserInputHandlerRegistry::create_action(
                    $this, 'dialog_rec_stop');
		return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);}
		else if ($user_input->control_id === 'dialog_rec_stop')	{
		return ActionFactory::show_title_dialog("Запись остановлена.");}
		else if ($user_input->control_id === 'new_rec')
		{
			if (!isset($user_input->selected_media_url))
                return null;
			if (!file_exists('/codecpack/script/iptv_record.sh')){
			$defs = $this->do_install_codecpack_defs($plugin_cookies);
					return  ActionFactory::show_dialog
							(
								"Кодеки не установлены или установленна старая версия.",
								$defs,
								true
							);}
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
            $media_url = MediaURL::decode($user_input->selected_media_url);
			$caption_g = $media_url->caption_g;
			$defs = $this->do_get_new_rec_defs($media_url, $start_tvg_times, $stop_tvg_times, $tvg_rec_day, $inf, $plugin_cookies);
			return  ActionFactory::show_dialog
			("Задать время записи:",
			$defs,
			true
			);
		}
		else if ($user_input->control_id === 'new_rec_conf')
		{
			$rec_start_t = $user_input->rec_start_t;
			$rec_start_d = $user_input->rec_start_d;
			$rec_stop_t = $user_input->rec_stop_t;
			$rec_stop_d = $user_input->rec_stop_d;
			$inf = $user_input->inf;
			$defs = $this->do_get_new_rec_conf_defs($rec_start_t, $rec_start_d, $rec_stop_t, $rec_stop_d, $inf, $plugin_cookies);
			return  ActionFactory::show_dialog
			("Добавить задание записи",
			$defs,
			true
			);
		}
		else if ($user_input->control_id === 'new_rec_apply')
		{
			if (!isset($user_input->selected_media_url))
                return null;
			$doc = file_get_contents('/config/settings.properties');
			if (preg_match('/time_zone =(.*)\s/', $doc, $matches)) {
			$tmp = explode(':', $matches[1]);
			$rec_shift =($tmp[0] * 3600 ) + ($tmp[1] * 60 );}
			$rec_shift = $rec_shift / 3600;
			$seconds = '00';
			$year = date("Y");
			$media_url = MediaURL::decode($user_input->selected_media_url);
			$channel_id = $media_url->channel_id;
			$channels = $this->tv->get_channels();
			$c = $channels->get($channel_id);
            $title = $c->get_title();
			$streaming_url = $c->streaming_url();
			$selected_media_url = $media_url->selected_media_url;
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
			return ActionFactory::show_title_dialog("Время окончания записи указанно не верно.");
			$cron_file = '/tmp/cron/crontabs/root';
			$hrs_s = substr($rec_start_t, 0, 2);
			$min_s = substr($rec_start_t, -2);
			$time_s = $hrs_s .":".$min_s;
			if (!preg_match('/^([0-1][0-9]|[2][0-3]):([0-5][0-9])$/', $time_s))
			return ActionFactory::show_title_dialog("Время начала записи указанно не верно.");

			$data_s = $day_s .":".$mns_s;
			if (!preg_match('/^([0-2][0-9]|[3][0-1]):([0-1][0-9])$/', $data_s))
			return ActionFactory::show_title_dialog("Дата начала записи указанно не верно.");
			$timestamp = mktime($hrs_s + $rec_shift, $min_s , $seconds, $mns_s, $day_s, $year);
			$unix_time = time();
			if ($unix_time > $timestamp)
			return ActionFactory::show_title_dialog("Время начала записи указанно не верно.");
			$hrs_s1 = strftime('%H',$timestamp);
			$min_s1 = strftime('%M',$timestamp);
			$day_s1 = strftime('%d',$timestamp);
			$mns_s1 = strftime('%m',$timestamp);
			$hrs_e = substr($rec_stop_t, 0, 2);
			$min_e = substr($rec_stop_t, -2);
			$time_e = $hrs_e .":".$min_e;
			if (!preg_match('/^([0-1][0-9]|[2][0-3]):([0-5][0-9])$/', $time_e))
			return ActionFactory::show_title_dialog("Время начала записи указанно не верно.");

			$data_e = $day_e .":".$mns_e;
			if (!preg_match('/^([0-2][0-9]|[3][0-1]):([0-1][0-9])$/', $data_e))
			return ActionFactory::show_title_dialog("Дата окончания записи указанно не верно.");
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
			return ActionFactory::show_title_dialog("Накопитель для записи не найден!!! Подключите к плееру накопитель.");
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
			return ActionFactory::show_title_dialog("Добавлено $title Старт:$hrs_s:$min_s $day_s-$mns_s Стоп:$hrs_e:$min_e $day_e-$mns_e");
			//break;
			}
		else if ($user_input->control_id == 'add_proxy')
        {
            if (!isset($user_input->selected_media_url))
                return null;
            $media_url = MediaURL::decode($user_input->selected_media_url);
            $caption_g = $media_url->caption_g;
			$channel_id = $media_url->channel_id;
			$channels = $this->tv->get_channels();
			$c = $channels->get($channel_id);
			$title = $c->get_title();
			$tid = str_replace('-', '', crc32($title));
			$streaming_url = $c->streaming_url();
			if ($m3u_type==7)
			$m3u_dir = HD::get_mount_smb_path($ip_path, $smb_user, $smb_pass, 'm3u_dir');
			if ($m3u_type==3)
			return ActionFactory::show_title_dialog("Для плейлистов по ссылке (http) поиска прокси нет!");
			else
			{
			$m3u_file = $m3u_dir . '/' . $caption_g;
			if ($m3u_type==1)
			{
			$m3u_file = $altiptv_data_path . '/playlists/' . $caption_g;
			$m3u_dir = $altiptv_data_path . '/playlists/';
			}
			if (preg_match('|^http|', $m3u_file))
						return ActionFactory::show_title_dialog("Для плейлистов по ссылке (http) поиска прокси нет!");
			$daten = file_get_contents($m3u_file);
			$pr=false;
			if (!$daten) {
			hd_print("НЕ МОГУ ОТКРЫТЬ PLAYLIST");
			return ActionFactory::show_title_dialog("Плейлист не найден (перемещен или удален)!!!");
			}
			else
			{
			if ((!preg_match('/\/udp\//i', $streaming_url)) && (!preg_match("/\/.*:.*:.*:.*:.*:.*/i", $streaming_url)))
			return ActionFactory::show_title_dialog("Этот канал без прокси!!!");
			if (preg_match('/http:\/\/ts:/i', $streaming_url))
			$streaming_url = str_replace('http://ts://', 'http://', $streaming_url);
			if (preg_match('/dream.sh?/i', $streaming_url))
    		{
		     $tmp = explode('dream.sh?',  $streaming_url);
    		 $streaming_url = $tmp[1];
			}
			preg_match_all('|http:\/\/(.*):(.*?)\/.*|', $streaming_url, $tmp);
			$ip = $tmp[1][0];
			$port = $tmp[2][0];
			$ip_port = $ip . ':' . $port;
			$pars = explode('.', $ip);
			$n1 = $pars[0];
			$n2 = $pars[1];
			$n3 = $pars[2];
			$n4 = $pars[3];
			$end_ipport = false;
			if (file_exists( $m3u_dir . '/' . $caption_g . '.txt'))
			{
			$proxy_list_file = $m3u_dir . '/' . $caption_g . '.txt';
			$proxy_arr = file($proxy_list_file);
			$proxy_arr = array_values($proxy_arr);
			$result = count($proxy_arr);
			shuffle($proxy_arr);
			foreach($proxy_arr as $proxy_ip)
			{
			$port = explode(':', $proxy_ip);
			$ip = $port[0];
			$port = $port[1];
			hd_silence_warnings();
			$fp = @fsockopen($ip, $port, $errno, $errstr, 0.2);
			hd_restore_warnings();
			if ($fp)
			{
			$end_ipport = $ip . ":" . $port;
			$end_ipport = str_replace("\n", "", $end_ipport);
			$end_ipport = str_replace("\r", "", $end_ipport);
			$daten = str_replace($ip_port, $end_ipport, $daten);
			$dateihandle1 = fopen($m3u_file,"w");
			if (!$dateihandle1)
			hd_print("НЕ МОГУ ЗАПИСАТЬ НА USB/HDD");
			fwrite($dateihandle1, $daten);
			@fclose($dateihandle1);
			@fclose($fp);
			break;
			}
			}
			if (!$end_ipport)
			{
			$prov = "Плейлист: $caption_g";
			$ipd = "Все ($result шт.) IP адреса proxy из файла: $caption_g.txt";
			$url = "ПРОВЕРЬТЕ: возможно этот список proxy не для этого плейлиста.";
			$defs = $this->do_get_proxy_defs($prov, $port, $ipd, $url, $plugin_cookies);
			$do_proxy = UserInputHandlerRegistry::create_action($this, 'add_proxy');
			$attrs['timer'] = ActionFactory::timer(3000);
			$attrs['actions'] = array(GUI_EVENT_TIMER => ActionFactory::close_dialog_and_run($do_proxy));
			$attrs['dialog_params'] = array('frame_style' => DIALOG_FRAME_STYLE_GLASS);
			return  ActionFactory::show_dialog
			(
			"Прокси из списка $caption_g.txt не найден, повторить поиск?",
			$defs,
			true,0,$attrs);
			}
			else
			{
			$perform_new_action = UserInputHandlerRegistry::create_action(
                    $this, 'cleer_folder');
			return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);
			}
			}
			elseif (preg_match('/^(25[0-5]|2[0-4][0-9]|[0-1][0-9]{2}|[0-9]{2}|[0-9])(\.(25[0-5]|2[0-4][0-9]|[0-1][0-9]{2}|[0-9]{2}|[0-9])){3}-(25[0-5]|2[0-4][0-9]|[0-1][0-9]{2}|[0-9]{2}|[0-9])(\.(25[0-5]|2[0-4][0-9]|[0-1][0-9]{2}|[0-9]{2}|[0-9])){3}$/i', $caption_g))
			{
			$ipds = explode('-', $caption_g);
			$ip1 = $ipds[0];
			$ip2 = $ipds[1];
			$an = explode('.', $ip1);
			$an = $an[2];
			$en = explode('.', $ip2);
			$en = $en[2];
			$an = rand($an, $en);
			$en = $an;
			$ipd = $caption_g;
			$prov = "Диапазон из названия плейлиста";
			}
			elseif (file_exists(DuneSystem::$properties['tmp_dir_path'].'/'  . $caption_g . $tid . '.ipi'))
			{
			$ipi_file = DuneSystem::$properties['tmp_dir_path'].'/'  . $caption_g . $tid . '.ipi';
			$ipi_info = file_get_contents($ipi_file);
			$tmp = explode('prov:', $ipi_info);
			$ipd = $tmp[0];
			$prov = $tmp[1];
			$ipds = explode('-', $ipd);
			$ip1 = $ipds[0];
			$ip2 = $ipds[1];
			$an = explode('.', $ip1);
			$an = $an[2];
			$en = explode('.', $ip2);
			$en = $en[2];
			$an = rand($an, $en);
			$en = $an;
			}
			else
			{
			$domain = $ip;
			$whis_url = 'http://1whois.ru/?url=' . $domain;
			$page = HD::http_get_document($whis_url);
			$page = iconv('windows-1251', 'UTF-8',$page);
			$page = str_replace("&nbsp;", "", $page);
			$ipd = explode('inetnum:', $page);
			$ipd = strstr($ipd[1], '<br />', true);
			$prov = explode('descr:', $page);
			$prov = strstr($prov[1], '<br />', true);
			$prov = preg_replace('|\s+|', '', $prov);
			$ipd = preg_replace('|\s+|', '', $ipd);
			$new_ip_inf = $ipd . "prov:" . $prov;
			$ip_inf = DuneSystem::$properties['tmp_dir_path'].'/'  . $caption_g . $tid .'.ipi';
			if (!$ipd===false)
			{
			$date_ip = fopen($ip_inf,"w");
			if (!$date_ip)
				{
				hd_print("Не могу записать в tmp Что-то здесь не так!!!");
				}
			fwrite($date_ip, $new_ip_inf);
			@fclose($date_ip);
			}
			$ipds = explode('-', $ipd);
			$ip1 = $ipds[0];
			$ip2 = $ipds[1];
			$an = explode('.', $ip1);
			$an = $an[2];
			$en = explode('.', $ip2);
			$en = $en[2];
			$an = rand($an, $en);
			$en = $an;
			if (!$en) {
			return ActionFactory::show_title_dialog("whois сервис не отвечает или не доступен!!!");
			}
			}
			if (!$en) {
			return ActionFactory::show_title_dialog("Что-то здесь не так!!!");
			}
			else
			{
				$port_f = false;
				if (file_exists(DuneSystem::$properties['tmp_dir_path'].'/'  . $caption_g . '.prt'))
						{
						$prt_file = DuneSystem::$properties['tmp_dir_path'].'/'  . $caption_g . '.prt';
						$temp_port = file_get_contents($prt_file);
						if ($temp_port !== '0')
						{
						$port = $temp_port;
						$port_f = true;
						}
						}
				ini_set( 'max_execution_time', 180 );
				for ($n3=$an; $n3<=$en; $n3++)
				{
				$url = $n1.'.'.$n2.'.'.$n3;
					for ($i = 0; $i <= 255; $i++)
					{
					hd_silence_warnings();
					$fp = @fsockopen($url.'.'.$i, $port, $errno, $errstr, 0.2);
					hd_restore_warnings();
					if ($fp)
					{
					$pr = $url.'.'.$i;
					if ($port_f === true)
					{
					$pr_f = "$pr:$port";
					$daten = str_replace($ip_port, $pr_f, $daten);
					}
					else
					{
					$daten = str_replace($ip, $pr, $daten);
					}
					$dateihandle1 = fopen($m3u_file,"w");
					if (!$dateihandle1)
					hd_print("НЕ МОГУ ЗАПИСАТЬ НА USB/HDD");
					fwrite($dateihandle1, $daten);
					@fclose($dateihandle1);
					@fclose($fp);
					$proxy_type = isset($plugin_cookies->proxy_type) ?
					$plugin_cookies->proxy_type : '1';
					if ($proxy_type==2)
					{
					$per_proxy = "$pr:$port";
					$save_proxy = "$per_proxy\n";
					$proxy_list_file = $m3u_dir . '/_' . $caption_g . '.txt';
					$fp = fopen($proxy_list_file, 'a+');
					$proxy_list = file_get_contents($proxy_list_file);
					$pos = strpos($proxy_list, $per_proxy);
					if ($pos === false)
					{
					$save_proxy_list = fwrite($fp, $save_proxy);
					if (!$save_proxy_list)
					hd_print("НЕ МОГУ ЗАПИСАТЬ НА USB/HDD");
					}
					fclose($fp);
					}
					break;
					}
					}
					if ($fp)
					break;
				}
			}
			}
			if (!$pr)
			{
			$ipd = preg_replace('|\s+|', '', $ipd);
			$url = "$url.(0-255)";
			if ($port_f === true)
			{
			$defs = $this->do_get_proxy_port_defs($prov, $port, $ipd, $url, $plugin_cookies);
			$do_proxy = UserInputHandlerRegistry::create_action($this, 'add_proxy_count');
			}
			else
			{
			$defs = $this->do_get_proxy_defs($prov, $port, $ipd, $url, $plugin_cookies);
			$do_proxy = UserInputHandlerRegistry::create_action($this, 'add_proxy');
			}

			$attrs['timer'] = ActionFactory::timer(3000);
			$attrs['actions'] = array(GUI_EVENT_TIMER => ActionFactory::close_dialog_and_run($do_proxy));
			$attrs['dialog_params'] = array('frame_style' => DIALOG_FRAME_STYLE_GLASS);
					return  ActionFactory::show_dialog
							(
								"Прокси не найден, пробуем искать еще?",
								$defs,
								true,0,$attrs
							);
			}
			else
			{
			$perform_new_action = UserInputHandlerRegistry::create_action(
                    $this, 'cleer_folder');
			return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);
			}
			}
		}
		else if ($user_input->control_id == 'add_proxy_count')
        {
            if (!isset($user_input->selected_media_url))
                return null;

            $media_url = MediaURL::decode($user_input->selected_media_url);
            $caption_g = $media_url->caption_g;
			$channel_id = $media_url->channel_id;
			$channels = $this->tv->get_channels();
			$c = $channels->get($channel_id);
			$title = $c->get_title();
			$tid = str_replace('-', '', crc32($title));
			$streaming_url = $c->streaming_url();
			if ($m3u_type==7)
			$m3u_dir = HD::get_mount_smb_path($ip_path, $smb_user, $smb_pass, 'm3u_dir');
			if ($m3u_type==3)
			return ActionFactory::show_title_dialog("Для плейлистов по ссылке (http) поиска прокси нет!");
			else
			{
			$m3u_file = $m3u_dir . '/' . $caption_g;
			if ($m3u_type==1)
			{
			$m3u_file = $altiptv_data_path . '/playlists/' . $caption_g;
			$m3u_dir = $altiptv_data_path . '/playlists/';
			}
			if (preg_match('|^http|', $m3u_file))
						return ActionFactory::show_title_dialog("Для плейлистов по ссылке (http) поиска прокси нет!");
			$daten = file_get_contents($m3u_file);
			$pr=false;
			$count_doc = 0;
			if (file_exists(DuneSystem::$properties['tmp_dir_path'].'/'  . $caption_g . '.cnt')) {
			$count_file = DuneSystem::$properties['tmp_dir_path'].'/'  . $caption_g . '.cnt';
			$count_doc = file_get_contents($count_file);
			}
			if (!$daten) {
			return ActionFactory::show_title_dialog("Плейлист не найден (перемещен или удален)!!!");
			}
			else
			{
			if ((!preg_match('/\/udp\//i', $streaming_url)) && (!preg_match("/\/.*:.*:.*:.*:.*:.*/i", $streaming_url)))
			{
			return ActionFactory::show_title_dialog("Этот канал без прокси!!!");
			}
			if (preg_match('/http:\/\/ts:/i', $streaming_url))
			{
			$streaming_url = str_replace('http://ts://', 'http://', $streaming_url);
			}
			if (preg_match('/dream.sh?/i', $streaming_url))
    		{
		     $tmp = explode('dream.sh?',  $streaming_url);
    		 $streaming_url = $tmp[1];
			}
			preg_match_all('|http:\/\/(.*):(.*?)\/.*|', $streaming_url, $tmp);
			$ip = $tmp[1][0];
			$port = $tmp[2][0];
			$ip_port = $ip . ':' . $port;
			$pars = explode('.', $ip);
			$n1 = $pars[0];
			$n2 = $pars[1];
			$n3 = $pars[2];
			$n4 = $pars[3];
			$end_ipport = false;
			if (file_exists( $m3u_dir . '/' . $caption_g . '.txt'))
			{
			$proxy_list_file = $m3u_dir . '/' . $caption_g . '.txt';
			$proxy_arr = file($proxy_list_file);
			$proxy_arr = array_values($proxy_arr);
			$result = count($proxy_arr);
			if ($count_doc > $result)
			{
			$count_doc = 0;
			}
			if ($count_doc <= 0)
			{
			$count_doc = 1;
			}
			$count_doc_r = $count_doc - 1;
			$proxy_arr = array_slice($proxy_arr, $count_doc_r);
			foreach($proxy_arr as $proxy_ip)
			{
			 ++$count_doc;

			$port = explode(':', $proxy_ip);
			$ip = $port[0];
			$port = $port[1];
			hd_silence_warnings();
			$fp = @fsockopen($ip, $port, $errno, $errstr, 0.2);
			hd_restore_warnings();
			if ($fp)
			{
			$end_ipport = $ip . ":" . $port;
			$end_ipport = str_replace("\n", "", $end_ipport);
			$end_ipport = str_replace("\r", "", $end_ipport);
			$daten = str_replace($ip_port, $end_ipport, $daten);
			$dateihandle1 = fopen($m3u_file,"w");
			if (!$dateihandle1)
			hd_print("НЕ МОГУ ЗАПИСАТЬ НА USB/HDD");
			fwrite($dateihandle1, $daten);
			@fclose($dateihandle1);
			@fclose($fp);
			break;
			}
			}
			$count_file = DuneSystem::$properties['tmp_dir_path'].'/'  . $caption_g . '.cnt';
			$new_count = $count_doc;
			$date_count = fopen($count_file,"w");
			if (!$date_count)
			{
			return ActionFactory::show_title_dialog("Не могу записать в tmp Что-то здесь не так!!!");
			}
			fwrite($date_count, $new_count);
			@fclose($date_count);
			if (!$end_ipport)
			{
			$prov = "Плейлист: $caption_g";
			$ipd = "Все ($result шт.) IP адреса proxy из файла: $caption_g.txt";
			$url = "ПРОВЕРЬТЕ: возможно этот список proxy не для этого плейлиста.";
			$defs = $this->do_get_proxy_defs($prov, $port, $ipd, $url, $plugin_cookies);
			$do_proxy = UserInputHandlerRegistry::create_action($this, 'add_proxy_count');
			$attrs['timer'] = ActionFactory::timer(3000);
			$attrs['actions'] = array(GUI_EVENT_TIMER => ActionFactory::close_dialog_and_run($do_proxy));
			$attrs['dialog_params'] = array('frame_style' => DIALOG_FRAME_STYLE_GLASS);
			return  ActionFactory::show_dialog
			(
			"Прокси из списка $caption_g.txt не найден, повторить поиск?",
			$defs,
			true,0,$attrs);
			}
			else
			{
			$perform_new_action = UserInputHandlerRegistry::create_action(
                    $this, 'cleer_folder');
			return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);
			}
			}
			elseif (preg_match('/^(25[0-5]|2[0-4][0-9]|[0-1][0-9]{2}|[0-9]{2}|[0-9])(\.(25[0-5]|2[0-4][0-9]|[0-1][0-9]{2}|[0-9]{2}|[0-9])){3}-(25[0-5]|2[0-4][0-9]|[0-1][0-9]{2}|[0-9]{2}|[0-9])(\.(25[0-5]|2[0-4][0-9]|[0-1][0-9]{2}|[0-9]{2}|[0-9])){3}$/i', $caption_g))
			{
			$ipds = explode('-', $caption_g);
			$ip1 = $ipds[0];
			$ip2 = $ipds[1];
			$an = explode('.', $ip1);
			$an = $an[2];
			$en = explode('.', $ip2);
			$en = $en[2];
			$re = $en - $an;
			if ($count_doc > $re)
			$count_doc = 0;
			$an = $an + $count_doc;
			$en = $an;
			$ipd = $caption_g;
			$prov = "Диапазон из названия плейлиста";
			}
			elseif (file_exists(DuneSystem::$properties['tmp_dir_path'].'/'  . $caption_g . $tid . '.ipi'))
			{
			$ipi_file = DuneSystem::$properties['tmp_dir_path'].'/'  . $caption_g . $tid . '.ipi';
			$ipi_info = file_get_contents($ipi_file);
			$tmp = explode('prov:', $ipi_info);
			$ipd = $tmp[0];
			$prov = $tmp[1];
			$ipds = explode('-', $ipd);
			$ip1 = $ipds[0];
			$ip2 = $ipds[1];
			$an = explode('.', $ip1);
			$an = $an[2];
			$en = explode('.', $ip2);
			$en = $en[2];
			$re = $en - $an;
			if ($count_doc > $re)
			$count_doc = 0;
			$an = $an + $count_doc;
			$en = $an;
			}
			else
			{
			$domain = $ip;
			$whis_url = 'http://1whois.ru/?url=' . $domain;
			$page = HD::http_get_document($whis_url);
			$page = iconv('windows-1251', 'UTF-8',$page);
			$page = str_replace("&nbsp;", "", $page);
			$ipd = explode('inetnum:', $page);
			$ipd = strstr($ipd[1], '<br />', true);
			$prov = explode('descr:', $page);
			$prov = strstr($prov[1], '<br />', true);
			$prov = preg_replace('|\s+|', '', $prov);
			$ipd = preg_replace('|\s+|', '', $ipd);
			$new_ip_inf = $ipd . "prov:" . $prov;
			$ip_inf = DuneSystem::$properties['tmp_dir_path'].'/'  . $caption_g . $tid . '.ipi';
			if (!$ipd===false)
			{
			$date_ip = fopen($ip_inf,"w");
			if (!$date_ip)
				hd_print("Не могу записать в tmp Что-то здесь не так!!!");
			fwrite($date_ip, $new_ip_inf);
			@fclose($date_ip);
			}
			$ipds = explode('-', $ipd);
			$ip1 = $ipds[0];
			$ip2 = $ipds[1];
			$an = explode('.', $ip1);
			$an = $an[2];
			$en = explode('.', $ip2);
			$en = $en[2];
			$re = $en - $an;
			if ($count_doc > $re)
			$count_doc = 0;
			$an = $an + $count_doc;
			$en = $an;
			if (!$ip2)
			return ActionFactory::show_title_dialog("whois сервис не отвечает или не доступен!!!");
			}
			if (!$ip2)
			return ActionFactory::show_title_dialog("Что-то здесь не так!!!");
			else
			{
			$count_file = DuneSystem::$properties['tmp_dir_path'].'/'  . $caption_g . '.cnt';
			$new_count = $count_doc +1;
			$date_count = fopen($count_file,"w");
			if (!$date_count)
			return ActionFactory::show_title_dialog("Не могу записать в tmp Что-то здесь не так!!!");
			fwrite($date_count, $new_count);
			@fclose($date_count);
			$port_f = false;
			if (file_exists(DuneSystem::$properties['tmp_dir_path'].'/'  . $caption_g . '.prt'))
			{
			$prt_file = DuneSystem::$properties['tmp_dir_path'].'/'  . $caption_g . '.prt';
			$temp_port = file_get_contents($prt_file);
			if ($temp_port !== '0')
			{
			$port = $temp_port;
			$port_f = true;
			}
			}
			ini_set( 'max_execution_time', 180 );
			for ($n3=$an; $n3<=$en; $n3++)
			{
			$url = $n1.'.'.$n2.'.'.$n3;
			for ($i = 0; $i <= 255; $i++)
			{
			hd_silence_warnings();
			$fp = @fsockopen($url.'.'.$i, $port, $errno, $errstr, 0.2);
			hd_restore_warnings();
			if ($fp)
			{
			$pr = $url.'.'.$i;
			if ($port_f === true)
			{
			$pr_f = "$pr:$port";
			$daten = str_replace($ip_port, $pr_f, $daten);
			}
			else
			$daten = str_replace($ip, $pr, $daten);
			$dateihandle1 = fopen($m3u_file,"w");
			if (!$dateihandle1)
			hd_print("НЕ МОГУ ЗАПИСАТЬ НА USB/HDD");
			fwrite($dateihandle1, $daten);
			@fclose($dateihandle1);
			@fclose($fp);
			$proxy_type = isset($plugin_cookies->proxy_type) ?
			$plugin_cookies->proxy_type : '1';
			if ($proxy_type==2)
			{
			$per_proxy = "$pr:$port";
			$save_proxy = "$per_proxy\n";
			$proxy_list_file = $m3u_dir . '/_' . $caption_g . '.txt';
			$fp = fopen($proxy_list_file, 'a+');
			$proxy_list = file_get_contents($proxy_list_file);
			$pos = strpos($proxy_list, $per_proxy);
			if ($pos === false)
			{
			$save_proxy_list = fwrite($fp, $save_proxy);
			if (!$save_proxy_list)
			hd_print("НЕ МОГУ ЗАПИСАТЬ НА USB/HDD");
			}
			fclose($fp);
			}
			break;
			}
			}
			if ($fp)
			break;
			}
			}
			}
			if (!$pr)
			{
			$ipd = preg_replace('|\s+|', '', $ipd);
			$url = "$url.(0-255)";
			$show_count="$new_count-й из $re";
			if ($port_f === true)
			$defs = $this->do_get_proxy_count_port_defs($prov, $port, $ipd, $url, $show_count, $plugin_cookies);
			else
			$defs = $this->do_get_proxy_count_defs($prov, $port, $ipd, $url, $show_count, $plugin_cookies);
			$do_proxy = UserInputHandlerRegistry::create_action($this, 'add_proxy_count');
			$attrs['timer'] = ActionFactory::timer(3000);
			$attrs['actions'] = array(GUI_EVENT_TIMER => ActionFactory::close_dialog_and_run($do_proxy));
			$attrs['dialog_params'] = array('frame_style' => DIALOG_FRAME_STYLE_GLASS);
					return  ActionFactory::show_dialog
							(
								"Прокси не найден, пробуем искать еще?",
								$defs,
								true,0,$attrs
							);
			}
			else
			{
			$perform_new_action = UserInputHandlerRegistry::create_action(
                    $this, 'cleer_folder');
			return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);
			}
			}
		}
        return null;
    }

    ///////////////////////////////////////////////////////////////////////

    private function get_regular_folder_item($group, $c, &$plugin_cookies)
    {
        return array
        (
            PluginRegularFolderItem::media_url =>
                MediaURL::encode(
                    array(
                        'channel_id' => $c->get_id(),
                        'group_id' => $group->get_id(),
						'caption_g' => $c->pl_name(),
						)),
            PluginRegularFolderItem::caption => $c->get_title(),
            PluginRegularFolderItem::view_item_params => array
            (
                ViewItemParams::icon_path => $c->get_icon_url(),
                ViewItemParams::item_detailed_icon_path => $c->get_icon_url(),
            ),
            PluginRegularFolderItem::starred =>
                $this->tv->is_favorite_channel_id(
                    $c->get_id(), $plugin_cookies),
        );
    }

    public function get_all_folder_items(MediaURL $media_url, &$plugin_cookies)
    {
        $this->tv->folder_entered($media_url, $plugin_cookies);

        $this->tv->ensure_channels_loaded($plugin_cookies);

        $group = $this->tv->get_group($media_url->group_id);

        $items = array();

        foreach ($group->get_channels($plugin_cookies) as $c)
        {
            $items[] = $this->get_regular_folder_item(
                $group, $c, $plugin_cookies);
        }

        return $items;
    }
	public function do_get_proxy_defs($prov, $port, $ipd, $url, &$plugin_cookies)
    {

		$defs = array();
        ControlFactory::add_label($defs, "Провайдер:", $prov);
		ControlFactory::add_label($defs, "Диапазон IP адресов:", $ipd);
		ControlFactory::add_label($defs, "Просканирован IP адрес:", $url);
		ControlFactory::add_label($defs, "Порт сканирования:", $port);
        $do_proxy = UserInputHandlerRegistry::create_action($this, 'add_proxy');
		ControlFactory::add_custom_close_dialog_and_apply_buffon($defs,
		'_do_proxy', 'Искать proxy', 300, $do_proxy);
        ControlFactory::add_close_dialog_button($defs,
                'Отмена', 300);
        return $defs;
    }
	public function do_get_proxy_port_defs($prov, $port, $ipd, $url, &$plugin_cookies)
    {

		$defs = array();
        ControlFactory::add_label($defs, "Провайдер:", $prov);
		ControlFactory::add_label($defs, "Диапазон IP адресов:", $ipd);
		ControlFactory::add_label($defs, "Просканирован IP адрес:", $url);
		ControlFactory::add_label($defs, "Указан порт сканирования:", $port);
        $do_proxy = UserInputHandlerRegistry::create_action($this, 'add_proxy');
		ControlFactory::add_custom_close_dialog_and_apply_buffon($defs,
		'_do_proxy', 'Искать proxy', 300, $do_proxy);
		$reset_port = UserInputHandlerRegistry::create_action($this, 'reset_port');
		ControlFactory::add_custom_close_dialog_and_apply_buffon($defs,
		'_reset_port', 'Сброс порта', 300, $reset_port);
        ControlFactory::add_close_dialog_button($defs,
                'Отмена', 300);
        return $defs;
    }
	public function do_get_del_rec_defs(&$plugin_cookies)
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
	return $defs;
    }
	public function do_get_proxy_count_port_defs($prov, $port, $ipd, $url, $show_count, &$plugin_cookies)
    {
        $defs = array();
        ControlFactory::add_label($defs, "Провайдер:", $prov);
		ControlFactory::add_label($defs, "Диапазон IP адресов:", $ipd);
		ControlFactory::add_label($defs, "Просканирован IP адрес:", $url);
		ControlFactory::add_label($defs, "Просканирован:", $show_count);
		ControlFactory::add_label($defs, "Указан порт сканирования:", $port);
        $do_proxy = UserInputHandlerRegistry::create_action($this, 'add_proxy_count');
		ControlFactory::add_custom_close_dialog_and_apply_buffon($defs,
		'_do_proxy', 'Искать proxy', 300, $do_proxy);
		$reset_port = UserInputHandlerRegistry::create_action($this, 'reset_port');
		ControlFactory::add_custom_close_dialog_and_apply_buffon($defs,
		'_reset_port', 'Сброс порта', 300, $reset_port);
        ControlFactory::add_close_dialog_button($defs,
                'Отмена', 300);
        return $defs;
    }
	public function do_get_proxy_count_defs($prov, $port, $ipd, $url, $show_count, &$plugin_cookies)
    {
        $defs = array();
        ControlFactory::add_label($defs, "Провайдер:", $prov);
		ControlFactory::add_label($defs, "Диапазон IP адресов:", $ipd);
		ControlFactory::add_label($defs, "Просканирован IP адрес:", $url);
		ControlFactory::add_label($defs, "Просканирован:", $show_count);
		ControlFactory::add_label($defs, "Порт сканирования:", $port);
        $do_proxy = UserInputHandlerRegistry::create_action($this, 'add_proxy_count');
		ControlFactory::add_custom_close_dialog_and_apply_buffon($defs,
		'_do_proxy', 'Искать proxy', 300, $do_proxy);
        ControlFactory::add_close_dialog_button($defs,
                'Отмена', 300);
        return $defs;
    }
	public function do_get_back_scan_defs(&$plugin_cookies)
    {

		$defs = array();
		ControlFactory::add_button_close ($defs, $this, $add_params=null,'new_back_scan',  'Сканирование:', 'Запустить', 600);
		ControlFactory::add_button ($defs, $this, $add_params=null,'new_sh_back_scan', 'Расписание:', 'Задать время запуска', 600);
		ControlFactory::add_label($defs, "Внимание:", 'Время сканирования зависит от размера диапазона');
		ControlFactory::add_label($defs, "", 'IP адресов провайдера. Повторный запуск сканирования');
		ControlFactory::add_label($defs, "", 'завершает предыдущее сканирование.');
		$info_back_scan = UserInputHandlerRegistry::create_action($this, 'info_back_scan');
        if (file_exists(DuneSystem::$properties['tmp_dir_path'] . '/scan_inf')) {
		ControlFactory::add_custom_close_dialog_and_apply_buffon($defs,
		'_do_info_back_scan', 'Состояние сканирования', 600, $info_back_scan);
		$del_back_scan = UserInputHandlerRegistry::create_action($this, 'del_back_scan');
        ControlFactory::add_custom_close_dialog_and_apply_buffon($defs,
		'_do_del_back_scan', 'Остановить сканирование', 600, $del_back_scan);
		}
		ControlFactory::add_close_dialog_button($defs,
            'ОК', 250);
	
        return $defs;
    }
	public function do_get_change_grup_defs($garb, &$plugin_cookies)
    {
		$group_names = array();
		$group_names = DemoConfig::get_group($plugin_cookies);
		$defs = array();
        $garb_ops = array();
		foreach($group_names as $key => $value)
		{
        $garb_ops[$key] = "$value [$key]";
        }

        ControlFactory::add_combobox($defs, $this, null,
            'garb_type', 'Группа:',
            $garb, $garb_ops,0, $need_confirm = false, $need_apply = false
        );
		$do_garb_type = UserInputHandlerRegistry::create_action($this, 'garb_type');
        ControlFactory::add_custom_close_dialog_and_apply_buffon($defs,
		'_do_garb_type', 'ОК', 250, $do_garb_type);
        ControlFactory::add_close_dialog_button($defs,
            'Отмена', 250);

        return $defs;
    }
	public function do_get_one_rec_defs(&$plugin_cookies)
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
		ActionFactory::show_dialog('Расписание записи каналов', $defs, 1);
		ControlFactory::add_close_dialog_button($defs,
            'Ок', 350);
		return $defs;}
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

        return $defs;
    }
	public function do_get_info_back_scan_defs(&$plugin_cookies)
    {

		$defs = array();
		$info = DuneSystem::$properties['tmp_dir_path'] . '/scan_inf';
		if (!file_exists($info)){
		ControlFactory::add_label($defs, '', 'Фоновое сканирование не запущено.');}
		else {
		$texts = file($info);
		$texts = array_values($texts);
		foreach($texts as $text){
		$tmp = explode('|', $text);
		$title = $tmp[0];
		$inf = $tmp[1];
		ControlFactory::add_label($defs, $title, $inf);
		}}
		// $info_back_scan = UserInputHandlerRegistry::create_action($this, 'info_back_scan');
        // ControlFactory::add_custom_close_dialog_and_apply_buffon($defs,
		// '_do_info_back_scan', 'Обновить', 350, $info_back_scan);
        ControlFactory::add_close_dialog_button($defs,
            'ОК', 350);

        return $defs;
    }
	public function do_get_new_count_defs($count_doc,&$plugin_cookies)
    {
        $defs = array();
        $count = $count_doc;
        ControlFactory::add_text_field($defs,0,0,
            'count', 'Номер:',
            $count, 1, 0, 0, 1, 70, 0, false);
		$do_count_apply = UserInputHandlerRegistry::create_action($this, 'new_count_apply');
        ControlFactory::add_custom_close_dialog_and_apply_buffon($defs,
		'_do_count_apply', 'ОК', 450, $do_count_apply);
		$reset_count_apply = UserInputHandlerRegistry::create_action($this, 'reset_count');
        ControlFactory::add_custom_close_dialog_and_apply_buffon($defs,
		'_do_reset_count', 'Сбросить счетчик', 450, $reset_count_apply);
        ControlFactory::add_close_dialog_button($defs,
            'Отмена', 450);

        return $defs;
    }
	public function do_get_new_port_defs($port_doc,&$plugin_cookies)
    {
        $defs = array();
        $port = $port_doc;
        ControlFactory::add_text_field($defs,0,0,
            'port', 'Номер:',
            $port, 1, 0, 0, 1, 350, 0, false);
		$do_port_apply = UserInputHandlerRegistry::create_action($this, 'new_port_apply');
        ControlFactory::add_custom_close_dialog_and_apply_buffon($defs,
		'_do_port_apply', 'ОК', 250, $do_port_apply);
        ControlFactory::add_close_dialog_button($defs,
            'Отмена', 250);

        return $defs;
    }
	public function do_get_rename_ch_defs($title,&$plugin_cookies)
    {
        $defs = array();
        ControlFactory::add_text_field($defs,0,0,
            'newname', '',
            $title, 0, 0, 0, 1, 750, 0, false);
		$do_rename_ch_apply = UserInputHandlerRegistry::create_action($this, 'rename_ch_apply');
        ControlFactory::add_custom_close_dialog_and_apply_buffon($defs,
		'_rename_ch_apply', 'ОК', 250, $do_rename_ch_apply);
        ControlFactory::add_close_dialog_button($defs,
            'Отмена', 250);
        return $defs;
    }
	public function do_get_delete_ch_defs($title, $caption_g, &$plugin_cookies)
    {
        $defs = array();
        ControlFactory::add_label($defs, "", "Вы уверены что хотите удалить канал");
		ControlFactory::add_label($defs, "", "$title с плейлиста $caption_g ?");
		ControlFactory::add_label($defs, "", "Внимание: после удаления адрес потока востановить не возможно.");
		$do_delete_ch_apply = UserInputHandlerRegistry::create_action($this, 'delete_ch_apply');
        ControlFactory::add_custom_close_dialog_and_apply_buffon($defs,
		'_delete_ch_apply', 'Да', 250, $do_delete_ch_apply);
        ControlFactory::add_close_dialog_button($defs,
            'Нет', 250);
        return $defs;
    }
	public function do_get_hide_ch_defs($title, $caption_g, &$plugin_cookies)
    {
        $defs = array();
        ControlFactory::add_label($defs, "", "Вы уверены что хотите скрыть канал");
		ControlFactory::add_label($defs, "", "$title с плейлиста $caption_g ?");
		ControlFactory::add_label($defs, "", "Внимание: востановить отображение канала");
		ControlFactory::add_label($defs, "", "можно через Список скрытых каналов.");
		$do_hide_ch_apply = UserInputHandlerRegistry::create_action($this, 'hide_ch_apply');
        ControlFactory::add_custom_close_dialog_and_apply_buffon($defs,
		'_hide_ch_apply', 'Да', 250, $do_hide_ch_apply);
        ControlFactory::add_close_dialog_button($defs,
            'Нет', 250);
        return $defs;
    }
	public function do_get_add_parental_defs($title, &$plugin_cookies)
    {
        $defs = array();
        ControlFactory::add_label($defs, "", "Вы уверены что хотите закрыть канал");
		ControlFactory::add_label($defs, "", "$title Родительским контролем ?");
		ControlFactory::add_label($defs, "", "Внимание: открыть канал можно через");
		ControlFactory::add_label($defs, "", "Открыть канал (Parental)");
		$do_add_parental_apply = UserInputHandlerRegistry::create_action($this, 'add_parental_apply');
        ControlFactory::add_custom_close_dialog_and_apply_buffon($defs,
		'_add_parental_apply', 'Да', 250, $do_add_parental_apply);
        ControlFactory::add_close_dialog_button($defs,
            'Нет', 250);
        return $defs;
    }
	public function do_get_new_rec_conf_defs($rec_start_t, $rec_start_d, $rec_stop_t, $rec_stop_d, $inf, &$plugin_cookies)
    {

		$defs = array();
        ControlFactory::add_label($defs, "", "Вы уверены что хотите добавить задание записи?");
		ControlFactory::add_label($defs, "$rec_start_t - $rec_stop_t", "$inf");
		$add_params ['rec_start_t'] = $rec_start_t;
		$add_params ['rec_start_d'] = $rec_start_d;
		$add_params ['rec_stop_t'] = $rec_stop_t;
		$add_params ['rec_stop_d'] = $rec_stop_d;
		$add_params ['inf'] = $inf;
		$new_rec_apply = UserInputHandlerRegistry::create_action($this, 'new_rec_apply', $add_params);
        ControlFactory::add_custom_close_dialog_and_apply_buffon($defs,
		'_add_new_rec_apply', 'Да', 250, $new_rec_apply);
        ControlFactory::add_close_dialog_button($defs,
            'Нет', 250);
        return $defs;
    }
	public function do_get_new_sh_back_scan_defs(&$plugin_cookies)
		{
			$defs = array();
			$settings_file = '/config/settings.properties';
			$doc = file_get_contents($settings_file);
			if (preg_match('/time_zone =(.*)\s/', $doc, $matches)) {
			$tmp = explode(':', $matches[1]);
			$sleep_shift =($tmp[0] * 3600 ) + ($tmp[1] * 60 );}
			//hd_print("$matches[1] =>$sleep_shift");

			$unix_time = time();// - $sleep_shift;
			$date = date("m-d H:i:s" , $unix_time);
			$sbs_day = date("d", $unix_time);
			$sbs_mns = date("m", $unix_time);
			$sbs_hrs = date("H", $unix_time);
			$sbs_min = date("i", $unix_time);
			$sbs_wday = '*';
			ControlFactory::add_button ($defs, $this, $add_params=null,'cleer_back_scan',
			"Расписание фонового сканирования:", 'Показать', 250);
			ControlFactory::add_label($defs, "Время запуска:", "cинтаксис crontab");
			ControlFactory::add_text_field($defs,0,0,
            'sbs_min', 'Минуты (число от 0 до 59):',
            $sbs_min, 1, 0, 0, 1, 350, 0, false);
			ControlFactory::add_text_field($defs,0,0,
            'sbs_hrs', 'Часы (число от 0 до 23):',
            $sbs_hrs, 1, 0, 0, 1, 350, 0, false);
			ControlFactory::add_label($defs, "Внимание:", "используется внутренее время плеера");
			ControlFactory::add_label($defs, "", "$matches[1] часа");
			ControlFactory::add_text_field($defs,0,0,
			'sbs_day', 'День(число от 1 до 31):',
            $sbs_day, 1, 0, 0, 1, 350, 0, false);
			ControlFactory::add_text_field($defs,0,0,
			'sbs_mns', 'Месяц (число от 1 до 12):',
            $sbs_mns, 1, 0, 0, 1, 350, 0, false);
			ControlFactory::add_text_field($defs,0,0,
			'sbs_wday', 'День недели(число от 1 до 7):',
            $sbs_wday, 1, 0, 0, 1, 350, 0, false);

			$do_sh_back_scan = UserInputHandlerRegistry::create_action($this, 'sh_back_scan');
			ControlFactory::add_custom_close_dialog_and_apply_buffon($defs,
			'_do_sh_back_scan', 'ОК', 250, $do_sh_back_scan);

			ControlFactory::add_close_dialog_button($defs,
				'Отмена', 250);

			return $defs;
		}
	public function do_get_new_shedl_back_scan_defs($altiptv_data_path,&$plugin_cookies)
		{
			$defs = array();
			$scan_file = $altiptv_data_path . '/data_file/bg_scan';
			if (file_exists($scan_file)){
			$line = file($scan_file);
			$cron_file = '/tmp/cron/crontabs/root';
			$cline = file_get_contents($cron_file);
			$inf2 = 0;
			$i=1;
			foreach($line as $key => $value){
			if (preg_match("|bg_scan|",$value)){
			preg_match( '@(.*)wget.*bg_scan(.*)@i', $value , $matches  );
			$inf =$i .'. '. $matches[2];
			$q = preg_quote($matches[2]);
			$inf2 = $matches[1];
			$add_params ['inf'] = $value;
			ControlFactory::add_button ($defs, $this, $add_params,'del_bg_apply', $inf, 'Удалить', 250);
			if (!preg_match("|$q|",$cline))
			ControlFactory::add_button ($defs, $this, $add_params,'read_bg_apply', $inf2, 'Востановить', 250);
			else
			ControlFactory::add_label($defs, $inf2, '');
			$i++;
			}}
			if ($inf2 == 0)
			ControlFactory::add_label($defs, '', 'Заданий сканирования нет');
			}else{
			ControlFactory::add_label($defs, '', 'Заданий сканирования нет');}
			ControlFactory::add_close_dialog_button($defs,
				'Ок', 250);

			return $defs;
		}
	public function do_get_new_vsetvid_defs($title, $channel_id, &$plugin_cookies)
    {
        $defs = array();
        $vsetvid = 0;
		$b = mb_substr($title,0,1,"UTF-8");
		$search = mb_strtolower($title, 'UTF-8');
		$in_array = array
			(
				'нтв+',
				'(a la carte)',
				'(твоё тв)',
				'нтв плюс'
			);
		$search = str_replace($in_array, '', $search);
		if (preg_match("/\s/",$search)){
			$tmp = explode(" ", $search);
			if((iconv_strlen($tmp[0], "UTF-8")) >= 3)
				$search=$tmp[0];
			else
				$search=$tmp[1];
		}
		$search = preg_quote($search);
		if ($channel_id < 40000)
			$vsetvid = $channel_id;
		$texts = HD::get_items('vsetv_list', &$plugin_cookies);
		$chnmbr_list = HD::get_items('chnmbr_list', &$plugin_cookies);
		$chnmbr = array_search($vsetvid, $chnmbr_list);
		//ksort($texts, SORT_LOCALE_STRING);
		$ch_ops2 = array();
		$ch_ops3 = array();
		$ch_ops = array();
		$ch_ops[0] =
		$ch_ops2[0] =
		$ch_ops3[0] =
		$alfavit_ops[''] ='Выбор';
		foreach($texts as $key => $value){
			$ch_ops[$value] = "$key => $value";
			$name = mb_strtolower($key, 'UTF-8');
			if (preg_match("|$search|i",$name))
				$ch_ops2[$value] = "$key => $value";
			if (preg_match("|^$b|i", $key))
				$ch_ops3[$value] = "$key => $value";
		}
		$alfavit = HD::get_item('alfavit_item', &$plugin_cookies);
		$alfavit_ops = HD::alphabet();
		ControlFactory::add_label($defs, "", 'Выбор каналов на первую букву алфавита:');
        ControlFactory::add_combobox($defs, $this, null,
            'alfavit', 'Алфавит:',
            $alfavit, $alfavit_ops, 50, $need_confirm = false, $need_apply = false
        );
		$do_alfavit_apply = UserInputHandlerRegistry::create_action($this, 'alfavit_vsetvid_apply');
        ControlFactory::add_custom_close_dialog_and_apply_buffon($defs,
		'_do_alfavit_apply', 'Показать', 250, $do_alfavit_apply);

        ControlFactory::add_combobox($defs, $this, null,
            'vsetvid2', 'Похожие:',
            $vsetvid, $ch_ops2, 0, $need_confirm = false, $need_apply = false
        );
		ControlFactory::add_combobox($defs, $this, null,
            'vsetvid4', 'Первая буква:',
            $vsetvid, $ch_ops3, 0, $need_confirm = false, $need_apply = false
        );
		ControlFactory::add_combobox($defs, $this, null,
            'vsetvid3', 'Все:',
            $vsetvid, $ch_ops, 0, $need_confirm = false, $need_apply = false
        );
		ControlFactory::add_label($defs, "", 'Цифра 0 - сбросить:');
		ControlFactory::add_text_field($defs,0,0,
            'vsetvid', 'Задать ID:',
            $vsetvid, 1, 0, 0, 1, 350, 0, false);
		ControlFactory::add_text_field($defs,0,0,
            'chnmbr', 'Задать №очередн.',
            $chnmbr, 1, 0, 0, 1, 350, 0, false);
		$do_port_apply = UserInputHandlerRegistry::create_action($this, 'new_vsetvid_apply');
        ControlFactory::add_custom_close_dialog_and_apply_buffon($defs,
		'_do_vsetvid_apply', 'Применить', 350, $do_port_apply);
        ControlFactory::add_close_dialog_button($defs,
            'Отмена', 350);
		$do_del_vsetvid = UserInputHandlerRegistry::create_action($this, 'del_vsetvid_apply');
        ControlFactory::add_custom_close_dialog_and_apply_buffon($defs,
		'_do_del_vsetvid', 'Удалить из списка каналов vsetv', 0, $do_del_vsetvid);
        return $defs;
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
	public function do_kill_pin_control_defs(&$plugin_cookies)
    {
        $defs = array();
        $pin = '';
        ControlFactory::add_text_field($defs,0,0,
            'pin', 'Код закрытых каналов:',
            $pin, 1, 1, 0, 1, 500, 0, false);
        $do_kill_parent_apply = UserInputHandlerRegistry::create_action($this, 'kill_parent_apply');
        ControlFactory::add_custom_close_dialog_and_apply_buffon($defs,
		'_kill_parent_apply', 'ОК', 250, $do_kill_parent_apply);
        ControlFactory::add_close_dialog_button($defs,
            'Отмена', 250);
        return $defs;
    }
	public function do_get_new_rec_defs($media_url, $start_tvg_times, $stop_tvg_times, $tvg_rec_day, $inf, &$plugin_cookies)
    {
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
        return $defs;
    }
    public function get_archive(MediaURL $media_url)
    {
        return $this->tv->get_archive($media_url);
    }
	
	

}

///////////////////////////////////////////////////////////////////////////
?>
