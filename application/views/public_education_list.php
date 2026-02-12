<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>양육마당</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
	<link href="https://cdn.jsdelivr.net/npm/pretendard@1.3.9/dist/web/static/pretendard.min.css" rel="stylesheet">
    <style>
		body{font-family: "Pretendard", Pretendard, -apple-system, BlinkMacSystemFont, system-ui, Roboto, "Helvetica Neue", "Segoe UI", "Apple SD Gothic Neo", "Noto Sans KR", "Malgun Gothic", "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", sans-serif}
        .card-container {
            width: 100%;
        }
        .edu-card {
            width: calc(33.333% - 1rem);
            margin-bottom: 1.5rem;
        }
        @media (max-width: 992px) {
            .edu-card {
                width: calc(50% - 1rem);
            }
        }
        @media (max-width: 768px) {
            .edu-card {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <?php



		if (!empty($wb_edu) && !empty($wb_edu->poster_img)): ?>
            <div class="text-center mb-4">
                <img src="<?php echo $wb_edu->poster_img; ?>" class="img-fluid" alt="양육마당 포스터">
            </div>
        <?php endif; ?>
        <div class="text-center mb-4">
            <h1 class="display-5 fw-bold">양육마당</h1>
            <p class="fs-5 text-muted">양육에 참여하세요!</p>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-center justify-content-between">
                    <div class="mb-2 mb-lg-0">
                        현재 개설된 양육 <strong id="totalEduCount">0</strong>건
                    </div>
                    <!-- 검색 영역 -->
                    <div class="d-flex flex-wrap g-1">
                        <div class="me-1 mb-1">
                            <input type="date" id="search_date" class="form-control form-control-sm" style="width: 130px;">
                        </div>
                        <!-- 진행요일 Dropdown -->
                        <div class="dropdown me-1 mb-1">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="search_day_btn" data-bs-toggle="dropdown" aria-expanded="false">
                                진행요일
                            </button>
                            <ul class="dropdown-menu" id="search_day_menu" aria-labelledby="search_day_btn">
                                <li><a class="dropdown-item" href="#"><input type="checkbox" value="월요일" class="form-check-input me-2">월요일</a></li>
                                <li><a class="dropdown-item" href="#"><input type="checkbox" value="화요일" class="form-check-input me-2">화요일</a></li>
                                <li><a class="dropdown-item" href="#"><input type="checkbox" value="수요일" class="form-check-input me-2">수요일</a></li>
                                <li><a class="dropdown-item" href="#"><input type="checkbox" value="목요일" class="form-check-input me-2">목요일</a></li>
                                <li><a class="dropdown-item" href="#"><input type="checkbox" value="금요일" class="form-check-input me-2">금요일</a></li>
                                <li><a class="dropdown-item" href="#"><input type="checkbox" value="토요일" class="form-check-input me-2">토요일</a></li>
                                <li><a class="dropdown-item" href="#"><input type="checkbox" value="주일" class="form-check-input me-2">주일</a></li>
                            </ul>
                        </div>
                        <!-- 진행시간 Dropdown -->
                        <div class="dropdown me-1 mb-1">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="search_time_btn" data-bs-toggle="dropdown" aria-expanded="false">
                                진행시간
                            </button>
                            <ul class="dropdown-menu" id="search_time_menu" aria-labelledby="search_time_btn">
                                <!-- 자바스크립트로 동적 생성 -->
                            </ul>
                        </div>
                        <!-- 연령대 Dropdown -->
                        <div class="dropdown me-1 mb-1">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="search_age_btn" data-bs-toggle="dropdown" aria-expanded="false">
                                연령대
                            </button>
                            <ul class="dropdown-menu" id="search_age_menu" aria-labelledby="search_age_btn">
                                <li><a class="dropdown-item" href="#"><input type="checkbox" value="10s" class="form-check-input me-2">10대</a></li>
                                <li><a class="dropdown-item" href="#"><input type="checkbox" value="20s" class="form-check-input me-2">20대</a></li>
                                <li><a class="dropdown-item" href="#"><input type="checkbox" value="30s" class="form-check-input me-2">30대</a></li>
                                <li><a class="dropdown-item" href="#"><input type="checkbox" value="40s" class="form-check-input me-2">40대</a></li>
                                <li><a class="dropdown-item" href="#"><input type="checkbox" value="50s" class="form-check-input me-2">50대</a></li>
                                <li><a class="dropdown-item" href="#"><input type="checkbox" value="60s" class="form-check-input me-2">60대 이상</a></li>
                            </ul>
                        </div>
                        <!-- 성별 Dropdown -->
                        <div class="dropdown me-1 mb-1">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="search_gender_btn" data-bs-toggle="dropdown" aria-expanded="false">
                                성별
                            </button>
                            <ul class="dropdown-menu" id="search_gender_menu" aria-labelledby="search_gender_btn">
                                <li><a class="dropdown-item" href="#"><input type="checkbox" value="male" class="form-check-input me-2">남</a></li>
                                <li><a class="dropdown-item" href="#"><input type="checkbox" value="female" class="form-check-input me-2">여</a></li>
                            </ul>
                        </div>
                        <div class="me-1 mb-1">
                            <input type="text" id="search_keyword" class="form-control form-control-sm" placeholder="카테고리, 장소, 담당자, 양육명">
                        </div>
                        <div class="me-1 mb-1">
                            <button id="btn_search" class="btn btn-sm btn-primary">검색</button>
                        </div>
                        <div class="mb-1">
                            <button id="btn_reset" class="btn btn-sm btn-outline-secondary">초기화</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 양육 리스트 -->
        <div class="row" id="edu-list-container">
            <!-- Masonry 라이브러리로 카드 정렬 -->
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/masonry-layout@4/dist/masonry.pkgd.min.js"></script>
    <script src="/assets/js/public_education.js?<?php echo time(); ?>"></script>
</body>
</html>
