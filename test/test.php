<?php
require_once __DIR__ . '/../src/LogReported.php';

$filePath = '../';
$uploadUrl = 'www.text.com';
$linesPer = 1;
$timeThreshold = 300;

//上报数据脚本
$logReported = new \hsmfdata\LogReported($filePath, $uploadUrl, $linesPer, $timeThreshold);
$logReported->handle();

require_once __DIR__ . '/../src/HsmfAnalytics.php';
$customer = new FileConsumer('../');
$sa = new HsmfAnalytics($customer);
//用户访问记录
$sa->track('123456', 'test', 'access_record', [
    'source_page' => '/page/1',
    'access_page' => '/page/2',
    'access_at' => '2023-12-26 12:00:00'
]);
//用户观看记录
$sa->track('123456', 'test', 'viewing_record', [
    "short_play_id" => "DgB",
    "short_play_name" => "短剧1",
    "episodes" => 3,
    "start_viewing_at" => "2023-12:29 00:21:00",
    "viewing_duration" => 50
]);
//用户追剧记录
$sa->track('123456', 'test', 'follow_record', [
    "short_play_id" => "DgB",
    "short_play_name" => "短剧1",
    "follow_short_play_at" => "2023-12:29 00:21:00"
]);
//用户取消追剧记录
$sa->track('123456', 'test', 'unfollow_record', [
    "short_play_id" => "DgB",
    "short_play_name" => "短剧1",
    "unfollow_short_play_at" => "2023-12:29 00:21:00"
]);
//用户看官币来源记录
$sa->track('123456', 'test', 'coins_source_record', [
    "type" => "DgB",
    "amount" => "短剧1",
    "distribution_at" => "2023-12:29 00:21:00"
]);
//用户点赞记录
$sa->track('123456', 'test', 'likes_record', [
    "short_play_id" => "DgB",
    "short_play_name" => "短剧1",
    "likes_at" => "2023-12:29 00:21:00"
]);
//用户签到记录
$sa->track('123456', 'test', 'sign_in_record', [
    "reward_amount" => "短剧1",
    "sign_in_at" => "2023-12:29 00:21:00"
]);
//用户充值记录
$sa->track('123456', 'test', 'recharge_record', [
    "order_id" => "短剧1",
    "order_at" => "2023-12:29 00:21:00",
    "money" => 10,
    "order_type" => 1,
    "pay_type" => 1,
    "vip_exp_at" => "2023-12:29 00:21:00",
    "short_play_id" => 1
]);
//用户消费记录
$sa->track('123456', 'test', 'consumption_record', [
    "serial_number" => "短剧1",
    "consum_at" => "2023-12:29 00:21:00",
    "consumer_type" => 10,
    "money" => 1,
    "balance" => 1,
    "gift_balance" => "2023-12:29 00:21:00",
    "gift_exp_at" => 1
]);
//用户退款记录
$sa->track('123456', 'test', 'consumption_record', [
    "order_id" => "短剧1",
    "refund_at" => "2023-12:29 00:21:00",
    "money" => 10
]);
$sa->close();

