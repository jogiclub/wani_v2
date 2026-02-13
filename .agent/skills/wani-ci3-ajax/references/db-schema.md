# WANI V2 DB 스키마 레퍼런스

기준: 사용자 제공 DDL (2026-02-13)

## 공통 규칙
- 대부분 테이블은 `org_id` 멀티테넌트 구조를 전제로 설계됨
- 삭제는 `del_yn` 소프트 삭제 패턴이 다수 사용됨
- 통계 테이블은 주차 기준 `sunday_date` + `att_year` 조합 사용
- JSON 저장은 `text`/`longtext` 컬럼에 저장하며 일부는 `json_valid` 체크 사용

## 핵심 엔터티
- `wb_org`: 조직 마스터
- `wb_user`: 사용자 마스터
- `wb_org_user`: 사용자-조직 매핑
- `wb_member`: 회원 마스터
- `wb_member_area`: 회원 지역
- `wb_org_category`: 조직 카테고리
- `wb_transfer_org`: 전입 조직

## 출석/통계 도메인
- `wb_att_type`: 출석 타입 정의
- `wb_member_att`: 회원 출석 이력
- `wb_attendance_weekly_stats`: 회원 주간 출석 통계
- `wb_attendance_weekly_type_stats`: 주간 출석 타입별 통계
- `wb_attendance_yearly_stats`: 연간 출석 통계
- `wb_member_weekly_stats`: 주간 신규회원 통계
- `wb_memo_weekly_type_stats`: 주간 메모 타입 통계
- `wb_timeline_weekly_type_stats`: 주간 타임라인 타입 통계

## 회원 부가정보 도메인
- `wb_member_family`: 회원 가족 관계 (self FK, cascade)
- `wb_member_timeline`: 회원 타임라인 이력
- `wb_detail_field`: 상세 필드 커스터마이징
- `wb_revision`: 회원 수정 이력(JSON)
- `wb_memo`: 메모
- `wb_message`: 메시지

## 교육(양육) 도메인
- `wb_edu`: 양육 마스터
- `wb_edu_applicant`: 양육 신청자 (`wb_edu` FK cascade)
- `wb_edu_category`: 양육 카테고리(JSON)
- `wb_edu_external_url`: 외부 신청 URL (액세스 코드/만료시간)

## 소모임 도메인
- `wb_moim`: 소모임 참여/직책
- `wb_moim_category`: 소모임 카테고리(JSON)

## 홈페이지 도메인
- `wb_homepage_board`: 게시판
- `wb_homepage_page`: 정적 페이지(HTML)
- `wb_homepage_link`: 링크형 메뉴

## 회계 도메인
- `wb_cash_book`: 장부 마스터 (계정과목/계좌 JSON)
- `wb_income_expense`: 수입/지출 거래내역

## 발송/문자 도메인
- `wb_sender_number`: 발신번호 관리
- `wb_send_template`: 메시지 템플릿
- `wb_send_reservation`: 예약 발송
- `wb_send_log`: 발송 로그
- `wb_send_package`: 충전 패키지
- `wb_send_charge_history`: 충전 이력
- `wb_send_address_book`: 주소록
- `wb_send_address_book_member`: 주소록 회원 매핑
- `wb_send_invitemail`: 초대 메일 로그

## 결제/초대 도메인
- `wb_payment_log`: PG 결제 로그
- `wb_invite`: 초대코드

## 중요 FK/유니크 제약
- `wb_edu_applicant.edu_idx -> wb_edu.edu_idx` (ON DELETE CASCADE)
- `wb_member_family.member_idx -> wb_member.member_idx` (ON DELETE CASCADE)
- `wb_member_family.related_member_idx -> wb_member.member_idx` (ON DELETE CASCADE)
- `wb_member_timeline.member_idx -> wb_member.member_idx` (ON UPDATE/DELETE CASCADE)
- `wb_attendance_weekly_stats`: `(org_id, member_idx, att_year, sunday_date)` 유니크
- `wb_attendance_weekly_type_stats`: `(org_id, att_year, sunday_date, att_type_name)` 유니크
- `wb_attendance_yearly_stats`: `(org_id, member_idx, att_year)` 유니크
- `wb_member_weekly_stats`: `(org_id, att_year, sunday_date)` 유니크
- `wb_memo_weekly_type_stats`: `(org_id, att_year, sunday_date, memo_type)` 유니크
- `wb_timeline_weekly_type_stats`: `(org_id, att_year, sunday_date, timeline_type)` 유니크

## 조회 성능에서 자주 쓰는 인덱스 축
- `org_id`
- `org_id + att_year`
- `sunday_date`
- `member_idx`
- `transaction_date`
- `send_date`
- `status` / `del_yn` / `is_active`

## 구현 시 주의사항
- 동일 이름 인덱스(`idx_org_id`, `idx_org_year`)가 여러 테이블에 반복되므로 테이블 단위로 구분해 해석
- JSON 컬럼은 문자열 기반 저장 방식이 혼재되어 있어 파싱 실패 처리 필요
- 날짜 기준 집계는 주차 시작 기준을 `sunday_date`로 통일

## 원본 반영 정책
- DB 구조가 변경되면 이 문서를 먼저 갱신하고, 이후 모델/쿼리/검증 코드를 수정
- 실시간 DB 조회가 불가한 세션에서는 본 문서를 단일 기준 소스로 사용
