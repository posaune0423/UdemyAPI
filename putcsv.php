<?php
require_once(__DIR__ . '/config.php');
require __DIR__ . '/vendor/autoload.php';

header("Content-Type: application/octet-stream");
header("Content-Disposition: attachment; filename=data.csv");


use \GuzzleHttp\Client;
use GuzzleHttp\Pool;

$client = new Client();

$base_url = "https://www.udemy.com/api-2.0/courses";
$search_opt = '?fields[course]=visible_instructors,url,title,num_subscribers,created,archive_time,primary_category,primary_subcategory';

session_start();

foreach($_SESSION['id_list'] as $v) {
	$id_list[] = $v;
}
foreach ($id_list as $course_id) {
	$urlList[] = $base_url .'/' . $course_id . $search_opt;
}



$requests = function ($urlList) use ($client) {
	foreach ($urlList as $url) {
		yield function () use ($client, $url) {
			return $client->getAsync($url);
		};
	}
};



$decode_res = [];

$pool = new Pool($client, $requests($urlList), [
	'concurrency' => 200,
	'fulfilled' => function ($response, $index) use ($urlList) {
		$decode_res[] = json_decode($response->getBody()->getContents(), true);


		$label = array(
			'動画名',
			'概要',
			'著者名',
			'カテゴリー',
			'サブカテゴリー',
			'トピック',
			'',
			'URL',
			'Views',
			'公開日時',
			'時間数',
		);

		$count = 0;

		foreach ($decode_res as $v) {
			$data = array(
				array(
					'title' => $v['title'],
					'description' => '',
					'author' => $v['visible_instructors'][0]['title'],
					'category' => $v['primary_category']['title'],
					'sub_category' => $v['primary_subcategory']['title'],
					'topic' => '',
					'' => '',
					'url' => 'https://www.udemy.com' . $v['url'],
					'views' => $v['num_subscribers'],
					'created' => date('Y/n/j', strtotime($v['created'])),
					'hour' => ''
				)
			);

			$count += 1;
		}
		$f = fopen('php://output', "w");

		if ( $f ) {
			if($count == 0){
				//カラム名書き出し
				fputcsv($f, $label);
			}

			foreach($data as $line){
				//取得データcsv書き出し
				if ($data[0]['title'] == ''){
					break;
				}
				fputcsv($f, $line);
			}
		}
		fclose($f);
	},
]);




$promise = $pool->promise();
$promise->wait();
