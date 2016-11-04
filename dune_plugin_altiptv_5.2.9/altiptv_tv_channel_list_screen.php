<?php
///////////////////////////////////////////////////////////////////////////

require_once 'lib/tv/tv_channel_list_screen.php';

class DemoTvChannelListScreen extends TvChannelListScreen
{
    public function get_all_folder_items(MediaURL $media_url, &$plugin_cookies)
    {
        $group_tv = isset($plugin_cookies->group_tv) ?
        $plugin_cookies->group_tv : '1';
		if ($group_tv == 4) {
		 if (DemoConfig::USE_M3U_FILE)
            $media_url->group_id = $this->tv->get_all_channel_group_id();
		}else{
		if (DemoConfig::USE_M3U_FILE && !isset($media_url->group_id))
            $media_url->group_id = $this->tv->get_all_channel_group_id();}

        return parent::get_all_folder_items($media_url, $plugin_cookies);
    }
}

///////////////////////////////////////////////////////////////////////////
?>
