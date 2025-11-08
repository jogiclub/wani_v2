/**
 * 파일 위치: assets/js/wani-latest-youtube-slide.js
 * 역할: EditorJS용 유튜브 슬라이드 커스텀 플러그인 - 타이틀, 서브타이틀 추가
 */

class WaniLatestYoutubeSlide {
	static get toolbox() {
		return {
			title: '유튜브슬라이드',
			icon: '<svg width="17" height="15" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>'
		};
	}

	constructor({data, api, config, block}) {
		this.api = api;
		this.config = config;
		this.block = block;
		this.data = {
			title: data.title || '',
			subtitle: data.subtitle || '',
			board_menu_ids: data.board_menu_ids || [],
			display_count: data.display_count || '3',
			show_board_name: data.show_board_name !== undefined ? data.show_board_name : true,
			show_title: data.show_title !== undefined ? data.show_title : true
		};
		this.wrapper = null;
		this.boardMenus = [];
	}

	render() {
		this.wrapper = document.createElement('div');
		this.wrapper.classList.add('wani-youtube-slide-wrapper');

		const container = document.createElement('div');
		container.classList.add('wani-youtube-slide-config');

		container.innerHTML = `
			<div class="card">
				<div class="card-header bg-danger bg-opacity-10">
					<small class="text-muted fw-bold">유튜브 슬라이드 설정</small>
				</div>
				<div class="card-body">
					<div class="mb-3">
						<label class="form-label small fw-bold">타이틀</label>
						<input type="text" class="form-control form-control-sm wani-youtube-title" placeholder="타이틀 입력 (선택사항)" value="${this.data.title}">
					</div>
					<div class="mb-3">
						<label class="form-label small fw-bold">서브타이틀</label>
						<textarea class="form-control form-control-sm wani-youtube-subtitle" rows="2" placeholder="서브타이틀 입력 (선택사항)">${this.data.subtitle}</textarea>
					</div>
					<div class="mb-3">
						<label class="form-label small fw-bold">게시판 선택</label>
						<div class="youtube-board-list" style="max-height: 200px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 4px; padding: 10px;">
							<div class="text-center text-muted small">
								<div class="spinner-border spinner-border-sm" role="status">
									<span class="visually-hidden">Loading...</span>
								</div>
								<div>게시판 목록 로딩중...</div>
							</div>
						</div>
					</div>
					<div class="mb-3">
						<label class="form-label small">표시 갯수</label>
						<select class="form-select form-select-sm wani-youtube-display-count">
							<option value="3">3개</option>
							<option value="4">4개</option>
							<option value="5">5개</option>
						</select>
					</div>
					<div class="mb-2">
						<div class="form-check">
							<input class="form-check-input wani-youtube-show-board-name" type="checkbox" id="showBoardName_${Date.now()}">
							<label class="form-check-label small" for="showBoardName_${Date.now()}">
								게시판명 표시
							</label>
						</div>
					</div>
					<div class="mb-2">
						<div class="form-check">
							<input class="form-check-input wani-youtube-show-title" type="checkbox" id="showTitle_${Date.now()}">
							<label class="form-check-label small" for="showTitle_${Date.now()}">
								타이틀 표시
							</label>
						</div>
					</div>
				</div>
			</div>
			<div class="mt-3 p-3 bg-light rounded">
				<small class="text-muted">
					<i class="bi bi-info-circle"></i> 선택한 게시판의 유튜브 동영상이 슬라이드 형태로 표시됩니다.
					(게시글에 유튜브 URL이 있어야 표시됩니다)
				</small>
			</div>
		`;

		this.wrapper.appendChild(container);

		const titleInput = container.querySelector('.wani-youtube-title');
		const subtitleTextarea = container.querySelector('.wani-youtube-subtitle');
		const boardListContainer = container.querySelector('.youtube-board-list');
		const displayCountSelect = container.querySelector('.wani-youtube-display-count');
		const showBoardNameCheckbox = container.querySelector('.wani-youtube-show-board-name');
		const showTitleCheckbox = container.querySelector('.wani-youtube-show-title');

		this.loadBoardMenus().then(() => {
			this.renderBoardCheckboxes(boardListContainer);
		});

		displayCountSelect.value = this.data.display_count;
		showBoardNameCheckbox.checked = this.data.show_board_name;
		showTitleCheckbox.checked = this.data.show_title;

		titleInput.addEventListener('input', (e) => {
			this.data.title = e.target.value;
		});

		subtitleTextarea.addEventListener('input', (e) => {
			this.data.subtitle = e.target.value;
		});

		displayCountSelect.addEventListener('change', (e) => {
			this.data.display_count = e.target.value;
		});

		showBoardNameCheckbox.addEventListener('change', (e) => {
			this.data.show_board_name = e.target.checked;
		});

		showTitleCheckbox.addEventListener('change', (e) => {
			this.data.show_title = e.target.checked;
		});

		return this.wrapper;
	}

	renderBoardCheckboxes(container) {
		if (this.boardMenus.length === 0) {
			container.innerHTML = '<div class="text-center text-muted small">게시판이 없습니다.</div>';
			return;
		}

		container.innerHTML = '';

		this.boardMenus.forEach(menu => {
			const checkboxWrapper = document.createElement('div');
			checkboxWrapper.classList.add('form-check', 'mb-2');

			const checkbox = document.createElement('input');
			checkbox.type = 'checkbox';
			checkbox.classList.add('form-check-input', 'youtube-board-checkbox');
			checkbox.value = menu.id;
			checkbox.id = `youtube_board_${menu.id}`;

			if (this.data.board_menu_ids.includes(menu.id)) {
				checkbox.checked = true;
			}

			checkbox.addEventListener('change', (e) => {
				if (e.target.checked) {
					if (!this.data.board_menu_ids.includes(menu.id)) {
						this.data.board_menu_ids.push(menu.id);
					}
				} else {
					const index = this.data.board_menu_ids.indexOf(menu.id);
					if (index > -1) {
						this.data.board_menu_ids.splice(index, 1);
					}
				}
			});

			const label = document.createElement('label');
			label.classList.add('form-check-label', 'small');
			label.htmlFor = checkbox.id;
			label.textContent = menu.name;

			checkboxWrapper.appendChild(checkbox);
			checkboxWrapper.appendChild(label);
			container.appendChild(checkboxWrapper);
		});
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
			title: this.data.title,
			subtitle: this.data.subtitle,
			board_menu_ids: this.data.board_menu_ids,
			display_count: this.data.display_count,
			show_board_name: this.data.show_board_name,
			show_title: this.data.show_title
		};
	}

	validate(savedData) {
		if (!savedData.board_menu_ids || savedData.board_menu_ids.length === 0) {
			return false;
		}
		return true;
	}
}

if (typeof window !== 'undefined') {
	window.WaniLatestYoutubeSlide = WaniLatestYoutubeSlide;
}
