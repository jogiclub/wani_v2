/**
 * 파일 위치: assets/js/wani-link-list.js
 * 역할: EditorJS용 바로가기 섹션 커스텀 플러그인
 */

class WaniLinkList {
	static get toolbox() {
		return {
			title: '바로가기 섹션',
			icon: '<svg width="17" height="15" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1zM8 13h8v-2H8v2zm9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4c2.76 0 5-2.24 5-5s-2.24-5-5-5z"/></svg>'
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
		if (!this.data.links || !Array.isArray(this.data.links)) {
			this.data.links = [this.createEmptyLink()];
		}
	}

	createEmptyLink() {
		return {
			id: 'link_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9),
			name: '',
			url: '',
			image: ''
		};
	}

	render() {
		this.wrapper = document.createElement('div');
		this.wrapper.classList.add('wani-link-list-wrapper');
		this.wrapper.style.border = '1px solid #dee2e6';
		this.wrapper.style.borderRadius = '8px';
		this.wrapper.style.padding = '20px';
		this.wrapper.style.backgroundColor = '#fff';

		const titleInput = document.createElement('input');
		titleInput.type = 'text';
		titleInput.classList.add('form-control', 'mb-3');
		titleInput.placeholder = '타이틀 입력';
		titleInput.value = this.data.title || '';
		titleInput.oninput = () => {
			this.data.title = titleInput.value;
		};

		const subtitleInput = document.createElement('textarea');
		subtitleInput.classList.add('form-control', 'mb-3');
		subtitleInput.placeholder = '서브타이틀 입력 (Enter로 줄바꿈 가능)';
		subtitleInput.value = this.data.subtitle || '';
		subtitleInput.rows = 2;
		subtitleInput.oninput = () => {
			this.data.subtitle = subtitleInput.value;
		};

		const linksContainer = document.createElement('div');
		linksContainer.classList.add('wani-links-container');
		linksContainer.style.marginTop = '15px';
		linksContainer.style.display = 'grid';
		linksContainer.style.gridTemplateColumns = 'repeat(auto-fill, minmax(200px, 1fr))';
		linksContainer.style.gap = '15px';

		this.data.links.forEach((linkData, index) => {
			const linkCard = this.createLinkCard(linkData, index, linksContainer);
			linksContainer.appendChild(linkCard);
		});

		const addButton = document.createElement('button');
		addButton.type = 'button';
		addButton.classList.add('btn', 'btn-sm', 'btn-outline-primary', 'mt-3');
		addButton.innerHTML = '<i class="bi bi-plus-lg"></i> 링크 추가';
		addButton.onclick = () => this.addLink(linksContainer);

		this.wrapper.appendChild(titleInput);
		this.wrapper.appendChild(subtitleInput);
		this.wrapper.appendChild(linksContainer);
		this.wrapper.appendChild(addButton);

		return this.wrapper;
	}

	createLinkCard(linkData, index, container) {
		const card = document.createElement('div');
		card.classList.add('link-card');
		card.style.border = '1px solid #e9ecef';
		card.style.borderRadius = '8px';
		card.style.padding = '15px';
		card.style.backgroundColor = '#f8f9fa';

		// 이미지 영역
		const imageWrapper = this.createImageWrapper(linkData);

		// 파일 input (숨김)
		const fileInput = document.createElement('input');
		fileInput.type = 'file';
		fileInput.accept = 'image/*';
		fileInput.style.display = 'none';

		fileInput.onchange = (e) => {
			const file = e.target.files[0];
			if (file) {
				this.uploadImage(file, linkData, imageWrapper);
			}
		};

		imageWrapper.onclick = () => fileInput.click();

		// 링크명 입력
		const nameInput = document.createElement('input');
		nameInput.type = 'text';
		nameInput.classList.add('form-control', 'form-control-sm', 'mb-2');
		nameInput.placeholder = '링크명';
		nameInput.value = linkData.name || '';
		nameInput.oninput = () => {
			linkData.name = nameInput.value;
		};

		// URL 입력
		const urlInput = document.createElement('input');
		urlInput.type = 'text';
		urlInput.classList.add('form-control', 'form-control-sm', 'mb-2');
		urlInput.placeholder = 'URL';
		urlInput.value = linkData.url || '';
		urlInput.oninput = () => {
			linkData.url = urlInput.value;
		};

		// 삭제 버튼
		const deleteButton = document.createElement('button');
		deleteButton.type = 'button';
		deleteButton.classList.add('btn', 'btn-sm', 'btn-danger', 'w-100', 'mt-2');
		deleteButton.innerHTML = '<i class="bi bi-dash"></i>';
		deleteButton.onclick = () => this.deleteLink(linkData, card, container);

		if (this.data.links.length === 1) {
			deleteButton.style.display = 'none';
		}

		card.appendChild(imageWrapper);
		card.appendChild(fileInput);
		card.appendChild(nameInput);
		card.appendChild(urlInput);
		card.appendChild(deleteButton);

		return card;
	}

	createImageWrapper(linkData) {
		const imageWrapper = document.createElement('div');
		imageWrapper.classList.add('link-image-wrapper');
		imageWrapper.style.position = 'relative';
		imageWrapper.style.width = '100%';
		imageWrapper.style.height = '150px';
		imageWrapper.style.backgroundColor = '#f8f9fa';
		imageWrapper.style.border = '2px dashed #dee2e6';
		imageWrapper.style.borderRadius = '4px';
		imageWrapper.style.marginBottom = '10px';
		imageWrapper.style.display = 'flex';
		imageWrapper.style.alignItems = 'center';
		imageWrapper.style.justifyContent = 'center';
		imageWrapper.style.cursor = 'pointer';
		imageWrapper.style.overflow = 'hidden';

		if (linkData.image) {
			const img = document.createElement('img');
			img.src = linkData.image;
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

	addLink(container) {
		const newLink = this.createEmptyLink();
		this.data.links.push(newLink);

		this.rerenderLinks(container);
	}

	deleteLink(linkData, card, container) {
		if (this.data.links.length <= 1) {
			this.showToast('최소 1개의 링크는 유지되어야 합니다.');
			return;
		}

		// 서버에 이미지 삭제 요청
		if (linkData.image) {
			this.deleteImageFromServer(linkData.image);
		}

		// 배열에서 제거
		const index = this.data.links.findIndex(link => link.id === linkData.id);
		if (index !== -1) {
			this.data.links.splice(index, 1);
		}

		// 재렌더링
		this.rerenderLinks(container);
	}

	rerenderLinks(container) {
		container.innerHTML = '';

		this.data.links.forEach((linkData, index) => {
			const linkCard = this.createLinkCard(linkData, index, container);
			container.appendChild(linkCard);
		});
	}

	uploadImage(file, linkData, imageWrapper) {
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
					if (linkData.image) {
						this.deleteImageFromServer(linkData.image);
					}

					linkData.image = data.file.url;

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

	showToast(message) {
		if (typeof window.showToast === 'function') {
			window.showToast(message);
		} else {
			alert(message);
		}
	}

	save() {
		console.log('WaniLinkList 저장:', this.data);
		return {
			title: this.data.title,
			subtitle: this.data.subtitle,
			links: this.data.links
		};
	}

	static get sanitize() {
		return {
			title: {},
			subtitle: {},
			links: {}
		};
	}
}

window.WaniLinkList = WaniLinkList;
