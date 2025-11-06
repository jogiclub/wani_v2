<?php
/**
 * 파일 위치: application/libraries/Nginx_manager.php
 * 역할: Nginx 설정 파일 및 홈페이지 디렉토리 관리
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
		if (empty($org_code)) {
			return array('success' => false, 'message' => '조직 코드가 비어있습니다.');
		}

		// domain이 비어있어도 기본 도메인({조직코드}.wani.im)으로 생성
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
	 * 파일 위치: application/libraries/Nginx_manager.php
	 * 역할: generate_nginx_config_content 메소드의 라우팅 규칙 수정
	 */

	private function generate_nginx_config_content($org_code, $domain, $org_name)
	{
		$root_dir = $this->homepage_dir . $org_code;

		// 기본 도메인: {조직코드}.wani.im
		$default_domain = $org_code . '.wani.im';

		// 사용자 설정 도메인이 있는 경우 server_name에 포함
		$server_names = $default_domain;
		if (!empty($domain)) {
			$domain = preg_replace('#^https?://#', '', $domain);
			$domain = trim($domain, '/');
			$server_names .= ' ' . $domain . ' www.' . $domain;
		}

		// SSL 인증서 체크 (기본 도메인 또는 사용자 도메인)
		$ssl_cert = '/etc/letsencrypt/live/' . $default_domain . '/fullchain.pem';
		$ssl_key = '/etc/letsencrypt/live/' . $default_domain . '/privkey.pem';

		// 기본 도메인 인증서가 없으면 사용자 도메인 인증서 확인
		if (!file_exists($ssl_cert) && !empty($domain)) {
			$ssl_cert = '/etc/letsencrypt/live/' . $domain . '/fullchain.pem';
			$ssl_key = '/etc/letsencrypt/live/' . $domain . '/privkey.pem';
		}

		$has_ssl = file_exists($ssl_cert) && file_exists($ssl_key);

		if ($has_ssl) {
			$config = <<<EOT
# {$org_name} ({$org_code})

# HTTP to HTTPS redirect
server {
    listen 80;
    listen [::]:80;
    server_name {$server_names};
    return 301 https://\$host\$request_uri;
}

# HTTPS server
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name {$server_names};

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

    # 페이지, 게시판 라우팅 - 모두 index.html로
    location ~ ^/(page|board)/ {
        try_files \$uri /index.html;
    }

    location / {
        try_files \$uri \$uri/ /index.html;
    }
    
    location ~ /\. {
        deny all;
    }
}
EOT;
		} else {
			$config = <<<EOT
# {$org_name} ({$org_code})

server {
    listen 80;
    listen [::]:80;
    server_name {$server_names};

    root {$root_dir};
    index index.html;

    access_log /var/log/nginx/{$org_code}_access.log;
    error_log /var/log/nginx/{$org_code}_error.log;

    client_max_body_size 20M;

    # 페이지, 게시판 라우팅 - 모두 index.html로
    location ~ ^/(page|board)/ {
        try_files \$uri /index.html;
    }

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
				log_message('error', '홈페이지 디렉토리 생성 실패: ' . $org_dir);
				return array('success' => false, 'message' => '홈페이지 디렉토리를 생성할 수 없습니다.');
			}
			@chown($org_dir, 'www-data');
			@chgrp($org_dir, 'www-data');
			@chmod($org_dir, 0755);
		}

		// 테마 번호 가져오기 (기본값: 1)
		$theme = isset($homepage_setting['theme']) ? $homepage_setting['theme'] : '1';

		// 테마 파일 경로
		$theme_file = $this->theme_dir . $theme . '/main.html';

		if (!file_exists($theme_file)) {
			log_message('error', '테마 파일을 찾을 수 없음: ' . $theme_file);
			return array('success' => false, 'message' => '테마 파일을 찾을 수 없습니다: theme ' . $theme);
		}

		// 테마 HTML 읽기
		$template_content = file_get_contents($theme_file);

		// 템플릿 변수 치환
		$index_content = $this->replace_template_variables($template_content, $org_code, $org_name, $homepage_setting);

		// index.html 저장
		$index_file = $org_dir . '/index.html';

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
		$theme = isset($homepage_setting['theme']) ? $homepage_setting['theme'] : '1';

		// 캐시 버스팅용 타임스탬프
		$timestamp = date('YmdHis');

		// ORG_CODE를 JavaScript 변수로 <head> 맨 위에 주입
		$org_code_script = "\n<script>const ORG_CODE = '{$org_code}';</script>";
		$template_content = str_replace('<head>', '<head>' . $org_code_script, $template_content);

		// 템플릿 변수 치환
		$replacements = array(
			'{{homepage_name}}' => htmlspecialchars($homepage_name, ENT_QUOTES, 'UTF-8'),
			'{{org_code}}' => htmlspecialchars($org_code, ENT_QUOTES, 'UTF-8'),
			'{{theme}}' => htmlspecialchars($theme, ENT_QUOTES, 'UTF-8'),
			'{{timestamp}}' => $timestamp
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
			return array('success' => false, 'message' => 'Nginx 설정 테스트 실패');
		}

		$reload_output = array();
		$reload_return = 0;
		@exec('sudo systemctl reload nginx 2>&1', $reload_output, $reload_return);

		if ($reload_return !== 0) {
			log_message('error', 'Nginx 재시작 실패: ' . implode("\n", $reload_output));
			return array('success' => false, 'message' => 'Nginx 재시작 실패');
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
			$files = glob($org_dir . '/*');
			foreach ($files as $file) {
				if (is_file($file)) {
					@unlink($file);
				}
			}

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
