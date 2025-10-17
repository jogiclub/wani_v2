<?php
/**
 * 파일 위치: application/libraries/Nginx_manager.php
 * 역할: Nginx 설정 파일 및 홈페이지 디렉토리 관리 (테마 기반)
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Nginx_manager
{
	private $CI;
	private $nginx_conf_dir = '/etc/nginx/conf.d/sites/';
	private $homepage_dir = '/var/www/wani/public/homepage/';
	private $theme_dir = '/var/www/wani/public/homepage/_theme/';
	private $php_fpm_socket = 'unix:/var/run/php/php7.4-fpm.sock';

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

		$domain = preg_replace('#^https?://#', '', $domain);
		$domain = trim($domain, '/');

		$config_content = $this->generate_nginx_config_content($org_code, $domain, $org_name);
		$config_file = $this->nginx_conf_dir . $org_code . '.conf';

		if (!is_dir($this->nginx_conf_dir)) {
			if (!@mkdir($this->nginx_conf_dir, 0755, true)) {
				log_message('error', 'Nginx config directory 생성 실패: ' . $this->nginx_conf_dir);
				return array('success' => false, 'message' => 'Nginx 설정 디렉토리를 생성할 수 없습니다.');
			}
		}

		if (@file_put_contents($config_file, $config_content) === false) {
			log_message('error', 'Nginx config 파일 생성 실패: ' . $config_file);
			return array('success' => false, 'message' => 'Nginx 설정 파일을 생성할 수 없습니다. 권한을 확인하세요.');
		}

		log_message('info', 'Nginx config 파일 생성 완료: ' . $config_file);

		$reload_result = $this->reload_nginx();

		return array(
			'success' => true,
			'message' => 'Nginx 설정 파일이 생성되었습니다.',
			'config_file' => $config_file,
			'reload_result' => $reload_result
		);
	}

	/**
	 * Nginx 설정 파일 내용 생성
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
			// SSL 인증서가 있는 경우: HTTPS 설정
			$config = <<<EOT
# {$org_name} ({$org_code}) - Auto Generated

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
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;

    root {$root_dir};
    index index.html index.htm;

    access_log /var/log/nginx/{$org_code}_access.log;
    error_log /var/log/nginx/{$org_code}_error.log;

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    client_max_body_size 20M;

    # 공통 정적 파일 경로 (테마, JS, CSS) - 중요: rewrite 사용
    location /homepage/ {
        alias /var/www/wani/public/homepage/;
        try_files \$uri \$uri/ =404;
    }

    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    location ~ /\. {
        deny all;
        access_log off;
        log_not_found off;
    }

    location / {
        try_files \$uri \$uri/ /index.html;
    }

    location = /robots.txt {
        access_log off;
        log_not_found off;
    }

    location = /favicon.ico {
        access_log off;
        log_not_found off;
    }
}

EOT;
		} else {
			// SSL 인증서가 없는 경우: HTTP만 사용
			$config = <<<EOT
# {$org_name} ({$org_code}) - Auto Generated
server {
    listen 80;
    listen [::]:80;
    server_name {$domain} www.{$domain};

    root {$root_dir};
    index index.html index.htm;

    access_log /var/log/nginx/{$org_code}_access.log;
    error_log /var/log/nginx/{$org_code}_error.log;

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    client_max_body_size 20M;

    # 공통 정적 파일 경로 (테마, JS, CSS)
    location /homepage/ {
        alias /var/www/wani/public/homepage/;
        try_files \$uri \$uri/ =404;
    }

    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    location ~ /\. {
        deny all;
        access_log off;
        log_not_found off;
    }

    location / {
        try_files \$uri \$uri/ /index.html;
    }

    location = /robots.txt {
        access_log off;
        log_not_found off;
    }

    location = /favicon.ico {
        access_log off;
        log_not_found off;
    }
}

EOT;
		}

		return $config;
	}

	/**
	 * 홈페이지 디렉토리 및 기본 index.html 생성
	 */
	public function create_homepage_directory($org_code, $org_name, $homepage_setting, $org_id)
	{
		if (empty($org_code)) {
			return array('success' => false, 'message' => '조직 코드가 비어있습니다.');
		}

		$org_dir = $this->homepage_dir . $org_code;

		if (!is_dir($org_dir)) {
			if (!@mkdir($org_dir, 0755, true)) {
				log_message('error', '홈페이지 디렉토리 생성 실패: ' . $org_dir);
				return array('success' => false, 'message' => '홈페이지 디렉토리를 생성할 수 없습니다.');
			}

			@chown($org_dir, 'www-data');
			@chgrp($org_dir, 'www-data');
			@chmod($org_dir, 0755);
		}

		// 테마 번호 가져오기
		$theme = isset($homepage_setting['theme']) ? $homepage_setting['theme'] : '1';

		// 테마 HTML 파일 읽기
		$theme_file = $this->theme_dir . $theme . '/main.html';

		if (!file_exists($theme_file)) {
			log_message('error', '테마 파일을 찾을 수 없습니다: ' . $theme_file);
			return array('success' => false, 'message' => '테마 파일을 찾을 수 없습니다.');
		}

		$theme_content = file_get_contents($theme_file);

		// 템플릿 변수 치환
		$index_content = $this->replace_template_variables($theme_content, $org_code, $org_name, $homepage_setting);

		$index_file = $org_dir . '/index.html';

		// 기존 파일이 있으면 삭제 (테마 변경 시 새로 생성하기 위함)
		if (file_exists($index_file)) {
			@unlink($index_file);
			log_message('info', '기존 index.html 파일 삭제: ' . $index_file);
		}

		if (@file_put_contents($index_file, $index_content) === false) {
			log_message('error', 'index.html 파일 생성 실패: ' . $index_file);
			return array('success' => false, 'message' => 'index.html 파일을 생성할 수 없습니다.');
		}

		@chmod($index_file, 0644);
		@chown($index_file, 'www-data');
		@chgrp($index_file, 'www-data');

		log_message('info', '홈페이지 디렉토리 및 index.html 생성 완료: ' . $org_dir);

		return array(
			'success' => true,
			'message' => '홈페이지 디렉토리가 생성되었습니다.',
			'directory' => $org_dir,
			'index_file' => $index_file,
			'theme' => $theme
		);
	}

	/**
	 * 템플릿 변수 치환
	 */
	/**
	 * 템플릿 변수 치환
	 */
	private function replace_template_variables($template_content, $org_code, $org_name, $homepage_setting)
	{
		$homepage_name = isset($homepage_setting['homepage_name']) ? $homepage_setting['homepage_name'] : $org_name;
		$domain = isset($homepage_setting['homepage_domain']) ? $homepage_setting['homepage_domain'] : '';

		// 도메인에서 프로토콜 제거
		$domain = preg_replace('#^https?://#', '', $domain);
		$domain = trim($domain, '/');

		// JavaScript에 조직 코드 주입
		$org_code_script = "<script>const ORG_CODE = '{$org_code}';</script>";

		// </head> 태그 앞에 스크립트 삽입
		$template_content = str_replace('</head>', $org_code_script . "\n</head>", $template_content);

		// 템플릿 변수 치환
		$replacements = array(
			'{{homepage_name}}' => htmlspecialchars($homepage_name),
			'{{org_code}}' => htmlspecialchars($org_code),
			'{{domain}}' => htmlspecialchars($domain)
		);

		return str_replace(array_keys($replacements), array_values($replacements), $template_content);
	}
	/**
	 * Nginx 설정 테스트 및 재시작
	 */
	private function reload_nginx()
	{
		$test_output = array();
		$test_return = 0;
		@exec('sudo nginx -t 2>&1', $test_output, $test_return);

		if ($test_return !== 0) {
			log_message('error', 'Nginx 설정 테스트 실패: ' . implode("\n", $test_output));
			return array('success' => false, 'message' => 'Nginx 설정 테스트 실패: ' . implode(' ', $test_output));
		}

		$reload_output = array();
		$reload_return = 0;
		@exec('sudo systemctl reload nginx 2>&1', $reload_output, $reload_return);

		if ($reload_return !== 0) {
			log_message('error', 'Nginx 재시작 실패: ' . implode("\n", $reload_output));
			return array('success' => false, 'message' => 'Nginx 재시작 실패: ' . implode(' ', $reload_output));
		}

		log_message('info', 'Nginx 설정이 성공적으로 적용되었습니다.');
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
				log_message('info', 'Nginx 설정 파일 삭제: ' . $config_file);
				return array('success' => true, 'message' => 'Nginx 설정 파일이 삭제되었습니다.');
			} else {
				log_message('error', 'Nginx 설정 파일 삭제 실패: ' . $config_file);
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
			// 디렉토리 내 모든 파일 삭제
			$files = glob($org_dir . '/*');
			foreach ($files as $file) {
				if (is_file($file)) {
					@unlink($file);
				}
			}

			// 디렉토리 삭제
			if (@rmdir($org_dir)) {
				log_message('info', '홈페이지 디렉토리 삭제: ' . $org_dir);
				return array('success' => true, 'message' => '홈페이지 디렉토리가 삭제되었습니다.');
			} else {
				log_message('error', '홈페이지 디렉토리 삭제 실패: ' . $org_dir);
				return array('success' => false, 'message' => '홈페이지 디렉토리 삭제 실패');
			}
		}

		return array('success' => true, 'message' => '디렉토리가 존재하지 않습니다.');
	}
}
