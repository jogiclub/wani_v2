/**
 * 파일 위치: assets/js/homepage/wani-preach.js
 * 역할: Editor.js용 게시판 블록 커스텀 플러그인 (관리자 페이지용)
 */

class WaniPreach {
	static get toolbox() {
		return {
			title: '게시판',
			icon: '<svg width="17" height="15" viewBox="0 0 336 276" xmlns="http://www.w3.org/2000/svg"><path d="M291 150V79c0-19-15-34-34-34H79c-19 0-34 15-34 34v42l67-44 81 72 56-29 42 30zm0 52l-43-30-56 30-81-67-66 39v23c0 19 15 34 34 34h178c17 0 31-13 34-29zM79 0h178c44 0 79 35 79 79v118c0 44-35 79-79 79H79c-44 0-79-35-79-79V79C0 35 35 0 79 0z"/></svg>'
		};
	}

	constructor({data, api}) {
		this.api = api;
		this.data = {
			menu_id: data.menu_id || '',
			limit: data.limit || 5,
			board_list: data.board_list || []
		};
		this.wrapper = null;
		this.orgId = $('#current_org_id').val();
	}

	render() {
		this.wrapper = document.createElement('div');
		this.wrapper.classList.add('wani-preach-block');

		const selectContainer = document.createElement('div');
		selectContainer.classList.add('mb-3', 'p-3', 'border', 'rounded', 'bg-light');
		selectContainer.innerHTML = `
			<div class="row g-2 align-items-center">
				<div class="col-md-6">
					<label class="form-label mb-1 small">게시판 선택</label>
					<select class="form-select form-select-sm wani-preach-menu-select">
						<option value="">게시판을 선택하세요</option>
					</select>
				</div>
				<div class="col-md-3">
					<label class="form-label mb-1 small">표시 게시물 수</label>
					<select class="form-select form-select-sm wani-preach-limit-select">
						<option value="3">3개</option>
						<option value="5">5개</option>
						<option value="10">10개</option>
					</select>
				</div>
				<div class="col-md-3">
					<label class="form-label mb-1 small">&nbsp;</label>
					<button type="button" class="btn btn-sm btn-primary w-100 wani-preach-load-btn">
						<i class="bi bi-arrow-clockwise"></i> 불러오기
					</button>
				</div>
			</div>
		`;

		const previewContainer = document.createElement('div');
		previewContainer.classList.add('wani-preach-preview', 'mt-3');

		this.wrapper.appendChild(selectContainer);
		this.wrapper.appendChild(previewContainer);

		this.loadBoardMenus();
		this.attachEvents();

		if (this.data.menu_id) {
			setTimeout(() => {
				const menuSelect = this.wrapper.querySelector('.wani-preach-menu-select');
				const limitSelect = this.wrapper.querySelector('.wani-preach-limit-select');
				menuSelect.value = this.data.menu_id;
				limitSelect.value = this.data.limit;
				this.renderPreview();
			}, 500);
		}

		return this.wrapper;
	}

	loadBoardMenus() {
		const menuSelect = this.wrapper.querySelector('.wani-preach-menu-select');

		$.ajax({
			url: '/homepage_menu/get_board_menus',
			type: 'POST',
			dataType: 'json',
			data: { org_id: this.orgId },
			success: (response) => {
				if (response.success && response.data) {
					response.data.forEach(menu => {
						const option = document.createElement('option');
						option.value = menu.id;
						option.textContent = menu.name;
						menuSelect.appendChild(option);
					});
				}
			},
			error: () => {
				console.error('게시판 메뉴 로드 실패');
			}
		});
	}

	attachEvents() {
		const loadBtn = this.wrapper.querySelector('.wani-preach-load-btn');
		loadBtn.addEventListener('click', () => this.loadBoardData());
	}

	loadBoardData() {
		const menuSelect = this.wrapper.querySelector('.wani-preach-menu-select');
		const limitSelect = this.wrapper.querySelector('.wani-preach-limit-select');
		const menuId = menuSelect.value;
		const limit = limitSelect.value;

		if (!menuId) {
			showToast('게시판을 선택해주세요.');
			return;
		}

		$.ajax({
			url: '/homepage_menu/get_board_list',
			type: 'POST',
			dataType: 'json',
			data: {
				org_id: this.orgId,
				menu_id: menuId,
				search_keyword: '',
				page: 1
			},
			success: (response) => {
				if (response.success) {
					this.data.menu_id = menuId;
					this.data.limit = parseInt(limit);
					this.data.board_list = response.data.slice(0, this.data.limit);
					this.renderPreview();
					showToast('게시판이 로드되었습니다.');
				}
			},
			error: () => {
				showToast('게시판 로드에 실패했습니다.');
			}
		});
	}

	renderPreview() {
		const previewContainer = this.wrapper.querySelector('.wani-preach-preview');
		const menuSelect = this.wrapper.querySelector('.wani-preach-menu-select');
		const selectedMenuName = menuSelect.options[menuSelect.selectedIndex]?.text || '게시판';

		if (!this.data.board_list || this.data.board_list.length === 0) {
			previewContainer.innerHTML = '<div class="text-center text-muted py-3">게시판을 선택하고 불러오기 버튼을 클릭하세요.</div>';
			return;
		}

		let html = `
			<div class="card">
				<div class="card-header d-flex justify-content-between align-items-center bg-white py-2">
					<h6 class="mb-0 fw-bold">${this.escapeHtml(selectedMenuName)}</h6>
					<a href="#" class="text-primary text-decoration-none small" onclick="return false;">
						<i class="bi bi-plus-circle"></i> 더보기
					</a>
				</div>
				<ul class="list-group list-group-flush">
		`;

		this.data.board_list.forEach(board => {
			const date = this.formatDate(board.reg_date);
			html += `
				<li class="list-group-item d-flex justify-content-between align-items-center py-2">
					<span class="text-truncate me-2">${this.escapeHtml(board.board_title)}</span>
					<small class="text-muted text-nowrap">${date}</small>
				</li>
			`;
		});

		html += `
				</ul>
			</div>
		`;

		previewContainer.innerHTML = html;
	}

	save() {
		return {
			menu_id: this.data.menu_id,
			limit: this.data.limit,
			board_list: this.data.board_list
		};
	}

	formatDate(dateString) {
		if (!dateString) return '';
		const date = new Date(dateString);
		const year = date.getFullYear();
		const month = String(date.getMonth() + 1).padStart(2, '0');
		const day = String(date.getDate()).padStart(2, '0');
		return `${year}-${month}-${day}`;
	}

	escapeHtml(text) {
		if (!text) return '';
		const map = {
			'&': '&amp;',
			'<': '&lt;',
			'>': '&gt;',
			'"': '&quot;',
			"'": '&#039;'
		};
		return text.replace(/[&<>"']/g, m => map[m]);
	}

	static get isReadOnlySupported() {
		return true;
	}
}

if (typeof window !== 'undefined') {
	window.WaniPreach = WaniPreach;
}
