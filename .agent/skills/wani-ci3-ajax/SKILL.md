---
name: wani-ci3-ajax
description: CodeIgniter3 + Bootstrap 5.3 + jQuery 기반 웹 기능 구현/수정 스킬. 이 프로젝트에서 MVC 패턴을 유지하면서 HTML 프레임 + AJAX 중심으로 빠른 로딩 구조를 구현할 때 사용하고, 공통 함수 재사용, toast/모달 UI 규칙, 한국어 응답 형식, 함수 단위 코드 제공 형식을 지켜야 할 때 사용.
---

# WANI CI3 AJAX 개발 스킬

## 작업 시작
1. 저장소 구조에서 `application`의 Controller/Model/View 연계를 먼저 확인하기
2. GitHub 연결 소스와 MCP 서버 코드가 사용 가능하면 동일 기능의 기존 구현을 우선 참조하기
3. 기존 공통 함수/헬퍼/라이브러리 존재 여부를 확인하고 재사용 계획을 먼저 세우기
4. DB 관련 구현 전 `.agent/skills/wani-ci3-ajax/references/db-schema.md`를 먼저 확인하기
5. 프론트 공통 동작 구현 전 `assets/js/common.js`의 기존 함수(예: toast, confirm modal, 포맷터, 공통 이벤트)를 먼저 확인하기

## 구현 규칙
1. CodeIgniter3 관례를 따르기
2. Bootstrap 5.3, jQuery 기반 코드를 유지하기
3. 화면은 HTML 프레임 위주로 구성하고 데이터 처리는 AJAX로 분리하기
4. 초기 로딩 성능을 위해 불필요한 동기 렌더링과 중복 요청을 피하기
5. MVC 경계를 넘지 않기
6. 유사 기능은 공통 함수로 추출하고 중복 함수를 만들지 않기
7. `common.js`와 중복되는 신규 로직이 생기면 중복 코드를 제거하고 `common.js` 호출로 통합하기

## UI/메시지 규칙
1. `alert` 대신 toast 메시지를 사용하기
2. `confirm` 대신 모달을 사용하기
3. 사용자 노출 메시지와 description은 이모티콘 없이 평문으로 작성하기

## 응답 및 코드 전달 형식
1. 한국어로 설명하기
2. 수정 코드 설명은 함수 단위로 정리하기
3. 각 코드 항목 최상단에 파일 경로와 코드 역할을 먼저 표기하기

## 제외 규칙
1. 가이드 문서와 테스트 코드는 사용자 요청 전에는 작성/제공하지 않기
