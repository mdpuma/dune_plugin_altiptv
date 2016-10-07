<?php
///////////////////////////////////////////////////////////////////////////

require_once 'lib/user_input_handler_registry.php';

class DemoTvEntryHandler
    implements UserInputHandler
{
    private $tv;

    public function __construct($tv)
    {
        $this->tv = $tv;

        UserInputHandlerRegistry::get_instance()->
            register_handler($this);
    }

    ///////////////////////////////////////////////////////////////////////

    public function get_handler_id()
    {
        return 'entry';
    }
	
    public function handle_user_input(&$user_input, &$plugin_cookies)
    {	
		
		$group_tv = isset($plugin_cookies->group_tv) ?
        $plugin_cookies->group_tv : '1';
		$start_tv = isset($plugin_cookies->start_tv) ?
        $plugin_cookies->start_tv : 0;
		if ($user_input->handler_id == 'entry')
        {
			if ($start_tv == 1)
				return ActionFactory::tv_play('tv_channel_list');
			else if ($group_tv == 4)
				return ActionFactory::open_folder('tv_channel_list');
			else
				return ActionFactory::open_folder('tv_group_list');
		}

    }
}

///////////////////////////////////////////////////////////////////////////
?>
