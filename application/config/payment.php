<?php
/**
 * 파일 위치: application/config/payment.php
 * 역할: PG 결제 관련 설정
 */
defined('BASEPATH') or exit('No direct script access allowed');

// URL 호스트 기반 환경 자동 설정
$http_host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';

// 운영 환경 판단: www.wani.im 또는 wani.im
$is_production = ($http_host === 'www.wani.im' || $http_host === 'wani.im');

// 개발 환경 판단: dev.wani.im
$is_development = ($http_host === 'dev.wani.im');

if ($is_production) {
	// 운영 환경 설정
	$config['smartro_pg'] = array(
		'mode' => 'REAL',
		'mid' => 'webhows01m',
		'merchant_key' => 'zAT7JcQwS6ZJzzkbyGHZti7GxT4RRqTUpAhPRPmw5qCR5AHDokTEeiK5bmDnDgTFR1VjWX8/OiOSDCIYtSJuYg==',
		'sdk_url' => 'https://pay.smartropay.co.kr/asset/js/SmartroPAY-1.0.min.js',
		'approval_url' => 'https://approval.smartropay.co.kr/payment/approval/urlCallApproval.do',
		'cancel_url' => 'https://approval.smartropay.co.kr/payment/cancel/cancelApproval.do'
	);

	// 로그 기록
	log_message('info', 'PG Payment Mode: PRODUCTION (Host: ' . $http_host . ')');

} else {
	// 테스트 환경 설정 (개발계 및 기타)
	$config['smartro_pg'] = array(
		'mode' => 'STG',
		'mid' => 't_2510201m',
		'merchant_key' => '0/4GFsSd7ERVRGX9WHOzJ96GyeMTwvIaKSWUCKmN3fDklNRGw3CualCFoMPZaS99YiFGOuwtzTkrLo4bR4V+Ow==',
		'sdk_url' => 'https://tpay.smartropay.co.kr/asset/js/SmartroPAY-1.0.min.js',
		'approval_url' => 'https://tapproval.smartropay.co.kr/payment/approval/urlCallApproval.do',
		'cancel_url' => 'https://tapproval.smartropay.co.kr/payment/cancel/cancelApproval.do'
	);

	// 로그 기록
	log_message('info', 'PG Payment Mode: TEST (Host: ' . $http_host . ')');
}

// 환경 정보 저장 (디버깅용)
$config['smartro_pg']['current_host'] = $http_host;
$config['smartro_pg']['environment'] = $is_production ? 'production' : 'development';
