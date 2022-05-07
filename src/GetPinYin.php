<?php
/***********************************************************
 * Description:  中文处理拼音
 * Copyright(C):
 * Created by:   PhpStorm
 * Version:      v1.0.0
 * Function:     Include function
 *
 * @author:      Jeffry    w@aiwrr.com
 * @datetime:    2022/1/24  16:39
 * @others:      Use the PhpStorm
 *
 * history:      Modify record
 ***********************************************************/

namespace Lronboy\Area;


class GetPinYin
{
	public static $pdo;
	
	/**
	 * description:初始化sqlite链接
	 *+----------------------------------------------------------------------
	 * @return void
	 *+----------------------------------------------------------------------
	 * @author: Admin  2022-01-24 16:48:31
	 * @access: static
	 * history: Modify record
	 */
	public static function init()
	{
		if(static::$pdo === null){
			$data_path = __DIR__. '/data/chineseData.sqlite';
			static::$pdo = new \PDO('sqlite:'.$data_path, '', '');
		}
	}
	
	/**
	 * description: 获取多个中文拼音
	 *+----------------------------------------------------------------------
	 * @param $char
	 * @return
	 * @throws \Exception
	 *+----------------------------------------------------------------------
	 * @author: Admin  2022-01-24 17:53:10
	 * @access: static
	 * history: Modify record
	 */
	public static function getCharsPinYin($chars)
	{
		if(!static::$pdo){
			self::init();
		}
		
		$chars_arr = preg_split('/(?<!^)(?!$)/u', $chars);
		$result = '';
		foreach ($chars_arr as $value){
			$py = self::getCharPinYin($value);
			if($py){
				$result .= $py;
			}else{
				$result = '';
				break;
			}
		}
		return $result;
	}
	
	
	/**
	 * description: 获取中文拼音
	 *+----------------------------------------------------------------------
	 * @param $char
	 * @return mixed
	 * @throws \Exception
	 *+----------------------------------------------------------------------
	 * @author: Admin  2022-01-26 14:57:15
	 * @access: static
	 * history: Modify record
	 */
	public static function getCharPinYin($char)
	{
		$stmt = static::$pdo->prepare('select pinyin  from chars where char = :char limit 1'); // 发送sql语句到数据库进行编译，但不执行
		if(false === $stmt) {
			$err_arr = static::$pdo->errorInfo();
			throw new \Exception($err_arr[2], $err_arr[1]); // 抛出异常信息
		}
		
		$stmt->bindValue('char', $char); // 绑定参数
		$result = $stmt->execute(); // 绑定参数后，执行sql语句
		if(false === $result){
			$err_arr = static::$pdo->errorInfo();
			throw new \Exception($err_arr[2], $err_arr[1]); // 抛出异常信息
		}
		
		$data = $stmt->fetch(\PDO::FETCH_ASSOC); //查询单条数据 PDO::FETCH_ASSOC返回关联数组类型的数据, PDO::FETCH_NUM返回索引数组类型的数据
		
		if($data === false || (isset($data['pinyin']) && !$data['pinyin'])){
			return $char;
		}else{
			$py_arr = explode(',', $data['pinyin']);
			return $py_arr[0];
		}
	}
}