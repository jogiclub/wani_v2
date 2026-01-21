/**
 * 파일 위치: assets/js/org_map_popup.js
 * 역할: 조직 위치를 카카오 지도에 표시하는 팝업 기능
 */

let map = null;
let geocoder = null;
let markers = [];
let orgData = [];

/**
 * 역할: 카카오 API 로드 완료 확인 및 초기화
 */
function waitForKakao() {
	if (typeof kakao !== 'undefined' && kakao.maps) {
		initializeMap();
	} else {
		setTimeout(waitForKakao, 100);
	}
}

// 페이지 로드 시 카카오 API 대기
$(function() {
	'use strict';
	waitForKakao();
});

/**
 * 역할: 카카오 지도 초기화
 */
function initializeMap() {
	// 세션 스토리지에서 조직 데이터 가져오기
	const storedData = sessionStorage.getItem('orgMapData');

	if (!storedData) {
		showToast('지도에 표시할 조직 데이터가 없습니다', 'warning');
		setTimeout(() => window.close(), 2000);
		return;
	}

	try {
		orgData = JSON.parse(storedData);

		if (!orgData || orgData.length === 0) {
			showToast('지도에 표시할 조직 데이터가 없습니다', 'warning');
			setTimeout(() => window.close(), 2000);
			return;
		}

		// 카카오 지도 초기화 (기본 위치: 서울시청)
		const container = document.getElementById('kakaoMap');
		const options = {
			center: new kakao.maps.LatLng(37.5665, 126.9780),
			level: 8
		};

		map = new kakao.maps.Map(container, options);
		geocoder = new kakao.maps.services.Geocoder();

		// 조직 주소를 좌표로 변환하여 마커 표시
		displayOrgMarkers();

	} catch (error) {
		console.error('지도 초기화 오류:', error);
		showToast('지도를 초기화하는 중 오류가 발생했습니다', 'error');
	}
}

/**
 * 역할: 조직 마커들을 지도에 표시
 */
function displayOrgMarkers() {
	let successCount = 0;
	let processedCount = 0;
	const totalCount = orgData.length;
	const bounds = new kakao.maps.LatLngBounds();

	orgData.forEach(function(org) {
		// 주소가 없는 경우 건너뛰기
		if (!org.org_address || org.org_address.trim() === '') {
			processedCount++;
			checkCompletion();
			return;
		}

		// 주소를 좌표로 변환
		geocoder.addressSearch(org.org_address, function(result, status) {
			processedCount++;

			if (status === kakao.maps.services.Status.OK) {
				const coords = new kakao.maps.LatLng(result[0].y, result[0].x);

				// 커스텀 오버레이 생성
				const content = createCustomOverlay(org);

				// 마커 생성
				const marker = new kakao.maps.Marker({
					map: map,
					position: coords
				});

				// 커스텀 오버레이 생성
				const customOverlay = new kakao.maps.CustomOverlay({
					position: coords,
					content: content,
					yAnchor: 1.5
				});

				// 마커 클릭 이벤트
				kakao.maps.event.addListener(marker, 'click', function() {
					// 모든 오버레이 닫기
					closeAllOverlays();
					// 현재 오버레이만 표시
					customOverlay.setMap(map);
					markers.forEach(m => {
						if (m.overlay === customOverlay) {
							m.isOpen = true;
						}
					});
				});

				// 지도 클릭 시 오버레이 닫기
				kakao.maps.event.addListener(map, 'click', function() {
					closeAllOverlays();
				});

				markers.push({
					marker: marker,
					overlay: customOverlay,
					isOpen: false
				});

				// 범위에 좌표 추가
				bounds.extend(coords);
				successCount++;
			} else {
				console.warn('주소 검색 실패:', org.org_name, org.org_address);
			}

			checkCompletion();
		});
	});

	/**
	 * 역할: 모든 주소 처리 완료 여부 확인
	 */
	function checkCompletion() {
		if (processedCount === totalCount) {
			if (successCount > 0) {
				// 모든 마커가 보이도록 지도 범위 조정
				map.setBounds(bounds);

				// 마커 개수 표시
				$('#markerCount').text(successCount);

				showToast(`${successCount}개의 조직 위치가 표시되었습니다`, 'success');
			} else {
				showToast('주소를 찾을 수 있는 조직이 없습니다', 'warning');
				$('#markerCount').text(0);
			}
		}
	}
}

/**
 * 역할: 커스텀 오버레이 HTML 생성
 */
function createCustomOverlay(org) {
	const orgName = escapeHtml(org.org_name || '이름 없음');
	const address = escapeHtml(org.org_address || '');
	const detailAddress = org.org_address_detail ? ' ' + escapeHtml(org.org_address_detail) : '';

	return `
		<div class="custom-overlay">
			<div class="title">${orgName}</div>
			<div class="address">${address}${detailAddress}</div>
		</div>
	`;
}

/**
 * 역할: 모든 커스텀 오버레이 닫기
 */
function closeAllOverlays() {
	markers.forEach(function(m) {
		if (m.isOpen) {
			m.overlay.setMap(null);
			m.isOpen = false;
		}
	});
}

/**
 * 역할: HTML 이스케이프 처리
 */
function escapeHtml(text) {
	const map = {
		'&': '&amp;',
		'<': '&lt;',
		'>': '&gt;',
		'"': '&quot;',
		"'": '&#039;'
	};
	return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
}

