<?php
// 데이터베이스 연결 설정
$db_host = 'localhost';
$db_user = 'root';
$db_pass = 'wkssm1125@';
$db_name = 'wani_db';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
$conn->set_charset('utf8mb4');

if ($conn->connect_error) {
    die('데이터베이스 연결 실패: ' . $conn->connect_error);
}

// 조직 ID 및 코드
$org_id = 3;
$org_code = '52ch_area0101';

// 메뉴 데이터 가져오기
$menu_query = "SELECT homepage_menu FROM wb_org WHERE org_id = ?";
$stmt = $conn->prepare($menu_query);
$stmt->bind_param('i', $org_id);
$stmt->execute();
$menu_result = $stmt->get_result();
$menu_row = $menu_result->fetch_assoc();
$menu_data = json_decode($menu_row['homepage_menu'] ?? '[]', true);
$stmt->close();

// 메인 페이지 내용 가져오기
$page_query = "SELECT page_content FROM wb_homepage_page WHERE org_id = ? AND menu_id = 'main'";
$stmt = $conn->prepare($page_query);
$stmt->bind_param('i', $org_id);
$stmt->execute();
$page_result = $stmt->get_result();
$page_row = $page_result->fetch_assoc();
$main_content = $page_row['page_content'] ?? '<div class="text-center py-5"><p class="text-muted">메인 페이지 내용을 설정해주세요.</p></div>';
$stmt->close();

$conn->close();

// 메뉴 HTML 생성 함수
function generateMenuHtml($menus, $parent_id = null) {
    $html = '';
    foreach ($menus as $menu) {
        if (($parent_id === null && empty($menu['parent_id'])) || 
            ($parent_id !== null && isset($menu['parent_id']) && $menu['parent_id'] === $parent_id)) {
            
            $hasChildren = false;
            foreach ($menus as $child) {
                if (isset($child['parent_id']) && $child['parent_id'] === $menu['id']) {
                    $hasChildren = true;
                    break;
                }
            }
            
            if ($hasChildren) {
                $html .= '<li class="nav-item dropdown">';
                $html .= '<a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">';
                $html .= htmlspecialchars($menu['name']);
                $html .= '</a>';
                $html .= '<ul class="dropdown-menu">';
                foreach ($menus as $child) {
                    if (isset($child['parent_id']) && $child['parent_id'] === $menu['id']) {
                        $html .= '<li><a class="dropdown-item" href="#">' . htmlspecialchars($child['name']) . '</a></li>';
                    }
                }
                $html .= '</ul></li>';
            } else {
                $html .= '<li class="nav-item">';
                $html .= '<a class="nav-link" href="#">' . htmlspecialchars($menu['name']) . '</a>';
                $html .= '</li>';
            }
        }
    }
    return $html;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="index, follow">
    <title>오병이어교회 1교구</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/variable/pretendardvariable.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Pretendard Variable', -apple-system, BlinkMacSystemFont, system-ui, Roboto, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
        }
        
        .logo-container {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .logo-container img {
            max-height: 50px;
            max-width: 150px;
            object-fit: contain;
        }
        
        .nav-link {
            color: rgba(255,255,255,0.9) !important;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .nav-link:hover {
            color: white !important;
        }
        
        .dropdown-menu {
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        main {
            flex: 1;
            padding: 3rem 0;
            background: #f8f9fa;
        }
        
        footer {
            background: #343a40;
            color: white;
            padding: 2rem 0;
            margin-top: auto;
        }
        
        footer a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
        }
        
        footer a:hover {
            color: white;
        }
    </style>
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark">
            <div class="container">
                <div class="logo-container">
                    <img src="https://wani.im/uploads/homepage_logos/logo_3_logo1_1760679702.png" alt="Logo 1">

                    <a class="navbar-brand" href="/">오병이어교회 1교구</a>
                </div>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <?php echo generateMenuHtml($menu_data); ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <main>
        <div class="container">
            <?php echo $main_content; ?>
        </div>
    </main>

    <footer>
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>오병이어교회 1교구</h5>
                    <p class="mb-0">조직 코드: 52ch_area0101</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> 오병이어교회 1교구. All rights reserved.</p>
                    <small class="text-muted">Powered by 왔니</small>
                </div>
            </div>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>