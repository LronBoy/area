<?php
/***********************************************************
 * Description:  Curl
 * Copyright(C):
 * Created by:   PhpStorm
 * Version:      v1.0.0
 * Function:     Include function
 *
 * @author:      Jeffry    w@aiwrr.com
 * @datetime:    2022/1/24  13:59
 * @others:      Use the PhpStorm
 *
 * history:      Modify record
 ***********************************************************/

namespace Lronboy\Area;

class HttpCurl
{
	private static $url     = '';   // 访问的url
	private static $oriUrl  = '';   // referer url
	private static $data    = [];   // 可能发出的数据 post,put
	private static $method;         // 访问方式，默认是GET请求
	
	/**
	 * description: 发送请求
	 *+----------------------------------------------------------------------
	 * @param string    $url        请求连接
	 * @param array     $data       请求参数
	 * @param string    $method     请求方法
	 * @return mixed
	 *+----------------------------------------------------------------------
	 * @author: Admin  2022-01-25 11:21:30
	 * @access: static
	 * history: Modify record
	 */
	public static function send($url, $data = [], $method = 'get') {
		
		if (!$url){
			throw new \Exception('url can not be null'); // 抛出异常信息
		}
		
		self::$url = $url;
		self::$method = $method;
		
		$urlArr = parse_url($url);
		self::$oriUrl = $urlArr['scheme'] .'://'. $urlArr['host'];
		self::$data = $data;
		
		if ( !in_array($method, ['get', 'post', 'put', 'delete']))
		{
			exit('error request method type!');
		}
		
		preg_match_all('/\d+/', $url, $path_dir);
		
		$path = __DIR__.'/data/html';
		foreach ($path_dir[0] as $v){
			$path = $path.'/'.$v;
			
			if(is_file($path.'.html') && strpos(file_get_contents($path.'.html'),'国家统计局') !== false){
				continue;
			}
			
			print_r(date('Y-m-d H:i:s').'---【html】处理：'.$path.".html\n");
			if(!is_dir($path)){
				@mkdir($path, 0777, true);
				$func = self::$method . 'Request';
				$html = self::$func(self::$url);
				if(strpos($html, '404 Not Found') !== false){
					#判断当前目录是否为空
					if(empty(array_diff(scandir($path),array('..','.')))){
						rmdir($path);
					}
					continue;
				}
				file_put_contents($path.'.html', $html);
			}else if(!is_file($path.'.html') || strpos(file_get_contents($path.'.html'),'国家统计局') === false){
				$func = self::$method . 'Request';
				$html = self::$func(self::$url);
				if(strpos($html, '404 Not Found') !== false){
					#判断当前目录是否为空
					if(empty(array_diff(scandir($path),array('..','.')))){
						rmdir($path);
					}
					continue;
				}
				file_put_contents($path.'.html', $html);
			}
		}

		
		return [
			'status' => true,
			'data'  => !is_file($path.'.html') ? '404 Not Found' : file_get_contents($path.'.html')
		];
		
	}
	
	
	/**
	 * description: 发起get请求
	 *+----------------------------------------------------------------------
	 * @return bool|string
	 *+----------------------------------------------------------------------
	 * @author: Admin  2022-01-25 11:19:37
	 * @access: public
	 * history: Modify record
	 */
	public static function getRequest() {
		return self::doRequest(0);
	}
	
	
	/**
	 * description: 发起post请求
	 *+----------------------------------------------------------------------
	 * @return bool|string
	 *+----------------------------------------------------------------------
	 * @author: Admin  2022-01-25 11:19:26
	 * @access: public
	 * history: Modify record
	 */
	public static function postRequest() {
		return self::doRequest(1);
	}
	
	
	/**
	 * description: 处理发起非get请求的传输数据
	 *+----------------------------------------------------------------------
	 * @param $postData
	 * @return false|string
	 *+----------------------------------------------------------------------
	 * @author: Admin  2022-01-25 11:19:19
	 * @access: public
	 * history: Modify record
	 */
	public static function dealPostData($postData) {
		if (!is_array($postData)){
			throw new \Exception('data should be array'); // 抛出异常信息
		};
		$o = '';
		foreach ($postData as $k => $v) {
			$o .= "$k=" . urlencode($v) . "&";
		}
		$postData = substr($o, 0, -1);
		return $postData;
	}
	
	
	/**
	 * description: 发起put请求
	 *+----------------------------------------------------------------------
	 * @param $param
	 * @return bool|string
	 *+----------------------------------------------------------------------
	 * @author: Admin  2022-01-25 11:19:04
	 * @access: public
	 * history: Modify record
	 */
	public static function putRequest($param) {
		return self::doRequest(2);
	}
	
	
	/**
	 * description: 发起delete请求
	 *+----------------------------------------------------------------------
	 * @param $param
	 * @return bool|string
	 *+----------------------------------------------------------------------
	 * @author: Admin  2022-01-25 11:18:44
	 * @access: public
	 * history: Modify record
	 */
	public static function deleteRequest($param) {
		return self::doRequest(3);
	}
	
	
	/**
	 * description: 基础发起curl请求函数
	 *+----------------------------------------------------------------------
	 * @param int $is_post 是否是post请求
	 * @return bool|string
	 *+----------------------------------------------------------------------
	 * @author: Admin  2022-01-25 11:20:27
	 * @access: public
	 * history: Modify record
	 */
	private static  function doRequest($is_post = 0) {
		$ch = curl_init();//初始化curl
		curl_setopt($ch, CURLOPT_URL, self::$url);//抓取指定网页
		curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		// 来源一定要设置成来自本站
		curl_setopt($ch, CURLOPT_REFERER, self::$oriUrl);
		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
		if($is_post == 1) curl_setopt($ch, CURLOPT_POST, $is_post);//post提交方式
		if (!empty(self::$data)) {
			self::$data = self::dealPostData(self::$data);
			curl_setopt($ch, CURLOPT_POSTFIELDS, self::$data);
		}
		
		$data = curl_exec($ch);//运行curl
		curl_close($ch);
		return $data;
	}
}



