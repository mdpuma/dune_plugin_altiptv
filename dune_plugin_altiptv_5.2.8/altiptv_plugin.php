<?php
///////////////////////////////////////////////////////////////////////////

require_once 'lib/default_dune_plugin.php';
require_once 'lib/utils.php';

require_once 'lib/tv/tv_group_list_screen.php';
require_once 'lib/tv/tv_favorites_screen.php';
require_once 'altiptv_entry_handler.php';
require_once 'altiptv_config.php';
require_once 'altiptv_m3u_tv.php';
require_once 'altiptv_setup_screen.php';
require_once 'altiptv_tv_channel_list_screen.php';

///////////////////////////////////////////////////////////////////////////

class DemoPlugin extends DefaultDunePlugin
{
	private $entry_handler;
    public function __construct()
    {
        $this->tv = new DemoM3uTv();
		$this->entry_handler = new DemoTvEntryHandler($this->tv);
        $this->add_screen(new DemoTvChannelListScreen($this->tv,
                DemoConfig::GET_TV_CHANNEL_LIST_FOLDER_VIEWS()));
        $this->add_screen(new TvFavoritesScreen($this->tv,
                DemoConfig::GET_TV_CHANNEL_LIST_FOLDER_VIEWS()));
        $this->add_screen(new TvGroupListScreen($this->tv,
                DemoConfig::GET_TV_GROUP_LIST_FOLDER_VIEWS()));
        $this->add_screen(new DemoSetupScreen());
    }

}

///////////////////////////////////////////////////////////////////////////
?>
