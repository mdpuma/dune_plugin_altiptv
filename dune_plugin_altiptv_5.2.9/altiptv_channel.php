<?php
///////////////////////////////////////////////////////////////////////////

require_once 'lib/tv/default_channel.php';

///////////////////////////////////////////////////////////////////////////

class DemoChannel extends DefaultChannel
{
    private $number;
    private $past_epg_days;
    private $future_epg_days;
    private $is_protected;
    private $buf_time;
	private $has_archive;
    ///////////////////////////////////////////////////////////////////////

    public function __construct
	(
        $id, 
		$title, 
		$icon_url, 
		$streaming_url, 
		$number, 
		$past_epg_days, 
		$future_epg_days, 
		$is_protected, 
		$buf_time, 
		$tvg_name, 
		$pl_name, 
		$has_archive, 
		$user_agent,
		$dune_zoom
	)
    {
        parent::__construct($id, $title, $icon_url, $streaming_url, $tvg_name, $pl_name, $user_agent, $dune_zoom);

        $this->number = $number;
        $this->past_epg_days = $past_epg_days;
        $this->future_epg_days = $future_epg_days;
        $this->is_protected = $is_protected;
		$this->buf_time = $buf_time;
		$this->tvg_name = $tvg_name;
		$this->pl_name = $pl_name;
		$this->streaming_url = $streaming_url;
		$this->has_archive = $has_archive;
		$this->user_agent = $user_agent;
		$this->dune_zoom = $dune_zoom;
    }

    ///////////////////////////////////////////////////////////////////////
	public function streaming_url()
    { return $this->streaming_url; }
	
	public function pl_name()
    { return $this->pl_name; }
	
	public function tvg_name()
    { return $this->tvg_name; }
	
    public function is_protected()
    { return $this->is_protected; }
	
	public function get_buffering_ms()
    { return $this->buf_time; }
    
	public function get_number()
    { return $this->number; }

    public function get_past_epg_days()
    { return $this->past_epg_days; }

    public function get_future_epg_days()
    { return $this->future_epg_days; }
	
	public function has_archive()
    { return $this->has_archive; }
	
	public function dune_zoom()
    { return $this->dune_zoom; }
	
	public function user_agent()
    { return $this->user_agent; }
	
	public function get_timeshift_hours() 
	 { return 4; }
    
}

///////////////////////////////////////////////////////////////////////////
?>
