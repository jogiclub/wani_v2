/**
 * 파일 위치: assets/js/wani-link-list-bg.js
 * 역할: EditorJS용 백그라운드 링크 섹션 커스텀 플러그인
 */

class WaniLinkListBg {
	static get toolbox() {
		return {
			title: '백그라운드 이미지 주요 링크',
			icon: '<svg width="17" height="15" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M4 4h16v16H4V4zm2 2v12h12V6H6zm2 8h8v2H8v-2z"/></svg>'
		};
	}

	constructor({ data, api, config }) {
		this.api = api;
		this.data = data || {};
		this.config = config || {};
		this.wrapper = null;

		if (!this.data.title) {
			this.data.title = '';
		}
		if (!this.data.subtitle) {
			this.data.subtitle = '';
		}
		if (!this.data.backgroundImage) {
			this.data.backgroundImage = '';
		}
		if (!this.data.buttons || !Array.isArray(this.data.buttons)) {
			this.data.buttons = [this.createEmptyButton()];
		}
	}

	createEmptyButton() {
		return {
			id: 'btn_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9),
			name: '',
			url: ''
		};
	}

	render() {
		this.wrapper = document.createElement('div');
		this.wrapper.classList.add('card', 'mb-5');

		// 1. 카드 헤더 생성 (Flexbox 설정)
		const cardsTitle = document.createElement('div');
		cardsTitle.classList.add('card-header', 'd-flex', 'align-items-center');

		// 2. 타이틀 및 아이콘 설정 (왼쪽 영역)
		cardsTitle.innerHTML = `
        <span>백그라운드 이미지 주요 링크</span>
        <i class="bi bi-info-circle-fill text-info ms-2" 
           data-bs-toggle="tooltip" 
           data-bs-html="true" 
           data-bs-placement="right"
           data-bs-custom-class="custom-tooltip"
           data-bs-container="body"           
           data-bs-title="<div class='text-start'><img src='/assets/images/homepage_bg_image.png' width='100%' style='margin-bottom:5px;'><small>백그라운드 이미지가 있는 소개 및 링크가 가능합니다.</small></div>"
           style="cursor: pointer;">
        </i>
    `;

		this.wrapper.appendChild(cardsTitle);



		// 카드 컨테이너
		const cardsContainer = document.createElement('div');
		cardsContainer.classList.add('card-body');



		// 타이틀 입력
		const titleInput = document.createElement('input');
		titleInput.type = 'text';
		titleInput.classList.add('form-control', 'mb-3');
		titleInput.placeholder = '타이틀 입력';
		titleInput.value = this.data.title || '';
		titleInput.oninput = () => {
			this.data.title = titleInput.value;
		};




		// 서브타이틀 입력
		const subtitleInput = document.createElement('textarea');
		subtitleInput.classList.add('form-control', 'mb-3');
		subtitleInput.placeholder = '서브타이틀 입력 (Enter로 줄바꿈 가능)';
		subtitleInput.value = this.data.subtitle || '';
		subtitleInput.rows = 2;
		subtitleInput.oninput = () => {
			this.data.subtitle = subtitleInput.value;
		};

		// 백그라운드 이미지 영역
		const bgImageSection = this.createBackgroundImageSection();

		// 버튼 컨테이너
		const buttonsContainer = document.createElement('div');
		buttonsContainer.classList.add('wani-buttons-container');
		buttonsContainer.style.marginTop = '15px';
		buttonsContainer.style.display = 'flex';
		buttonsContainer.style.flexDirection = 'column';
		buttonsContainer.style.gap = '10px';

		this.data.buttons.forEach((buttonData, index) => {
			const buttonCard = this.createButtonCard(buttonData, index, buttonsContainer);
			buttonsContainer.appendChild(buttonCard);
		});

		// 버튼 추가
		const addButton = document.createElement('button');
		addButton.type = 'button';
		addButton.classList.add('btn', 'btn-sm', 'btn-outline-primary', 'ms-auto');
		addButton.innerHTML = '<i class="bi bi-plus-lg"></i> 버튼 추가';
		addButton.onclick = () => this.addButton(buttonsContainer);

		cardsContainer.appendChild(titleInput);
		cardsContainer.appendChild(subtitleInput);
		cardsContainer.appendChild(bgImageSection);
		cardsContainer.appendChild(buttonsContainer);
		this.wrapper.appendChild(cardsContainer);
		cardsTitle.appendChild(addButton);

		return this.wrapper;
	}

	createBackgroundImageSection() {
		const section = document.createElement('div');
		section.classList.add('mb-3');

		const label = document.createElement('label');
		label.classList.add('form-label', 'fw-bold');
		label.textContent = '백그라운드 이미지';

		const imageWrapper = document.createElement('div');
		imageWrapper.classList.add('bg-image-wrapper');
		imageWrapper.style.position = 'relative';
		imageWrapper.style.width = '100%';
		imageWrapper.style.height = '200px';
		imageWrapper.style.backgroundColor = '#f8f9fa';
		imageWrapper.style.border = '2px dashed #dee2e6';
		imageWrapper.style.borderRadius = '4px';
		imageWrapper.style.display = 'flex';
		imageWrapper.style.alignItems = 'center';
		imageWrapper.style.justifyContent = 'center';
		imageWrapper.style.cursor = 'pointer';
		imageWrapper.style.overflow = 'hidden';

		if (this.data.backgroundImage) {
			const img = document.createElement('img');
			img.src = this.data.backgroundImage;
			img.style.width = '100%';
			img.style.height = '100%';
			img.style.objectFit = 'cover';
			imageWrapper.appendChild(img);
		} else {
			const placeholder = document.createElement('div');
			placeholder.innerHTML = '<i class="bi bi-image" style="font-size: 48px; color: #adb5bd;"></i><p class="mt-2 mb-0 text-muted">클릭하여 이미지 업로드</p>';
			placeholder.style.textAlign = 'center';
			imageWrapper.appendChild(placeholder);
		}

		// 파일 input (숨김)
		const fileInput = document.createElement('input');
		fileInput.type = 'file';
		fileInput.accept = 'image/*';
		fileInput.style.display = 'none';

		fileInput.onchange = (e) => {
			const file = e.target.files[0];
			if (file) {
				this.uploadBackgroundImage(file, imageWrapper);
			}
		};

		imageWrapper.onclick = () => fileInput.click();

		// 이미지 삭제 버튼
		if (this.data.backgroundImage) {
			const deleteBtn = document.createElement('button');
			deleteBtn.type = 'button';
			deleteBtn.classList.add('btn', 'btn-sm', 'btn-danger');
			deleteBtn.style.position = 'absolute';
			deleteBtn.style.top = '10px';
			deleteBtn.style.right = '10px';
			deleteBtn.innerHTML = '<i class="bi bi-trash"></i>';
			deleteBtn.onclick = (e) => {
				e.stopPropagation();
				this.deleteBackgroundImage(imageWrapper);
			};
			imageWrapper.appendChild(deleteBtn);
		}

		section.appendChild(label);
		section.appendChild(imageWrapper);
		section.appendChild(fileInput);

		return section;
	}

	createButtonCard(buttonData, index, container) {
		const card = document.createElement('div');
		card.classList.add('button-card');
		card.style.display = 'grid';
		card.style.gridTemplateColumns = '1fr 1fr auto';
		card.style.gap = '10px';
		card.style.alignItems = 'center';
		card.style.padding = '10px';
		card.style.border = '1px solid #e9ecef';
		card.style.borderRadius = '4px';
		card.style.backgroundColor = '#f8f9fa';

		// 버튼명 입력
		const nameInput = document.createElement('input');
		nameInput.type = 'text';
		nameInput.classList.add('form-control', 'form-control-sm');
		nameInput.placeholder = '버튼명';
		nameInput.value = buttonData.name || '';
		nameInput.oninput = () => {
			buttonData.name = nameInput.value;
		};

		// URL 입력
		const urlInput = document.createElement('input');
		urlInput.type = 'text';
		urlInput.classList.add('form-control', 'form-control-sm');
		urlInput.placeholder = 'URL';
		urlInput.value = buttonData.url || '';
		urlInput.oninput = () => {
			buttonData.url = urlInput.value;
		};

		// 삭제 버튼
		const deleteButton = document.createElement('button');
		deleteButton.type = 'button';
		deleteButton.classList.add('btn', 'btn-sm', 'btn-danger');
		deleteButton.innerHTML = '<i class="bi bi-trash"></i>';
		deleteButton.onclick = () => this.deleteButton(buttonData, card, container);

		if (this.data.buttons.length === 1) {
			deleteButton.style.display = 'none';
		}

		card.appendChild(nameInput);
		card.appendChild(urlInput);
		card.appendChild(deleteButton);

		return card;
	}

	addButton(container) {
		const newButton = this.createEmptyButton();
		this.data.buttons.push(newButton);

		this.rerenderButtons(container);
	}

	deleteButton(buttonData, card, container) {
		if (this.data.buttons.length <= 1) {
			this.showToast('최소 1개의 버튼은 유지되어야 합니다.');
			return;
		}

		// 배열에서 제거
		const index = this.data.buttons.findIndex(btn => btn.id === buttonData.id);
		if (index !== -1) {
			this.data.buttons.splice(index, 1);
		}

		// 재렌더링
		this.rerenderButtons(container);
	}

	rerenderButtons(container) {
		container.innerHTML = '';

		this.data.buttons.forEach((buttonData, index) => {
			const buttonCard = this.createButtonCard(buttonData, index, container);
			container.appendChild(buttonCard);
		});
	}

	uploadBackgroundImage(file, imageWrapper) {
		const formData = new FormData();
		formData.append('image', file);
		formData.append('org_id', this.config.org_id || '');

		// 로딩 표시
		imageWrapper.innerHTML = '<div class="spinner-border text-primary" role="status"></div>';

		$.ajax({
			url: '/homepage_menu/upload_link_image',
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			dataType: 'json',
			success: (data) => {
				if (data.success === 1) {
					// 기존 이미지가 있으면 서버에서 삭제
					if (this.data.backgroundImage) {
						this.deleteImageFromServer(this.data.backgroundImage);
					}

					this.data.backgroundImage = data.file.url;

					// 이미지 표시
					imageWrapper.innerHTML = '';
					const img = document.createElement('img');
					img.src = data.file.url;
					img.style.width = '100%';
					img.style.height = '100%';
					img.style.objectFit = 'cover';
					imageWrapper.appendChild(img);

					// 삭제 버튼 추가
					const deleteBtn = document.createElement('button');
					deleteBtn.type = 'button';
					deleteBtn.classList.add('btn', 'btn-sm', 'btn-danger');
					deleteBtn.style.position = 'absolute';
					deleteBtn.style.top = '10px';
					deleteBtn.style.right = '10px';
					deleteBtn.innerHTML = '<i class="bi bi-trash"></i>';
					deleteBtn.onclick = (e) => {
						e.stopPropagation();
						this.deleteBackgroundImage(imageWrapper);
					};
					imageWrapper.appendChild(deleteBtn);

					this.showToast('이미지가 업로드되었습니다.');
				} else {
					imageWrapper.innerHTML = '<i class="bi bi-image" style="font-size: 48px; color: #adb5bd;"></i><p class="mt-2 mb-0 text-muted">클릭하여 이미지 업로드</p>';
					this.showToast(data.message || '이미지 업로드 실패');
				}
			},
			error: (xhr, status, error) => {
				console.error('이미지 업로드 오류:', error);
				imageWrapper.innerHTML = '<i class="bi bi-image" style="font-size: 48px; color: #adb5bd;"></i><p class="mt-2 mb-0 text-muted">클릭하여 이미지 업로드</p>';
				this.showToast('이미지 업로드 중 오류가 발생했습니다.');
			}
		});
	}

	deleteBackgroundImage(imageWrapper) {
		if (this.data.backgroundImage) {
			this.deleteImageFromServer(this.data.backgroundImage);
		}

		this.data.backgroundImage = '';

		// 플레이스홀더로 복원
		imageWrapper.innerHTML = '<div style="text-align: center;"><i class="bi bi-image" style="font-size: 48px; color: #adb5bd;"></i><p class="mt-2 mb-0 text-muted">클릭하여 이미지 업로드</p></div>';
		imageWrapper.onclick = () => {
			const fileInput = imageWrapper.nextElementSibling;
			if (fileInput) fileInput.click();
		};

		this.showToast('이미지가 삭제되었습니다.');
	}

	deleteImageFromServer(imageUrl) {
		$.ajax({
			url: '/homepage_menu/delete_link_image',
			type: 'POST',
			dataType: 'json',
			contentType: 'application/json',
			data: JSON.stringify({ image_url: imageUrl }),
			success: (data) => {
				if (data.success) {
					console.log('이미지 삭제 완료:', imageUrl);
				}
			},
			error: (xhr, status, error) => {
				console.error('이미지 삭제 오류:', error);
			}
		});
	}



	save() {
		console.log('WaniLinkListBg 저장:', this.data);
		return {
			title: this.data.title,
			subtitle: this.data.subtitle,
			backgroundImage: this.data.backgroundImage,
			buttons: this.data.buttons
		};
	}

	static get sanitize() {
		return {
			title: {},
			subtitle: {},
			backgroundImage: {},
			buttons: {}
		};
	}
}

window.WaniLinkListBg = WaniLinkListBg;
