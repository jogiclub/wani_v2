---
trigger: always_on
glob:
description: DB 테이블 정의 참조 규칙
---

# DB 테이블 정보 참조

- DB 스키마 기준 문서는 `.agent/skills/wani-ci3-ajax/references/db-schema.md`에 저장한다.
- DB 관련 작업 시 위 파일을 우선 확인하고, 없거나 불일치하면 최신 DDL로 갱신한다.
- 컬럼/인덱스/제약조건을 추정으로 작성하지 않고 저장된 DDL을 기준으로 반영한다.
