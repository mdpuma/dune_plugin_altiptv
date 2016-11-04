<?php


function
safely_get_value_of_global_variables ($name, $key)
{
  return (isset ($name[$key]) ) ? ($name[$key]) : ('');
}

class DuneSystem
{
  public static $properties = array ();
};
DuneSystem::$properties['plugin_name']      = safely_get_value_of_global_variables ($_ENV, 'PLUGIN_NAME');
DuneSystem::$properties['install_dir_path'] = safely_get_value_of_global_variables ($_ENV, 'PLUGIN_INSTALL_DIR_PATH');
DuneSystem::$properties['tmp_dir_path']     = safely_get_value_of_global_variables ($_ENV, 'PLUGIN_TMP_DIR_PATH');
DuneSystem::$properties['plugin_www_url']   = safely_get_value_of_global_variables ($_ENV, 'PLUGIN_WWW_URL');
DuneSystem::$properties['plugin_cgi_url']   = safely_get_value_of_global_variables ($_ENV, 'PLUGIN_CGI_URL');
DuneSystem::$properties['data_dir_path']    = safely_get_value_of_global_variables ($_ENV, 'PLUGIN_DATA_DIR_PATH');

$PLAYLIST_DIR = DuneSystem::$properties['data_dir_path'] . '/playlists/';

class do_config
{
    const PROTOCOL_VERSION = 5;
    public static $ROOT_DIR = "/tmp/mnt/storage/";
    public static $PLAYLIST_DIR = '/flashdata/playlists/';
}
do_config::$PLAYLIST_DIR = DuneSystem::$properties['data_dir_path'] . '/playlists/';
