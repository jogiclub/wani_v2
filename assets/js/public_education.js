'use strict';

$(document).ready(function() {
    let masonry;
    let searchParams = {
        date: '',
        days: [],
        times: [],
        ages: [],
        genders: [],
        keyword: ''
    };

    function initializePage() {
        initSearchControls();
        loadEduList();
        loadTotalCount();
    }

    function initSearchControls() {
        $('#btn_search').on('click', function() {
            searchParams.date = $('#search_date').val();
            searchParams.keyword = $('#search_keyword').val();
            loadEduList();
        });

        $('#btn_reset').on('click', resetSearch);

        $('#search_keyword, #search_date').on('keypress', function(e) {
            if (e.which === 13) {
                $('#btn_search').trigger('click');
            }
        });

        setupMultiSelectDropdown('day', searchParams.days, '진행요일');
        setupMultiSelectDropdown('time', searchParams.times, '진행시간');
        setupMultiSelectDropdown('age', searchParams.ages, '연령대');
        setupMultiSelectDropdown('gender', searchParams.genders, '성별');

        loadDistinctEduTimes();
    }

    function loadDistinctEduTimes() {
        $.ajax({
            url: '/public_education/get_distinct_edu_times',
            type: 'POST',
            dataType: 'json',
            success: function(res) {
                if (res.success && res.data) {
                    const $menu = $('#search_time_menu');
                    $menu.empty();
                    res.data.forEach(function(time) {
                        const $li = $('<li><a class="dropdown-item" href="#"><input type="checkbox" value="' + escapeHtml(time) + '" class="form-check-input me-2">' + escapeHtml(time) + '</a></li>');
                        $menu.append($li);
                    });
                }
            }
        });
    }

    function setupMultiSelectDropdown(type, targetArray, defaultBtnText) {
        const menuId = `#search_${type}_menu`;
        const btnId = `#search_${type}_btn`;

        $(document).on('change', `${menuId} input[type="checkbox"]`, function() {
            const value = $(this).val();
            if ($(this).is(':checked')) {
                if (!targetArray.includes(value)) {
                    targetArray.push(value);
                }
            } else {
                const index = targetArray.indexOf(value);
                if (index > -1) {
                    targetArray.splice(index, 1);
                }
            }
            updateMultiSelectButtonText($(btnId), targetArray, defaultBtnText);
            $('#btn_search').trigger('click');
        });

        $(document).on('click', menuId, function(e) {
            e.stopPropagation();
        });
    }

    function updateMultiSelectButtonText($btn, arr, defaultText) {
        if (arr.length === 0) {
            $btn.text(defaultText);
        } else if (arr.length === 1) {
            $btn.text(arr[0]);
        } else {
            $btn.text(`${defaultText} (${arr.length}개)`);
        }
    }

    function resetSearch() {
        searchParams = { date: '', days: [], times: [], ages: [], genders: [], keyword: '' };
        $('#search_date').val('');
        $('#search_keyword').val('');
        $('.dropdown-menu input[type="checkbox"]').prop('checked', false);
        updateMultiSelectButtonText($('#search_day_btn'), searchParams.days, '진행요일');
        updateMultiSelectButtonText($('#search_time_btn'), searchParams.times, '진행시간');
        updateMultiSelectButtonText($('#search_age_btn'), searchParams.ages, '연령대');
        updateMultiSelectButtonText($('#search_gender_btn'), searchParams.genders, '성별');
        loadEduList();
    }

    function loadTotalCount() {
        $.ajax({
            url: '/public_education/get_total_public_edu_count',
            type: 'POST',
            dataType: 'json',
            success: function(res) {
                $('#totalEduCount').text(res.total_count || 0);
            }
        });
    }

    function loadEduList() {
        $.ajax({
            url: '/public_education/get_edu_list',
            type: 'POST',
            data: searchParams,
            dataType: 'json',
            success: function(res) {
                renderEduCards(res.data || []);
            }
        });
    }

    function renderEduCards(data) {
        const container = $('#edu-list-container');
        container.empty();

        if (data.length === 0) {
            container.html('<div class="col-12 text-center"><p class="text-muted">해당 조건의 양육이 없습니다.</p></div>');
            return;
        }

        data.forEach(function(edu) {
            const days = edu.edu_days ? JSON.parse(edu.edu_days).join(', ') : '미정';
            const times = edu.edu_times ? JSON.parse(edu.edu_times).join(', ') : '미정';
            const posterImage = edu.poster_img ? `<img src="${escapeHtml(edu.poster_img)}" class="card-img-top" alt="포스터">` : '';
            
            let buttonHtml = '';
            if (edu.access_code) {
                const applyUrl = `/education/apply/${edu.org_id}/${edu.edu_idx}/${edu.access_code}`;
                buttonHtml = `<a href="${applyUrl}" class="btn btn-primary btn-sm">신청하기</a>`;
            } else {
                buttonHtml = `<a href="/education/detail/${edu.edu_idx}" class="btn btn-secondary btn-sm">상세보기</a>`;
            }

            const card = `
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100">
                        ${posterImage}
                        <div class="card-body">
                            <h5 class="card-title">${escapeHtml(edu.edu_name)}</h5>
                            <h6 class="card-subtitle mb-2 text-muted">${escapeHtml(edu.org_name)}</h6>
                            <p class="card-text">
                                <span class="badge bg-primary me-1">${escapeHtml(edu.category_name || '미분류')}</span>
                            </p>
                            <ul class="list-unstyled mb-0">
                                <li><i class="bi bi-calendar-check me-2"></i>${edu.edu_start_date} ~ ${edu.edu_end_date}</li>
                                <li><i class="bi bi-clock me-2"></i>${days} ${times}</li>
                                <li><i class="bi bi-geo-alt me-2"></i>${escapeHtml(edu.edu_location)}</li>
                                <li><i class="bi bi-person me-2"></i>${escapeHtml(edu.edu_leader)}</li>
                            </ul>
                        </div>
                        <div class="card-footer text-end">
                            ${buttonHtml}
                        </div>
                    </div>
                </div>
            `;
            container.append(card);
        });

        if (masonry) {
            masonry.destroy();
        }
        masonry = new Masonry(container[0], {
            itemSelector: '.col-lg-4',
            percentPosition: true
        });
    }

    function escapeHtml(text) {
        if (!text) return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return text.toString().replace(/[&<>"']/g, m => map[m]);
    }

    initializePage();
});
