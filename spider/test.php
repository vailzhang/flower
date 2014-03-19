<?php

$curl = new Curl();
$curl->setMethod('POST');
$curl->setUrl('http://spider/index.php?r=spider_job/insert');
$r = $curl->fetch(array(
		'_s' => '51job',
		'type' => '全职',
		'url' => 'http://www.asdfasdf.com',
		'edu' => '不限',
		'exp' => '不限',
		'salary' => '不限',
		'city' => '上海',
		'date' => '2013-5-8',
		'title' => '招聘高级工程师',
		'employer' => '公司名',
		'other' => '其它信息'
));

echo $r;
print_r(json_decode($r, true));


