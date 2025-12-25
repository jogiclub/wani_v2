/**
 * 파일 위치: assets/js/wani-latest-list.js
 * 역할: EditorJS용 좌우 최신 게시글 커스텀 플러그인
 */

class WaniLatestList {
	static get toolbox() {
		return {
			title: '좌/우 최신 게시글',
			icon: '<svg width="17" height="15" viewBox="0 0 336 276" xmlns="http://www.w3.org/2000/svg"><path d="M291 150V79c0-19-15-34-34-34H79c-19 0-34 15-34 34v42l67-44 81 72 56-29 42 30zm0 52l-43-30-56 30-81-67-66 39v23c0 19 15 34 34 34h178c17 0 31-13 34-29zM79 0h178c44 0 79 35 79 79v118c0 44-35 79-79 79H79c-44 0-79-35-79-79V79C0 35 35 0 79 0z"/></svg>'
		};
	}

	constructor({data, api, config, block}) {
		this.api = api;
		this.config = config;
		this.block = block;
		this.data = {
			boards: data.boards || [
				{ menu_id: '', limit: 5 },
				{ menu_id: '', limit: 5 }
			]
		};
		this.wrapper = null;
		this.boardMenus = [];
	}

	render() {
		this.wrapper = document.createElement('div');
		this.wrapper.classList.add('wani-latest-list-wrapper');

		const container = document.createElement('div');
		container.classList.add('wani-latest-list-config');

		// 2개의 게시판 선택 영역
		container.innerHTML = `
            <div class="card mb-5">
                <div class="card-header">
                    좌/우 최신 게시글
                    <i class="bi bi-info-circle-fill text-info ms-2" 
                       data-bs-toggle="tooltip" 
                       data-bs-html="true" 
                       data-bs-placement="right"
                       data-bs-custom-class="custom-tooltip"
                       data-bs-container="body"           
                       data-bs-title="<div class='text-start'><small>좌/우에 각각 다른 게시판의 최신 게시글을 가져옵니다.</small></div>"
                       style="cursor: pointer;">
                    </i>
                </div>                        
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="mb-2">
                                <select class="form-select form-select-sm wani-latest-list-menu" data-index="0">
                                    <option value="">게시판 선택</option>
                                </select>
                            </div>
                            <div>
                                <select class="form-select form-select-sm wani-latest-list-limit" data-index="0">
                                    <option value="3">3개</option>
                                    <option value="5">5개</option>
                                    <option value="10">10개</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-6">
                            <div class="mb-2">
                                <select class="form-select form-select-sm wani-latest-list-menu" data-index="1">
                                    <option value="">게시판 선택</option>
                                </select>
                            </div>
                            <div>
                                <select class="form-select form-select-sm wani-latest-list-limit" data-index="1">
                                    <option value="3">3개</option>
                                    <option value="5">5개</option>
                                    <option value="10">10개</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>                    
            </div>
        `;

		this.wrapper.appendChild(container);

		// 게시판 메뉴 목록 로드
		const menuSelects = container.querySelectorAll('.wani-latest-list-menu');
		const limitSelects = container.querySelectorAll('.wani-latest-list-limit');

		this.loadBoardMenus().then(() => {
			// 게시판 메뉴 옵션 추가
			menuSelects.forEach(select => {
				this.boardMenus.forEach(menu => {
					const option = document.createElement('option');
					option.value = menu.id;
					option.textContent = menu.name;
					select.appendChild(option);
				});

				const index = parseInt(select.dataset.index);
				if (this.data.boards[index] && this.data.boards[index].menu_id) {
					select.value = this.data.boards[index].menu_id;
				}
			});

			// 툴팁 초기화
			const tooltipTriggerList = container.querySelectorAll('[data-bs-toggle="tooltip"]');
			tooltipTriggerList.forEach(tooltipTriggerEl => {
				new bootstrap.Tooltip(tooltipTriggerEl);
			});
		});

		// 저장된 limit 값 설정
		limitSelects.forEach(select => {
			const index = parseInt(select.dataset.index);
			if (this.data.boards[index]) {
				select.value = this.data.boards[index].limit;
			}
		});

		// 이벤트 리스너
		menuSelects.forEach(select => {
			select.addEventListener('change', (e) => {
				const index = parseInt(e.target.dataset.index);
				this.data.boards[index].menu_id = e.target.value;
			});
		});

		limitSelects.forEach(select => {
			select.addEventListener('change', (e) => {
				const index = parseInt(e.target.dataset.index);
				this.data.boards[index].limit = parseInt(e.target.value);
			});
		});

		return this.wrapper;
	}

	loadBoardMenus() {
		return new Promise((resolve, reject) => {
			const orgId = document.getElementById('current_org_id')?.value;
			if (!orgId) {
				resolve();
				return;
			}

			$.ajax({
				url: '/homepage_menu/get_board_menus',
				method: 'POST',
				data: { org_id: orgId },
				dataType: 'json',
				success: (response) => {
					if (response.success && response.data) {
						this.boardMenus = response.data;
					}
					resolve();
				},
				error: () => {
					resolve();
				}
			});
		});
	}

	save() {
		return {
			boards: this.data.boards
		};
	}

	validate(savedData) {
		// 최소 1개 이상의 게시판이 선택되어야 함
		if (!savedData.boards || savedData.boards.length === 0) {
			return false;
		}

		const hasValidBoard = savedData.boards.some(board => board.menu_id);
		return hasValidBoard;
	}

	static get sanitize() {
		return {
			boards: {}
		};
	}
}

if (typeof window !== 'undefined') {
	window.WaniLatestList = WaniLatestList;
}
