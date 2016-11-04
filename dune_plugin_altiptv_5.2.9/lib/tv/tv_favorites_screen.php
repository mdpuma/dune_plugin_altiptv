<?php

require_once 'lib/tv/tv.php';
require_once 'lib/abstract_preloaded_regular_screen.php';

class TvFavoritesScreen extends AbstractPreloadedRegularScreen
    implements UserInputHandler
{
    const ID = 'tv_favorites';

    public static function get_media_url_str()
    {
        return MediaURL::encode(
            array(
                'screen_id' => self::ID,
                'is_favorites' => true));
    }

    ///////////////////////////////////////////////////////////////////////

    private $tv;

    public function __construct(Tv $tv, $folder_views)
    {
        $this->tv = $tv;

        parent::__construct(self::ID, $folder_views);

        UserInputHandlerRegistry::get_instance()->register_handler($this);
    }

    ///////////////////////////////////////////////////////////////////////

    public function get_action_map(MediaURL $media_url, &$plugin_cookies)
    {
        $move_backward_favorite_action =
            UserInputHandlerRegistry::create_action(
                $this, 'move_backward_favorite');
        $move_backward_favorite_action['caption'] = 'Вверх';

        $move_forward_favorite_action =
            UserInputHandlerRegistry::create_action(
                $this, 'move_forward_favorite');
        $move_forward_favorite_action['caption'] = 'Вниз';

        $remove_favorite_action =
            UserInputHandlerRegistry::create_action(
                $this, 'remove_favorite');
        $remove_favorite_action['caption'] = 'Удалить из Избранного';

        $menu_items[] = array(
            GuiMenuItemDef::caption => 'Удалить из Избранного',
            GuiMenuItemDef::action => $remove_favorite_action);
		
		$actions_info =
            UserInputHandlerRegistry::create_action(
                $this, 'info');
				
        $popup_menu_action = ActionFactory::show_popup_menu($menu_items);

        return array
        (
            GUI_EVENT_KEY_ENTER => ActionFactory::tv_play(),
            GUI_EVENT_KEY_PLAY  => ActionFactory::tv_play(),
            GUI_EVENT_KEY_B_GREEN => $move_backward_favorite_action,
            GUI_EVENT_KEY_C_YELLOW => $move_forward_favorite_action,
            GUI_EVENT_KEY_D_BLUE => $remove_favorite_action,
            GUI_EVENT_KEY_POPUP_MENU => $popup_menu_action,
			GUI_EVENT_KEY_INFO => $actions_info,
        );
    }

    public function get_handler_id()
    { return self::ID; }

    private function get_update_action($sel_increment,
        &$user_input, &$plugin_cookies)
    {
        $parent_media_url = MediaURL::decode($user_input->parent_media_url);

        $num_favorites = 
            count($this->tv->get_fav_channel_ids($plugin_cookies));

        $sel_ndx = $user_input->sel_ndx + $sel_increment;
        if ($sel_ndx < 0)
            $sel_ndx = 0;
        if ($sel_ndx >= $num_favorites)
            $sel_ndx = $num_favorites - 1;

        $range = HD::create_regular_folder_range(
            $this->get_all_folder_items(
                $parent_media_url, $plugin_cookies));
        return ActionFactory::update_regular_folder(
            $range, true, $sel_ndx);
    }

    public function handle_user_input(&$user_input, &$plugin_cookies)
    {
        hd_print('Tv favorites: handle_user_input:');
       /*  foreach ($user_input as $key => $value)
            hd_print("  $key => $value"); */

        if ($user_input->control_id == 'move_backward_favorite')
        {
            if (!isset($user_input->selected_media_url))
                return null;

            $media_url = MediaURL::decode($user_input->selected_media_url);
            $channel_id = $media_url->channel_id;

            $this->tv->change_tv_favorites(PLUGIN_FAVORITES_OP_MOVE_UP,
                $channel_id, $plugin_cookies);

            return $this->get_update_action(-1, $user_input, $plugin_cookies);
        }
		else if ($user_input->control_id == 'info')
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
        else if ($user_input->control_id == 'move_forward_favorite')
        {
            if (!isset($user_input->selected_media_url))
                return null;

            $media_url = MediaURL::decode($user_input->selected_media_url);
            $channel_id = $media_url->channel_id;

            $this->tv->change_tv_favorites(PLUGIN_FAVORITES_OP_MOVE_DOWN,
                $channel_id, $plugin_cookies);

            return $this->get_update_action(1, $user_input, $plugin_cookies);
        }
        else if ($user_input->control_id == 'remove_favorite')
        {
            if (!isset($user_input->selected_media_url))
                return null;

            $media_url = MediaURL::decode($user_input->selected_media_url);
            $channel_id = $media_url->channel_id;

            $this->tv->change_tv_favorites(PLUGIN_FAVORITES_OP_REMOVE,
                $channel_id, $plugin_cookies);

            return $this->get_update_action(0, $user_input, $plugin_cookies);
        }

        return null;
    }

    ///////////////////////////////////////////////////////////////////////

    public function get_all_folder_items(MediaURL $media_url, &$plugin_cookies)
    {
        $this->tv->folder_entered($media_url, $plugin_cookies);

        $fav_channel_ids = $this->tv->get_fav_channel_ids($plugin_cookies);

        $items = array();

        foreach ($fav_channel_ids as $channel_id)
        {
            if (preg_match('/^\s*$/', $channel_id))
                continue;
			if ($channel_id==false)
                continue;
            try
            {
                $c = $this->tv->get_channel($channel_id);
            }
            catch (Exception $e)
            {
                hd_print("Warning: channel '$channel_id' not found.");
                continue;
            }

            array_push($items,
                array
                (
                    PluginRegularFolderItem::media_url =>
                        MediaURL::encode(
                            array(
                                'channel_id' => $c->get_id(),
                                'group_id' => '__favorites')),
                    PluginRegularFolderItem::caption => $c->get_title(),
                    PluginRegularFolderItem::view_item_params => array
                    (
                        ViewItemParams::icon_path => $c->get_icon_url(),
                        ViewItemParams::item_detailed_icon_path => $c->get_icon_url(),
                    ),
                    PluginRegularFolderItem::starred => false,
                ));
        }

        return $items;
    }

    public function get_archive(MediaURL $media_url)
    {
        return $this->tv->get_archive($media_url);
    }
}

?>
