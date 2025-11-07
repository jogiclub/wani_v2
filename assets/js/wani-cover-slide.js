/**
 * 파일 위치: assets/js/wani-cover-slide.js
 * 역할: EditorJS용 카드 그리드 커스텀 플러그인
 */

class WaniCoverSlide {
	static get toolbox() {
		return {
			title: '카드 그리드',
			icon: '<svg width="17" height="15" viewBox="0 0 336 276" xmlns="http://www.w3.org/2000/svg"><path d="M291 150V79c0-19-15-34-34-34H79c-19 0-34 15-34 34v42l67-44 81 72 56-29 42 30zm0 52l-43-30-56 30-81-67-66 39v23c0 19 15 34 34 34h178c17 0 31-13 34-29zM79 0h178c44 0 79 35 79 79v118c0 44-35 79-79 79H79c-44 0-79-35-79-79V79C0 35 35 0 79 0z"/></svg>'
		};
	}

	constructor({ data, api, config }) {
		this.api = api;
		this.data = data || {};
		this.config = config || {};
		this.wrapper = null;

		// 기본 데이터 구조
		if (!this.data.cards || !Array.isArray(this.data.cards)) {
			this.data.cards = [this.createEmptyCard()];
		}
	}

	createEmptyCard() {
		return {
			id: 'card_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9),
			image: '',
			title: '',
			subtitle: ''
		};
	}

	render() {
		this.wrapper = document.createElement('div');
		this.wrapper.classList.add('wani-cover-slide-wrapper');

		// 카드 컨테이너
		const cardsContainer = document.createElement('div');
		cardsContainer.classList.add('wani-cards-container');
		cardsContainer.style.display = 'grid';
		cardsContainer.style.gridTemplateColumns = 'repeat(auto-fill, minmax(250px, 1fr))';
		cardsContainer.style.gap = '20px';
		cardsContainer.style.marginBottom = '15px';

		// 카드 렌더링
		this.data.cards.forEach((cardData, index) => {
			const card = this.renderCard(cardData, index);
			cardsContainer.appendChild(card);
		});

		// 추가 버튼
		const addButton = document.createElement('button');
		addButton.type = 'button';
		addButton.classList.add('btn', 'btn-sm', 'btn-outline-primary');
		addButton.innerHTML = '<i class="bi bi-plus-lg"></i> 카드 추가';
		addButton.onclick = () => this.addCard(cardsContainer);

		this.wrapper.appendChild(cardsContainer);
		this.wrapper.appendChild(addButton);

		return this.wrapper;
	}

	renderCard(cardData, index) {
		const cardElement = document.createElement('div');
		cardElement.classList.add('wani-card-item');
		cardElement.style.border = '1px solid #dee2e6';
		cardElement.style.borderRadius = '8px';
		cardElement.style.padding = '15px';
		cardElement.style.backgroundColor = '#fff';

		// 이미지 영역
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

		// 타이틀 입력
		const titleInput = document.createElement('input');
		titleInput.type = 'text';
		titleInput.classList.add('form-control', 'form-control-sm', 'mb-2');
		titleInput.placeholder = '타이틀 입력';
		titleInput.value = cardData.title || '';
		titleInput.onchange = () => {
			cardData.title = titleInput.value;
		};

		// 서브타이틀 입력
		const subtitleInput = document.createElement('input');
		subtitleInput.type = 'text';
		subtitleInput.classList.add('form-control', 'form-control-sm', 'mb-2');
		subtitleInput.placeholder = '서브타이틀 입력';
		subtitleInput.value = cardData.subtitle || '';
		subtitleInput.onchange = () => {
			cardData.subtitle = subtitleInput.value;
		};

		// 삭제 버튼
		const deleteButton = document.createElement('button');
		deleteButton.type = 'button';
		deleteButton.classList.add('btn', 'btn-sm', 'btn-danger', 'w-100');
		deleteButton.innerHTML = '<i class="bi bi-trash"></i> 삭제';
		deleteButton.onclick = () => this.deleteCard(cardElement, cardData, index);

		// 카드 조립
		cardElement.appendChild(imageWrapper);
		cardElement.appendChild(fileInput);
		cardElement.appendChild(titleInput);
		cardElement.appendChild(subtitleInput);
		cardElement.appendChild(deleteButton);

		return cardElement;
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

		// jQuery AJAX 사용 (기존 프로젝트 방식과 일관성 유지)
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
			cards: this.data.cards
		};
	}

	static get sanitize() {
		return {
			cards: {}
		};
	}
}

window.WaniCoverSlide = WaniCoverSlide;
