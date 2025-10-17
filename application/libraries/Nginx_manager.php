<?php
/**
 * 파일 위치: application/libraries/Nginx_manager.php
 * 역할: Nginx 설정 파일 및 홈페이지 디렉토리 관리 (최소화 버전)
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Nginx_manager
{
	private $CI;
	private $nginx_conf_dir = '/etc/nginx/conf.d/sites/';
	private $homepage_dir = '/var/www/wani/public/homepage/';
	private $theme_dir = '/var/www/wani/public/homepage/_theme/';

	public function __construct()
	{
		$this->CI =& get_instance();
	}

	/**
	 * Nginx 설정 파일 생성
	 */
	public function create_nginx_config($org_code, $domain, $org_name)
	{
		if (empty($org_code) || empty($domain)) {
			return array('success' => false, 'message' => '조직 코드 또는 도메인이 비어있습니다.');
		}

		// 도메인 정리 (프로토콜 제거)
		$domain = preg_replace('#^https?://#', '', $domain);
		$domain = trim($domain, '/');

		$config_content = $this->generate_nginx_config_content($org_code, $domain, $org_name);
		$config_file = $this->nginx_conf_dir . $org_code . '.conf';

		// 디렉토리 생성
		if (!is_dir($this->nginx_conf_dir)) {
			if (!@mkdir($this->nginx_conf_dir, 0755, true)) {
				return array('success' => false, 'message' => 'Nginx 설정 디렉토리를 생성할 수 없습니다.');
			}
		}

		// 설정 파일 저장
		if (@file_put_contents($config_file, $config_content) === false) {
			return array('success' => false, 'message' => 'Nginx 설정 파일을 생성할 수 없습니다.');
		}

		// Nginx 재시작
		$reload_result = $this->reload_nginx();

		return array(
			'success' => true,
			'message' => 'Nginx 설정 파일이 생성되었습니다.',
			'config_file' => $config_file,
			'reload_result' => $reload_result
		);
	}

	/**
	 * Nginx 설정 파일 내용 생성 (최소화 버전)
	 */
	private function generate_nginx_config_content($org_code, $domain, $org_name)
	{
		$root_dir = $this->homepage_dir . $org_code;

		// SSL 인증서 경로
		$ssl_cert = '/etc/letsencrypt/live/' . $domain . '/fullchain.pem';
		$ssl_key = '/etc/letsencrypt/live/' . $domain . '/privkey.pem';

		// SSL 인증서 존재 여부 확인
		$has_ssl = file_exists($ssl_cert) && file_exists($ssl_key);

		if ($has_ssl) {
			// HTTPS 설정
			$config = <<<EOT
# {$org_name} ({$org_code})

# HTTP to HTTPS redirect
server {
    listen 80;
    listen [::]:80;
    server_name {$domain} www.{$domain};
    return 301 https://\$host\$request_uri;
}

# HTTPS server
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name {$domain} www.{$domain};

    ssl_certificate {$ssl_cert};
    ssl_certificate_key {$ssl_key};
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    root {$root_dir};
    index index.html;

    access_log /var/log/nginx/{$org_code}_access.log;
    error_log /var/log/nginx/{$org_code}_error.log;

    client_max_body_size 20M;

    location / {
        try_files \$uri \$uri/ /index.html;
    }
    
    location ~ /\. {
        deny all;
    }
}
EOT;
		} else {
			// HTTP만 사용
			$config = <<<EOT
# {$org_name} ({$org_code})

server {
    listen 80;
    listen [::]:80;
    server_name {$domain} www.{$domain};

    root {$root_dir};
    index index.html;

    access_log /var/log/nginx/{$org_code}_access.log;
    error_log /var/log/nginx/{$org_code}_error.log;

    client_max_body_size 20M;

    location / {
        try_files \$uri \$uri/ /index.html;
    }
    
    location ~ /\. {
        deny all;
    }
}
EOT;
		}

		return $config;
	}

	/**
	 * 홈페이지 디렉토리 및 index.html 생성
	 */
	public function create_homepage_directory($org_code, $org_name, $homepage_setting, $org_id)
	{
		if (empty($org_code)) {
			return array('success' => false, 'message' => '조직 코드가 비어있습니다.');
		}

		$org_dir = $this->homepage_dir . $org_code;

		// 디렉토리 생성
		if (!is_dir($org_dir)) {
			if (!@mkdir($org_dir, 0755, true)) {
				return array('success' => false, 'message' => '홈페이지 디렉토리를 생성할 수 없습니다.');
			}
			@chmod($org_dir, 0755);
		}

		// 테마 번호 가져오기 (기본값: 1)
		$theme = isset($homepage_setting['theme']) ? $homepage_setting['theme'] : '1';

		// 테마 파일 경로
		$theme_file = $this->theme_dir . $theme . '/main.html';

		if (!file_exists($theme_file)) {
			return array('success' => false, 'message' => '테마 파일을 찾을 수 없습니다.');
		}

		// 테마 HTML 읽기
		$template_content = file_get_contents($theme_file);

		// 템플릿 변수 치환
		$index_content = $this->replace_template_variables($template_content, $org_code, $org_name, $homepage_setting);

		// index.html 저장
		$index_file = $org_dir . '/index.html';

		if (@file_put_contents($index_file, $index_content) === false) {
			return array('success' => false, 'message' => 'index.html 파일을 생성할 수 없습니다.');
		}

		@chmod($index_file, 0644);

		return array(
			'success' => true,
			'message' => '홈페이지가 생성되었습니다.',
			'directory' => $org_dir,
			'index_file' => $index_file,
			'theme' => $theme
		);
	}

	/**
	 * 템플릿 변수 치환
	 */
	private function replace_template_variables($template_content, $org_code, $org_name, $homepage_setting)
	{
		$homepage_name = isset($homepage_setting['homepage_name']) ? $homepage_setting['homepage_name'] : $org_name;

		// 조직 코드를 JavaScript 변수로 주입
		$org_code_script = "<script>const ORG_CODE = '{$org_code}';</script>";
		$template_content = str_replace('</head>', $org_code_script . "\n</head>", $template_content);

		// 템플릿 변수 치환 (조직명과 조직코드만)
		$replacements = array(
			'{{homepage_name}}' => htmlspecialchars($homepage_name),
			'{{org_code}}' => htmlspecialchars($org_code)
		);

		return str_replace(array_keys($replacements), array_values($replacements), $template_content);
	}

	/**
	 * Nginx 재시작
	 */
	private function reload_nginx()
	{
		// Nginx 설정 테스트
		$test_output = array();
		$test_return = 0;
		@exec('sudo nginx -t 2>&1', $test_output, $test_return);

		if ($test_return !== 0) {
			return array('success' => false, 'message' => 'Nginx 설정 테스트 실패');
		}

		// Nginx 재시작
		$reload_output = array();
		$reload_return = 0;
		@exec('sudo systemctl reload nginx 2>&1', $reload_output, $reload_return);

		if ($reload_return !== 0) {
			return array('success' => false, 'message' => 'Nginx 재시작 실패');
		}

		return array('success' => true, 'message' => 'Nginx가 재시작되었습니다.');
	}

	/**
	 * Nginx 설정 파일 삭제
	 */
	public function delete_nginx_config($org_code)
	{
		$config_file = $this->nginx_conf_dir . $org_code . '.conf';

		if (file_exists($config_file)) {
			if (@unlink($config_file)) {
				$this->reload_nginx();
				return array('success' => true, 'message' => 'Nginx 설정 파일이 삭제되었습니다.');
			} else {
				return array('success' => false, 'message' => 'Nginx 설정 파일 삭제 실패');
			}
		}

		return array('success' => true, 'message' => '설정 파일이 존재하지 않습니다.');
	}

	/**
	 * 홈페이지 디렉토리 삭제
	 */
	public function delete_homepage_directory($org_code)
	{
		$org_dir = $this->homepage_dir . $org_code;

		if (is_dir($org_dir)) {
			// 디렉토리 내 파일 삭제
			$files = glob($org_dir . '/*');
			foreach ($files as $file) {
				if (is_file($file)) {
					@unlink($file);
				}
			}

			// 디렉토리 삭제
			if (@rmdir($org_dir)) {
				return array('success' => true, 'message' => '홈페이지 디렉토리가 삭제되었습니다.');
			} else {
				return array('success' => false, 'message' => '홈페이지 디렉토리 삭제 실패');
			}
		}

		return array('success' => true, 'message' => '디렉토리가 존재하지 않습니다.');
	}
}
