-- LodgeX CH-11 Service Rating reference schema
-- Working Draft 0.1 — adapt primary keys, tenant scoping, timestamps, soft-delete
-- conventions and naming to the existing LodgeX repository.
-- This file is a logical reference, not a drop-in production migration.

CREATE TABLE service_rating_policies (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tenant_id BIGINT UNSIGNED NOT NULL,
    project_id BIGINT UNSIGNED NOT NULL,
    policy_code VARCHAR(100) NOT NULL,
    name VARCHAR(255) NOT NULL,
    status VARCHAR(30) NOT NULL,
    current_version_id BIGINT UNSIGNED NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    UNIQUE KEY uq_service_rating_policy_code (tenant_id, project_id, policy_code),
    KEY idx_service_rating_policy_project (tenant_id, project_id, status)
);

CREATE TABLE service_rating_policy_versions (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    service_rating_policy_id BIGINT UNSIGNED NOT NULL,
    version VARCHAR(50) NOT NULL,
    status VARCHAR(30) NOT NULL,
    effective_from DATETIME NULL,
    effective_to DATETIME NULL,
    time_zone VARCHAR(100) NOT NULL,
    policy_json JSON NOT NULL,
    policy_hash CHAR(64) NOT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    approved_by BIGINT UNSIGNED NULL,
    approved_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    UNIQUE KEY uq_service_rating_policy_version (service_rating_policy_id, version),
    KEY idx_service_rating_policy_effective (service_rating_policy_id, status, effective_from, effective_to)
);

ALTER TABLE service_rating_policies
    ADD CONSTRAINT fk_service_rating_current_version
    FOREIGN KEY (current_version_id) REFERENCES service_rating_policy_versions(id);

CREATE TABLE company_project_service_ratings (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tenant_id BIGINT UNSIGNED NOT NULL,
    company_id BIGINT UNSIGNED NOT NULL,
    project_id BIGINT UNSIGNED NOT NULL,
    current_published_snapshot_id BIGINT UNSIGNED NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'active',
    activated_at DATETIME NULL,
    ended_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    UNIQUE KEY uq_company_project_service_rating (tenant_id, company_id, project_id),
    KEY idx_company_service_rating (tenant_id, company_id, status),
    KEY idx_project_service_rating (tenant_id, project_id, status)
);

CREATE TABLE service_rating_snapshots (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tenant_id BIGINT UNSIGNED NOT NULL,
    company_project_service_rating_id BIGINT UNSIGNED NOT NULL,
    policy_version_id BIGINT UNSIGNED NOT NULL,
    sequence_no INT UNSIGNED NOT NULL,
    evaluation_window_start DATETIME NOT NULL,
    evaluation_window_end DATETIME NOT NULL,
    evidence_cutoff_at DATETIME NOT NULL,
    overall_grade CHAR(1) NULL,
    calculation_status VARCHAR(30) NOT NULL,
    publication_status VARCHAR(30) NOT NULL,
    data_quality_status VARCHAR(30) NOT NULL,
    evidence_fingerprint CHAR(64) NULL,
    calculation_key CHAR(64) NOT NULL,
    calculation_trace JSON NOT NULL,
    prior_snapshot_id BIGINT UNSIGNED NULL,
    superseded_by_snapshot_id BIGINT UNSIGNED NULL,
    calculated_by_type VARCHAR(50) NOT NULL,
    calculated_by_id VARCHAR(100) NULL,
    calculated_at DATETIME NOT NULL,
    published_by BIGINT UNSIGNED NULL,
    published_at DATETIME NULL,
    correlation_id VARCHAR(100) NOT NULL,
    created_at TIMESTAMP NOT NULL,
    UNIQUE KEY uq_service_rating_calculation_key (calculation_key),
    UNIQUE KEY uq_service_rating_snapshot_sequence (company_project_service_rating_id, sequence_no),
    KEY idx_service_rating_snapshot_current (company_project_service_rating_id, publication_status, calculated_at),
    KEY idx_service_rating_snapshot_window (evaluation_window_start, evaluation_window_end),
    CONSTRAINT fk_snapshot_company_project_rating
        FOREIGN KEY (company_project_service_rating_id) REFERENCES company_project_service_ratings(id),
    CONSTRAINT fk_snapshot_policy_version
        FOREIGN KEY (policy_version_id) REFERENCES service_rating_policy_versions(id),
    CONSTRAINT fk_snapshot_prior
        FOREIGN KEY (prior_snapshot_id) REFERENCES service_rating_snapshots(id),
    CONSTRAINT fk_snapshot_superseded_by
        FOREIGN KEY (superseded_by_snapshot_id) REFERENCES service_rating_snapshots(id)
);

ALTER TABLE company_project_service_ratings
    ADD CONSTRAINT fk_company_project_current_snapshot
    FOREIGN KEY (current_published_snapshot_id) REFERENCES service_rating_snapshots(id);

CREATE TABLE service_rating_criterion_results (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    snapshot_id BIGINT UNSIGNED NOT NULL,
    criterion_code VARCHAR(50) NOT NULL,
    applicable BOOLEAN NOT NULL,
    applicability_reason_code VARCHAR(100) NULL,
    grade CHAR(1) NULL,
    numerator DECIMAL(18,6) NULL,
    denominator DECIMAL(18,6) NULL,
    measured_value DECIMAL(18,6) NULL,
    measured_unit VARCHAR(50) NULL,
    threshold_code VARCHAR(100) NULL,
    threshold_json JSON NULL,
    driver_summary VARCHAR(1000) NULL,
    result_trace JSON NOT NULL,
    data_quality_status VARCHAR(30) NOT NULL,
    exception_count INT UNSIGNED NOT NULL DEFAULT 0,
    critical_override_applied BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP NOT NULL,
    UNIQUE KEY uq_snapshot_criterion (snapshot_id, criterion_code),
    KEY idx_criterion_grade (criterion_code, grade),
    CONSTRAINT fk_criterion_snapshot
        FOREIGN KEY (snapshot_id) REFERENCES service_rating_snapshots(id)
);

CREATE TABLE service_rating_evidence_links (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    snapshot_id BIGINT UNSIGNED NOT NULL,
    criterion_result_id BIGINT UNSIGNED NULL,
    source_product VARCHAR(50) NOT NULL,
    source_record_type VARCHAR(100) NOT NULL,
    source_record_id VARCHAR(100) NOT NULL,
    source_record_version VARCHAR(100) NULL,
    source_effective_at DATETIME NULL,
    source_observed_at DATETIME NULL,
    data_classification VARCHAR(50) NOT NULL,
    evidence_hash CHAR(64) NULL,
    display_summary VARCHAR(1000) NULL,
    evidence_metadata JSON NULL,
    created_at TIMESTAMP NOT NULL,
    KEY idx_evidence_snapshot (snapshot_id),
    KEY idx_evidence_source (source_product, source_record_type, source_record_id),
    CONSTRAINT fk_evidence_snapshot
        FOREIGN KEY (snapshot_id) REFERENCES service_rating_snapshots(id),
    CONSTRAINT fk_evidence_criterion
        FOREIGN KEY (criterion_result_id) REFERENCES service_rating_criterion_results(id)
);

CREATE TABLE service_rating_exceptions (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tenant_id BIGINT UNSIGNED NOT NULL,
    company_id BIGINT UNSIGNED NOT NULL,
    project_id BIGINT UNSIGNED NOT NULL,
    criterion_code VARCHAR(50) NOT NULL,
    exception_type VARCHAR(100) NOT NULL,
    reason_code VARCHAR(100) NOT NULL,
    reason_text TEXT NOT NULL,
    scope_json JSON NOT NULL,
    evidence_json JSON NULL,
    effective_from DATETIME NOT NULL,
    effective_to DATETIME NOT NULL,
    status VARCHAR(30) NOT NULL,
    requested_by BIGINT UNSIGNED NOT NULL,
    approved_by BIGINT UNSIGNED NULL,
    approved_at DATETIME NULL,
    revoked_by BIGINT UNSIGNED NULL,
    revoked_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    KEY idx_exception_scope (tenant_id, project_id, company_id, criterion_code, status),
    KEY idx_exception_effective (effective_from, effective_to)
);

CREATE TABLE service_rating_critical_overrides (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tenant_id BIGINT UNSIGNED NOT NULL,
    company_id BIGINT UNSIGNED NOT NULL,
    project_id BIGINT UNSIGNED NOT NULL,
    criterion_code VARCHAR(50) NOT NULL,
    critical_rule_code VARCHAR(100) NOT NULL,
    status VARCHAR(30) NOT NULL,
    scope_json JSON NOT NULL,
    evidence_json JSON NOT NULL,
    containment_action TEXT NULL,
    effective_from DATETIME NOT NULL,
    effective_to DATETIME NULL,
    requested_by BIGINT UNSIGNED NOT NULL,
    approved_by BIGINT UNSIGNED NULL,
    second_approved_by BIGINT UNSIGNED NULL,
    approved_at DATETIME NULL,
    resolved_by BIGINT UNSIGNED NULL,
    resolved_at DATETIME NULL,
    resolution_reason TEXT NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    KEY idx_critical_override_scope (tenant_id, project_id, company_id, status),
    KEY idx_critical_override_effective (effective_from, effective_to)
);

CREATE TABLE service_rating_review_requests (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tenant_id BIGINT UNSIGNED NOT NULL,
    snapshot_id BIGINT UNSIGNED NOT NULL,
    company_id BIGINT UNSIGNED NOT NULL,
    project_id BIGINT UNSIGNED NOT NULL,
    status VARCHAR(30) NOT NULL,
    priority VARCHAR(30) NOT NULL,
    reason_code VARCHAR(100) NOT NULL,
    criteria_json JSON NOT NULL,
    challenged_evidence_json JSON NOT NULL,
    company_statement TEXT NOT NULL,
    requested_treatment TEXT NULL,
    submitted_by BIGINT UNSIGNED NOT NULL,
    submitted_at DATETIME NOT NULL,
    assigned_to BIGINT UNSIGNED NULL,
    assigned_at DATETIME NULL,
    response_due_at DATETIME NULL,
    decision_code VARCHAR(100) NULL,
    decision_findings TEXT NULL,
    decided_by BIGINT UNSIGNED NULL,
    co_approved_by BIGINT UNSIGNED NULL,
    decided_at DATETIME NULL,
    resulting_snapshot_id BIGINT UNSIGNED NULL,
    closed_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    KEY idx_review_queue (tenant_id, project_id, status, response_due_at),
    KEY idx_review_company (tenant_id, company_id, status),
    CONSTRAINT fk_review_snapshot
        FOREIGN KEY (snapshot_id) REFERENCES service_rating_snapshots(id),
    CONSTRAINT fk_review_resulting_snapshot
        FOREIGN KEY (resulting_snapshot_id) REFERENCES service_rating_snapshots(id)
);

CREATE TABLE service_rating_review_messages (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    review_request_id BIGINT UNSIGNED NOT NULL,
    message_type VARCHAR(50) NOT NULL,
    body TEXT NOT NULL,
    attachment_refs JSON NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL,
    KEY idx_review_message (review_request_id, created_at),
    CONSTRAINT fk_review_message_request
        FOREIGN KEY (review_request_id) REFERENCES service_rating_review_requests(id)
);

CREATE TABLE service_rating_corrective_actions (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tenant_id BIGINT UNSIGNED NOT NULL,
    company_id BIGINT UNSIGNED NOT NULL,
    project_id BIGINT UNSIGNED NOT NULL,
    source_snapshot_id BIGINT UNSIGNED NULL,
    criterion_code VARCHAR(50) NULL,
    severity VARCHAR(30) NOT NULL,
    status VARCHAR(50) NOT NULL,
    issue_statement TEXT NOT NULL,
    containment TEXT NULL,
    root_cause TEXT NULL,
    corrective_action TEXT NULL,
    preventive_action TEXT NULL,
    company_owner_id BIGINT UNSIGNED NULL,
    project_verifier_id BIGINT UNSIGNED NULL,
    due_at DATETIME NULL,
    evidence_json JSON NULL,
    effectiveness_check TEXT NULL,
    verified_by BIGINT UNSIGNED NULL,
    verified_at DATETIME NULL,
    closed_at DATETIME NULL,
    reopened_at DATETIME NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    KEY idx_corrective_action_company (tenant_id, company_id, status, due_at),
    KEY idx_corrective_action_project (tenant_id, project_id, status, due_at),
    CONSTRAINT fk_corrective_action_snapshot
        FOREIGN KEY (source_snapshot_id) REFERENCES service_rating_snapshots(id)
);

CREATE TABLE service_rating_manual_evidence (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tenant_id BIGINT UNSIGNED NOT NULL,
    company_id BIGINT UNSIGNED NOT NULL,
    project_id BIGINT UNSIGNED NOT NULL,
    criterion_code VARCHAR(50) NOT NULL,
    evidence_type VARCHAR(100) NOT NULL,
    measured_fact_json JSON NOT NULL,
    source_organization VARCHAR(255) NOT NULL,
    source_reference VARCHAR(255) NOT NULL,
    attachment_refs JSON NULL,
    data_classification VARCHAR(50) NOT NULL,
    effective_from DATETIME NOT NULL,
    effective_to DATETIME NULL,
    status VARCHAR(30) NOT NULL,
    entered_by BIGINT UNSIGNED NOT NULL,
    verified_by BIGINT UNSIGNED NULL,
    verified_at DATETIME NULL,
    superseded_by_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL,
    KEY idx_manual_evidence_scope (tenant_id, project_id, company_id, criterion_code, status),
    CONSTRAINT fk_manual_evidence_superseded
        FOREIGN KEY (superseded_by_id) REFERENCES service_rating_manual_evidence(id)
);
