<?php
/**
 * 파일 위치: application/config/payment.php
 * 역할: PG 결제 관련 설정
 */
defined('BASEPATH') or exit('No direct script access allowed');

// 환경 설정 (development: 테스트, production: 운영)
$environment = ENVIRONMENT;

if ($environment === 'production') {
	// 운영 환경 설정
	$config['smartro_pg'] = array(
		'mode' => 'REAL',
		'mid' => '',  // 운영 MID 입력
		'merchant_key' => '',  // 운영 상점키 입력
		'sdk_url' => 'https://pay.smartropay.co.kr/asset/js/SmartroPAY-1.0.min.js',
		'approval_url' => 'https://approval.smartropay.co.kr/payment/approval/urlCallApproval.do'
	);
} else {
	// 테스트 환경 설정
	$config['smartro_pg'] = array(
		'mode' => 'STG',
		'mid' => 'smartrotest',  // 테스트 MID (발급받은 값으로 변경)
		'merchant_key' => 'testmerchantkey123',  // 테스트 상점키 (발급받은 값으로 변경)
		'sdk_url' => 'https://tpay.smartropay.co.kr/asset/js/SmartroPAY-1.0.min.js',
		'approval_url' => 'https://tapproval.smartropay.co.kr/payment/approval/urlCallApproval.do'
	);
}
