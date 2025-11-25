/**
 * 파일 위치: assets/js/wani-intro-link.js
 * 역할: EditorJS용 소개 링크 커스텀 플러그인 - 상단 타이틀/서브타이틀과 커버 슬라이드
 */

class WaniIntroLink {
	static get toolbox() {
		return {
			title: '소개 링크',
			icon: '<svg width="17" height="15" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>'
		};
	}

	constructor({ data, api, config }) {
		this.api = api;
		this.data = data || {};
		this.config = config || {};
		this.wrapper = null;

		// 기본 데이터 구조 초기화
		if (!this.data.title) {
			this.data.title = '';
		}
		if (!this.data.subtitle) {
			this.data.subtitle = '';
		}
		if (!this.data.cards || !Array.isArray(this.data.cards)) {
			this.data.cards = [this.createEmptyCard()];
		}
	}

	createEmptyCard() {
		return {
			id: 'card_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9),
			image: '',
			imageTitle: '',
			title: '',
			subtitle: '',
			buttons: [this.createEmptyButton()]
		};
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
		this.wrapper.classList.add('wani-intro-link-wrapper');

		// 상단 타이틀 영역
		const headerSection = this.createHeaderSection();
		this.wrapper.appendChild(headerSection);

		// 카드 컨테이너
		const cardsContainer = document.createElement('div');
		cardsContainer.classList.add('wani-cards-container');
		cardsContainer.style.display = 'grid';
		cardsContainer.style.gridTemplateColumns = 'repeat(auto-fill, minmax(280px, 1fr))';
		cardsContainer.style.gap = '20px';
		cardsContainer.style.marginBottom = '15px';

		// 카드 렌더링
		this.data.cards.forEach((cardData, index) => {
			const card = this.renderCard(cardData, index);
			cardsContainer.appendChild(card);
		});

		// 카드 추가 버튼
		const addButton = document.createElement('button');
		addButton.type = 'button';
		addButton.classList.add('btn', 'btn-sm', 'btn-outline-primary');
		addButton.innerHTML = '<i class="bi bi-plus-lg"></i> 카드 추가';
		addButton.onclick = () => this.addCard(cardsContainer);

		this.wrapper.appendChild(cardsContainer);
		this.wrapper.appendChild(addButton);

		return this.wrapper;
	}

	createHeaderSection() {
		const headerSection = document.createElement('div');
		headerSection.classList.add('header-section');
		headerSection.style.marginBottom = '25px';
		headerSection.style.padding = '15px';
		headerSection.style.backgroundColor = '#f8f9fa';
		headerSection.style.borderRadius = '8px';
		headerSection.style.border = '1px solid #dee2e6';

		// 타이틀 입력
		const titleInput = document.createElement('input');
		titleInput.type = 'text';
		titleInput.classList.add('form-control', 'form-control-lg', 'mb-2');
		titleInput.placeholder = '메인 타이틀 입력';
		titleInput.value = this.data.title || '';
		titleInput.style.fontWeight = 'bold';
		titleInput.oninput = () => {
			this.data.title = titleInput.value;
		};

		// 서브타이틀 입력
		const subtitleInput = document.createElement('textarea');
		subtitleInput.classList.add('form-control');
		subtitleInput.placeholder = '서브타이틀 입력 (Enter로 줄바꿈 가능)';
		subtitleInput.value = this.data.subtitle || '';
		subtitleInput.rows = 2;
		subtitleInput.oninput = () => {
			this.data.subtitle = subtitleInput.value;
		};

		headerSection.appendChild(titleInput);
		headerSection.appendChild(subtitleInput);

		return headerSection;
	}

	renderCard(cardData, index) {
		const cardElement = document.createElement('div');
		cardElement.classList.add('wani-card-item');
		cardElement.style.border = '1px solid #dee2e6';
		cardElement.style.borderRadius = '8px';
		cardElement.style.padding = '15px';
		cardElement.style.backgroundColor = '#fff';

		// 이미지 영역
		const imageWrapper = this.createImageWrapper(cardData);

		// 파일 input (숨김)
		const fileInput = document.createElement('input');
		fileInput.type = 'file';
		fileInput.accept = 'image/*';
		fileInput.style.display = 'none';

		fileInput.onchange = (e) => {
			const file = e.target.files[0];
			if (file) {
				this.uploadImage(file, cardData, imageWrapper);
			}
		};

		imageWrapper.onclick = () => fileInput.click();

		// 이미지 제목 입력
		const imageTitleInput = document.createElement('input');
		imageTitleInput.type = 'text';
		imageTitleInput.classList.add('form-control', 'form-control-sm', 'mb-2');
		imageTitleInput.placeholder = '이미지 제목 입력';
		imageTitleInput.value = cardData.imageTitle || '';
		imageTitleInput.oninput = () => {
			cardData.imageTitle = imageTitleInput.value;
		};

		// 타이틀 입력
		const titleInput = document.createElement('input');
		titleInput.type = 'text';
		titleInput.classList.add('form-control', 'form-control-sm', 'mb-2');
		titleInput.placeholder = '타이틀 입력';
		titleInput.value = cardData.title || '';
		titleInput.oninput = () => {
			cardData.title = titleInput.value;
		};

		// 서브타이틀 입력
		const subtitleInput = document.createElement('textarea');
		subtitleInput.classList.add('form-control', 'mb-3');
		subtitleInput.placeholder = '서브타이틀 입력 (Enter로 줄바꿈 가능)';
		subtitleInput.value = cardData.subtitle || '';
		subtitleInput.rows = 2;
		subtitleInput.oninput = () => {
			cardData.subtitle = subtitleInput.value;
		};

		// 버튼 목록 영역
		const buttonsContainer = this.createButtonsContainer(cardData);

		// 삭제 버튼
		const deleteButton = document.createElement('button');
		deleteButton.type = 'button';
		deleteButton.classList.add('btn', 'btn-sm', 'btn-danger', 'w-100', 'mt-3');
		deleteButton.innerHTML = '<i class="bi bi-trash"></i> 삭제';
		deleteButton.onclick = () => this.deleteCard(cardElement, cardData, index);

		// 카드 조립
		cardElement.appendChild(imageWrapper);
		cardElement.appendChild(fileInput);
		cardElement.appendChild(imageTitleInput);
		cardElement.appendChild(titleInput);
		cardElement.appendChild(subtitleInput);
		cardElement.appendChild(buttonsContainer);
		cardElement.appendChild(deleteButton);

		return cardElement;
	}

	createImageWrapper(cardData) {
		const imageWrapper = document.createElement('div');
		imageWrapper.classList.add('card-image-wrapper');
		imageWrapper.style.position = 'relative';
		imageWrapper.style.width = '100%';
		imageWrapper.style.height = '200px';
		imageWrapper.style.backgroundColor = '#f8f9fa';
		imageWrapper.style.border = '2px dashed #dee2e6';
		imageWrapper.style.borderRadius = '4px';
		imageWrapper.style.marginBottom = '15px';
		imageWrapper.style.display = 'flex';
		imageWrapper.style.alignItems = 'center';
		imageWrapper.style.justifyContent = 'center';
		imageWrapper.style.cursor = 'pointer';
		imageWrapper.style.overflow = 'hidden';

		if (cardData.image) {
			const img = document.createElement('img');
			img.src = cardData.image;
			img.style.width = '100%';
			img.style.height = '100%';
			img.style.objectFit = 'cover';
			imageWrapper.appendChild(img);
		} else {
			const placeholder = document.createElement('div');
			placeholder.innerHTML = '<i class="bi bi-image" style="font-size: 48px; color: #adb5bd;"></i>';
			placeholder.style.textAlign = 'center';
			imageWrapper.appendChild(placeholder);
		}

		return imageWrapper;
	}

	createButtonsContainer(cardData) {
		const container = document.createElement('div');
		container.classList.add('card-buttons-container');
		container.style.border = '1px solid #dee2e6';
		container.style.borderRadius = '4px';
		container.style.padding = '10px';
		container.style.backgroundColor = '#f8f9fa';

		// 버튼 데이터 초기화
		if (!cardData.buttons || !Array.isArray(cardData.buttons) || cardData.buttons.length === 0) {
			cardData.buttons = [this.createEmptyButton()];
		}

		// 버튼 목록 렌더링
		cardData.buttons.forEach((buttonData, index) => {
			const buttonRow = this.createButtonRow(cardData, buttonData, index, container);
			container.appendChild(buttonRow);
		});

		return container;
	}

	createButtonRow(cardData, buttonData, index, container) {
		const row = document.createElement('div');
		row.classList.add('button-row', 'mb-2');
		row.style.display = 'flex';
		row.style.gap = '5px';
		row.style.alignItems = 'center';

		// 버튼명 입력
		const nameInput = document.createElement('input');
		nameInput.type = 'text';
		nameInput.classList.add('form-control', 'form-control-sm');
		nameInput.placeholder = '버튼명';
		nameInput.value = buttonData.name || '';
		nameInput.style.flex = '1';
		nameInput.oninput = () => {
			buttonData.name = nameInput.value;
		};

		// URL 입력
		const urlInput = document.createElement('input');
		urlInput.type = 'text';
		urlInput.classList.add('form-control', 'form-control-sm');
		urlInput.placeholder = 'URL';
		urlInput.value = buttonData.url || '';
		urlInput.style.flex = '2';
		urlInput.oninput = () => {
			buttonData.url = urlInput.value;
		};

		// 추가 버튼 (+)
		const addBtn = document.createElement('button');
		addBtn.type = 'button';
		addBtn.classList.add('btn', 'btn-sm', 'btn-outline-success');
		addBtn.innerHTML = '<i class="bi bi-plus-lg"></i>';
		addBtn.style.minWidth = '32px';
		addBtn.onclick = () => this.addButton(cardData, container);

		// 삭제 버튼 (-)
		const deleteBtn = document.createElement('button');
		deleteBtn.type = 'button';
		deleteBtn.classList.add('btn', 'btn-sm', 'btn-outline-danger');
		deleteBtn.innerHTML = '<i class="bi bi-dash"></i>';
		deleteBtn.style.minWidth = '32px';

		// 첫 번째 버튼은 삭제 버튼 숨김
		if (index === 0 && cardData.buttons.length === 1) {
			deleteBtn.style.display = 'none';
		}

		deleteBtn.onclick = () => this.deleteButton(cardData, index, row, container);

		row.appendChild(nameInput);
		row.appendChild(urlInput);
		row.appendChild(addBtn);
		row.appendChild(deleteBtn);

		return row;
	}

	addButton(cardData, container) {
		const newButton = this.createEmptyButton();
		cardData.buttons.push(newButton);

		// 컨테이너 재렌더링
		this.rerenderButtons(cardData, container);
	}

	deleteButton(cardData, index, row, container) {
		if (cardData.buttons.length <= 1) {
			this.showToast('최소 1개의 버튼은 유지되어야 합니다.');
			return;
		}

		cardData.buttons.splice(index, 1);

		// 컨테이너 재렌더링
		this.rerenderButtons(cardData, container);
	}

	rerenderButtons(cardData, container) {
		// 기존 버튼 행 제거
		container.innerHTML = '';

		// 버튼 목록 재렌더링
		cardData.buttons.forEach((buttonData, index) => {
			const buttonRow = this.createButtonRow(cardData, buttonData, index, container);
			container.appendChild(buttonRow);
		});
	}

	addCard(container) {
		const newCard = this.createEmptyCard();
		this.data.cards.push(newCard);

		const cardElement = this.renderCard(newCard, this.data.cards.length - 1);
		container.appendChild(cardElement);
	}

	deleteCard(cardElement, cardData, index) {
		// 서버에 이미지 삭제 요청
		if (cardData.image) {
			this.deleteImageFromServer(cardData.image);
		}

		// 배열에서 제거
		this.data.cards.splice(index, 1);

		// DOM에서 제거
		cardElement.remove();

		// 카드가 하나도 없으면 빈 카드 하나 추가
		if (this.data.cards.length === 0) {
			const container = this.wrapper.querySelector('.wani-cards-container');
			const emptyCard = this.createEmptyCard();
			this.data.cards.push(emptyCard);
			const cardElement = this.renderCard(emptyCard, 0);
			container.appendChild(cardElement);
		}
	}

	uploadImage(file, cardData, imageWrapper) {
		const formData = new FormData();
		formData.append('image', file);
		formData.append('org_id', this.config.org_id || '');

		// 로딩 표시
		imageWrapper.innerHTML = '<div class="spinner-border text-primary" role="status"></div>';

		// jQuery AJAX 사용
		$.ajax({
			url: '/homepage_menu/upload_card_image',
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			dataType: 'json',
			success: (data) => {
				if (data.success === 1) {
					cardData.image = data.file.url;

					// 이미지 표시
					imageWrapper.innerHTML = '';
					const img = document.createElement('img');
					img.src = data.file.url;
					img.style.width = '100%';
					img.style.height = '100%';
					img.style.objectFit = 'cover';
					imageWrapper.appendChild(img);

					this.showToast('이미지가 업로드되었습니다.');
				} else {
					imageWrapper.innerHTML = '<i class="bi bi-image" style="font-size: 48px; color: #adb5bd;"></i>';
					this.showToast(data.message || '이미지 업로드 실패');
				}
			},
			error: (xhr, status, error) => {
				console.error('이미지 업로드 오류:', error);
				imageWrapper.innerHTML = '<i class="bi bi-image" style="font-size: 48px; color: #adb5bd;"></i>';
				this.showToast('이미지 업로드 중 오류가 발생했습니다.');
			}
		});
	}

	deleteImageFromServer(imageUrl) {
		$.ajax({
			url: '/homepage_menu/delete_card_image',
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

	showToast(message) {
		if (typeof window.showToast === 'function') {
			window.showToast(message);
		} else {
			alert(message);
		}
	}

	save() {
		return {
			title: this.data.title,
			subtitle: this.data.subtitle,
			cards: this.data.cards
		};
	}

	static get sanitize() {
		return {
			title: {},
			subtitle: {},
			cards: {}
		};
	}
}

window.WaniIntroLink = WaniIntroLink;
