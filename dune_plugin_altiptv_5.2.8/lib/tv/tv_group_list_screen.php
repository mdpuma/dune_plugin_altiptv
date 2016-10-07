<?php
///////////////////////////////////////////////////////////////////////////

require_once 'lib/abstract_preloaded_regular_screen.php';
require_once 'lib/abstract_controls_screen.php';
///////////////////////////////////////////////////////////////////////////

class TvGroupListScreen extends AbstractPreloadedRegularScreen
implements UserInputHandler
{
    const ID = 'tv_group_list';
	
    ///////////////////////////////////////////////////////////////////////

    protected $tv;

    ///////////////////////////////////////////////////////////////////////

    public function __construct($tv, $folder_views)
    {
        parent::__construct(self::ID, $folder_views);

        $this->tv = $tv;
		
		UserInputHandlerRegistry::get_instance()->register_handler($this);
    }

    ///////////////////////////////////////////////////////////////////////

    public function get_action_map(MediaURL $media_url, &$plugin_cookies)
    {	
		$ver = file_get_contents(DuneSystem::$properties['install_dir_path'].'/dune_plugin.xml');
		if (is_null($ver)) {
				hd_print('Can`t load dune_plugin.xml');
				return 'n/a';
			}
		$xml = HD::parse_xml_document($ver);
		$plugin_version = strval($xml->version);
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
				if (preg_match('/Выключение/i', $doc)){
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
		$background_rec ='';
		$rec_file = DuneSystem::$properties['tmp_dir_path'] . '/background_rec_stop';
		if (file_exists($rec_file)){
		$background_rec = 'REC:' . (trim(file_get_contents($rec_file)));
		}
		$info_view = UserInputHandlerRegistry::create_action(
                    $this, 'info_view');
		$info_view['caption'] = "Изменения в v$plugin_version";
		$sleep_view = UserInputHandlerRegistry::create_action(
                    $this, 'sleep_view');
		$sleep_view['caption'] = "Sleep таймер $sleep_time";
		$setup_view = ActionFactory::open_folder(
		DemoSetupScreen::get_media_url_str());
		$setup_view['caption'] = "Настройки  $background_rec";
		$popup_menu_action =
                UserInputHandlerRegistry::create_action(
                    $this, 'popup_menu');
        $cleer_action =
                UserInputHandlerRegistry::create_action(
                    $this, 'cleer_folder_key');
		$groups_action =
                UserInputHandlerRegistry::create_action(
                    $this, 'groups_folder_key');
		$info_back_scan_action =
                UserInputHandlerRegistry::create_action(
                    $this, 'info_back_scan');
		$view_info = 
				UserInputHandlerRegistry::create_action(
                    $this, 'group_view_info');
        $view_info['caption'] = "Информация о группе";
		return array
        (
            GUI_EVENT_KEY_ENTER => ActionFactory::open_folder(),
            GUI_EVENT_KEY_PLAY  => ActionFactory::tv_play(),
			GUI_EVENT_KEY_D_BLUE => $setup_view,
			GUI_EVENT_KEY_SETUP => $setup_view,
			GUI_EVENT_KEY_B_GREEN => $info_view,
			GUI_EVENT_KEY_C_YELLOW => $sleep_view,
			GUI_EVENT_KEY_CLEAR => $cleer_action,
			GUI_EVENT_KEY_POPUP_MENU => $popup_menu_action,
			GUI_EVENT_KEY_SELECT => $groups_action,
			GUI_EVENT_KEY_SEARCH => $info_back_scan_action,
			GUI_EVENT_KEY_INFO => $view_info,
        );
    }
	 public function get_handler_id()
    { return self::ID; }
    ///////////////////////////////////////////////////////////////////////

    public function get_all_folder_items(MediaURL $media_url, &$plugin_cookies)
    {
        $this->tv->folder_entered($media_url, $plugin_cookies);
        $this->tv->ensure_channels_loaded($plugin_cookies);
        $items = array();
		$group_list = HD::get_items('group_list', &$plugin_cookies);
		$i=1000;
        foreach ($this->tv->get_groups() as $group)
        {
            $media_url = $group->is_favorite_channels() ?
                TvFavoritesScreen::get_media_url_str() :
                TvChannelListScreen::get_media_url_str($group->get_id());
			$key = false;
			if ($group->get_id()=='__favorites')
				$n = 0;
			else if ($group->get_id()=='__all_channels')
				$n = 1;
			else{
				$key = array_search($group->get_title(), $group_list);
				if ($key === false)
					$n = $i;
				else
					$n = $key+2;
			}
			$items[$n] = array
				(
					PluginRegularFolderItem::media_url => $media_url,
					PluginRegularFolderItem::caption => $group->get_title(),
					PluginRegularFolderItem::view_item_params => array
					(
						ViewItemParams::icon_path => $group->get_icon_url(),
						ViewItemParams::item_detailed_icon_path => $group->get_icon_url()
					)
				);
			$i++;
        }
		ksort($items);
		$items = array_values($items);
        $this->tv->add_special_groups($items);

        return $items;
    }

    public function get_archive(MediaURL $media_url)
    {
        return $this->tv->get_archive($media_url);
    }
	 public function handle_user_input(&$user_input, &$plugin_cookies)
    {
        $altiptv_data_path = DemoConfig::get_altiptv_data_path(&$plugin_cookies);
        //foreach ($user_input as $key => $value)
            //hd_print("  $key => $value");
		
        if ($user_input->control_id == 'info_view')
        {
			$post_action = null;
			$doc = HD::http_get_document('http://dune-club.info/plugins/update/altiptv3/info.txt');
			ControlFactory::add_multiline_label($defs, '', $doc, 15);
							ControlFactory::add_custom_close_dialog_and_apply_buffon($defs, 'setup', 'Ok', 250,  $post_action);
							return ActionFactory::show_dialog('Информация об изменениях.', $defs,
									true, 1500);
        }
		else if ($user_input->control_id == 'popup_menu')
        {
            if (!isset($user_input->selected_media_url))
                return null;
			$group_tv = isset($plugin_cookies->group_tv) ?
			$plugin_cookies->group_tv : '1';
            $media_url = MediaURL::decode($user_input->selected_media_url);
			if (($media_url->screen_id == 'tv_favorites')||($media_url->group_id == '__all_channels')){
			$prov_pl_action =
                UserInputHandlerRegistry::create_action(
                    $this, 'prov_pl');
			$prov_pl_caption = 'Выбрать провайдерский плейлист';
			$hide_list_action =
                UserInputHandlerRegistry::create_action(
                    $this, 'hide_list_all');
			$hide_list_caption = 'Скрытые каналы всех плейлистов';
			$menu_items[] = array(
				GuiMenuItemDef::caption => $prov_pl_caption,
                GuiMenuItemDef::action => $prov_pl_action);	
			if ($group_tv == 1){	
			$menu_items[] = array(
				GuiMenuItemDef::caption => $hide_list_caption,
                GuiMenuItemDef::action => $hide_list_action);}
            return ActionFactory::show_popup_menu($menu_items);}
			else{
			$up_pl_action =
                UserInputHandlerRegistry::create_action(
                    $this, 'up_pl');
			$up_pl_caption = 'Поднять вверх';
			$rename_pl_action =
                UserInputHandlerRegistry::create_action(
                    $this, 'rename_pl');
			if ($group_tv == 1)
            $rename_pl_caption = 'Переименовать плейлист';
			if (($group_tv == 2)||($group_tv == 5))
            $rename_pl_caption = 'Переименовать категорию';
			if ($group_tv == 3)
            $rename_pl_caption = 'Переименовать плейлист/категорию';
			$prov_pl_action =
                UserInputHandlerRegistry::create_action(
                    $this, 'prov_pl');
			$prov_pl_caption = 'Выбрать провайдерский плейлист';
			$hide_list_action =
                UserInputHandlerRegistry::create_action(
                    $this, 'hide_list');
			$hide_list_caption = 'Скрытые каналы плейлиста';
			$add_cat_action =
                UserInputHandlerRegistry::create_action(
                    $this, 'add_category');
			$add_cat_caption = 'Добавить категорию';
			$del_cat_action =
                UserInputHandlerRegistry::create_action(
                    $this, 'del_category');
			$del_cat_caption = 'Удалить категорию';
			$menu_items[] = array(
                GuiMenuItemDef::caption => $rename_pl_caption,
                GuiMenuItemDef::action => $rename_pl_action);	
			if ($group_tv == 1){	
			$menu_items[] = array(
				GuiMenuItemDef::caption => $hide_list_caption,
                GuiMenuItemDef::action => $hide_list_action);
			$user_agent_action =
                UserInputHandlerRegistry::create_action(
                    $this, 'add_user_agent');
			$user_agent_caption = 'Задать каналам плейлиста User Agent';
			$menu_items[] = array(
				GuiMenuItemDef::caption => $user_agent_caption,
                GuiMenuItemDef::action => $user_agent_action);}
			if (($group_tv == 2)||($group_tv == 5)){
			$menu_items[] = array(
				GuiMenuItemDef::caption => $add_cat_caption,
                GuiMenuItemDef::action => $add_cat_action);
			}
			if (($group_tv == 2)&&(($media_url->group_id) > 13)){
			$menu_items[] = array(
				GuiMenuItemDef::caption => $del_cat_caption,
                GuiMenuItemDef::action => $del_cat_action);	
				}
			if (($group_tv == 5)&&(($media_url->group_id) > 13)){
			$menu_items[] = array(
				GuiMenuItemDef::caption => $del_cat_caption,
                GuiMenuItemDef::action => $del_cat_action);	
				}
			$menu_items[] = array(
				GuiMenuItemDef::caption => $prov_pl_caption,
                GuiMenuItemDef::action => $prov_pl_action);
			$menu_items[] = array(
				GuiMenuItemDef::caption => $up_pl_caption,
                GuiMenuItemDef::action => $up_pl_action
				);	
            return ActionFactory::show_popup_menu($menu_items);}
        }
		else if ($user_input->control_id == 'rename_del_menu')
			{
			if (!isset($user_input->selected_media_url))
                return null;
			$media_url = MediaURL::decode($user_input->selected_media_url);
            $group = $this->tv->get_group($media_url->group_id);
			$name_pl = $group->get_title();
			$name_pl_defs = array();
			$link = $altiptv_data_path . '/data_file/name_pl';
			if (file_exists($link)){
			$doc = file_get_contents($link);
			$name_pl_defs = unserialize($doc);
			$key = array_search($name_pl, $name_pl_defs);
			if ($key == true){
			unset ($name_pl_defs [$key]);
			$skey = serialize($name_pl_defs);
			$date_vsetv = fopen($link,"w");
			if (!$date_vsetv)
				{
				return ActionFactory::show_title_dialog("Не могу записать name_pl Что-то здесь не так!!!");
				}
			fwrite($date_vsetv, $skey);
			@fclose($date_vsetv);
			$perform_new_action = null;
			return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);
			}
			else
			return ActionFactory::show_title_dialog("Плейлист и категория не были переименованы!!!");
			}
			else
			return ActionFactory::show_title_dialog("Плейлист и категория не были переименованы!!!");
			}
		else if ($user_input->control_id == 'prov_pl_apply')
			{
			if (!isset($user_input->selected_media_url))
                return null;
			$prov_pl = $user_input->prov_pl;
			$plugin_cookies->prov_pl = $prov_pl;
			$perform_new_action = null;
			return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);
			}
		else if ($user_input->control_id == 'hide_list_all')
			{
			if (!isset($user_input->selected_media_url))
                return null;
			$prov_pl = isset($plugin_cookies->prov_pl) ?
			$plugin_cookies->prov_pl : 0;
			$hide_ch_defs = array();
			$name_pl_defs = array();
			$link = $altiptv_data_path . '/data_file/hide_ch';
			if (!file_exists($link)){
			return ActionFactory::show_title_dialog("Скрытых каналов нет");
			}
			$links = $altiptv_data_path . '/data_file/name_pl';
			if (file_exists($links)){
			$docs = file_get_contents($links);
			$name_pl_defs = unserialize($docs);
			}
			$doch = file_get_contents($link);
			$hide_ch_defs = unserialize($doch);
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
			$add_params ['key'] = 'hide_list_all';
			ControlFactory::add_button_close ($defs, $this, $add_params,'cleer_hide', $ch_pl, 'Востановить', 0);
			$i++;}
			$cleer_hide_all = UserInputHandlerRegistry::create_action($this, 'cleer_hide_all_pl');
			ControlFactory::add_custom_close_dialog_and_apply_buffon($defs, '_del', 'Востановить все', 0,  $cleer_hide_all);
			$cleer_hide_pl = UserInputHandlerRegistry::create_action($this, 'cleer_hide_pl');
			ControlFactory::add_custom_close_dialog_and_apply_buffon($defs, '_del', 'Удалить из списка каналы без плейлистов', 0,  $cleer_hide_pl);
			ControlFactory::add_close_dialog_button($defs,
            'Отмена', 350);
			return ActionFactory::show_dialog("Скрытые каналы:", $defs, 1);		
			}
		else if ($user_input->control_id == 'rename_pl_apply')
			{
			if (!isset($user_input->selected_media_url))
                return null;
			$newname = $user_input->newname;
			if ($newname == '')
			return ActionFactory::show_title_dialog("Новое имя не может быть пустым!");
            $media_url = MediaURL::decode($user_input->selected_media_url);
            $group = $this->tv->get_group($media_url->group_id);
			$name_pl = $group->get_title();
			if ($newname == $name_pl)
			return ActionFactory::show_title_dialog("Мда...");
			$name_pl_defs = array();
			$link = $altiptv_data_path . '/data_file/name_pl';
			if (file_exists($link)){
			$doc = file_get_contents($link);
			$name_pl_defs = unserialize($doc);
			$key = array_search($name_pl, $name_pl_defs);
			if ($key == true)
			$name_pl = $key;
			}
			$name_pl_defs [$name_pl] = $newname;
			$skey = serialize($name_pl_defs);
			$date_vsetv = fopen($link,"w");
			if (!$date_vsetv)
				{
				return ActionFactory::show_title_dialog("Не могу записать name_pl Что-то здесь не так!!!");
				}
			fwrite($date_vsetv, $skey);
			@fclose($date_vsetv);
			$perform_new_action = null;
			return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);
			}
		else if ($user_input->control_id == 'add_category_apply')
			{
			if (!isset($user_input->selected_media_url))
                return null;
			$newname = $user_input->newname;
			if ($newname == '')
			return ActionFactory::show_title_dialog("Новое имя не может быть пустым!");
			$name_pl_defs = array();
			$link = $altiptv_data_path . '/data_file/group_names';
			if (file_exists($link)){
			$doc = file_get_contents($link);
			$name_pl_defs = unserialize($doc);
			}
			$name_pl_defs [] = $newname;
			$skey = serialize($name_pl_defs);
			$date_vsetv = fopen($link,"w");
			if (!$date_vsetv)
				{
				return ActionFactory::show_title_dialog("Не могу записать group_names Что-то здесь не так!!!");
				}
			fwrite($date_vsetv, $skey);
			@fclose($date_vsetv);
			$perform_new_action = null;
			return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);
			}
		else if ($user_input->control_id == 'rename_pl')
			{
			if (!isset($user_input->selected_media_url))
                return null;
            $media_url = MediaURL::decode($user_input->selected_media_url);
            $group = $this->tv->get_group($media_url->group_id);
			$name_pl = $group->get_title();
			$defs = $this->do_get_rename_pl_defs($name_pl, $plugin_cookies);
			return  ActionFactory::show_dialog
			("Переименовать: $name_pl",
			$defs,
			true
			);
			}
		else if ($user_input->control_id == 'up_pl')
			{
				if (!isset($user_input->selected_media_url))
					return null;
				$media_url = MediaURL::decode($user_input->selected_media_url);
				$group = $this->tv->get_group($media_url->group_id);
				$name_pl = $group->get_title();				
				$group_list = HD::get_items('group_list', &$plugin_cookies);
				$group_list = array_flip($group_list);
				unset ($group_list[$name_pl]) ;
				$group_list = array_flip($group_list);
				array_unshift($group_list, $name_pl);
				HD::save_items('group_list', $group_list, &$plugin_cookies);
				return ActionFactory::invalidate_folders(array('tv_group_list'), null);
			}
		else if ($user_input->control_id == 'add_user_agent')
			{
			if (!isset($user_input->selected_media_url))
                return null;
            $media_url = MediaURL::decode($user_input->selected_media_url);
            $group = $this->tv->get_group($media_url->group_id);
			$name_pl = $name = $group->get_title();
			$name_pl_defs = HD::get_items('name_pl', &$plugin_cookies);
			$is_renemed = array_search($name_pl, $name_pl_defs);
				if ($is_renemed != false)
					$name_pl = $is_renemed;
			$user_agents = HD::get_items('user_agent', &$plugin_cookies);
			$user_agent = '';
			if (isset($user_agents[$name_pl]))
				$user_agent = $user_agents[$name_pl];
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
			$add_params ['name_pl'] = $name_pl;
			ControlFactory::add_close_dialog_and_apply_button(&$defs,
			$this, $add_params,
			'apply_user_agent', 'Применить', 0, $gui_params = null);
			ControlFactory::add_close_dialog_and_apply_button(&$defs,
			$this, $add_params,
			'del_user_agent', 'Очистить все User Agent', 0, $gui_params = null);
			$attrs['actions'] = null;
			return ActionFactory::show_dialog("Задать User Agent плейлисту $name", $defs,true,0,$attrs);
			}
		else if ($user_input->control_id == 'del_user_agent')
			{
				$user_agents = array();
				HD::save_items('user_agent', $user_agents, &$plugin_cookies);
				return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);
			}
		else if ($user_input->control_id == 'apply_user_agent')
			{
				if (!isset($user_input->selected_media_url))
                return null;
				$name_pl = $user_input->name_pl;
				$user_agents = HD::get_items('user_agent', &$plugin_cookies);
				$user_agent_list_ops = HD::get_items('user_agent_list_ops', &$plugin_cookies);
				if ((($user_input->user_agent_list == 'v')&&($user_input->new_user_agent == ''))||($user_input->user_agent_list == 'del'))
				{
					if (isset($user_agents[$name_pl])){
						unset ($user_agents[$name_pl]);
						HD::save_items('user_agent', $user_agents, &$plugin_cookies);
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
					$user_agents[$name_pl] = $user_input->new_user_agent;
					HD::save_items('user_agent', $user_agents, &$plugin_cookies);
					$perform_new_action = null;
					return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);
				}
				if (($user_input->user_agent_list !== 'v')&&($user_input->new_user_agent == '')){
					$user_agents[$name_pl] = $user_input->user_agent_list;
					HD::save_items('user_agent', $user_agents, &$plugin_cookies);
					$perform_new_action = null;
					return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);
				}
				
			}
		else if ($user_input->control_id == 'add_category')
			{
				if (!isset($user_input->selected_media_url))
                return null;
			$defs = $this->do_get_add_category_defs($plugin_cookies);
			return  ActionFactory::show_dialog
			("Добавить новую категорию",
			$defs,
			true
			);
			}
		else if ($user_input->control_id == 'del_category')
			{
			if (!isset($user_input->selected_media_url))
                return null;
            $media_url = MediaURL::decode($user_input->selected_media_url);
			$id_key = ($media_url->group_id - 13);
			$link = $altiptv_data_path . '/data_file/group_names';
			if (!file_exists($link))
			return ActionFactory::show_title_dialog("err!");
			$group_plus = unserialize(file_get_contents($link));
			unset ($group_plus[$id_key]);
			$skey = serialize($group_plus);
			$date_vsetv = fopen($link,"w");
			if (!$date_vsetv)
			return ActionFactory::show_title_dialog("Не могу записать group_names Что-то здесь не так!!!");
			fwrite($date_vsetv, $skey);
			@fclose($date_vsetv);
			$perform_new_action = UserInputHandlerRegistry::create_action(
                    $this, 'cleer_folder');
		return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);
			}
		else if ($user_input->control_id == 'cleer_hide_all_pl')
			{
			if (!isset($user_input->selected_media_url))
                return null;
			$link = $altiptv_data_path . '/data_file/hide_ch';
			if (!file_exists($link)){
			return ActionFactory::show_title_dialog("Скрытых каналов нет");
			}
			unlink($link);
			$perform_new_action = UserInputHandlerRegistry::create_action(
                    $this, 'cleer_hide_done');
			return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);
			}
		else if ($user_input->control_id == 'group_view_info')
			{
			if (!isset($user_input->selected_media_url))
                return null;
            $media_url = MediaURL::decode($user_input->selected_media_url);
            $group = $this->tv->get_group($media_url->group_id);
			$name_pl = $group->get_title();
			$group_id = $media_url->group_id;
			return ActionFactory::show_title_dialog("Иконка в altiptv_data/icons $name_pl.png или $group_id.png");
			}
		else if ($user_input->control_id == 'cleer_hide_all')
			{
			if (!isset($user_input->selected_media_url))
                return null;
			$link = $altiptv_data_path . '/data_file/hide_ch';
			if (!file_exists($link)){
			return ActionFactory::show_title_dialog("Скрытых каналов нет");
			}
			$media_url = MediaURL::decode($user_input->selected_media_url);
			$group = $this->tv->get_group($media_url->group_id);
			$name_pl = $group->get_title();
			$media_url = MediaURL::decode($user_input->selected_media_url);
			$group = $this->tv->get_group($media_url->group_id);
			$name_pl = $group->get_title();
			$prov_pl = isset($plugin_cookies->prov_pl) ?
			$plugin_cookies->prov_pl : 0;
			if (($prov_pl !== '0') &&(preg_match("/$name_pl/u",$prov_pl))){
			$tmp = explode('|', $prov_pl);
			$path_parts = pathinfo($tmp[1]);		
			$name_pl = $path_parts['filename'];
			}
			$name_pl_defs = array();
			$link = $altiptv_data_path . '/data_file/name_pl';
			if (file_exists($link)){
			$doc = file_get_contents($link);
			$name_pl_defs = unserialize($doc);
			$key = array_search($name_pl, $name_pl_defs);
			if ($key == true)
			$name_pl = $key;}
			$hide_ch_defs = array();
			$link = $altiptv_data_path . '/data_file/hide_ch';
			if (!file_exists($link)){
			return ActionFactory::show_title_dialog("Скрытых каналов нет");
			}
			$doch = file_get_contents($link);
			$hide_ch_defs = unserialize($doch);
			$i=1;
			foreach($hide_ch_defs as $key => $value){
			$tmp = explode('|', $key);
			if (!preg_match("/$name_pl/u",$tmp[0]))
			continue;
			unset ($hide_ch_defs[$key]);
			}
			$skey = serialize($hide_ch_defs);
			$date_vsetv = fopen($link,"w");
			if (!$date_vsetv)
				{
				return ActionFactory::show_title_dialog("Не могу записать hide_ch Что-то здесь не так!!!");
				}
			fwrite($date_vsetv, $skey);
			@fclose($date_vsetv);
			$perform_new_action = UserInputHandlerRegistry::create_action(
                    $this, 'cleer_hide_done');
			return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);
			}
			else if ($user_input->control_id == 'cleer_hide_pl')
			{
			if (!isset($user_input->selected_media_url))
                return null;
			$link = $altiptv_data_path . '/data_file/hide_ch';
			if (!file_exists($link)){
			return ActionFactory::show_title_dialog("Скрытых каналов нет");
			}
			$media_url = MediaURL::decode($user_input->selected_media_url);
			$prov_pl = isset($plugin_cookies->prov_pl) ?
			$plugin_cookies->prov_pl : 0;
			$name_pl_defs = array();
			$link = $altiptv_data_path . '/data_file/name_pl';
			if (file_exists($link)){
			$doc = file_get_contents($link);
			$name_pl_defs = unserialize($doc);
			}
			$all_pl = array();
			foreach ($this->tv->get_groups() as $g)
			{
				$name_pl = $g->get_title();
				$key = array_search($name_pl, $name_pl_defs);
				if ($key == true)
				$name_pl = $key;
				if (($prov_pl !== '0') &&(preg_match("/$name_pl/u",$prov_pl))){
				$tmp = explode('|', $prov_pl);
				$path_parts = pathinfo($tmp[1]);		
				$name_pl = $path_parts['filename'];
				}
				$all_pl[] = $name_pl;
			}
						
			$hide_ch_defs = array();
			$link = $altiptv_data_path . '/data_file/hide_ch';
			if (!file_exists($link)){
			return ActionFactory::show_title_dialog("Скрытых каналов нет");
			}
			$doch = file_get_contents($link);
			$hide_ch_defs = unserialize($doch);
			$i=1;
			foreach($hide_ch_defs as $key => $value){
			$tmp = explode('|', $key);
			$p = array_search($tmp[0], $all_pl);
			if ($p == true)
			continue;
			unset ($hide_ch_defs[$key]);
			}
			$skey = serialize($hide_ch_defs);
			$date_vsetv = fopen($link,"w");
			if (!$date_vsetv)
				{
				return ActionFactory::show_title_dialog("Не могу записать hide_ch Что-то здесь не так!!!");
				}
			fwrite($date_vsetv, $skey);
			@fclose($date_vsetv);
			$perform_new_action = UserInputHandlerRegistry::create_action(
                    $this, 'cleer_hide_done');
			return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);
			}
			else if ($user_input->control_id == 'cleer_hide_done')
			{
			return ActionFactory::show_title_dialog("Востановленно!");
			}
		else if ($user_input->control_id == 'hide_list')
			{
			if (!isset($user_input->selected_media_url))
                return null;
			$media_url = MediaURL::decode($user_input->selected_media_url);
			$group = $this->tv->get_group($media_url->group_id);
			$name_pl = $group->get_title();
			$rname = $name_pl;
			$prov_pl = isset($plugin_cookies->prov_pl) ?
			$plugin_cookies->prov_pl : 0;
			if (($prov_pl !== '0') &&(preg_match("/$name_pl/u",$prov_pl))){
			$tmp = explode('|', $prov_pl);
			$path_parts = pathinfo($tmp[1]);		
			$name_pl = $path_parts['filename'];
			}
			$name_pl_defs = array();
			$link = $altiptv_data_path . '/data_file/name_pl';
			if (file_exists($link)){
			$doc = file_get_contents($link);
			$name_pl_defs = unserialize($doc);
			$key = array_search($name_pl, $name_pl_defs);
			if ($key == true)
			$name_pl = $key;}
			$hide_ch_defs = array();
			$link = $altiptv_data_path . '/data_file/hide_ch';
			if (!file_exists($link)){
			return ActionFactory::show_title_dialog("Скрытых каналов нет");
			}
			$doch = file_get_contents($link);
			$hide_ch_defs = unserialize($doch);
			$add_params = array();
			$i=1;
			foreach($hide_ch_defs as $key => $value){
			$tmp = explode('|', $key);
			$ch_pl = "$i. Канал: $tmp[1] [".$rname."]";
			if (!preg_match("/$name_pl/u",$tmp[0]))
			continue;
			$add_params ['ch_pl'] = $key;
			$add_params ['key'] = 'hide_list';
			if (count($add_params) == 0)
			return ActionFactory::show_title_dialog("Скрытых каналов в этом плейлисте нет.");
			ControlFactory::add_button_close ($defs, $this, $add_params,'cleer_hide', $ch_pl, 'Востановить', 0);
			$i++;}
			if (count($add_params) == 0)
			return ActionFactory::show_title_dialog("Скрытых каналов в этом плейлисте нет.");
			$cleer_hide_all = UserInputHandlerRegistry::create_action($this, 'cleer_hide_all');
			ControlFactory::add_custom_close_dialog_and_apply_buffon($defs, '_del', 'Востановить все из этого плейлиста', 0,  $cleer_hide_all);
			$cleer_hide_pl = UserInputHandlerRegistry::create_action($this, 'cleer_hide_pl');
			ControlFactory::add_custom_close_dialog_and_apply_buffon($defs, '_del', 'Удалить из списка каналы без плейлистов', 0,  $cleer_hide_pl);
			ControlFactory::add_close_dialog_button($defs,
            'Отмена', 350);
			return ActionFactory::show_dialog("Скрытые каналы:", $defs, 1);		
			}
		else if ($user_input->control_id == 'prov_pl')
			{
			if (!isset($user_input->selected_media_url))
                return null;
            $media_url = MediaURL::decode($user_input->selected_media_url);
			$defs = $this->do_get_prov_pl_defs($plugin_cookies);
			if ($defs === false)
				return ActionFactory::show_title_dialog("Список провайдеров не доступен!!!");
			return  ActionFactory::show_dialog
			("Выбрать плейлист провайдера:",
			$defs,
			true
			);
			}
		else if ($user_input->control_id === 'cleer_folder_key')
		{
		$perform_new_action = null;
		return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);
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
		else if ($user_input->control_id === 'groups_folder_key')
		{
		$group_tv = isset($plugin_cookies->group_tv) ?
        $plugin_cookies->group_tv : '1';
		$group_tv = $group_tv + 1;
		if ($group_tv == 6)
		$group_tv = 1;
		$plugin_cookies->group_tv = $group_tv;
		if ($group_tv == 4)
			$perform_new_action = ActionFactory::open_folder('tv_channel_list');
		else
			$perform_new_action = null;
		return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);
		}
		if ($user_input->control_id == 'sleep_view')
        {
		$defs = $this->do_get_new_sleep_defs($plugin_cookies);
					return  ActionFactory::show_dialog
							(
								"Sleep таймер",
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
			$hide_ch_defs = array();
			$link = $altiptv_data_path . '/data_file/hide_ch';
			if (!file_exists($link)){
			return ActionFactory::show_title_dialog("Скрытых каналов нет");
			}
			$doch = file_get_contents($link);
			$hide_ch_defs = unserialize($doch);
			$is_hide = (array_key_exists($ch_pl, $hide_ch_defs)) ? 1 : 0;
					if ($is_hide == 0){
					$tmp = explode('|', $ch_pl);
					return ActionFactory::show_title_dialog("Канал $tmp[1] уже востановлен!");
					}
			unset ($hide_ch_defs [$ch_pl]);
			$skey = serialize($hide_ch_defs);
			$date_vsetv = fopen($link,"w");
			if (!$date_vsetv)
				{
				return ActionFactory::show_title_dialog("Не могу записать hide_ch Что-то здесь не так!!!");
				}
			fwrite($date_vsetv, $skey);
			@fclose($date_vsetv);
			$perform_new_action = UserInputHandlerRegistry::create_action(
                    $this, $user_input->key);
		return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);
			}
		if ($user_input->control_id == 'sleep_time_hour')
        {
			$control_id = $user_input->control_id;
			$sleep_time_hour = $user_input->{$control_id};
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
			$perform_new_action = null;
			return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);
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
			return ActionFactory::show_title_dialog("Таймер выключениня не был задан!");
			}
			shell_exec('crontab -e');
			$perform_new_action = null;
			return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);
		}
		}
		public function do_get_new_sleep_defs(&$plugin_cookies)
		{	$sleep_time_hour = 0;
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
				$sleep_time_hour, $sleep_time_ops, 0, true);
			$do_time_hour = UserInputHandlerRegistry::create_action($this, 'sleep_time_hour');
			ControlFactory::add_button ($defs, $this, $add_params=null,'cleer_sleep',
			"Сброс Sleep таймера:", 'Очистить таймеры', 0);	
			ControlFactory::add_close_dialog_button($defs,
				'OK', 250);
	 
			return $defs;
		} 
		public function do_get_prov_pl_defs(&$plugin_cookies)
		{
			
			$defs = array();
			$ch_ops = array();
			$prov_pl = isset($plugin_cookies->prov_pl) ?
			$plugin_cookies->prov_pl : 0;
			$ch_ops[0] = 'Не выбрано';
			$load_ch_ops = HD::load_prov_info();
			if ($load_ch_ops === false)
            return false;
			$ch_ops = array_merge ($ch_ops, $load_ch_ops);
			
			ControlFactory::add_label($defs, "Внимание:", 'Иконки и EPG присваиваются через POPUP');
			ControlFactory::add_label($defs, "", ' => Задать каналу id vsetv');
			ControlFactory::add_combobox($defs, $this, null,
            'prov_pl', 'Выбор провайдера:',
            $prov_pl, $ch_ops, 500, $need_confirm = false, $need_apply = false
			);
			$do_prov_pl_apply = UserInputHandlerRegistry::create_action($this, 'prov_pl_apply');
			ControlFactory::add_custom_close_dialog_and_apply_buffon($defs,
			'_prov_pl_apply', 'ОК', 250, $do_prov_pl_apply);
			ControlFactory::add_close_dialog_button($defs,
				'Отмена', 250);
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
		public function do_get_rename_pl_defs($title,&$plugin_cookies)
		{
			$defs = array();
			ControlFactory::add_text_field($defs,0,0,
				'newname', '',
				$title, 0, 0, 0, 1, 750, 0, false);
			$do_rename_ch_apply = UserInputHandlerRegistry::create_action($this, 'rename_pl_apply');
			ControlFactory::add_custom_close_dialog_and_apply_buffon($defs,
			'_rename_pl_apply', 'ОК', 250, $do_rename_ch_apply);
			$rename_del_menu = UserInputHandlerRegistry::create_action($this, 'rename_del_menu');
			ControlFactory::add_custom_close_dialog_and_apply_buffon($defs, '_del', 'Сброс', 250,  $rename_del_menu);
			ControlFactory::add_close_dialog_button($defs,
				'Отмена', 250);
			return $defs;
		}
		public function do_get_add_category_defs(&$plugin_cookies)
		{
			$defs = array();
			ControlFactory::add_text_field($defs,0,0,
				'newname', '',
				'', 0, 0, 0, 1, 750, 0, false);
			$add_category_ch_apply = UserInputHandlerRegistry::create_action($this, 'add_category_apply');
			ControlFactory::add_custom_close_dialog_and_apply_buffon($defs,
			'_add_category_apply', 'ОК', 250, $add_category_ch_apply);
			ControlFactory::add_close_dialog_button($defs,
				'Отмена', 250);
			return $defs;
		}
}

///////////////////////////////////////////////////////////////////////////
?>
