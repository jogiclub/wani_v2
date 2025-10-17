/**
 * 파일 위치: /var/www/wani/public/homepage/_theme/1/assets/js/theme.js
 * 역할: 테마 1 전용 스크립트 (테마별 커스터마이징)
 */

// 테마 1 초기화 함수
function initTheme() {
	console.log('테마 1 초기화');

	// 테마별 추가 기능이 필요한 경우 여기에 작성
	// 예: 특수 애니메이션, 인터랙션 등
}

// 페이지 로드 완료 후 테마 초기화
document.addEventListener('DOMContentLoaded', () => {
	initTheme();
});

// 테마 1 전용 유틸리티 함수들
const Theme1Utils = {
	// 부드러운 스크롤 효과
	smoothScroll: function(targetId) {
		const element = document.getElementById(targetId);
		if (element) {
			element.scrollIntoView({ behavior: 'smooth' });
		}
	},

	// 애니메이션 효과 추가
	addAnimation: function(element, animationClass) {
		if (element) {
			element.classList.add(animationClass);
		}
	}
};

// 전역으로 노출 (필요한 경우)
window.Theme1Utils = Theme1Utils;
