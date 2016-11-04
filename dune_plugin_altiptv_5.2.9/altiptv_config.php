<?php

class DemoConfig
{
    const ALL_CHANNEL_GROUP_CAPTION     = 'Все каналы';
    const FAV_CHANNEL_GROUP_CAPTION     = 'Избранное';
    const USE_M3U_FILE = true;
    const CHANNEL_SORT_FUNC_CB = 'DemoConfig::sort_channels_cb';
	const CHANNEL_SORT_FUNC_BC = 'DemoConfig::sort_channels_bc';
    const CHANNEL_SORT_FUNC_BA = 'DemoConfig::sort_channels_ba';
    ///////////////////////////////////////////////////////////////////////
	
    public static function sort_channels_cb($a, $b)
    {
        return strnatcasecmp($a->get_title(), $b->get_title());
    }
	public static function sort_channels_bc($a, $b)
    {
        $_a = intval($a->get_number());
		$_b = intval($b->get_number());
		if (($_a >0)&&($_b > 0))
			return strnatcasecmp($_a, $_b);
		else
			return strnatcasecmp($_b, $_a);
    }
	public static function sort_channels_ba($a, $b)
    {
        $_a = intval($a->get_number());
		$_b = intval($b->get_number());
		if (($_a < 1)&&($_b < 1))
        		return strnatcasecmp($a->get_title(), $b->get_title());
		else if (($_a >0)&&($_b > 0))
			return strnatcasecmp($_a, $_b);
		else
			return strnatcasecmp($_b, $_a);
    }
	public static function get_group($plugin_cookies) {
		$group_data_path = self::get_altiptv_data_path(&$plugin_cookies) .'/data_file/group_names';
    	$group_names = array('Общие','Познавательные','Новостные','Развлекательные','Детские','Фильмы и сериалы','Музыкальные','Спортивные','Мужские','Взрослые','Региональные','Религиозные','Радио','HD каналы');
		if (file_exists($group_data_path)){
		$doc = file_get_contents($group_data_path);
		$items = unserialize($doc);
		foreach($items as $k => $v)
		$group_names [14 + $k] = $v;
		}
		return ($group_names);
    }
	public static function get_group_name($gid,$plugin_cookies) {
		$group_names = array();
    	$group_names = self::get_group($plugin_cookies);
		$gid = array_key_exists($gid, $group_names) ? $gid : 0;    
		return array($gid, $group_names[$gid]);
    }
	
	public static function get_altiptv_data_path(&$plugin_cookies) {
		$altdata_type = isset($plugin_cookies->altdata_type) ?
        $plugin_cookies->altdata_type : '1';
    	if ($altdata_type == 1)
		$altiptv_data_path = DuneSystem::$properties['data_dir_path']."/altiptv_data";
		else if ($altdata_type == 2)
		$altiptv_data_path = "/D/altiptv_data";
		else if ($altdata_type == 3){
		$altdata_smb_user = isset($plugin_cookies->altdata_smb_user) ? 
		$plugin_cookies->altdata_smb_user : 'guest';
		$altdata_smb_pass = isset($plugin_cookies->altdata_smb_pass) ? 
		$plugin_cookies->altdata_smb_pass : 'guest';
		$altdata_ip_path = isset($plugin_cookies->altdata_ip_path) ? 
		$plugin_cookies->altdata_ip_path : '';
		$tmp = explode('/', $altdata_ip_path);
		$ip_lan = $tmp[0].'/'.$tmp[1];
		$tmp = explode($ip_lan, $altdata_ip_path);
		$folder_lan = $tmp[1] . '/altiptv_data';
		$path_m = '/tmp/mnt/smb/path_m';
		$altiptv_data_path = $path_m .'/'. $folder_lan;
		$altiptv_data_path = preg_replace("/(\/{2,})/","/",$altiptv_data_path);
		if (!file_exists($altiptv_data_path)) {
		if (!file_exists($path_m))
		shell_exec("mkdir $path_m");
		shell_exec('mount -t cifs -o rw,username='.$altdata_smb_user.',password='.$altdata_smb_pass.' //'.$ip_lan." ".$path_m); }
		}else{
			$altiptv_data_path = $altdata_type . "/altiptv_data";
		}
		if (!file_exists($altiptv_data_path)) {
		$altiptv_data_path = DuneSystem::$properties['data_dir_path']."/altiptv_data";
		}
		return $altiptv_data_path;
    }
    ///////////////////////////////////////////////////////////////////////
    // Folder views.

    public static function GET_TV_GROUP_LIST_FOLDER_VIEWS()
    {
        return array(
            // 1) 170*98 1x5
			array
            (
                PluginRegularFolderView::async_icon_loading => false,

                PluginRegularFolderView::view_params => array
                (
                    ViewParams::num_cols => 1,
                    ViewParams::num_rows => 5,
                    ViewParams::paint_details => true,
                    ViewParams::zoom_detailed_icon => false,
					ViewParams::background_path=> DuneSystem::$properties['data_dir_path'].'/altiptv_data/icons/bg.jpg',
					ViewParams::background_order => 0,
					ViewParams::background_height => 1080,
					ViewParams::background_width => 1920,
					ViewParams::optimize_full_screen_background => true,
                ),

                PluginRegularFolderView::base_view_item_params => array
                (
    		    ViewItemParams::item_paint_icon => true,
    		    ViewItemParams::item_layout => HALIGN_LEFT,
    		    ViewItemParams::icon_valign => VALIGN_CENTER,
    		    ViewItemParams::icon_dx => 10,
    		    ViewItemParams::icon_dy => -5,
				ViewItemParams::icon_width => 170,
				ViewItemParams::icon_height => 98,
    		    ViewItemParams::item_caption_font_size => FONT_SIZE_NORMAL,
    		    ViewItemParams::item_caption_width => 950,

                ),

                PluginRegularFolderView::not_loaded_view_item_params => array (),
            ),
			// 2) 170*98 4x2
            array
            (
                PluginRegularFolderView::async_icon_loading => false,

                PluginRegularFolderView::view_params => array
                (
                    ViewParams::num_cols => 4,
                    ViewParams::num_rows => 2,
                    ViewParams::paint_details => false,
                    ViewParams::paint_sandwich => true,
                    ViewParams::sandwich_base => DuneSystem::$properties['data_dir_path'].'/altiptv_data/icons/null.png',
                    ViewParams::sandwich_mask => 'cut_icon://{name=sandwich_mask}',
                    ViewParams::sandwich_cover => 'cut_icon://{name=sandwich_cover}',
                    ViewParams::sandwich_width => 245,
                    ViewParams::sandwich_height => 140,
                    ViewParams::sandwich_icon_upscale_enabled => true,
                    ViewParams::sandwich_icon_keep_aspect_ratio => false,
					ViewParams::background_path=> DuneSystem::$properties['data_dir_path'].'/altiptv_data/icons/bg.jpg',
					ViewParams::background_order => 0,
					ViewParams::background_height => 1080,
					ViewParams::background_width => 1920,
					ViewParams::optimize_full_screen_background => true,
                ),

                PluginRegularFolderView::base_view_item_params => array
                (
                    ViewItemParams::item_paint_icon => true,
                    ViewItemParams::item_layout => HALIGN_CENTER,
                    ViewItemParams::icon_valign => VALIGN_CENTER,
                    ViewItemParams::item_paint_caption => true,
                    ViewItemParams::icon_scale_factor => 1.25,
                    ViewItemParams::icon_sel_scale_factor => 1.5,
                ),

                PluginRegularFolderView::not_loaded_view_item_params => array (),
            ),
			// 3) 245x140 5x3
			array
					(
						PluginRegularFolderView::async_icon_loading => false,

						PluginRegularFolderView::view_params => array
						(
							ViewParams::num_cols => 4,
							ViewParams::num_rows => 3,
							ViewParams::paint_icon_selection_box=> true,
							ViewParams::paint_path_box => true,
							ViewParams::paint_scrollbar => true,
							ViewParams::paint_help_line => true,
							ViewParams::paint_details => true,
							ViewParams::paint_content_box_background => false,
							ViewParams::background_path=> DuneSystem::$properties['data_dir_path'].'/altiptv_data/icons/bg.jpg',
							ViewParams::background_order => 0,
							ViewParams::background_height => 1080,
							ViewParams::background_width => 1920,
							ViewParams::optimize_full_screen_background => true,
							ViewParams::sandwich_width => 140,
							ViewParams::sandwich_height => 140,
							ViewParams::sandwich_icon_upscale_enabled => true,
							ViewParams::sandwich_icon_keep_aspect_ratio => false
						),

						PluginRegularFolderView::base_view_item_params => array
						(
							ViewItemParams::item_paint_icon => true,
							ViewItemParams::item_layout => HALIGN_CENTER,
							ViewItemParams::icon_valign => VALIGN_CENTER,
							ViewItemParams::item_paint_caption => true,
							ViewItemParams::icon_scale_factor => 1.25,
							ViewItemParams::icon_sel_scale_factor => 1.4,
						),

						PluginRegularFolderView::not_loaded_view_item_params => array ()
					),
					//4) 1x12 84x48
			array
            (
                PluginRegularFolderView::async_icon_loading => true,

                PluginRegularFolderView::view_params => array
                (
                    ViewParams::num_cols => 1,
                    ViewParams::num_rows => 12,
                    ViewParams::paint_details => true,
					ViewParams::background_path=> DuneSystem::$properties['data_dir_path'].'/altiptv_data/icons/bg.jpg',
					ViewParams::background_order => 0,
					ViewParams::background_height => 1080,
					ViewParams::background_width => 1920,
					ViewParams::optimize_full_screen_background => true,
                ),

                PluginRegularFolderView::base_view_item_params => array
                (
                    ViewItemParams::item_paint_icon => true,
                    ViewItemParams::item_layout => HALIGN_LEFT,
                    ViewItemParams::icon_valign => VALIGN_CENTER,
                    ViewItemParams::icon_dx => 10,
                    ViewItemParams::icon_dy => -5,
                    ViewItemParams::icon_width => 84,
                    ViewItemParams::icon_height => 48,
                    ViewItemParams::item_caption_width => 1060,
                    ViewItemParams::item_caption_font_size => 28,
                    ViewItemParams::icon_path => 'plugin_file://icons/channel_unset.png',
                ),

                PluginRegularFolderView::not_loaded_view_item_params => array (),
            ),
            //5) 5x4 245x140
			array
            (
                PluginRegularFolderView::async_icon_loading => false,

                PluginRegularFolderView::view_params => array
                (
                    ViewParams::num_cols => 5,
                    ViewParams::num_rows => 4,
                    ViewParams::paint_details => false,
                    ViewParams::paint_sandwich => true,
                    ViewParams::sandwich_base => DuneSystem::$properties['data_dir_path'].'/altiptv_data/icons/null.png',
                    ViewParams::sandwich_mask => 'cut_icon://{name=sandwich_mask}',
                    ViewParams::sandwich_cover => 'cut_icon://{name=sandwich_cover}',
                    ViewParams::sandwich_width => 245,
                    ViewParams::sandwich_height => 140,
                    ViewParams::sandwich_icon_upscale_enabled => true,
                    ViewParams::sandwich_icon_keep_aspect_ratio => false,
					ViewParams::background_path=> DuneSystem::$properties['data_dir_path'].'/altiptv_data/icons/bg.jpg',
					ViewParams::background_order => 0,
					ViewParams::background_height => 1080,
					ViewParams::background_width => 1920,
					ViewParams::optimize_full_screen_background => true,
                ),

                PluginRegularFolderView::base_view_item_params => array
                (
                    ViewItemParams::item_paint_icon => true,
                    ViewItemParams::item_layout => HALIGN_CENTER,
                    ViewItemParams::icon_valign => VALIGN_CENTER,
                    ViewItemParams::item_paint_caption => false,
                    ViewItemParams::icon_scale_factor => 1.0,
                    ViewItemParams::icon_sel_scale_factor => 1.2,
                ),

                PluginRegularFolderView::not_loaded_view_item_params => array (),
            ),
			//6)4x3 245x140
            array
            (
                PluginRegularFolderView::async_icon_loading => false,

                PluginRegularFolderView::view_params => array
                (
                    ViewParams::num_cols => 4,
                    ViewParams::num_rows => 3,
                    ViewParams::paint_details => false,
                    ViewParams::paint_sandwich => true,
                    ViewParams::sandwich_base => DuneSystem::$properties['data_dir_path'].'/altiptv_data/icons/null.png',
                    ViewParams::sandwich_mask => 'cut_icon://{name=sandwich_mask}',
                    ViewParams::sandwich_cover => 'cut_icon://{name=sandwich_cover}',
                    ViewParams::sandwich_width => 245,
                    ViewParams::sandwich_height => 140,
                    ViewParams::sandwich_icon_upscale_enabled => true,
                    ViewParams::sandwich_icon_keep_aspect_ratio => false,
					ViewParams::background_path=> DuneSystem::$properties['data_dir_path'].'/altiptv_data/icons/bg.jpg',
					ViewParams::background_order => 0,
					ViewParams::background_height => 1080,
					ViewParams::background_width => 1920,
					ViewParams::optimize_full_screen_background => true,
                ),

                PluginRegularFolderView::base_view_item_params => array
                (
                    ViewItemParams::item_paint_icon => true,
                    ViewItemParams::item_layout => HALIGN_CENTER,
                    ViewItemParams::icon_valign => VALIGN_CENTER,
                    ViewItemParams::item_paint_caption => false,
                    ViewItemParams::icon_scale_factor => 1.25,
                    ViewItemParams::icon_sel_scale_factor => 1.5,
                ),

                PluginRegularFolderView::not_loaded_view_item_params => array (),
            ),
			//7)3x2 245x140
	    array
            (
                PluginRegularFolderView::async_icon_loading => true,

                PluginRegularFolderView::view_params => array
                (
                    ViewParams::num_cols => 3,
                    ViewParams::num_rows => 2,
                    ViewParams::paint_details => false,
                    ViewParams::paint_sandwich => true,
                    ViewParams::sandwich_base => DuneSystem::$properties['data_dir_path'].'/altiptv_data/icons/null.png',
                    ViewParams::sandwich_mask => 'cut_icon://{name=sandwich_mask}',
                    ViewParams::sandwich_cover => 'cut_icon://{name=sandwich_cover}',
                    ViewParams::sandwich_width => 245,
                    ViewParams::sandwich_height => 140,
                    ViewParams::sandwich_icon_upscale_enabled => true,
                    ViewParams::sandwich_icon_keep_aspect_ratio => false,
					ViewParams::background_path=> DuneSystem::$properties['data_dir_path'].'/altiptv_data/icons/bg.jpg',
					ViewParams::background_order => 0,
					ViewParams::background_height => 1080,
					ViewParams::background_width => 1920,
					ViewParams::optimize_full_screen_background => true,
                ),

                PluginRegularFolderView::base_view_item_params => array
                (
                    ViewItemParams::item_paint_icon => true,
                    ViewItemParams::item_layout => HALIGN_CENTER,
                    ViewItemParams::icon_valign => VALIGN_CENTER,
                    ViewItemParams::item_paint_caption => false,
                    ViewItemParams::icon_scale_factor => 1.75,
                    ViewItemParams::icon_sel_scale_factor => 2,
                    ViewItemParams::icon_path => 'plugin_file://icons/channel_unset.png',
                ),

                PluginRegularFolderView::not_loaded_view_item_params => array (),
            ),
			//8) 3x1 246x246
			array
            (
                PluginRegularFolderView::async_icon_loading => true,

                PluginRegularFolderView::view_params => array
                (
                    ViewParams::num_cols => 5,
                    ViewParams::num_rows => 1,
                    ViewParams::paint_details => false,
                    ViewParams::paint_sandwich => true,
                    ViewParams::sandwich_base => DuneSystem::$properties['data_dir_path'].'/altiptv_data/icons/null.png',
                    ViewParams::sandwich_mask => 'cut_icon://{name=sandwich_mask}',
                    ViewParams::sandwich_cover => 'cut_icon://{name=sandwich_cover}',
					ViewParams::item_width => 246,
                    ViewParams::item_height => 246,
                    ViewParams::sandwich_icon_upscale_enabled => true,
                    ViewParams::sandwich_icon_keep_aspect_ratio => false,
					ViewParams::paint_icon_selection_box => true,
                    ViewParams::background_path=> DuneSystem::$properties['data_dir_path'].'/altiptv_data/icons/bg.jpg',
					ViewParams::background_order => 0,
					ViewParams::background_height => 1080,
					ViewParams::background_width => 1920,
                    ViewParams::paint_content_box_background => false,
                    ViewParams::optimize_full_screen_background => true,
					ViewParams::paint_scrollbar => false,
					ViewParams::orientation => 'horizontal',
					ViewParams::cycle_mode_enabled => true,
					ViewParams::paint_path_box => true,
					ViewParams::scroll_animation_enabled => true,
					ViewParams::cycle_mode_gap => 'yes',


                ),

                PluginRegularFolderView::base_view_item_params => array
                (
                    ViewItemParams::item_paint_icon => true,
                    ViewItemParams::item_layout => VALIGN_CENTER,
                    ViewItemParams::icon_valign => HALIGN_CENTER,
                    ViewItemParams::item_paint_caption => false,
                    ViewItemParams::icon_scale_factor => 1.0,
                    ViewItemParams::icon_sel_scale_factor => 1.0,
                    #ViewItemParams::icon_dy => -5,
                    ViewItemParams::icon_width => 246,
                    ViewItemParams::icon_height => 246,
					ViewItemParams::icon_sel_width => 246,
                    ViewItemParams::icon_sel_height => 246,
                ),

                PluginRegularFolderView::not_loaded_view_item_params => array (),
            ),
			//9)4x3 180x180
			array
            (
                PluginRegularFolderView::async_icon_loading => false,

                PluginRegularFolderView::view_params => array
                (
                    ViewParams::num_cols => 4,
                    ViewParams::num_rows => 3,
                    ViewParams::paint_details => false,
                    ViewParams::paint_sandwich => true,
                    ViewParams::sandwich_base => DuneSystem::$properties['data_dir_path'].'/altiptv_data/icons/null.png',
                    ViewParams::sandwich_mask => 'cut_icon://{name=sandwich_mask}',
                    ViewParams::sandwich_cover => 'cut_icon://{name=sandwich_cover}',
					ViewParams::item_width => 180,
                    ViewParams::item_height => 180,
                    ViewParams::sandwich_width => 180,
                    ViewParams::sandwich_height => 180,
					ViewParams::sandwich_icon_upscale_enabled => true,
                    ViewParams::sandwich_icon_keep_aspect_ratio => false,
                    ViewParams::paint_path_box => true,
                    ViewParams::background_path=> DuneSystem::$properties['data_dir_path'].'/altiptv_data/icons/bg.jpg',
					ViewParams::background_order => 0,
					ViewParams::background_height => 1080,
					ViewParams::background_width => 1920,
					ViewParams::sandwich_icon_keep_aspect_ratio => true,
                    ViewParams::paint_content_box_background => true,
                    ViewParams::optimize_full_screen_background => true,
                    ViewParams::sandwich_icon_upscale_enabled => true,
                    ViewParams::sandwich_icon_keep_aspect_ratio => true,
                ),

                PluginRegularFolderView::base_view_item_params => array
                (
                    ViewItemParams::item_paint_icon => true,
                    ViewItemParams::item_layout => VALIGN_CENTER,
                    ViewItemParams::icon_valign => HALIGN_CENTER,
                    ViewItemParams::item_paint_caption => false,
                    ViewItemParams::icon_scale_factor => 1.0,
                    ViewItemParams::icon_sel_scale_factor => 1.0,
                    ViewItemParams::icon_width => 180,
                    ViewItemParams::icon_height => 180,
					ViewItemParams::item_paint_caption => true,
                ),

                PluginRegularFolderView::not_loaded_view_item_params => array (),
            ),
			//10)5x3 120x120
            array
            (
                PluginRegularFolderView::async_icon_loading => false,

                PluginRegularFolderView::view_params => array
                (
                    ViewParams::num_cols => 5,
                    ViewParams::num_rows => 3,
                    ViewParams::paint_details => false,
                    ViewParams::paint_sandwich => true,
                    ViewParams::sandwich_base => DuneSystem::$properties['data_dir_path'].'/altiptv_data/icons/null.png',
                    ViewParams::sandwich_mask => 'cut_icon://{name=sandwich_mask}',
                    ViewParams::sandwich_cover => 'cut_icon://{name=sandwich_cover}',
					ViewParams::item_width => 120,
                    ViewParams::item_height => 120,
                    ViewParams::sandwich_width => 120,
                    ViewParams::sandwich_height => 120,
					ViewParams::sandwich_icon_upscale_enabled => true,
                    ViewParams::sandwich_icon_keep_aspect_ratio => false,
                    ViewParams::paint_path_box => true,
                    ViewParams::background_path=> DuneSystem::$properties['data_dir_path'].'/altiptv_data/icons/bg.jpg',
					ViewParams::background_order => 0,
					ViewParams::background_height => 1080,
					ViewParams::background_width => 1920,
                    ViewParams::background_order => 1,
                    ViewParams::paint_content_box_background => true,
                    ViewParams::optimize_full_screen_background => true,
                    ViewParams::sandwich_icon_upscale_enabled => true,
                    ViewParams::sandwich_icon_keep_aspect_ratio => true,
					ViewParams::paint_icon_selection_box => true,
                ),

                PluginRegularFolderView::base_view_item_params => array
                (
                    ViewItemParams::item_paint_icon => true,
                    ViewItemParams::item_layout => VALIGN_CENTER,
                    ViewItemParams::icon_valign => HALIGN_CENTER,
                    ViewItemParams::item_paint_caption => false,
                    ViewItemParams::icon_scale_factor => 1.0,
                    ViewItemParams::icon_sel_scale_factor => 1.0,
                    ViewItemParams::icon_width => 120,
                    ViewItemParams::icon_height => 120,
					ViewItemParams::item_paint_caption => true,
                ),

                PluginRegularFolderView::not_loaded_view_item_params => array (),
            )
        );
    }

    public static function GET_TV_CHANNEL_LIST_FOLDER_VIEWS()
    {
        return array(
            //.1) 1x10 75x55
			array
            (
                PluginRegularFolderView::async_icon_loading => false,

                PluginRegularFolderView::view_params => array
                (
                    ViewParams::num_cols => 1,
                    ViewParams::num_rows => 10,
                    ViewParams::paint_details => true,
					ViewParams::background_path=> DuneSystem::$properties['data_dir_path'].'/altiptv_data/icons/bg.jpg',
					ViewParams::background_order => 0,
					ViewParams::background_height => 1080,
					ViewParams::background_width => 1920,
					ViewParams::optimize_full_screen_background => true,
                ),

                PluginRegularFolderView::base_view_item_params => array
                (
    		    ViewItemParams::item_paint_icon => true,
    		    ViewItemParams::item_layout => HALIGN_LEFT,
    		    ViewItemParams::icon_valign => VALIGN_CENTER,
    		    ViewItemParams::icon_dx => 0,
    		    ViewItemParams::icon_dy => -5,
				ViewItemParams::icon_width => 75,
				ViewItemParams::icon_height => 55,
    		    ViewItemParams::item_caption_font_size => FONT_SIZE_NORMAL,
    		    ViewItemParams::item_caption_width => 1100,
                ),

                PluginRegularFolderView::not_loaded_view_item_params => array (),
            ),
			//.2) 1x15 75x43
			array
            (
                PluginRegularFolderView::async_icon_loading => true,

                PluginRegularFolderView::view_params => array
                (
                    ViewParams::num_cols => 2,
                    ViewParams::num_rows => 15,
                    ViewParams::paint_details => true,
					ViewParams::background_path=> DuneSystem::$properties['data_dir_path'].'/altiptv_data/icons/bg.jpg',
					ViewParams::background_order => 0,
					ViewParams::background_height => 1080,
					ViewParams::background_width => 1920,
					ViewParams::optimize_full_screen_background => true,
                ),

                PluginRegularFolderView::base_view_item_params => array
                (
                    ViewItemParams::item_paint_icon => true,
                    ViewItemParams::item_layout => HALIGN_LEFT,
                    ViewItemParams::icon_valign => VALIGN_CENTER,
                    ViewItemParams::icon_dx => 10,
                    ViewItemParams::icon_dy => -5,
                    ViewItemParams::icon_width => 75,
                    ViewItemParams::icon_height => 43,
                    ViewItemParams::item_caption_width => 485,
                    ViewItemParams::item_caption_font_size => FONT_SIZE_SMALL,
                    ViewItemParams::icon_path => 'plugin_file://icons/channel_unset.png',
                ),

                PluginRegularFolderView::not_loaded_view_item_params => array (),
            ),
			//.3) 1x10 75x55
			array
            (
                PluginRegularFolderView::async_icon_loading => false,

                PluginRegularFolderView::view_params => array
                (
                    ViewParams::num_cols => 1,
                    ViewParams::num_rows => 10,
                    ViewParams::paint_details => true,
					ViewParams::paint_sandwich => true,
                    ViewParams::sandwich_base => DuneSystem::$properties['data_dir_path'].'/altiptv_data/icons/null.png',
					ViewParams::background_path=> DuneSystem::$properties['data_dir_path'].'/altiptv_data/icons/bg.jpg',
					ViewParams::background_order => 0,
					ViewParams::background_height => 1080,
					ViewParams::background_width => 1920,
					ViewParams::optimize_full_screen_background => true,
                ),

                PluginRegularFolderView::base_view_item_params => array
                (
    		    ViewItemParams::item_paint_icon => true,
    		    ViewItemParams::item_layout => HALIGN_LEFT,
    		    ViewItemParams::icon_valign => VALIGN_CENTER,
    		    ViewItemParams::icon_dx => 0,
    		    ViewItemParams::icon_dy => -5,
				ViewItemParams::icon_width => 75,
				ViewItemParams::icon_height => 55,
    		    ViewItemParams::item_caption_font_size => FONT_SIZE_NORMAL,
    		    ViewItemParams::item_caption_width => 1100,
                ),

                PluginRegularFolderView::not_loaded_view_item_params => array (),
            ),
			//.4) 2x15 75x55
			array
            (
                PluginRegularFolderView::async_icon_loading => true,

                PluginRegularFolderView::view_params => array
                (
                    ViewParams::num_cols => 2,
                    ViewParams::num_rows => 15,
                    ViewParams::paint_details => true,
					ViewParams::background_path=> DuneSystem::$properties['data_dir_path'].'/altiptv_data/icons/bg.jpg',
					ViewParams::background_order => 0,
					ViewParams::background_height => 1080,
					ViewParams::background_width => 1920,
					ViewParams::optimize_full_screen_background => true,
                ),

                PluginRegularFolderView::base_view_item_params => array
                (
                    ViewItemParams::item_paint_icon => true,
                    ViewItemParams::item_layout => HALIGN_LEFT,
                    ViewItemParams::icon_valign => VALIGN_CENTER,
                    ViewItemParams::icon_dx => 10,
                    ViewItemParams::icon_dy => -5,
                    ViewItemParams::icon_width => 75,
                    ViewItemParams::icon_height => 55,
                    ViewItemParams::item_caption_width => 485,
                    ViewItemParams::item_caption_font_size => FONT_SIZE_SMALL,
                    ViewItemParams::icon_path => 'plugin_file://icons/channel_unset.png',
                ),

                PluginRegularFolderView::not_loaded_view_item_params => array (),
            ),
			//.5) 3x12 45x45
			array
            (
                PluginRegularFolderView::async_icon_loading => true,

                PluginRegularFolderView::view_params => array
                (
                    ViewParams::num_cols => 3,
                    ViewParams::num_rows => 12,
                    ViewParams::paint_details => false,
					ViewParams::background_path=> DuneSystem::$properties['data_dir_path'].'/altiptv_data/icons/bg.jpg',
					ViewParams::background_order => 0,
					ViewParams::background_height => 1080,
					ViewParams::background_width => 1920,
					ViewParams::optimize_full_screen_background => true,
                ),

                PluginRegularFolderView::base_view_item_params => array
                (
                    ViewItemParams::item_paint_icon => true,
                    ViewItemParams::item_layout => HALIGN_LEFT,
                    ViewItemParams::icon_valign => VALIGN_CENTER,
                    ViewItemParams::icon_dx => 10,
                    ViewItemParams::icon_dy => -5,
                    ViewItemParams::icon_width => 45,
                    ViewItemParams::icon_height => 45,
                    ViewItemParams::item_caption_width => 475,
                    ViewItemParams::item_caption_font_size => 3,
                    ViewItemParams::icon_path => 'plugin_file://icons/channel_unset.png',
                ),

                PluginRegularFolderView::not_loaded_view_item_params => array (),
            ),
			//.5) 5x4 245x140
            array
            (
                PluginRegularFolderView::async_icon_loading => false,

                PluginRegularFolderView::view_params => array
                (
                    ViewParams::num_cols => 5,
                    ViewParams::num_rows => 4,
                    ViewParams::paint_details => false,
                    ViewParams::paint_sandwich => true,
                    ViewParams::sandwich_base => DuneSystem::$properties['data_dir_path'].'/altiptv_data/icons/null.png',
                    ViewParams::sandwich_mask => 'cut_icon://{name=sandwich_mask}',
                    ViewParams::sandwich_cover => 'cut_icon://{name=sandwich_cover}',
                    ViewParams::sandwich_width => 245,
                    ViewParams::sandwich_height => 140,
                    ViewParams::sandwich_icon_upscale_enabled => true,
                    ViewParams::sandwich_icon_keep_aspect_ratio => false,
					ViewParams::background_path=> DuneSystem::$properties['data_dir_path'].'/altiptv_data/icons/bg.jpg',
					ViewParams::background_order => 0,
					ViewParams::background_height => 1080,
					ViewParams::background_width => 1920,
					ViewParams::optimize_full_screen_background => true,
                ),

                PluginRegularFolderView::base_view_item_params => array
                (
                    ViewItemParams::item_paint_icon => true,
                    ViewItemParams::item_layout => HALIGN_CENTER,
                    ViewItemParams::icon_valign => VALIGN_CENTER,
                    ViewItemParams::item_paint_caption => false,
                    ViewItemParams::icon_scale_factor => 1.0,
                    ViewItemParams::icon_sel_scale_factor => 1.2,
                    ViewItemParams::icon_path => 'plugin_file://icons/channel_unset.png'
                ),

                PluginRegularFolderView::not_loaded_view_item_params => array (),
            ),
			//.5) 4x3 245x140
            array
            (
                PluginRegularFolderView::async_icon_loading => false,

                PluginRegularFolderView::view_params => array
                (
                    ViewParams::num_cols => 4,
                    ViewParams::num_rows => 3,
                    ViewParams::paint_details => false,
                    ViewParams::paint_sandwich => true,
                    ViewParams::sandwich_base => DuneSystem::$properties['data_dir_path'].'/altiptv_data/icons/null.png',
                    ViewParams::sandwich_mask => 'cut_icon://{name=sandwich_mask}',
                    ViewParams::sandwich_cover => 'cut_icon://{name=sandwich_cover}',
                    ViewParams::sandwich_width => 245,
                    ViewParams::sandwich_height => 140,
                    ViewParams::sandwich_icon_upscale_enabled => true,
                    ViewParams::sandwich_icon_keep_aspect_ratio => false,
					ViewParams::background_path=> DuneSystem::$properties['data_dir_path'].'/altiptv_data/icons/bg.jpg',
					ViewParams::background_order => 0,
					ViewParams::background_height => 1080,
					ViewParams::background_width => 1920,
					ViewParams::optimize_full_screen_background => true,
                ),

                PluginRegularFolderView::base_view_item_params => array
                (
                    ViewItemParams::item_paint_icon => true,
                    ViewItemParams::item_layout => HALIGN_CENTER,
                    ViewItemParams::icon_valign => VALIGN_CENTER,
                    ViewItemParams::item_paint_caption => false,
                    ViewItemParams::icon_scale_factor => 1.25,
                    ViewItemParams::icon_sel_scale_factor => 1.5,
                    ViewItemParams::icon_path => 'plugin_file://icons/channel_unset.png',
                ),

                PluginRegularFolderView::not_loaded_view_item_params => array (),
            ),
			//.5) 4x4 245x140
            array
            (
                PluginRegularFolderView::async_icon_loading => false,

                PluginRegularFolderView::view_params => array
                (
                    ViewParams::num_cols => 4,
                    ViewParams::num_rows => 4,
                    ViewParams::paint_details => true,
                    ViewParams::paint_sandwich => true,
                    ViewParams::sandwich_base => DuneSystem::$properties['data_dir_path'].'/altiptv_data/icons/null.png',
                    ViewParams::sandwich_mask => 'cut_icon://{name=sandwich_mask}',
                    ViewParams::sandwich_cover => 'cut_icon://{name=sandwich_cover}',
                    ViewParams::sandwich_width => 245,
                    ViewParams::sandwich_height => 140,
                    ViewParams::sandwich_icon_upscale_enabled => true,
                    ViewParams::sandwich_icon_keep_aspect_ratio => false,
					ViewParams::background_path=> DuneSystem::$properties['data_dir_path'].'/altiptv_data/icons/bg.jpg',
					ViewParams::background_order => 0,
					ViewParams::background_height => 1080,
					ViewParams::background_width => 1920,
					ViewParams::optimize_full_screen_background => true,
                ),

                PluginRegularFolderView::base_view_item_params => array
                (
                    ViewItemParams::item_paint_icon => true,
                    ViewItemParams::item_layout => HALIGN_CENTER,
                    ViewItemParams::icon_valign => VALIGN_CENTER,
                    ViewItemParams::item_paint_caption => false,
                    ViewItemParams::icon_scale_factor => 1.0,
                    ViewItemParams::icon_sel_scale_factor => 1.2,
                    ViewItemParams::icon_path => 'plugin_file://icons/channel_unset.png',
                ),

                PluginRegularFolderView::not_loaded_view_item_params => array (),
            ),
			//.5) 3x3 245x140
            array
            (
                PluginRegularFolderView::async_icon_loading => false,

                PluginRegularFolderView::view_params => array
                (
                    ViewParams::num_cols => 3,
                    ViewParams::num_rows => 3,
                    ViewParams::paint_details => true,
                    ViewParams::paint_sandwich => true,
                    ViewParams::sandwich_base => DuneSystem::$properties['data_dir_path'].'/altiptv_data/icons/null.png',
                    ViewParams::sandwich_mask => 'cut_icon://{name=sandwich_mask}',
                    ViewParams::sandwich_cover => 'cut_icon://{name=sandwich_cover}',
                    ViewParams::sandwich_width => 245,
                    ViewParams::sandwich_height => 140,
                    ViewParams::sandwich_icon_upscale_enabled => true,
                    ViewParams::sandwich_icon_keep_aspect_ratio => false,
					ViewParams::background_path=> DuneSystem::$properties['data_dir_path'].'/altiptv_data/icons/bg.jpg',
					ViewParams::background_order => 0,
					ViewParams::background_height => 1080,
					ViewParams::background_width => 1920,
					ViewParams::optimize_full_screen_background => true,
                ),

                PluginRegularFolderView::base_view_item_params => array
                (
                    ViewItemParams::item_paint_icon => true,
                    ViewItemParams::item_layout => HALIGN_CENTER,
                    ViewItemParams::icon_valign => VALIGN_CENTER,
                    ViewItemParams::item_paint_caption => false,
                    ViewItemParams::icon_scale_factor => 1.25,
                    ViewItemParams::icon_sel_scale_factor => 1.5,
                    ViewItemParams::icon_path => 'plugin_file://icons/channel_unset.png',
                ),

                PluginRegularFolderView::not_loaded_view_item_params => array (),
            ),
			//.5) 3x6 85x85
			 array
            (
                PluginRegularFolderView::async_icon_loading => true,

                PluginRegularFolderView::view_params => array
                (
                    ViewParams::num_cols => 3,
                    ViewParams::num_rows => 6,
                    ViewParams::paint_details => false,
					ViewParams::background_path=> DuneSystem::$properties['data_dir_path'].'/altiptv_data/icons/bg.jpg',
					ViewParams::background_order => 0,
					ViewParams::background_height => 1080,
					ViewParams::background_width => 1920,
					ViewParams::optimize_full_screen_background => true,
                ),

                PluginRegularFolderView::base_view_item_params => array
                (
                    ViewItemParams::item_paint_icon => true,
                    ViewItemParams::item_layout => HALIGN_LEFT,
                    ViewItemParams::icon_valign => VALIGN_CENTER,
                    ViewItemParams::icon_dx => 10,
                    ViewItemParams::icon_dy => -5,
                    ViewItemParams::icon_width => 85,
                    ViewItemParams::icon_height => 85,
                    ViewItemParams::item_caption_width => 435,
                    ViewItemParams::item_caption_font_size => FONT_SIZE_SMALL,
                    ViewItemParams::icon_path => 'plugin_file://icons/channel_unset.png',
                ),

                PluginRegularFolderView::not_loaded_view_item_params => array (),
            ),
			//.5) 5x5 245x98
            array
            (
                PluginRegularFolderView::async_icon_loading => true,

                PluginRegularFolderView::view_params => array
                (
                    ViewParams::num_cols => 5,
                    ViewParams::num_rows => 5,
                    ViewParams::paint_details => false,
                    ViewParams::paint_sandwich => true,
                    ViewParams::sandwich_base => DuneSystem::$properties['data_dir_path'].'/altiptv_data/icons/null.png',
                    ViewParams::sandwich_mask => 'cut_icon://{name=sandwich_mask}',
                    ViewParams::sandwich_cover => 'cut_icon://{name=sandwich_cover}',
                    ViewParams::sandwich_width => 245,
                    ViewParams::sandwich_height => 98,
                    ViewParams::sandwich_icon_upscale_enabled => true,
                    ViewParams::sandwich_icon_keep_aspect_ratio => true,
					ViewParams::background_path=> DuneSystem::$properties['data_dir_path'].'/altiptv_data/icons/bg.jpg',
					ViewParams::background_order => 0,
					ViewParams::background_height => 1080,
					ViewParams::background_width => 1920,
					ViewParams::optimize_full_screen_background => true,
                ),

                PluginRegularFolderView::base_view_item_params => array
                (
                    ViewItemParams::item_paint_icon => true,
                    ViewItemParams::item_layout => HALIGN_CENTER,
                    ViewItemParams::icon_valign => VALIGN_CENTER,
                    ViewItemParams::item_paint_caption => false,
                    ViewItemParams::icon_scale_factor => 1.0,
                    ViewItemParams::icon_sel_scale_factor => 1.2,
                    ViewItemParams::icon_path => 'plugin_file://icons/channel_unset.png'
                ),

                PluginRegularFolderView::not_loaded_view_item_params => array (),
            ),
			//.5) 4x3 245x98
            array
            (
                PluginRegularFolderView::async_icon_loading => true,

                PluginRegularFolderView::view_params => array
                (
                    ViewParams::num_cols => 4,
                    ViewParams::num_rows => 3,
                    ViewParams::paint_details => false,
                    ViewParams::paint_sandwich => true,
                    ViewParams::sandwich_base => DuneSystem::$properties['data_dir_path'].'/altiptv_data/icons/null.png',
                    ViewParams::sandwich_mask => 'cut_icon://{name=sandwich_mask}',
                    ViewParams::sandwich_cover => 'cut_icon://{name=sandwich_cover}',
                    ViewParams::sandwich_width => 245,
                    ViewParams::sandwich_height => 98,
                    ViewParams::sandwich_icon_upscale_enabled => true,
                    ViewParams::sandwich_icon_keep_aspect_ratio => true,
					ViewParams::background_path=> DuneSystem::$properties['data_dir_path'].'/altiptv_data/icons/bg.jpg',
					ViewParams::background_order => 0,
					ViewParams::background_height => 1080,
					ViewParams::background_width => 1920,
					ViewParams::optimize_full_screen_background => true,
                ),

                PluginRegularFolderView::base_view_item_params => array
                (
                    ViewItemParams::item_paint_icon => true,
                    ViewItemParams::item_layout => HALIGN_CENTER,
                    ViewItemParams::icon_valign => VALIGN_CENTER,
                    ViewItemParams::item_paint_caption => true,
                    ViewItemParams::icon_scale_factor => 1.25,
                    ViewItemParams::icon_sel_scale_factor => 1.5,
                    ViewItemParams::icon_path => 'plugin_file://icons/channel_unset.png',
                ),

                PluginRegularFolderView::not_loaded_view_item_params => array (),
            ),
			//.5) 4x5 245x98
            array
            (
                PluginRegularFolderView::async_icon_loading => true,

                PluginRegularFolderView::view_params => array
                (
                    ViewParams::num_cols => 4,
                    ViewParams::num_rows => 5,
                    ViewParams::paint_details => false,
                    ViewParams::paint_sandwich => true,
                    ViewParams::sandwich_base => DuneSystem::$properties['data_dir_path'].'/altiptv_data/icons/null.png',
                    ViewParams::sandwich_mask => 'cut_icon://{name=sandwich_mask}',
                    ViewParams::sandwich_cover => 'cut_icon://{name=sandwich_cover}',
                    ViewParams::sandwich_width => 245,
                    ViewParams::sandwich_height => 98,
                    ViewParams::sandwich_icon_upscale_enabled => true,
                    ViewParams::sandwich_icon_keep_aspect_ratio => true,
					ViewParams::background_path=> DuneSystem::$properties['data_dir_path'].'/altiptv_data/icons/bg.jpg',
					ViewParams::background_order => 0,
					ViewParams::background_height => 1080,
					ViewParams::background_width => 1920,
					ViewParams::optimize_full_screen_background => true,
                ),

                PluginRegularFolderView::base_view_item_params => array
                (
                    ViewItemParams::item_paint_icon => true,
                    ViewItemParams::item_layout => HALIGN_CENTER,
                    ViewItemParams::icon_valign => VALIGN_CENTER,
                    ViewItemParams::item_paint_caption => false,
                    ViewItemParams::icon_scale_factor => 1.0,
                    ViewItemParams::icon_sel_scale_factor => 1.2,
                    ViewItemParams::icon_path => 'plugin_file://icons/channel_unset.png',
                ),

                PluginRegularFolderView::not_loaded_view_item_params => array (),
            ),
			//.5) 3x4 245x98
            array
            (
                PluginRegularFolderView::async_icon_loading => true,

                PluginRegularFolderView::view_params => array
                (
                    ViewParams::num_cols => 3,
                    ViewParams::num_rows => 4,
                    ViewParams::paint_details => false,
                    ViewParams::paint_sandwich => true,
                    ViewParams::sandwich_base => DuneSystem::$properties['data_dir_path'].'/altiptv_data/icons/null.png',
                    ViewParams::sandwich_mask => 'cut_icon://{name=sandwich_mask}',
                    ViewParams::sandwich_cover => 'cut_icon://{name=sandwich_cover}',
                    ViewParams::sandwich_width => 245,
                    ViewParams::sandwich_height => 98,
                    ViewParams::sandwich_icon_upscale_enabled => true,
                    ViewParams::sandwich_icon_keep_aspect_ratio => true,
					ViewParams::background_path=> DuneSystem::$properties['data_dir_path'].'/altiptv_data/icons/bg.jpg',
					ViewParams::background_order => 0,
					ViewParams::background_height => 1080,
					ViewParams::background_width => 1920,
					ViewParams::optimize_full_screen_background => true,
                ),

                PluginRegularFolderView::base_view_item_params => array
                (
                    ViewItemParams::item_paint_icon => true,
                    ViewItemParams::item_layout => HALIGN_CENTER,
                    ViewItemParams::icon_valign => VALIGN_CENTER,
                    ViewItemParams::item_paint_caption => false,
                    ViewItemParams::icon_scale_factor => 1.25,
                    ViewItemParams::icon_sel_scale_factor => 1.5,
                    ViewItemParams::icon_path => 'plugin_file://icons/channel_unset.png',
                ),

                PluginRegularFolderView::not_loaded_view_item_params => array (),
            ),
			//.5) 5x5 210x120
			array
            (
                PluginRegularFolderView::async_icon_loading => false,

                PluginRegularFolderView::view_params => array
                (
                    ViewParams::num_cols => 5,
                    ViewParams::num_rows => 5,
                    ViewParams::paint_details => false,
                    ViewParams::paint_sandwich => true,
                    ViewParams::sandwich_base => DuneSystem::$properties['data_dir_path'].'/altiptv_data/icons/null.png',
                    ViewParams::sandwich_mask => 'cut_icon://{name=sandwich_mask}',
                    ViewParams::sandwich_cover => 'cut_icon://{name=sandwich_cover}',
                    ViewParams::sandwich_width => 210,
                    ViewParams::sandwich_height => 120,
                    ViewParams::sandwich_icon_upscale_enabled => true,
                    ViewParams::sandwich_icon_keep_aspect_ratio => true,
					ViewParams::background_path=> DuneSystem::$properties['data_dir_path'].'/altiptv_data/icons/bg.jpg',
					ViewParams::background_order => 0,
					ViewParams::background_height => 1080,
					ViewParams::background_width => 1920,
					ViewParams::optimize_full_screen_background => true,
                ),

                PluginRegularFolderView::base_view_item_params => array
                (
                    ViewItemParams::item_paint_icon => true,
                    ViewItemParams::item_layout => HALIGN_CENTER,
                    ViewItemParams::icon_valign => VALIGN_CENTER,
                    ViewItemParams::item_paint_caption => false,
                    ViewItemParams::icon_scale_factor => 1.0,
                    ViewItemParams::icon_sel_scale_factor => 1.2,
                    ViewItemParams::icon_path => 'plugin_file://icons/channel_unset.png'
                ),

                PluginRegularFolderView::not_loaded_view_item_params => array (),
            ),
			//.5) 4x3 210x120
            array
            (
                PluginRegularFolderView::async_icon_loading => false,

                PluginRegularFolderView::view_params => array
                (
                    ViewParams::num_cols => 4,
                    ViewParams::num_rows => 3,
                    ViewParams::paint_details => false,
                    ViewParams::paint_sandwich => true,
                    ViewParams::sandwich_base => DuneSystem::$properties['data_dir_path'].'/altiptv_data/icons/null.png',
                    ViewParams::sandwich_mask => 'cut_icon://{name=sandwich_mask}',
                    ViewParams::sandwich_cover => 'cut_icon://{name=sandwich_cover}',
                    ViewParams::sandwich_width => 210,
                    ViewParams::sandwich_height => 120,
                    ViewParams::sandwich_icon_upscale_enabled => true,
                    ViewParams::sandwich_icon_keep_aspect_ratio => true,
					ViewParams::background_path=> DuneSystem::$properties['data_dir_path'].'/altiptv_data/icons/bg.jpg',
					ViewParams::background_order => 0,
					ViewParams::background_height => 1080,
					ViewParams::background_width => 1920,
					ViewParams::optimize_full_screen_background => true,
                ),

                PluginRegularFolderView::base_view_item_params => array
                (
                    ViewItemParams::item_paint_icon => true,
                    ViewItemParams::item_layout => HALIGN_CENTER,
                    ViewItemParams::icon_valign => VALIGN_CENTER,
                    ViewItemParams::item_paint_caption => true,
                    ViewItemParams::icon_scale_factor => 1.25,
                    ViewItemParams::icon_sel_scale_factor => 1.5,
                    ViewItemParams::icon_path => 'plugin_file://icons/channel_unset.png',
                ),

                PluginRegularFolderView::not_loaded_view_item_params => array (),
            ),
			//.5) 4x5 210x120
            array
            (
                PluginRegularFolderView::async_icon_loading => false,

                PluginRegularFolderView::view_params => array
                (
                    ViewParams::num_cols => 4,
                    ViewParams::num_rows => 5,
                    ViewParams::paint_details => false,
                    ViewParams::paint_sandwich => true,
                    ViewParams::sandwich_base => DuneSystem::$properties['data_dir_path'].'/altiptv_data/icons/null.png',
                    ViewParams::sandwich_mask => 'cut_icon://{name=sandwich_mask}',
                    ViewParams::sandwich_cover => 'cut_icon://{name=sandwich_cover}',
                    ViewParams::sandwich_width => 210,
                    ViewParams::sandwich_height => 120,
                    ViewParams::sandwich_icon_upscale_enabled => true,
                    ViewParams::sandwich_icon_keep_aspect_ratio => true,
					ViewParams::background_path=> DuneSystem::$properties['data_dir_path'].'/altiptv_data/icons/bg.jpg',
					ViewParams::background_order => 0,
					ViewParams::background_height => 1080,
					ViewParams::background_width => 1920,
					ViewParams::optimize_full_screen_background => true,
                ),

                PluginRegularFolderView::base_view_item_params => array
                (
                    ViewItemParams::item_paint_icon => true,
                    ViewItemParams::item_layout => HALIGN_CENTER,
                    ViewItemParams::icon_valign => VALIGN_CENTER,
                    ViewItemParams::item_paint_caption => false,
                    ViewItemParams::icon_scale_factor => 1.0,
                    ViewItemParams::icon_sel_scale_factor => 1.2,
                    ViewItemParams::icon_path => 'plugin_file://icons/channel_unset.png',
                ),

                PluginRegularFolderView::not_loaded_view_item_params => array (),
            ),
			//.5) 3x4 210x120
            array
            (
                PluginRegularFolderView::async_icon_loading => false,

                PluginRegularFolderView::view_params => array
                (
                    ViewParams::num_cols => 3,
                    ViewParams::num_rows => 4,
                    ViewParams::paint_details => false,
                    ViewParams::paint_sandwich => true,
                    ViewParams::sandwich_base => DuneSystem::$properties['data_dir_path'].'/altiptv_data/icons/null.png',
                    ViewParams::sandwich_mask => 'cut_icon://{name=sandwich_mask}',
                    ViewParams::sandwich_cover => 'cut_icon://{name=sandwich_cover}',
                    ViewParams::sandwich_width => 210,
                    ViewParams::sandwich_height => 120,
                    ViewParams::sandwich_icon_upscale_enabled => true,
                    ViewParams::sandwich_icon_keep_aspect_ratio => true,
					ViewParams::background_path=> DuneSystem::$properties['data_dir_path'].'/altiptv_data/icons/bg.jpg',
					ViewParams::background_order => 0,
					ViewParams::background_height => 1080,
					ViewParams::background_width => 1920,
					ViewParams::optimize_full_screen_background => true,
                ),

                PluginRegularFolderView::base_view_item_params => array
                (
                    ViewItemParams::item_paint_icon => true,
                    ViewItemParams::item_layout => HALIGN_CENTER,
                    ViewItemParams::icon_valign => VALIGN_CENTER,
                    ViewItemParams::item_paint_caption => false,
                    ViewItemParams::icon_scale_factor => 1.25,
                    ViewItemParams::icon_sel_scale_factor => 1.5,
                    ViewItemParams::icon_path => 'plugin_file://icons/channel_unset.png',
                ),

                PluginRegularFolderView::not_loaded_view_item_params => array (),
            ),
			//.5) 4x3 150x150
			array
            (
                PluginRegularFolderView::async_icon_loading => false,

                PluginRegularFolderView::view_params => array
                (
                    ViewParams::num_cols => 4,
                    ViewParams::num_rows => 3,
                    ViewParams::paint_details => false,
                    ViewParams::paint_sandwich => true,
                    ViewParams::sandwich_base => DuneSystem::$properties['data_dir_path'].'/altiptv_data/icons/null.png',
                    ViewParams::sandwich_mask => 'cut_icon://{name=sandwich_mask}',
                    ViewParams::sandwich_cover => 'cut_icon://{name=sandwich_cover}',
                    ViewParams::sandwich_width => 150,
                    ViewParams::sandwich_height => 150,
                    ViewParams::paint_path_box => true,
                    ViewParams::background_path=> DuneSystem::$properties['data_dir_path'].'/altiptv_data/icons/bg.jpg',
					ViewParams::background_order => 0,
					ViewParams::background_height => 1080,
					ViewParams::background_width => 1920,
					ViewParams::optimize_full_screen_background => true,
					ViewParams::sandwich_icon_keep_aspect_ratio => true,
                    ViewParams::paint_content_box_background => true,
                    ViewParams::sandwich_icon_upscale_enabled => true,
                    ViewParams::sandwich_icon_keep_aspect_ratio => true,
                ),

                PluginRegularFolderView::base_view_item_params => array
                (
                    ViewItemParams::item_paint_icon => true,
                    ViewItemParams::item_layout => HALIGN_CENTER,
                    ViewItemParams::icon_valign => VALIGN_CENTER,
                    ViewItemParams::item_paint_caption => true,
                    ViewItemParams::icon_scale_factor => 1.25,
                    ViewItemParams::icon_sel_scale_factor => 1.25,
                    ViewItemParams::icon_path => 'plugin_file://icons/channel_unset.png',
                ),

                PluginRegularFolderView::not_loaded_view_item_params => array (),
            ),
			//.5) 3x10 60x60
            array
            (
                PluginRegularFolderView::async_icon_loading => true,

                PluginRegularFolderView::view_params => array
                (
                    ViewParams::num_cols => 3,
                    ViewParams::num_rows => 10,
                    ViewParams::paint_details => true,
                    ViewParams::paint_path_box => true,
                    ViewParams::background_path=> DuneSystem::$properties['data_dir_path'].'/altiptv_data/icons/bg.jpg',
					ViewParams::background_order => 0,
					ViewParams::background_height => 1080,
					ViewParams::background_width => 1920,
					ViewParams::optimize_full_screen_background => true,
                    ViewParams::paint_content_box_background => true,
                    ViewParams::optimize_full_screen_background => true,
                ),

                PluginRegularFolderView::base_view_item_params => array
                (
                    ViewItemParams::item_paint_icon => true,
                    ViewItemParams::item_layout => HALIGN_LEFT,
                    ViewItemParams::icon_valign => VALIGN_CENTER,
                    ViewItemParams::icon_dx => 10,
                    ViewItemParams::icon_dy => -5,
                    ViewItemParams::icon_width => 60,
                    ViewItemParams::icon_height => 60,
                    ViewItemParams::item_caption_width => 250,
                    ViewItemParams::item_caption_font_size => FONT_SIZE_SMALL,
                    #ViewItemParams::item_caption_dy => HALIGN_RIGHT,
                    ViewItemParams::icon_path => 'plugin_file://icons/channel_unset.png',
                ),

                PluginRegularFolderView::not_loaded_view_item_params => array
                (
                    ViewItemParams::icon_path => 'plugin_file://icons/mov_unset.png',
                    ViewItemParams::item_detailed_icon_path => 'missing://',
                ),
            ),
			//.5) 5x5 100x100
            array
            (
                PluginRegularFolderView::async_icon_loading => false,

                PluginRegularFolderView::view_params => array
                (
                    ViewParams::num_cols => 5,
                    ViewParams::num_rows => 5,
                    ViewParams::paint_details => false,
                    ViewParams::paint_sandwich => true,
                    ViewParams::sandwich_base => DuneSystem::$properties['data_dir_path'].'/altiptv_data/icons/null.png',
                    ViewParams::sandwich_mask => 'cut_icon://{name=sandwich_mask}',
                    ViewParams::sandwich_cover => 'cut_icon://{name=sandwich_cover}',
                    ViewParams::sandwich_width => 100,
                    ViewParams::sandwich_height => 100,
                    ViewParams::paint_path_box => true,
                    ViewParams::background_path=> DuneSystem::$properties['data_dir_path'].'/altiptv_data/icons/bg.jpg',
					ViewParams::background_order => 0,
					ViewParams::background_height => 1080,
					ViewParams::background_width => 1920,
					ViewParams::optimize_full_screen_background => true,
                    ViewParams::paint_content_box_background => true,
                    ViewParams::sandwich_icon_upscale_enabled => true,
                    ViewParams::sandwich_icon_keep_aspect_ratio => false,
                ),

                PluginRegularFolderView::base_view_item_params => array
                (
                    ViewItemParams::item_paint_icon => true,
                    ViewItemParams::item_layout => HALIGN_CENTER,
                    ViewItemParams::icon_valign => VALIGN_CENTER,
                    ViewItemParams::item_paint_caption => false,
                    ViewItemParams::icon_scale_factor => 1.0,
                    ViewItemParams::icon_sel_scale_factor => 1.0,
                    ViewItemParams::icon_path => 'plugin_file://icons/channel_unset.png'
                ),

                PluginRegularFolderView::not_loaded_view_item_params => array (),
            ),
			//.5) 4x5 120x120
            array
            (
                PluginRegularFolderView::async_icon_loading => false,

                PluginRegularFolderView::view_params => array
                (
                    ViewParams::num_cols => 4,
                    ViewParams::num_rows => 5,
                    ViewParams::paint_details => false,
                    ViewParams::paint_sandwich => true,
                    ViewParams::sandwich_base => DuneSystem::$properties['data_dir_path'].'/altiptv_data/icons/null.png',
                    ViewParams::sandwich_mask => 'cut_icon://{name=sandwich_mask}',
                    ViewParams::sandwich_cover => 'cut_icon://{name=sandwich_cover}',
                    ViewParams::sandwich_width => 120,
                    ViewParams::sandwich_height => 120,
                    ViewParams::paint_path_box => true,
                    ViewParams::background_path=> DuneSystem::$properties['data_dir_path'].'/altiptv_data/icons/bg.jpg',
					ViewParams::background_order => 0,
					ViewParams::background_height => 1080,
					ViewParams::background_width => 1920,
					ViewParams::optimize_full_screen_background => true,
                    ViewParams::paint_content_box_background => true,
                    ViewParams::sandwich_icon_upscale_enabled => true,
                    ViewParams::sandwich_icon_keep_aspect_ratio => true,
                ),

                PluginRegularFolderView::base_view_item_params => array
                (
                    ViewItemParams::item_paint_icon => true,
                    ViewItemParams::item_layout => HALIGN_CENTER,
                    ViewItemParams::icon_valign => VALIGN_CENTER,
                    ViewItemParams::item_paint_caption => false,
                    ViewItemParams::icon_scale_factor => 1.0,
                    ViewItemParams::icon_sel_scale_factor => 1.0,
                    ViewItemParams::icon_path => 'plugin_file://icons/channel_unset.png',
                ),

                PluginRegularFolderView::not_loaded_view_item_params => array (),
            ),
			//.5) 3x4 130x130
            array
            (
                PluginRegularFolderView::async_icon_loading => false,

                PluginRegularFolderView::view_params => array
                (
                    ViewParams::num_cols => 3,
                    ViewParams::num_rows => 4,
                    ViewParams::paint_details => false,
                    ViewParams::paint_sandwich => true,
                    ViewParams::sandwich_base => DuneSystem::$properties['data_dir_path'].'/altiptv_data/icons/null.png',
                    ViewParams::sandwich_mask => 'cut_icon://{name=sandwich_mask}',
                    ViewParams::sandwich_cover => 'cut_icon://{name=sandwich_cover}',
                    ViewParams::sandwich_width => 130,
                    ViewParams::sandwich_height => 130,
                    ViewParams::paint_path_box => true,
                    ViewParams::background_path=> DuneSystem::$properties['data_dir_path'].'/altiptv_data/icons/bg.jpg',
					ViewParams::background_order => 0,
					ViewParams::background_height => 1080,
					ViewParams::background_width => 1920,
					ViewParams::optimize_full_screen_background => true,
                    ViewParams::paint_content_box_background => true,
                    ViewParams::sandwich_icon_upscale_enabled => true,
                    ViewParams::sandwich_icon_keep_aspect_ratio => true,
                ),

                PluginRegularFolderView::base_view_item_params => array
                (
                    ViewItemParams::item_paint_icon => true,
                    ViewItemParams::item_layout => HALIGN_CENTER,
                    ViewItemParams::icon_valign => VALIGN_CENTER,
                    ViewItemParams::item_paint_caption => false,
                    ViewItemParams::icon_scale_factor => 1.25,
                    ViewItemParams::icon_sel_scale_factor => 1.25,
                    ViewItemParams::icon_path => 'plugin_file://icons/channel_unset.png',
                ),

                PluginRegularFolderView::not_loaded_view_item_params => array (),
            )

            
        );
    }
    
}

?>
