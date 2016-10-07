<?
error_reporting (E_ALL);
require_once "do_config.php";
$info = DuneSystem::$properties['tmp_dir_path'] . '/scan_inf';
$pid_info = DuneSystem::$properties['tmp_dir_path'] . '/pid_inf';
if (file_exists($pid_info)){
$kill_pid = file_get_contents($pid_info);
shell_exec("kill $kill_pid > /dev/null &");}
$pid = posix_getpid();
$alt_pid = fopen($pid_info,"w");
fwrite($alt_pid, $pid);
@fclose($alt_pid);
$n1 = $_GET["n1"];
$n2 = $_GET["n2"];
$an = $_GET["an"];
$en = $_GET["en"];
$port = $_GET["port"];
$proxy_list_file = $_GET["plf"];
$prov = $_GET["prov"];
if (file_exists($proxy_list_file))
unlink($proxy_list_file);
$count_a = ($en - $an)+1;
$scan = "$n1.$n2.$an.0 - $n1.$n2.$en.255";
$count = 0;
$cpl = 'не найдено';
$new_inf = "Провайдер:| $prov\nДиапазон:| $scan\nПорт: |$port\nВыполнено:| 0%\nНайдено всего:| $cpl адресов прокси\n |Сканирование запущено!!!";
$alt_inf = fopen($info,"w");
fwrite($alt_inf, $new_inf);
@fclose($alt_inf);
	for ($n3=$an; $n3<=$en; $n3++)	
	{
	$count++;
	$url = $n1.'.'.$n2.'.'.$n3;
	$pr =  round((($count / $count_a)*100) , 2);
	if ($pr < 100)
	$new_inf = "Провайдер:| $prov\nДиапазон:| $scan\nПорт: |$port\nСканируется диапазон:| $url.(0-255)\nДиапазон:| $count из $count_a\nВыполняется:| $pr%\nНайдено:| $cpl адресов прокси";
	for ($i = 0; $i <= 255; $i++) 
		{
		$fp = @fsockopen($url.'.'.$i, $port, $errno, $errstr, 0.2);
			if ($fp) 
			{
				$pr = $url.'.'.$i;
				$per_proxy = "$pr:$port";
				$save_proxy = "$per_proxy\n";
				$fp = fopen($proxy_list_file, 'a+');
				$proxy_list = file_get_contents($proxy_list_file);
				$pos = strpos($proxy_list, $per_proxy);
				if ($pos === false)
				{
					$save_proxy_list = fwrite($fp, $save_proxy);
					if (!$save_proxy_list)
					$cpl = "НЕ МОГУ ЗАПИСАТЬ НА USB/HDD";
				}
				fclose($fp);
			}
		}
		if (file_exists($proxy_list_file)){
		$pra = file($proxy_list_file);
		$cpl = count($pra);
		}
		if ($pr == 100)
		$new_inf = "Провайдер:| $prov\nДиапазон:| $scan\nПорт: |$port\nВыполнено:| $pr%\nНайдено всего:| $cpl адресов прокси\n |Сканирование завершено!!!";
		$alt_inf = fopen($info,"w");
		fwrite($alt_inf, $new_inf);
		@fclose($alt_inf);
	}