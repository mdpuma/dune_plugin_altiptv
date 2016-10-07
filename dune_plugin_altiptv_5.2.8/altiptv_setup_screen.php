<?php
///////////////////////////////////////////////////////////////////////////
require_once 'lib/abstract_preloaded_regular_screen.php';
require_once 'lib/abstract_controls_screen.php';

///////////////////////////////////////////////////////////////////////////

class DemoSetupScreen extends AbstractControlsScreen
{
    const ID = 'setup';
	private $page_num = 1;

    ///////////////////////////////////////////////////////////////////////

    public function __construct()
    {
        parent::__construct(self::ID);

    }
	public static function get_media_url_str()
    {
        return MediaURL::encode(array('screen_id' => self::ID));
    }
	
    public function do_get_control_defs(&$plugin_cookies)
    {
        $defs = array();
        $show_tv = isset($plugin_cookies->show_tv) ?
        $plugin_cookies->show_tv : 'yes';
		$ico_show = isset($plugin_cookies->ico_show) ?
        $plugin_cookies->ico_show : 'yes';
		$link = DuneSystem::$properties['data_dir_path'].'/altiptv_data/data_file/fav';
		$fav_show = file_exists($link) ? file_get_contents ($link): 'yes';
        $m3u = isset($plugin_cookies->m3u) ?
        $plugin_cookies->m3u : '';
        $use_proxy = isset($plugin_cookies->use_proxy) ?
        $plugin_cookies->use_proxy : 'no';
        $proxy_ip = isset($plugin_cookies->proxy_ip) ?
        $plugin_cookies->proxy_ip : '192.168.1.1';
		$proxy_port = isset($plugin_cookies->proxy_port) ?
		$plugin_cookies->proxy_port : '9999';
		$m3u_type = isset($plugin_cookies->m3u_type) ?
		$plugin_cookies->m3u_type : '1';
		$proxy_type = isset($plugin_cookies->proxy_type) ?
		$plugin_cookies->proxy_type : '1';
		$dload_http = isset($plugin_cookies->dload_http) ?
		$plugin_cookies->dload_http : 0;
		$arc = isset($plugin_cookies->arc) ?
		$plugin_cookies->arc : 'arc_itm';
		$rec_type = isset($plugin_cookies->rec_type) ?
		$plugin_cookies->rec_type : 0;
		$m3u_dir = isset($plugin_cookies->m3u_dir) ?
		$plugin_cookies->m3u_dir : '/D';
		$epg_shift = isset($plugin_cookies->epg_shift) ?
		$plugin_cookies->epg_shift : '0';
		$epg_shiftjtv = isset($plugin_cookies->epg_shiftjtv) ?
		$plugin_cookies->epg_shiftjtv : '0';
		$epg_shiftmail = isset($plugin_cookies->epg_shiftmail) ?
		$plugin_cookies->epg_shiftmail : '0';
		$epg_shiftakado = isset($plugin_cookies->epg_shiftakado) ?
		$plugin_cookies->epg_shiftakado : '0';
		$epg_shiftntvplus = isset($plugin_cookies->epg_shiftntvplus) ?
		$plugin_cookies->epg_shiftntvplus : '0';
		$epg_shiftteleguide = isset($plugin_cookies->epg_shiftteleguide) ?
		$plugin_cookies->epg_shiftteleguide : '0';
		$epg_shifttvprogrlt = isset($plugin_cookies->epg_shifttvprogrlt) ?
		$plugin_cookies->epg_shifttvprogrlt : '0';
		$epg_shiftteleman = isset($plugin_cookies->epg_shiftteleman) ?
		$plugin_cookies->epg_shiftteleman : '0';
		$start_tv = isset($plugin_cookies->start_tv) ?
        $plugin_cookies->start_tv : 0;
		$group_tv = isset($plugin_cookies->group_tv) ?
        $plugin_cookies->group_tv : '1';
		$show_hd = isset($plugin_cookies->show_hd) ?
        $plugin_cookies->show_hd : 'no';
		$group_p = isset($plugin_cookies->group_p) ?
        $plugin_cookies->group_p : '0';
		$double_tv = isset($plugin_cookies->double_tv) ?
        $plugin_cookies->double_tv : '1';
		$fav_save = isset($plugin_cookies->fav_save) ?
        $plugin_cookies->fav_save : '0';
		$epg_type = isset($plugin_cookies->epg_type) ?
        $plugin_cookies->epg_type : '1';
		$altdata_type = isset($plugin_cookies->altdata_type) ?
        $plugin_cookies->altdata_type : '1';
		$cod_type = isset($plugin_cookies->cod_type) ?
        $plugin_cookies->cod_type : '0';
		$epg_shiftvspielfilm = isset($plugin_cookies->epg_shiftvspielfilm) ?
		$plugin_cookies->epg_shiftvspielfilm : '0';
		$epg_shifttvlistingsuk = isset($plugin_cookies->epg_shifttvlistingsuk) ?
		$plugin_cookies->epg_shifttvlistingsuk : '0';
		$sort_channels = isset($plugin_cookies->sort_channels) ? 
		$plugin_cookies->sort_channels : 'no';
		$buf_time = isset($plugin_cookies->buf_time) ? 
		$plugin_cookies->buf_time : '0';
		$recdata = isset($plugin_cookies->recdata) ? 
		$plugin_cookies->recdata : '/D';
		$recdata_dir = isset($plugin_cookies->recdata_dir) ? 
		$plugin_cookies->recdata_dir : '/';
		$epg_font = isset($plugin_cookies->epg_font) ? 
		$plugin_cookies->epg_font : PLUGIN_FONT_NORMAL;
		$ver = file_get_contents(DuneSystem::$properties['install_dir_path'].'/dune_plugin.xml');
		if (is_null($ver)) 
				hd_print('Can`t load dune_plugin.xml');
		$xml = HD::parse_xml_document($ver);
		$plugin_version = strval($xml->version);
		if ($xml->check_update->url == '')
			$use_update = 'no';
		else
			$use_update = 'yes';
        
		$show_ops = array();
        $show_ops['yes'] = 'Да';
        $show_ops['no'] = 'Нет';
		
		$dload_http_ops = array();
        $dload_http_ops[0] = 'file';
        $dload_http_ops[1] = 'curl';
		
		$arc_ops = array();
        $arc_ops['arc_itm'] = 'Архивы moyo + RT';
        $arc_ops['arc_itm_rt'] = 'Архивы только RT';
		$arc_ops['arc_itm_moyo'] = 'Архивы только moyo';
		
		$group_ops = array();
        $group_ops['1'] = 'Названия плейлистов';
        $group_ops['2'] = 'Категории каналов из плагина';
		$group_ops['3'] = 'Категории каналов из плейлиста';
		$group_ops['4'] = 'Без категорий';
		$group_ops['5'] = 'Категории каналов из плагина+';
		
		$start_ops = array();
		$start_ops[0] = 'Категории и каналы';
		$start_ops[1] = 'Запускать просмотр ТВ';
		
		$recdata_ops = array();
        $recdata_ops['/D'] = 'Первый HDD/USB-диск';
		$recdata_ops[1] = 'SMB папка';
		foreach (glob('/tmp/mnt/storage/*') as $file) 
    		if (is_dir($file)) $recdata_ops[$file] = ' -'.substr($file,17,strlen($file));
		
		$altdata_ops = array();
        $altdata_ops['1'] = 'Плагин';
        $altdata_ops['2'] = 'Первый HDD/USB-диск';
		$altdata_ops['3'] = 'SMB папка';
		foreach (glob('/tmp/mnt/storage/*') as $file) 
    		if (is_dir($file)) $altdata_ops[$file] = ' -'.substr($file,17,strlen($file));
		
		$show_sort = array();
        $show_sort[0] = 'По положению в плейлисте';
		$show_sort[1] = 'Алфавит только Все каналы';
		$show_sort[2] = 'Алфавитный порядок';
		$show_sort[3] = 'По номеру очередности';
		$show_sort[4] = '№ + алфавит';
		
		$double_ops = array();
        $double_ops['1'] = '1 вариант';
        $double_ops['2'] = '2 вариант';
		
		$group_p_ops = array();
        $group_p_ops['0'] = 'group-title всем каналам';
        $group_p_ops['1'] = 'group-title не всем каналам';
		
		$fav_save_ops = array();
        $fav_save_ops['0'] = 'В куках плагина';
        $fav_save_ops['1'] = 'В altiptv_data';
		
		$epg_type_ops = array();
        $epg_type_ops['1'] = 'vsetv';
		$epg_type_ops['3'] = 'tv.mail.ru';
		$epg_type_ops['4'] = 'akado.ru';
		$epg_type_ops['5'] = 'ntvplus.ru';
		$epg_type_ops['8'] = 'teleguide.info';
		$epg_type_ops['9'] = 'tvprograma.lt';
		$epg_type_ops['10'] = 'teleman.pl';
		$epg_type_ops['6'] = 'tvspielfilm.de';
		$epg_type_ops['7'] = 'tvlistings UK';
		//$epg_type_ops['11'] = 'tvlistings all';
        $epg_type_ops['2'] = 'jtv с плейлиста';
		
		$cod_type_ops = array();
		$cod_type_ops['0'] = 'авто';
        $cod_type_ops['1'] = 'только utf-8';
		$cod_type_ops['2'] = 'только win-1251';
        
		
		$m3u_ops = array();
        $m3u_ops['1'] = 'Плейлисты из плагина';
        $m3u_ops['2'] = 'Плейлисты из каталога первого HDD/USB-диска';
        $m3u_ops['3'] = 'Плейлист по ссылке (http)';
		$m3u_ops['7'] = 'Плейлисты из SMB папки';
		$m3u_ops['6'] = 'Плейлисты по ссылке (http) из файла pls.txt';
		if (file_exists('/persistfs/main_screen_items/'))
			{
		$m3u_ops['4'] = 'Плейлисты из каталога Избранного';
			}
		if (file_exists('/flashdata/main_screen_items/'))
			{
		if (!file_exists('/persistfs/main_screen_items/'))
			{
		$m3u_ops['5'] = 'Плейлисты из каталога Избранного';
			}
			}
		$m3u_ops['8'] = 'Комбинированный режим';
		$proxy_ops = array();
        $proxy_ops['1'] = 'Не сохранять адреса proxy';
        $proxy_ops['2'] = 'Cохранять адреса proxy';
       

	for ($i = -12; $i<13; $i++)
		$shift_ops[$i*3600] = $i; 
	for ($i = -12; $i<13; $i++)
		$shiftjtv_ops[$i*3600] = $i; 
	$show_buf_time_ops = array();
        $show_buf_time_ops[0] = 'По умолчанию';
        $show_buf_time_ops[500] = '0.5 сек';
        $show_buf_time_ops[1000] = '1 сек';
        $show_buf_time_ops[2000] = '2 сек';
        $show_buf_time_ops[3000] = '3 сек';
        $show_buf_time_ops[5000] = '5 сек';
        $show_buf_time_ops[10000] = '10 сек';
	
	$rec_ops = array();
		$rec_ops[-1] = 'Спрашивать';
        $rec_ops[0] = 'По умолчанию';
        $rec_ops[60] = '+1 минута';
        $rec_ops[300] = '+5 минут';
        $rec_ops[600] = '+10 минут';
        $rec_ops[900] = '+15 минут';
        $rec_ops[1800] = '+30 минут';
	
	$epg_font_ops = array();
        $epg_font_ops[PLUGIN_FONT_NORMAL] = 'Нормальный';
        $epg_font_ops[PLUGIN_FONT_SMALL] = 'Мелкий';
		
	if ($this->page_num == 1)
    {
	$this->add_button(
    $defs, 'dload_help',
    "altIPTV v$plugin_version", 'Скачать HELP по плагину',
    0);
	$this->add_combobox($defs, 
			'epg_font', 'Шрифт EPG:', 
			$epg_font, $epg_font_ops, 400, true);
	$this->add_combobox($defs,
            'm3u_type', 'Загружать:',
            $m3u_type, $m3u_ops, 0, true);
	if ($m3u_type == 2) {
	    $m3u_dir_ops = array();
	    $m3u_dir_ops['/D'] = '/';

	    foreach (glob('/D/*') as $file) 
    		if (is_dir($file)) $m3u_dir_ops[$file] = substr($file,3,strlen($file));


	    $this->add_combobox($defs,
            	'm3u_dir', 'Каталог:',
            	$m3u_dir, $m3u_dir_ops, 0, true);
	}
	else if ($m3u_type == 4) {

	    $m3u_dir_ops = array();
	    $m3u_dir_ops['/persistfs/main_screen_items/'] = '/';

	    foreach (glob('/persistfs/main_screen_items/*') as $file) 
    		if (is_dir($file)) $m3u_dir_ops[$file] = substr($file,29,strlen($file));


	    $this->add_combobox($defs,
            	'm3u_dir', 'Каталог в "Избранном":',
            	$m3u_dir, $m3u_dir_ops, 0, true);
	}
	else if ($m3u_type == 5) {

	    $m3u_dir_ops = array();
	    $m3u_dir_ops['/flashdata/main_screen_items/'] = '/';

	    foreach (glob('/flashdata/main_screen_items/*') as $file) 
    		if (is_dir($file)) $m3u_dir_ops[$file] = substr($file,29,strlen($file));


	    $this->add_combobox($defs,
            	'm3u_dir', 'Каталог в "Избранном":',
            	$m3u_dir, $m3u_dir_ops, 0, true);
	}
	else if ($m3u_type == 3) {
	    $this->add_text_field($defs,
        	'm3u', 'Ссылка на плейлист:', $m3u,
		false, false, false, true, 500, false, true);
	}
	else if ($m3u_type == 7) {
	    
        $this->add_button($defs,
            'ip_path_smb',
            'Путь, логин и пароль SMB:',
            'Изменить',
            500
        );	
	}
	if ($m3u_type == 6) {

	    $m3u_dir_ops = array();
	    $m3u_dir_ops['/D'] = '/';

	    foreach (glob('/D/*') as $file) 
    		if (is_dir($file)) $m3u_dir_ops[$file] = substr($file,3,strlen($file));


	    $this->add_combobox($defs,
            	'm3u_dir', 'Каталог c файлом  pls.txt:',
            	$m3u_dir, $m3u_dir_ops, 0, true);
	}
	$this->add_combobox($defs,
            'dload_http', 'Загружать http плейлисты через:',
            $dload_http, $dload_http_ops, 0, true);
			
	if ((file_exists('/persistfs/DVBT_channels/'))||
	(file_exists('/flashdata/DVBT_channels/'))){
	$this->add_button
        (
            $defs,
            'dvbt_channels',
            'DVBT плейлист:',
            'Выгрузить',
            500
        );	
	}
	$this->add_combobox($defs,
            'start_tv', 'Старт плагина:',
            $start_tv, $start_ops, 0, true);
			
	$this->add_combobox($defs,
            'group_tv', 'Вид групп ТВ каналов:',
            $group_tv, $group_ops, 0, true);
	if (($group_tv == 2)||($group_tv == 5)){
	$this->add_combobox($defs,
            'show_hd', 'Автокатегория HD:',
            $show_hd, $show_ops, 0, true);
	}
	if ($group_tv == 3) {
	$this->add_combobox($defs,
            'group_p', 'Задавать:',
            $group_p, $group_p_ops, 0, true);
	}
	
	$this->add_combobox($defs,
            'rec_type', 'Коррекция времени записи:',
            $rec_type, $rec_ops, 0, true);
	$this->add_combobox($defs,
            'recdata', 'Сохранять записи в:',
            $recdata, $recdata_ops, 0, true);		
	if ($recdata !== '1') {
	    $recdata_dir_ops = array();
	    $recdata_dir_ops["$recdata"] = '/';
	    foreach (glob("$recdata/*") as $file)
		if (is_dir($file)) {
		if (preg_match('|/tmp/mnt/|', $file))
			$file	= basename($file);
		else
			$file	= substr($file,3,strlen($file));
    	$recdata_dir_ops['/'.$file.'/'] = $file;
		}
	    $this->add_combobox($defs,
            	'recdata_dir', 'Каталог:',
            	$recdata_dir, $recdata_dir_ops, 0, true);
	}	
	elseif ($recdata == '1') {
		 $this->add_button($defs,
            'recdata_path_smb',
            'Путь, логин и пароль SMB:',
            'Изменить',
            500
        );	
	}
		
	
	$this->add_button
            (
                $defs,
                'page2',
                '',
                'Настройки далее ==>',
                0
            );
	}
	else if ($this->page_num == 2)
    {
        
		$this->add_button
            (
                $defs,
                'page1',
                'Настройки страница 2 из 4             ',
                '<== Назад',
                0
            );
	$this->add_button
        (
            $defs,
            'pin_dialog',
            'Код для закрытых каналов:',
            'Изменить...',
            500
        );
	$this->add_combobox($defs,
            'cod_type', 'Кодировки плейлистов:',
            $cod_type, $cod_type_ops, 0, true);
	$this->add_combobox($defs,
            'ico_show', 'Иконки с плейлистов:',
            $ico_show, $show_ops, 0, true);
	$this->add_combobox($defs,
            'double_tv', 'Сохранение избранного:',
            $double_tv, $double_ops, 0, true);
	$this->add_combobox($defs,
            'fav_save', 'Избранное в:',
            $fav_save, $fav_save_ops, 0, true);
	$this->add_combobox($defs,
            'fav_show', 'Показывать избранное:',
            $fav_show, $show_ops, 0, true);
	$this->add_combobox($defs,
            'epg_shift', 'Коррекция ТВ программы vsetv(час.):',
            $epg_shift, $shift_ops, 0, true);
	$this->add_combobox($defs,
            'epg_type', 'Установить коррекцию EPG для:',
            $epg_type, $epg_type_ops, 0, true);
	// if ($epg_type == 11) {
	// $this->add_combobox($defs,
            // 'epg_shifttvl_all', 'Коррекция EPG tvlistings all:',
            // $epg_shifttvl_all, $shiftjtv_ops, 0, true);}
	if ($epg_type == 10) {
	$this->add_combobox($defs,
            'epg_shiftteleman', 'Коррекция EPG teleman.pl:',
            $epg_shiftteleman, $shiftjtv_ops, 0, true);}
	if ($epg_type == 9) {
	$this->add_combobox($defs,
            'epg_shifttvprogrlt', 'Коррекция EPG tvprograma.lt:',
            $epg_shifttvprogrlt, $shiftjtv_ops, 0, true);}
	if ($epg_type == 8) {
	$this->add_combobox($defs,
            'epg_shiftteleguide', 'Коррекция EPG teleguide:',
            $epg_shiftteleguide, $shiftjtv_ops, 0, true);}
	if ($epg_type == 2) {
	$this->add_combobox($defs,
            'epg_shiftjtv', 'Коррекция EPG jtv:',
            $epg_shiftjtv, $shiftjtv_ops, 0, true);}
	if ($epg_type == 3) {
	$this->add_combobox($defs,
            'epg_shiftmail', 'Коррекция EPG tv.mail.ru:',
            $epg_shiftmail, $shiftjtv_ops, 0, true);}
	if ($epg_type == 4) {
	$this->add_combobox($defs,
            'epg_shiftakado', 'Коррекция EPG akado.ru:',
            $epg_shiftakado, $shiftjtv_ops, 0, true);}
	if ($epg_type == 5) {
	$this->add_combobox($defs,
            'epg_shiftntvplus', 'Коррекция EPG ntvplus.ru:',
            $epg_shiftntvplus, $shiftjtv_ops, 0, true);}
	if ($epg_type == 6) {
	$this->add_combobox($defs,
            'epg_shiftvspielfilm', 'Коррекция EPG tvspielfilm.de:',
            $epg_shiftvspielfilm, $shiftjtv_ops, 0, true);}
	if ($epg_type == 7) {
	$this->add_combobox($defs,
            'epg_shifttvlistingsuk', 'Коррекция EPG tvlistings UK:',
            $epg_shifttvlistingsuk, $shiftjtv_ops, 0, true);}
	$this->add_combobox($defs,
            'sort_channels', 'Сортировка каналов:',
            $sort_channels, $show_sort, 0, true);
		

	$this->add_button
            (
                $defs,
                'page3',
                '',
                'Настройки далее ==>',
                0
            );
	}
	else if ($this->page_num == 3)
	{
	$this->add_button
            (
                $defs,
                'page2',
                'Настройки страница 3 из 4             ',
                '<== Назад',
                0
            );
	$this->add_combobox
            ($defs,'buf_time', 'Время буферизации:', 
			$buf_time, $show_buf_time_ops, 0, true );	
	$this->add_combobox($defs,
            'altdata_type', 'Расположение altiptv_data:',
            $altdata_type, $altdata_ops, 0, true);
	if ($altdata_type == 3) {
		
        $this->add_button($defs,
            'altdata_path_smb',
            'Путь, логин и пароль SMB:',
            'Изменить',
            500
        );	
		   
	}	
	if ($altdata_type == 2)
	$this->add_label($defs, 'Внимание', 'Возможно удаление внесенных изменений:');
	$this->add_button
            (
                $defs,
                'export_myiptv_channels_id',
                'Выгрузить altiptv_data',
                'Плагин => Накопитель',
                0
            );
	$this->add_button
            (
                $defs,
                'import_myiptv_channels_id',
                'Загрузить altiptv_data',
                'Накопитель => Плагин',
                0
            );		
	$this->add_button
            (
                $defs,
                'del_alt_logo',
                'Очистить altiptv_data в плагине',
                'Удалить',
                0
            );	
	$this->add_button
            (
                $defs,
                'import_bg',
                'Бекграунд',
                'Загрузить/Удалить',
                0
            );
	$this->add_combobox($defs,
            'proxy_type', 'Адреса рабочих proxy:',
            $proxy_type, $proxy_ops, 0, true);

			$this->add_button
            (
                $defs,
                'page4',
                '',
                'Настройки далее ==>',
                0
            );
	}
	else if ($this->page_num == 4)
	{
		$this->add_button
				(
					$defs,
					'page3',
					'Настройки страница 4 из 4             ',
					'<== Назад',
					0
				);
		$this->add_combobox($defs,
				'use_proxy', 'Использовать proxy-сервер:',
				$use_proxy, $show_ops, 0, true);
		
		if ($use_proxy == 'yes') {

				$this->add_text_field($defs,
				'proxy_ip', 'Адрес proxy-сервера (IP или DNS):', $proxy_ip,
			false, false, false, true, 500, false, true);

			$this->add_text_field($defs,
				'proxy_port', 'Порт proxy-сервера:', $proxy_port,
				true, false, false,  true, null, false, true);
		}
		
		$this->add_button
            (
                $defs,
                'prov_pl',
                'Провайдерский плейлист:',
                'Выбрать',
                0
            );
			
		$this->add_button
            (
                $defs,
                'restart',
                '',
                'Перезагрузить плагин',
                0
            );	
		$this->add_button
            (
                $defs,
                'restart_pl',
                '',
                'Быстрая перезагрузка',
                0
            );	
		$this->add_button
            (
                $defs,
                'reboot_pl',
                '',
                'Полная перезагрузка',
                0
            );
		
		$this->add_combobox($defs,
				'use_update', 'Автообновление плагина:',
				$use_update, $show_ops, 0, true);
				
		$this->add_button
            (
                $defs,
                'rt_server',
                'RT сервер архивов',
                'Задать',
                0
            );
		$arc_server = HD::get_item('arc_server', &$plugin_cookies);
		$this->add_label($defs, 'Текущий:', $arc_server);
		$this->add_combobox($defs,
            'arc', 'Архивы:',
            $arc, $arc_ops, 0, true);
			
		$this->add_button
            (
                $defs,
                'moyo_useragent',
                'UserAgent Архивов Moyo:',
                'Задать',
                0
            );	
	}
        return $defs;
    }
	public function do_get_fav_show_defs()
    {
        $defs = array();
        ControlFactory::add_label($defs, "", "Для применения изменений необходима перезагрузка плеера");
		ControlFactory::add_label($defs, "", "Перезагрузить плеер сейчас?");
       $this->add_close_dialog_and_apply_button($defs,
            'restart_pl', 'Да', 250);
        $this->add_close_dialog_button($defs,
            'Нет', 250);
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
			$this->add_close_dialog_and_apply_button($defs,
            'prov_pl_apply', 'ОК', 250);
			$this->add_close_dialog_button($defs,
            'Отмена', 250);
			return $defs;
		}
	public function do_get_pin_control_defs(&$plugin_cookies)
    {
        $defs = array();

        $pin1 = '';
        $pin2 = '';
        
        $this->add_text_field($defs,
            'pin1', 'Старый код закрытых каналов:',
            $pin1, 1, 1, 0, 1, 500, 0, false);
        $this->add_text_field($defs,
            'pin2', 'Новый код закрытых каналов:',
            $pin2, 1, 1, 0, 1, 500, 0, false);

        $this->add_label($defs, '', '');
        
        $this->add_close_dialog_and_apply_button($defs,
            'pin_apply', 'ОК', 250);
        $this->add_close_dialog_button($defs,
            'Отмена', 250);
 
        return $defs;
    }
	public function do_get_import_bg_defs(&$plugin_cookies)
    {
		$defs = array();

        $this->add_button
            (
                $defs,
                'import_bgd',
                'Бекграунд',
                'Загрузить',
                0
            );

		$this->add_button
            (
                $defs,
                'del_bg',
                'Бекграунд',
                'Удалить',
                0
            );

        $this->add_close_dialog_button($defs,
            'ОК', 250);
 
        return $defs;
    }
	public function do_get_ip_path_smb_defs(&$plugin_cookies)
    {
        $smb_user = isset($plugin_cookies->smb_user) ? 
		$plugin_cookies->smb_user : 'guest';
		$smb_pass = isset($plugin_cookies->smb_pass) ? 
		$plugin_cookies->smb_pass : 'guest';
		$ip_path = isset($plugin_cookies->ip_path) ? 
		$plugin_cookies->ip_path : '';
		$defs = array();
		
		$this->add_text_field($defs,
                    'ip_path',
                   'Путь к SMB папке(IP/имя папки/..)',
                    $ip_path, 0, 0, 0, 1, 750, 0, 0
            );
        $this->add_text_field($defs,
                    'smb_user',
                    'Имя пользователя SMB папки:',
                    $smb_user, 0, 0, 0, 1, 750, 0, 0
            );

		$this->add_text_field($defs,
                    'smb_pass',
                    'Пароль SMB папки:',
                    $smb_pass, 0, 1, 0, 1, 750, 0, 0
            );
        
        $this->add_close_dialog_and_apply_button($defs,
            'ip_path_smb_apply', 'ОК', 250);
        $this->add_close_dialog_button($defs,
            'Отмена', 250);
 
        return $defs;
    }
	public function do_get_recdata_path_smb_defs(&$plugin_cookies)
    {
        $recdata_smb_user = isset($plugin_cookies->recdata_smb_user) ? 
		$plugin_cookies->recdata_smb_user : 'guest';
		$recdata_smb_pass = isset($plugin_cookies->recdata_smb_pass) ? 
		$plugin_cookies->recdata_smb_pass : 'guest';
		$recdata_ip_path = isset($plugin_cookies->recdata_ip_path) ? 
		$plugin_cookies->recdata_ip_path : '';
		$defs = array();
		
		$this->add_text_field($defs,
                    'recdata_ip_path',
                   'Путь к SMB папке(IP/имя папки/..)',
                    $recdata_ip_path, 0, 0, 0, 1, 750, 0, 0
            );
        $this->add_text_field($defs,
                    'recdata_smb_user',
                    'Имя пользователя SMB папки:',
                    $recdata_smb_user, 0, 0, 0, 1, 750, 0, 0
            );

		$this->add_text_field($defs,
                    'recdata_smb_pass',
                    'Пароль SMB папки:',
                    $recdata_smb_pass, 0, 1, 0, 1, 750, 0, 0
            );
        
        $this->add_close_dialog_and_apply_button($defs,
            'recdata_path_smb_apply', 'ОК', 250);
        $this->add_close_dialog_button($defs,
            'Отмена', 250);
 
        return $defs;
    }
		public function do_get_altdata_path_smb_defs(&$plugin_cookies)
    {
        $altdata_ip_path = isset($plugin_cookies->altdata_ip_path) ? 
		$plugin_cookies->altdata_ip_path : '';
		$altdata_smb_user = isset($plugin_cookies->altdata_smb_user) ? 
		$plugin_cookies->altdata_smb_user : 'guest';
		$altdata_smb_pass = isset($plugin_cookies->altdata_smb_pass) ? 
		$plugin_cookies->altdata_smb_pass : 'guest';
		$defs = array();
		$this->add_text_field($defs,
                    'altdata_ip_path',
                   'Путь к SMB папке(IP/имя папки/..)',
                    $altdata_ip_path, 0, 0, 0, 1, 500, 0, 0
            );
        $this->add_text_field($defs,
                    'altdata_smb_user',
                    'Имя пользователя SMB папки:',
                    $altdata_smb_user, 0, 0, 0, 1, 500, 0, 0
            );

		$this->add_text_field($defs,
                    'altdata_smb_pass',
                    'Пароль SMB папки:',
                    $altdata_smb_pass, 0, 1, 0, 1, 500, 0, 0
            ); 
        
        $this->add_close_dialog_and_apply_button($defs,
            'altdata_path_smb_apply', 'ОК', 250);
        $this->add_close_dialog_button($defs,
            'Отмена', 250);
 
        return $defs;
    }
    public function get_control_defs(MediaURL $media_url, &$plugin_cookies)
    {
        return $this->do_get_control_defs($plugin_cookies);
    }

    public function handle_user_input(&$user_input, &$plugin_cookies)
    {
        
		hd_silence_warnings();
        if ($user_input->action_type === 'confirm' || $user_input->action_type === 'apply' )
        {
            $control_id = $user_input->control_id;
            $new_value = $user_input->{$control_id};

            if ($control_id === 'show_tv')
                $plugin_cookies->show_tv = $new_value;
			if ($control_id === 'show_hd'){
                $plugin_cookies->show_hd = $new_value;
				$perform_new_action = UserInputHandlerRegistry::create_action(
                    $this, 'reset_controls');
		return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);
				}
			if ($control_id === 'group_p'){
                $plugin_cookies->group_p = $new_value;
				$perform_new_action = UserInputHandlerRegistry::create_action(
                    $this, 'reset_controls');
		return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);
				}
			else if ($control_id === 'use_update'){	
			$link = DuneSystem::$properties['install_dir_path'].'/dune_plugin.xml';
			$ver = file_get_contents($link);
			if ($user_input->use_update == 'no')
			$ver = str_replace('<url>http://dune-club.info/plugins/update/altiptv3/update5.txt</url>', 
			'<url></url>', $ver);
			else
			$ver = str_replace('<url></url>', 
			'<url>http://dune-club.info/plugins/update/altiptv3/update5.txt</url>', $ver);	
			$date_altiptv = fopen($link,"w");
				if (!$date_altiptv)
					return ActionFactory::show_title_dialog("Не могу записать dune_plugin.xml Что-то здесь не так!!!");
				fwrite($date_altiptv, $ver);
				@fclose($date_altiptv);
			$defs = $this->do_get_fav_show_defs();
			return  ActionFactory::show_dialog
			("Необходима перезагрузка",
			$defs,
			true);
			}
			else if ($control_id === 'group_tv'){
                $plugin_cookies->group_tv = $new_value;
				$start_tv = isset($plugin_cookies->start_tv) ?
				$plugin_cookies->start_tv : 0;
				if ($start_tv==1){
				$defs = array();	
				$defs = $this->do_get_fav_show_defs();
				return  ActionFactory::show_dialog
				("Необходима перезагрузка",
				$defs,
				true);
				}
			$perform_new_action = UserInputHandlerRegistry::create_action(
                    $this, 'reset_controls');
			return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);
				}
			else if ($control_id === 'start_tv'){
                $plugin_cookies->start_tv = $new_value;
				$perform_new_action = UserInputHandlerRegistry::create_action(
                    $this, 'reset_controls');
			return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);
				}
			 else if ($control_id === 'epg_font')
                $plugin_cookies->epg_font = $new_value;
			else if ($control_id === 'ico_show'){
                $plugin_cookies->ico_show = $new_value;
				$perform_new_action = UserInputHandlerRegistry::create_action(
                    $this, 'reset_controls');
		return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);
				}
				else if ($control_id === 'fav_show'){
                $plugin_cookies->fav_show = $new_value;
				$link = DuneSystem::$properties['data_dir_path'].'/altiptv_data/data_file/fav';
				$date_altiptv = fopen($link,"w");
				if (!$date_altiptv)
					{
					return ActionFactory::show_title_dialog("Не могу записать hide_ch Что-то здесь не так!!!");
					}
				fwrite($date_altiptv, $new_value);
				@fclose($date_altiptv);
				$defs = $this->do_get_fav_show_defs();
			return  ActionFactory::show_dialog
			("Необходима перезагрузка",
			$defs,
			true);}
			else if ($control_id === 'double_tv')
                $plugin_cookies->double_tv = $new_value;
			else if ($control_id === 'fav_save'){
                $plugin_cookies->fav_save = $new_value;
				$perform_new_action = UserInputHandlerRegistry::create_action(
                    $this, 'reset_controls');
		return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);}
			else if ($control_id === 'buf_time')
                $plugin_cookies->buf_time = $new_value;
            else if ($control_id === 'm3u'){
                $plugin_cookies->m3u = $new_value;
				$perform_new_action = UserInputHandlerRegistry::create_action(
                    $this, 'reset_controls');
		return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);}
		else if ($control_id === 'prov_pl')
			{
			$defs = $this->do_get_prov_pl_defs($plugin_cookies);
			if ($defs === false)
				return ActionFactory::show_title_dialog("Список провайдеров не доступен!!!");
			return  ActionFactory::show_dialog
			("Выбрать плейлист провайдера:",
			$defs,
			true
			);
			}
			else if ($control_id === 'restart')
			{
                $url = 'plugin_launcher://altiptv';
				return ActionFactory::launch_media_url($url);
			}
			else if ($user_input->control_id == 'moyo_useragent')
			{
				
				$moyo_user_agent = HD::get_item('moyo_user_agent', &$plugin_cookies);
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
					$moyo_user_agent, 0, 0, 0, 1, 750, 0, false);

				ControlFactory::add_close_dialog_and_apply_button(&$defs,
				$this, null,
				'apply_user_agent', 'Применить', 0, $gui_params = null);
				
				ControlFactory::add_close_dialog_and_apply_button(&$defs,
				$this, null,
				'del_user_agent', 'Очистить все User Agent', 0, $gui_params = null);
				$attrs['actions'] = null;
				return ActionFactory::show_dialog("Задать User Agent архивам Moyo", $defs,true,0,$attrs);
			}
			else if ($user_input->control_id == 'apply_user_agent')
			{
				$user_agent_list_ops = HD::get_items('user_agent_list_ops', &$plugin_cookies);
				if ((($user_input->user_agent_list == 'v')&&($user_input->new_user_agent == ''))||($user_input->user_agent_list == 'del'))
				{
					HD::save_item('moyo_user_agent', '', &$plugin_cookies);
					return ActionFactory::invalidate_folders(array('tv_group_list'), null);
				}
				if (($user_input->user_agent_list == 'v')&&($user_input->new_user_agent !== '')){
					$u_a[$user_input->new_user_agent] = substr($user_input->new_user_agent, 0, 50) . '...';
					$user_agent_list_ops = array_merge ($u_a, $user_agent_list_ops);
					HD::save_items('user_agent_list_ops', $user_agent_list_ops, &$plugin_cookies);
					HD::save_item('moyo_user_agent', $user_input->new_user_agent, &$plugin_cookies);
					return ActionFactory::invalidate_folders(array('tv_group_list'), null);
				}
				if (($user_input->user_agent_list !== 'v')&&($user_input->new_user_agent == '')){
					HD::save_item('moyo_user_agent', $user_input->user_agent_list, &$plugin_cookies);
					return ActionFactory::invalidate_folders(array('tv_group_list'), null);
				}
			}
			else if ($user_input->control_id == 'del_user_agent')
			{
				HD::save_item('moyo_user_agent', '', &$plugin_cookies);
				return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);
			}
			else if ($control_id === 'dvbt_channels')
			{
                $m3u_type = isset($plugin_cookies->m3u_type) ?
				$plugin_cookies->m3u_type : '1';
				$m3u_dir = isset($plugin_cookies->m3u_dir) ?
				$plugin_cookies->m3u_dir : '/D';
				$altiptv_data_path = DemoConfig::get_altiptv_data_path(&$plugin_cookies);
				if (file_exists('/persistfs/DVBT_channels/'))
					$link = '/persistfs/DVBT_channels/';
				else if (file_exists('/flashdata/DVBT_channels/'))
					$link = '/flashdata/DVBT_channels/';
				$files = scandir($link);
				array_shift($files);
				array_shift($files);
				$m3u_exp = "#EXTM3U\n";
				foreach($files as $file){
				$dune_folder = $link . $file . '/dune_folder.txt';
				$txt = file_get_contents($dune_folder);
				preg_match('/media_url = (.*)\s/', $txt, $match);
				$m3u_exp .= "#EXTINF:0," . $file . "\n";
				$m3u_exp .= $match[1] . "\n";
				}
				if ($m3u_type == 1)
					$m3u_link = $altiptv_data_path . "/playlists/dvb-t2.m3u";
				else if ($m3u_type == 2)
					$m3u_link = $m3u_dir . "/dvb-t2.m3u";
				else
					$m3u_link = '/D/dvb-t2.m3u';
				$data = fopen($m3u_link,"w");
						if (!$data)
							{
							return ActionFactory::show_title_dialog("Не могу записать плейлист Что-то здесь не так!!!");
							}
						fwrite($data, $m3u_exp);
						@fclose($data);
				$perform_new_action = UserInputHandlerRegistry::create_action(
                    $this, 'reset_controls');
				return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);
				//return ActionFactory::show_title_dialog("Плейлист выгружен!");
			}
			else if ($control_id === 'export_myiptv_channels_id')
			{	if (!file_exists('/D/'))
				return ActionFactory::show_title_dialog("Подключите накопитель к плееру и повторите!");
                $mci = DuneSystem::$properties['data_dir_path'].'/altiptv_data/';
				shell_exec("cp -r $mci /D/ > /dev/null &");
				return ActionFactory::show_title_dialog("altiptv_data выгружается в фоновом режиме это может занять несколько минут.");
			}
			else if ($control_id === 'import_myiptv_channels_id')
			{	if (!file_exists('/D/altiptv_data/'))
				return ActionFactory::show_title_dialog("altiptv_data не найдена!");
                $mci = DuneSystem::$properties['data_dir_path'];
				shell_exec("cp -r /D/altiptv_data/ $mci  > /dev/null &");
				$perform_new_action = ActionFactory::show_title_dialog("altiptv_data загружается в фоновом режиме это может занять несколько минут.");
				return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);
			}
			else if ($control_id === 'prov_pl_apply')
			{
			$plugin_cookies->prov_pl = $user_input->prov_pl;
			$perform_new_action = UserInputHandlerRegistry::create_action(
            $this, 'reset_controls');
				return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);
			}
			else if ($control_id === 'rt_server')
			{
				$arc_server = HD::get_item('arc_server', &$plugin_cookies);
				$defs = array();
				$this->add_label($defs, '', 'Часть ссылки между http:// и /hls');
				$this->add_label($defs, '', 'Поддерживаются только серверы RT');
				$this->add_text_field($defs,
				'arc_link', '',
				$arc_server, 0, 0, 0, 1, 1000, 0, 0);
				$this->add_label($defs, '', '');
				$this->add_close_dialog_and_apply_button($defs,
					'rt_server_apply', 'Применить', 300);
				return  ActionFactory::show_dialog
							(
								"Введите адрес сервера архивов RT",
								$defs,
								true
							);
			}
			else if ($control_id === 'rt_server_apply')
			{
			$arc_link = $user_input->arc_link;
			$arc_link = str_replace(array ('http://','/hls/'), '', $arc_link);
			HD::save_item('arc_server', $arc_link,&$plugin_cookies);
				return UserInputHandlerRegistry::create_action(
            $this, 'reset_controls');
			}
			else if ($control_id === 'recdata_dir')
			{
			$plugin_cookies->recdata_dir = $user_input->recdata_dir;
			$perform_new_action = UserInputHandlerRegistry::create_action(
            $this, 'reset_controls');
				return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);
			}
			else if ($control_id === 'recdata')
			{
			$plugin_cookies->recdata = $user_input->recdata;
			$perform_new_action = UserInputHandlerRegistry::create_action(
            $this, 'reset_controls');
				return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);
			}
			else if ($control_id === 'arc')
			{
			$plugin_cookies->arc = $user_input->arc;
				return UserInputHandlerRegistry::create_action(
            $this, 'reset_controls');
			}
			else if ($control_id === 'import_bgd')
			{	if (!file_exists('/D/bg.jpg'))
				return ActionFactory::show_title_dialog("В корневом каталоге bg.jpg не найден!");
                $mci = DuneSystem::$properties['data_dir_path'].'/altiptv_data/icons/bg.jpg';
				shell_exec("cp /D/bg.jpg $mci");
				$defs = $this->do_get_fav_show_defs();
				return  ActionFactory::show_dialog
				("Необходима перезагрузка",
				$defs,
				true);
			}
			else if ($control_id === 'del_bg')
			{	if (!file_exists(DuneSystem::$properties['data_dir_path'].'/altiptv_data/icons/bg.jpg'))
				return ActionFactory::show_title_dialog("Файл bg.jpg не найден! Удаление не требуется.");
                $mci = DuneSystem::$properties['data_dir_path'].'/altiptv_data/icons/bg.jpg';
				unlink($mci);
				$defs = $this->do_get_fav_show_defs();
				return  ActionFactory::show_dialog
				("Необходима перезагрузка",
				$defs,
				true);
			}
			else if ($control_id === 'del_alt_logo')
			{	
				$mci = DuneSystem::$properties['data_dir_path'].'/altiptv_data/';
				$mpi = DuneSystem::$properties['install_dir_path'].'/data/';
				if (file_exists($mci))
				shell_exec("rm -rf $mci");
				if (!file_exists($mci))
				shell_exec("cp -r $mpi $mci  > /dev/null &");
				$perform_new_action = ActionFactory::show_title_dialog("altiptv_data сбрасывается в фоновом режиме это может занять несколько минут.");
				return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);
			}
			else if ($control_id === 'restart_pl')
			{
                shell_exec('killall shell');
				 break;
			}
			else if ($control_id === 'reboot_pl')
			{
                shell_exec('reboot');
				 break;
			}
			else if ($control_id === 'info_release')
			{
                $post_action = null;
				$doc = HD::http_get_document('http://dune-club.info/plugins/update/altiptv/info.txt');
				ControlFactory::add_multiline_label($defs, '', $doc, 15);
						ControlFactory::add_custom_close_dialog_and_apply_buffon($defs, 'setup', 'Ok', 250,  $post_action);
						return ActionFactory::show_dialog('Информация об изменениях.', $defs,
								true, 1500);
			}
            else if ($control_id === 'm3u_type'){
                $plugin_cookies->m3u_type = $new_value;
			$perform_new_action = UserInputHandlerRegistry::create_action(
						$this, 'reset_controls');
			return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);
				}
			else if ($control_id === 'altdata_type'){
                $plugin_cookies->altdata_type = $new_value;
			$perform_new_action = UserInputHandlerRegistry::create_action(
						$this, 'reset_controls');
			return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);
				}
			else if ($control_id === 'proxy_type')
                $plugin_cookies->proxy_type = $new_value;
			else if ($control_id === 'dload_http')
                $plugin_cookies->dload_http = $user_input->dload_http;
			else if ($control_id === 'rec_type')
                $plugin_cookies->rec_type = $new_value;
			else if ($control_id === 'm3u_dir'){
				$plugin_cookies->m3u_dir = $new_value;
				$perform_new_action = UserInputHandlerRegistry::create_action(
                    $this, 'reset_controls');
		return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);
				}
			else if ($control_id === 'ip_path_smb'){
				$defs = $this->do_get_ip_path_smb_defs($plugin_cookies);
					
					return  ActionFactory::show_dialog
							(
								"Логин и пароль SMB папки",
								$defs,
								true
							);
				}
			else if ($control_id === 'recdata_path_smb'){
				$defs = $this->do_get_recdata_path_smb_defs($plugin_cookies);
					
					return  ActionFactory::show_dialog
							(
								"Логин и пароль SMB папки",
								$defs,
								true
							);
				}
			else if ($control_id === 'import_bg'){
				$defs = $this->do_get_import_bg_defs($plugin_cookies);
					
					return  ActionFactory::show_dialog
							(
								"Бекграунд плагина (Обои)",
								$defs,
								true
							);
				}
			else if ($control_id === 'altdata_path_smb'){
				$defs = $this->do_get_altdata_path_smb_defs($plugin_cookies);
					
					return  ActionFactory::show_dialog
							(
								"Путь, логин и пароль SMB папки",
								$defs,
								true
							);
				}
			else if ($control_id === 'ip_path_smb_apply'){
				$plugin_cookies->smb_user = $user_input->smb_user;
				$plugin_cookies->smb_pass = $user_input->smb_pass;
				$plugin_cookies->ip_path = $user_input->ip_path;
				$perform_new_action = UserInputHandlerRegistry::create_action(
						$this, 'reset_controls');
			return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);
			}
			else if ($control_id === 'recdata_path_smb_apply'){
				$plugin_cookies->recdata_smb_user = $user_input->recdata_smb_user;
				$plugin_cookies->recdata_smb_pass = $user_input->recdata_smb_pass;
				$plugin_cookies->recdata_ip_path = $user_input->recdata_ip_path;
				$perform_new_action = UserInputHandlerRegistry::create_action(
						$this, 'reset_controls');
			return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);
			}
			else if ($control_id === 'altdata_path_smb_apply'){
				$plugin_cookies->altdata_smb_user = $user_input->altdata_smb_user;
				$plugin_cookies->altdata_smb_pass = $user_input->altdata_smb_pass;
				$plugin_cookies->altdata_ip_path = $user_input->altdata_ip_path;
				$perform_new_action = UserInputHandlerRegistry::create_action(
						$this, 'reset_controls');
			return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);
			}
			else if ($control_id === 'epg_shift')
				$plugin_cookies->epg_shift = $new_value;
			else if ($control_id === 'epg_type')
				$plugin_cookies->epg_type = $new_value;
			else if ($control_id === 'cod_type'){
				$plugin_cookies->cod_type = $new_value;
				$perform_new_action = UserInputHandlerRegistry::create_action(
                    $this, 'reset_controls');
		return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);
				}
			else if ($control_id === 'epg_shiftjtv')
				$plugin_cookies->epg_shiftjtv = $new_value;
			else if ($control_id === 'epg_shiftteleman')
				$plugin_cookies->epg_shiftteleman = $new_value;
			// else if ($control_id === 'epg_shifttvl_all')
				// $plugin_cookies->epg_shifttvl_all = $new_value;
			else if ($control_id === 'epg_shiftteleguide')
				$plugin_cookies->epg_shiftteleguide = $new_value;
			else if ($control_id === 'epg_shifttvprogrlt')
				$plugin_cookies->epg_shifttvprogrlt = $new_value;
			else if ($control_id === 'epg_shiftmail')
				$plugin_cookies->epg_shiftmail = $new_value;
			else if ($control_id === 'epg_shiftakado')
				$plugin_cookies->epg_shiftakado = $new_value;
			else if ($control_id === 'epg_shiftntvplus')
				$plugin_cookies->epg_shiftntvplus = $new_value;
			else if ($control_id === 'epg_shiftntvplus')
				$plugin_cookies->epg_shiftntvplus = $new_value;
			else if ($control_id === 'epg_shiftvspielfilm')
				$plugin_cookies->epg_shiftvspielfilm = $new_value;
			else if ($control_id === 'page1')
            {
                $this->page_num = 1;
                return ActionFactory::reset_controls(
                    $this->do_get_control_defs($plugin_cookies), null, 1);
            }
            else if ($control_id === 'page2')
            {
                $this->page_num = 2;
                return ActionFactory::reset_controls(
                    $this->do_get_control_defs($plugin_cookies), null, 1);
            }
			else if ($control_id === 'page3')
            {
                $this->page_num = 3;
                return ActionFactory::reset_controls(
                    $this->do_get_control_defs($plugin_cookies), null, 1);
            }
			else if ($control_id === 'page4')
            {
                $this->page_num = 4;
                return ActionFactory::reset_controls(
                    $this->do_get_control_defs($plugin_cookies), null, 1);
            }
			else if ($control_id === 'use_proxy') {
					$plugin_cookies->use_proxy = $new_value;
					$plugin_cookies->proxy_ip = isset($plugin_cookies->proxy_ip) ? $plugin_cookies->proxy_ip : '192.168.1.1';
		    $plugin_cookies->proxy_port = isset($plugin_cookies->proxy_port) ? $plugin_cookies->proxy_port : '9999';
                }
			// else if ($user_input->control_id === 'show_help')
            // return ActionFactory::launch_media_url(
                // "www://http://dune-club.info/instructions/altIPTV:::fullscreen=1&zoom_level=150&overscan=0");
			else if ($user_input->control_id === 'dload_help'){
            if (!file_exists('/D/'))
			return ActionFactory::show_title_dialog("Накопитель для загрузки HELP не найден! Подключите накопитель.");
			shell_exec("wget -c -t 0 http://dune-club.info/plugins/update/altiptv3/help_altIPTV.pdf -P /D/ > /tmp/run/altwget.log &");
			return ActionFactory::show_title_dialog("help_altIPTV.pdf - добавлен в закачки");
			}
            else if ($control_id === 'proxy_ip')
                $plugin_cookies->proxy_ip = $new_value;
            else if ($control_id === 'proxy_port')
                $plugin_cookies->proxy_port = $new_value;
			else if ($control_id === 'sort_channels'){
                $plugin_cookies->sort_channels = $new_value;
				$perform_new_action = UserInputHandlerRegistry::create_action($this, 'reset_controls');
				return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);
			}
			else if ($control_id === 'pin_dialog')
				{
					$defs = $this->do_get_pin_control_defs($plugin_cookies);
					
					return  ActionFactory::show_dialog
							(
								"Родительский контроль",
								$defs,
								true
							);
				}
			else if ($control_id === 'pin_apply')
				{
					if ($user_input->pin1 == '' || $user_input->pin2 == '')
						return null;
					
					$msg = '';
					$action = null;

					$pin = isset($plugin_cookies->pin) ? $plugin_cookies->pin : '0000';

					if ($user_input->pin1 == $pin)
					{
					$plugin_cookies->pin = $user_input->{'pin2'};
					$msg = 'Код изменен!';
					}
					else
					{
					$msg = 'Код не изменен!';
					}
				
					return  ActionFactory::show_title_dialog
							(
								$msg,
								$action
							);
				}
				else if ($control_id === 'reset_controls')
				{
				return ActionFactory::reset_controls(
            $this->do_get_control_defs($plugin_cookies));
				}
        }

        return ActionFactory::reset_controls(
            $this->do_get_control_defs($plugin_cookies));
			hd_restore_warnings();
    }
	
}

///////////////////////////////////////////////////////////////////////////
?>
