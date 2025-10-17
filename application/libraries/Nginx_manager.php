<?php
/**
 * 파일 위치: application/libraries/Nginx_manager.php
 * 역할: Nginx 설정 파일 및 홈페이지 디렉토리 관리 (PHP 7.4 기준)
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Nginx_manager
{
	private $CI;
	private $nginx_conf_dir = '/etc/nginx/conf.d/sites/';
	private $homepage_dir = '/var/www/wani/public/homepage/';
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

		// 도메인에서 프로토콜 제거
		$domain = preg_replace('#^https?://#', '', $domain);
		$domain = trim($domain, '/');

		// Nginx 설정 내용
		$config_content = $this->generate_nginx_config_content($org_code, $domain, $org_name);

		// 설정 파일 경로
		$config_file = $this->nginx_conf_dir . $org_code . '.conf';

		// 디렉토리가 없으면 생성
		if (!is_dir($this->nginx_conf_dir)) {
			if (!@mkdir($this->nginx_conf_dir, 0755, true)) {
				log_message('error', 'Nginx config directory 생성 실패: ' . $this->nginx_conf_dir);
				return array('success' => false, 'message' => 'Nginx 설정 디렉토리를 생성할 수 없습니다.');
			}
		}

		// 설정 파일 작성
		if (@file_put_contents($config_file, $config_content) === false) {
			log_message('error', 'Nginx config 파일 생성 실패: ' . $config_file);
			return array('success' => false, 'message' => 'Nginx 설정 파일을 생성할 수 없습니다. 권한을 확인하세요.');
		}

		log_message('info', 'Nginx config 파일 생성 완료: ' . $config_file);

		// Nginx 설정 테스트 및 재시작
		$reload_result = $this->reload_nginx();

		return array(
			'success' => true,
			'message' => 'Nginx 설정 파일이 생성되었습니다.',
			'config_file' => $config_file,
			'reload_result' => $reload_result
		);
	}

	/**
	 * Nginx 설정 파일 내용 생성 (PHP 7.4 기준)
	 */
	private function generate_nginx_config_content($org_code, $domain, $org_name)
	{
		$root_dir = $this->homepage_dir . $org_code;

		$config = <<<EOT
# {$org_name} ({$org_code}) - Auto Generated
server {
    listen 80;
    listen [::]:80;
    server_name {$domain} www.{$domain};

    root {$root_dir};
    index index.php index.html index.htm;

    # 로그 파일
    access_log /var/log/nginx/{$org_code}_access.log;
    error_log /var/log/nginx/{$org_code}_error.log;

    # 보안 헤더
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # 클라이언트 최대 업로드 크기
    client_max_body_size 20M;

    # PHP 처리 (PHP 7.4)
    location ~ \.php$ {
        try_files \$uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass {$this->php_fpm_socket};
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        fastcgi_param PATH_INFO \$fastcgi_path_info;
        include fastcgi_params;
        
        # PHP 타임아웃 설정
        fastcgi_read_timeout 300;
        fastcgi_connect_timeout 300;
        fastcgi_send_timeout 300;
    }

    # Static files 캐싱
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    # 숨김 파일 접근 차단
    location ~ /\. {
        deny all;
        access_log off;
        log_not_found off;
    }

    # 기본 location
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    # robots.txt 처리
    location = /robots.txt {
        access_log off;
        log_not_found off;
    }

    # favicon.ico 처리
    location = /favicon.ico {
        access_log off;
        log_not_found off;
    }
}

# HTTPS 리다이렉트 (SSL 인증서 설정 후 활성화)
# server {
#     listen 443 ssl http2;
#     listen [::]:443 ssl http2;
#     server_name {$domain} www.{$domain};
#
#     ssl_certificate /etc/letsencrypt/live/{$domain}/fullchain.pem;
#     ssl_certificate_key /etc/letsencrypt/live/{$domain}/privkey.pem;
#     ssl_protocols TLSv1.2 TLSv1.3;
#     ssl_ciphers HIGH:!aNULL:!MD5;
#     ssl_prefer_server_ciphers on;
#
#     root {$root_dir};
#     index index.php index.html index.htm;
#
#     access_log /var/log/nginx/{$org_code}_ssl_access.log;
#     error_log /var/log/nginx/{$org_code}_ssl_error.log;
#
#     client_max_body_size 20M;
#
#     location ~ \.php$ {
#         try_files \$uri =404;
#         fastcgi_split_path_info ^(.+\.php)(/.+)$;
#         fastcgi_pass {$this->php_fpm_socket};
#         fastcgi_index index.php;
#         fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
#         fastcgi_param PATH_INFO \$fastcgi_path_info;
#         include fastcgi_params;
#         fastcgi_read_timeout 300;
#         fastcgi_connect_timeout 300;
#         fastcgi_send_timeout 300;
#     }
#
#     location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
#         expires 30d;
#         add_header Cache-Control "public, immutable";
#         access_log off;
#     }
#
#     location ~ /\. {
#         deny all;
#         access_log off;
#         log_not_found off;
#     }
#
#     location / {
#         try_files \$uri \$uri/ /index.php?\$query_string;
#     }
# }

EOT;

		return $config;
	}

	/**
	 * 홈페이지 디렉토리 및 기본 index.php 생성
	 */
	public function create_homepage_directory($org_code, $org_name, $homepage_setting)
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

			// 디렉토리 소유권 설정
			@chown($org_dir, 'www-data');
			@chgrp($org_dir, 'www-data');
			@chmod($org_dir, 0755);
		}

		// 기본 index.php 생성
		$index_file = $org_dir . '/index.php';
		$index_content = $this->generate_index_php_content($org_code, $org_name, $homepage_setting);

		if (@file_put_contents($index_file, $index_content) === false) {
			log_message('error', 'index.php 파일 생성 실패: ' . $index_file);
			return array('success' => false, 'message' => 'index.php 파일을 생성할 수 없습니다.');
		}

		// 파일 권한 및 소유권 설정
		@chmod($index_file, 0644);
		@chown($index_file, 'www-data');
		@chgrp($index_file, 'www-data');

		log_message('info', '홈페이지 디렉토리 및 index.php 생성 완료: ' . $org_dir);

		return array(
			'success' => true,
			'message' => '홈페이지 디렉토리가 생성되었습니다.',
			'directory' => $org_dir,
			'index_file' => $index_file
		);
	}

	/**
	 * 기본 index.php 내용 생성
	 */
	private function generate_index_php_content($org_code, $org_name, $homepage_setting)
	{
		$homepage_name = isset($homepage_setting['homepage_name']) ? htmlspecialchars($homepage_setting['homepage_name']) : $org_name;
		$theme = isset($homepage_setting['theme']) ? $homepage_setting['theme'] : '1';
		$logo1 = isset($homepage_setting['logo1']) ? htmlspecialchars($homepage_setting['logo1']) : '';
		$logo2 = isset($homepage_setting['logo2']) ? htmlspecialchars($homepage_setting['logo2']) : '';

		$content = <<<'EOT'
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="index, follow">
    <title>%%HOMEPAGE_NAME%%</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/variable/pretendardvariable.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Pretendard Variable', -apple-system, BlinkMacSystemFont, system-ui, Roboto, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
        }
        .main-container {
            text-align: center;
            color: white;
            padding: 3rem;
            max-width: 800px;
        }
        .logo-container {
            margin-bottom: 2rem;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .logo-container img {
            max-width: 300px;
            max-height: 150px;
            background: rgba(255, 255, 255, 0.9);
            padding: 1rem;
            border-radius: 10px;
        }
        h1 {
            font-size: clamp(2rem, 5vw, 3rem);
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        .subtitle {
            font-size: clamp(1.2rem, 3vw, 1.5rem);
            opacity: 0.9;
            margin-bottom: 2rem;
        }
        .info-box {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            margin-top: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .info-box p {
            margin-bottom: 0.5rem;
        }
        hr {
            border-color: rgba(255,255,255,0.3);
            margin: 1.5rem 0;
        }
        @media (max-width: 768px) {
            .main-container {
                padding: 2rem 1rem;
            }
            .logo-container img {
                max-width: 200px;
                max-height: 100px;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
%%LOGO_SECTION%%
        
        <h1>%%HOMEPAGE_NAME%%</h1>
        <p class="subtitle">환영합니다!</p>
        
        <div class="info-box">
            <p><strong>조직 코드:</strong> %%ORG_CODE%%</p>
            <p><strong>테마:</strong> %%THEME%%</p>
            <p><strong>PHP 버전:</strong> <?php echo phpversion(); ?></p>
            <hr>
            <small style="opacity: 0.8;">이 페이지는 자동으로 생성되었습니다.<br>콘텐츠를 수정하려면 index.php 파일을 편집하세요.</small>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
EOT;

		// 로고 섹션 생성
		$logo_section = '';
		if (!empty($logo1) || !empty($logo2)) {
			$logo_section = "        <div class=\"logo-container\">\n";
			if (!empty($logo1)) {
				$logo_section .= "            <img src=\"https://wani.im{$logo1}\" alt=\"Logo 1\">\n";
			}
			if (!empty($logo2)) {
				$logo_section .= "            <img src=\"https://wani.im{$logo2}\" alt=\"Logo 2\">\n";
			}
			$logo_section .= "        </div>\n";
		}

		return str_replace(
			array('%%HOMEPAGE_NAME%%', '%%ORG_CODE%%', '%%THEME%%', '%%LOGO_SECTION%%'),
			array($homepage_name, $org_code, $theme, $logo_section),
			$content
		);
	}

	/**
	 * Nginx 설정 테스트 및 재시작
	 */
	private function reload_nginx()
	{
		// Nginx 설정 테스트
		$test_output = array();
		$test_return = 0;
		@exec('sudo nginx -t 2>&1', $test_output, $test_return);

		if ($test_return !== 0) {
			log_message('error', 'Nginx 설정 테스트 실패: ' . implode("\n", $test_output));
			return array('success' => false, 'message' => 'Nginx 설정 테스트 실패: ' . implode(' ', $test_output));
		}

		// Nginx 재시작
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
}
