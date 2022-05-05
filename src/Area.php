<?php
/***********************************************************
 * Description:  Description
 * Copyright(C):
 * Created by:   PhpStorm
 * Version:      v1.0.0
 * Function:     Include function
 *
 * @author:      Jeffry    w@aiwrr.com
 * @datetime:    2022/1/25  14:20
 * @others:      Use the PhpStorm
 *
 * history:      Modify record
 ***********************************************************/

namespace Lronboy\Area;
require_once './HttpCurl.php';
require_once './GetPinYin.php';

class Area
{
	private static $url =  'http://www.stats.gov.cn/tjsj/tjbz/tjyqhdmhcxhfdm/';
	
	private static $query_url = null;
	
	const LEVEL_PROVINCE= 1;
	const LEVEL_CITY    = 2;
	const LEVEL_COUNTY  = 3;
	const LEVEL_TOWN    = 4;
	const LEVEL_VILLAGE = 5;
	
	private static $area_level = [
		self::LEVEL_PROVINCE    => 'Province',
		self::LEVEL_CITY        => 'City',
		self::LEVEL_COUNTY      => 'County',
		self::LEVEL_TOWN        => 'Town',
		self::LEVEL_VILLAGE     => 'Village'
	];
	
	private static $area_tr = [
		self::LEVEL_PROVINCE    => 'provincetr',
		self::LEVEL_CITY        => 'citytr',
		self::LEVEL_COUNTY      => 'countytr',
		self::LEVEL_TOWN        => 'towntr',
		self::LEVEL_VILLAGE     => 'villagetr',
	];
	
	private static $pdo;
	

	
	/**
	 * description: 获取省份信息
	 *+----------------------------------------------------------------------
	 * @return array|mixed
	 * @throws \Exception
	 *+----------------------------------------------------------------------
	 * @author: Admin  2022-01-26 16:00:27
	 * @access: static
	 * history: Modify record
	 */
	public static function getProvince()
	{
		# 初始化
		if(!static::$pdo){self::init();}
		# 获取请求URL
		self::getHtmlUrl();
		# 请求处理数据
		self::handleData([['url' => '', 'id' => 0]], self::LEVEL_PROVINCE);
	}
	
	
	/**
	 * description: 获取城市信息
	 *+----------------------------------------------------------------------
	 * @return void
	 * @throws \Exception
	 *+----------------------------------------------------------------------
	 * @author: Admin  2022-02-07 10:31:56
	 * @access: static
	 * history: Modify record
	 */
	public static function getCity()
	{
		# 获取父级数据
		$data = self::getParentData(self::LEVEL_PROVINCE);
		
		# 获取请求URL
		self::getHtmlUrl();
		
		# 请求数据处理
		self::handleData($data, self::LEVEL_CITY);
	}
	
	
	/**
	 * description: 获取区县信息
	 *+----------------------------------------------------------------------
	 * @return void
	 * @throws \Exception
	 *+----------------------------------------------------------------------
	 * @author: Admin  2022-02-07 11:15:47
	 * @access: static
	 * history: Modify record
	 */
	public static function getCounty()
	{
		# 获取父级数据
		$data = self::getParentData(self::LEVEL_CITY);
		# 获取请求URL
		self::getHtmlUrl();
		# 请求数据处理
		self::handleData($data, self::LEVEL_COUNTY);
	}
	
	
	/**
	 * description: 获取乡镇信息
	 *+----------------------------------------------------------------------
	 * @return void
	 * @throws \Exception
	 *+----------------------------------------------------------------------
	 * @author: Admin  2022-02-07 16:01:38
	 * @access: static
	 * history: Modify record
	 */
	public static function getTown()
	{
		# 获取父级数据
		$data = self::getParentData(self::LEVEL_COUNTY);
		# 获取请求URL
		self::getHtmlUrl();
		# 请求数据处理
		self::handleData($data, self::LEVEL_TOWN);
	}
	
	/**
	 * description: 获取村信息
	 *+----------------------------------------------------------------------
	 * @return void
	 *+----------------------------------------------------------------------
	 * @author: Admin  2022-02-07 16:02:58
	 * @access: static
	 * history: Modify record
	 */
	public static function getVillage(){
		# 获取父级数据
		$data = self::getParentData(self::LEVEL_TOWN);
		# 获取请求URL
		self::getHtmlUrl();
		# 请求数据处理
		self::handleData($data, self::LEVEL_VILLAGE);
	}
	
	/**
	 * description:初始化sqlite链接
	 *+----------------------------------------------------------------------
	 * @return void
	 *+----------------------------------------------------------------------
	 * @author: Admin  2022-01-24 16:48:31
	 * @access: static
	 * history: Modify record
	 */
	private static function init()
	{
		if(static::$pdo === null){
			$data_path = __DIR__.'/data/area_'.date('Y').'.sqlite';
			static::$pdo = new \PDO('sqlite:'.$data_path, '', '');
			
			//判断表是否存在
			$sSQL = "SELECT count(*) as UDS_COUNT FROM sqlite_master WHERE type='table' AND name = 'area'";
			
			$ret = static::$pdo->query($sSQL);
			$count = 0;
			while($row = $ret->fetchAll(\PDO::FETCH_ASSOC)){
				$count = $row['UDS_COUNT'];
			}
			if(!$count){
				/*初始化创建数据表，可创建多个表*/
				$createTable = 'CREATE TABLE area
	            (id             BIGINT          NOT NULL,
	             name           VARCHAR(128)    NOT NULL,
	             abbreviation   VARCHAR(128)    NOT NULL,
	             fid            BIGINT          NOT NULL,
	             level          INT2            NOT NULL,
	             uc_first       VARCHAR(8)      NOT NULL,
	             pinyin         VARCHAR(255)    NOT NULL,
	             url            VARCHAR(128)    NOT NULL);';
				static::$pdo->exec($createTable);
			}
		}
	}
	
	
	/**
	 * description: 获取父级数据
	 *+----------------------------------------------------------------------
	 * @param   int     $parent_level 父级的level
	 * @return  array
	 *+----------------------------------------------------------------------
	 * @author: Admin  2022-02-07 10:29:55
	 * @access: static
	 * history: Modify record
	 */
	private static function getParentData($parent_level = self::LEVEL_PROVINCE)
	{
		if(!static::$pdo){self::init();} // 231182415000 231182415598
		$level = $parent_level+1;
		$fid_stmt = static::$pdo->query("select * from area where level={$level} order by fid desc limit 1");
		$fid_arr = $fid_stmt->fetch();
		
		if(isset($fid_arr) && !empty($fid_arr)){
			$stmt = static::$pdo->query("select * from area where level={$parent_level} and id >= {$fid_arr['fid']}");
		}else{
			$stmt = static::$pdo->query("select * from area where level={$parent_level}");
		}
		
		$data = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		if(empty($data)){
			$area_name = self::$area_level[$parent_level];
			$func_name = 'get'.$area_name;
			self::$func_name();
			if(isset($fid_arr) && !empty($fid_arr)){
				$stmt = static::$pdo->query("select * from area where level={$parent_level} and id >= {$fid_arr['fid']}");
			}else{
				$stmt = static::$pdo->query("select * from area where level={$parent_level}");
			}
			$data = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		}
		return $data;
	}
	
	
	/**
	 * description: 获取最新的资源地址
	 *+----------------------------------------------------------------------
	 * @return array|mixed
	 * @throws \Exception
	 *+----------------------------------------------------------------------
	 * @author: Admin  2022-01-27 09:36:29
	 * @access: static
	 * history: Modify record
	 */
	private static function getHtmlUrl(){
		if(self::$query_url == null){
			$now_year = date('Y');
			$url      = self::$url.$now_year.'/';
			$html_str = HttpCurl::send($url);
			
			if(!$html_str['status']){
				return $html_str;
			}
			
			if(strpos($html_str['data'], '404 Not Found') !== false || strpos($html_str['data'], 'Page Not Found') !== false){
				$url = self::$url.($now_year-1).'/';
			}
			self::$query_url = $url;
		}
	}
	
	
	/**
	 * description: 处理远程数据
	 *+----------------------------------------------------------------------
	 * @param $data
	 * @param int $level
	 * @return void
	 * @throws \Exception
	 *+----------------------------------------------------------------------
	 * @author: Admin  2022-02-07 11:08:12
	 * @access: static
	 * history: Modify record
	 */
	private static function handleData($data, $level = self::LEVEL_PROVINCE)
	{
		foreach ($data as $value){
			
			# 获取远程请求数据
			$html_str = HttpCurl::send(self::$query_url.$value['url']);
//			$html_str = HttpCurl::send('http://www.stats.gov.cn/tjsj/tjbz/tjyqhdmhcxhfdm/2021/13/07/32/130732100.html');
			
			# 跳出不能访问链接
			if(strpos($html_str['data'], '404 Not Found') !== false  || strpos($html_str['data'], 'Page Not Found') !== false){
				continue;
			}
			
			$temp_str = strstr(strstr($html_str['data'], self::$area_tr[$level]),'table', true);
			
			if($level == self::LEVEL_VILLAGE){
				$matches_arr[0] = [];
				
				$temp_arr = explode('</td></tr>',$temp_str);
				foreach ($temp_arr as $v){
					$str = substr($v, strrpos($v, '>')+1);
					if(mb_strlen($str) <= 1){
						continue;
					}
					$matches_arr[0][] = $str;
				}
			}else{
				$temp_str = str_replace('（lang）','', $temp_str);
				preg_match_all('/[^\x00-\xff]+/u', $temp_str, $matches_arr);
			}
			
			if($level == self::LEVEL_PROVINCE){
				preg_match_all('/\d+/', $temp_str, $matches_code_arr);
			}else{
				preg_match_all('/\d{12}/', $temp_str, $matches_code_arr);
			}
			
			if(isset($matches_arr[0]) && isset($matches_code_arr)){
				self::handleSqliteData($matches_arr[0], $matches_code_arr[0], $value['id'], $level);
			}
		}
	}
	
	/**
	 * description: 处理数据库数据
	 *+----------------------------------------------------------------------
	 * @param $city_arr
	 * @param $code_arr
	 * @param $fid
	 * @param $type
	 * @return void
	 *+----------------------------------------------------------------------
	 * @author: Admin  2022-01-26 14:30:11
	 * @access: static
	 * history: Modify record
	 */
	private static function handleSqliteData($city_arr, $code_arr, $fid, $type)
	{
		foreach ($city_arr as $key => $value){
			$code           = str_pad($code_arr[$key], 12, 0);
			
			print_r(date('Y-m-d H:i:s').'===【处理:】'.$code.'==='.$value."\n");
			
			if($code == '000000000000'){
				exit;
			}
			
			$query_sql      = "select * from area where id='{$code}'";
			$result         = static::$pdo->query($query_sql);
			$data = $result->fetch(\PDO::FETCH_ASSOC);
			
			$url            = self::getUrl($code, $type);
			$area           = self::getAbbreviation($value, $type, $fid);
			
			$pin_yin        = GetPinYin::getCharsPinYin($area['abbreviation']);
			$uc_first       = strtoupper(mb_substr($pin_yin, 0, 1));
			
			if($data['id'] == $code){
				$sql = "UPDATE area SET id={$code},name='{$area['area']}',abbreviation='{$area['abbreviation']}',fid={$fid},level={$type},uc_first='{$uc_first}',pinyin='{$pin_yin}',url='{$url}' WHERE id='{$code}'";
			}else{
				$sql = "INSERT INTO area VALUES({$code},'{$area['area']}','{$area['abbreviation']}',$fid,$type,'{$uc_first}','{$pin_yin}','{$url}')";
			}
			static::$pdo->exec($sql);
			
		}
	}
	
	
	/**
	 * description: 获取下一级链接
	 *+----------------------------------------------------------------------
	 * @param $code
	 * @param $type
	 * @return false|string
	 *+----------------------------------------------------------------------
	 * @author: Admin  2022-01-26 14:30:32
	 * @access: static
	 * history: Modify record
	 */
	private static function getUrl($code, $type)
	{
		if($type == self::LEVEL_PROVINCE){
			return substr($code,  0, 2).'.html';
		}elseif ($type == self::LEVEL_CITY){
			return substr($code,0,2).'/'.substr($code,0,4).'.html';
		}elseif ($type == self::LEVEL_COUNTY){
			return substr($code,0,2).'/'.substr($code,2,2).'/'.substr($code, 0, 6).'.html';
		}elseif ($type == self::LEVEL_TOWN){
			return substr($code,0,2).'/'.substr($code,2,2).'/'.substr($code,4,2).'/'.substr($code, 0, 9).'.html';
		}
	}
	
	
	/**
	 * description: 获取简称
	 *+----------------------------------------------------------------------
	 * @param $area_name
	 * @param $type
	 * @param $fid
	 * @return string|string[]
	 *+----------------------------------------------------------------------
	 * @author: Admin  2022-01-26 14:43:51
	 * @access: static
	 * history: Modify record
	 */
	private static function getAbbreviation($area_name, $type, $fid)
	{
		if($type == self::LEVEL_PROVINCE){
			$area_temp = str_replace('省','', $area_name);
			$area_temp = str_replace('市','', $area_temp);
			$area_temp = str_replace('回族自治区','', $area_temp);
			$area_temp = str_replace('回族自治区','', $area_temp);
			$area_temp = str_replace('维吾尔自治区','', $area_temp);
			$area_temp = str_replace('壮族自治区','', $area_temp);
			return ['area' => $area_name, 'abbreviation'=>str_replace('自治区','', $area_temp)];
		}elseif ($type == self::LEVEL_CITY){
			$abbreviation_temp = str_replace('市市辖区','市辖区', $area_name);
			
			$abbreviation_temp = str_replace('土家族苗族自治州','', $abbreviation_temp);
			$abbreviation_temp = str_replace('藏族羌族自治州','', $abbreviation_temp);
			$abbreviation_temp = str_replace('布依族苗族自治州','', $abbreviation_temp);
			$abbreviation_temp = str_replace('苗族侗族自治州','', $abbreviation_temp);
			$abbreviation_temp = str_replace('壮族苗族自治州','', $abbreviation_temp);
			$abbreviation_temp = str_replace('傣族景颇族自治州','', $abbreviation_temp);
			$abbreviation_temp = str_replace('蒙古族藏族自治州','', $abbreviation_temp);
			$abbreviation_temp = str_replace('哈尼族彝族自治州','', $abbreviation_temp);
			
			$abbreviation_temp = str_replace('朝鲜族自治州','', $abbreviation_temp);
			$abbreviation_temp = str_replace('傈僳族自治州','', $abbreviation_temp);
			$abbreviation_temp = str_replace('哈萨克自治州','', $abbreviation_temp);
			$abbreviation_temp = str_replace('克孜勒苏柯尔克孜自治州','克洲', $abbreviation_temp);
			$abbreviation_temp = str_replace('藏族自治州','', $abbreviation_temp);
			$abbreviation_temp = str_replace('彝族自治州','', $abbreviation_temp);
			$abbreviation_temp = str_replace('傣族自治州','', $abbreviation_temp);
			$abbreviation_temp = str_replace('白族自治州','', $abbreviation_temp);
			$abbreviation_temp = str_replace('回族自治州','', $abbreviation_temp);
			$abbreviation_temp = str_replace('蒙古自治州','', $abbreviation_temp);
			
			$abbreviation_temp = str_replace('省直辖县级行政区划','省直辖县', $abbreviation_temp);
			$abbreviation_temp = str_replace('自治区直辖县级行政区划','直辖县', $abbreviation_temp);
			
			# 地区-特殊处理
			if(mb_substr($abbreviation_temp, mb_strlen($abbreviation_temp)-2, 2) == '地区'){
				$abbreviation_temp = str_replace('地区','', $abbreviation_temp);
			}
			
			# 盟-特殊处理
			if(mb_substr($abbreviation_temp, mb_strlen($abbreviation_temp)-1, 1) == '盟'){
				$abbreviation_temp = str_replace('盟','', $abbreviation_temp);
			}
			
			# 省级市-直辖县特殊处理
			$abbreviation_temp = $abbreviation_temp == '县' ? '市直辖县' : $abbreviation_temp;
			
			# 最后是市-特殊处理
			if(mb_substr($abbreviation_temp, mb_strlen($abbreviation_temp)-1, 1) == '市'){
				$abbreviation_temp = str_replace('市','', $abbreviation_temp);
			}
			
			if(in_array($abbreviation_temp, ['市辖区', '省直辖县', '直辖县', '市直辖县'])){
				$query_sql      = "select * from area where id={$fid}";
				$result         = static::$pdo->query($query_sql);
				$data           = $result->fetch(\PDO::FETCH_ASSOC);
				$abbreviation_temp = $data['abbreviation'].$abbreviation_temp;
				$area_name      = $abbreviation_temp;
			}
			
			return ['area'=>$area_name, 'abbreviation' => $abbreviation_temp];
		}elseif ($type == self::LEVEL_COUNTY){
			if(in_array($area_name, ['市辖区', '省直辖县', '直辖县', '市直辖县'])){
				$query_sql      = "select * from area where id={$fid}";
				$result         = static::$pdo->query($query_sql);
				$data           = $result->fetch(\PDO::FETCH_ASSOC);
				$abbreviation_temp = $data['abbreviation'].$area_name;
				$area_name      = $abbreviation_temp;
			}
			return ['area' => $area_name, 'abbreviation' => $area_name];
		}
		
		return ['area' => $area_name, 'abbreviation' => $area_name];
	}
}
# Area::getProvince();
