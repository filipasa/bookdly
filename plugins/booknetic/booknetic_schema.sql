-- =============================================================================
-- Booknetic 5 – Full Database Schema
-- Database  : dbs15761012
-- Table prefix: pznp_bkntc_   (WordPress base prefix: pznp_)
--
-- Table naming convention: {wp_prefix}bkntc_{table_name}
--   e.g.  pznp_bkntc_appointments
--
-- Generated from source: /booknetic 5/app/Models/ + insert patterns
-- =============================================================================

USE `dbs15761012`;

-- ---------------------------------------------------------------------------
-- 1. LOCATIONS CATEGORY  (no FK deps)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pznp_bkntc_location_categories` (
    `id`         INT(11)      NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(255) NOT NULL DEFAULT '',
    `tenant_id`  INT(11)               DEFAULT NULL,
    `created_by` INT(11)               DEFAULT NULL,
    `updated_by` INT(11)               DEFAULT NULL,
    `created_at` INT(11)               DEFAULT NULL,
    `updated_at` INT(11)               DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 2. LOCATIONS
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pznp_bkntc_locations` (
    `id`                  INT(11)       NOT NULL AUTO_INCREMENT,
    `name`                VARCHAR(255)  NOT NULL DEFAULT '',
    `image`               VARCHAR(500)           DEFAULT NULL,
    `address`             VARCHAR(500)           DEFAULT NULL,
    `phone_number`        VARCHAR(50)            DEFAULT NULL,
    `notes`               TEXT                   DEFAULT NULL,
    `latitude`            VARCHAR(50)            DEFAULT NULL,
    `longitude`           VARCHAR(50)            DEFAULT NULL,
    `is_active`           TINYINT(1)    NOT NULL DEFAULT 1,
    `address_components`  TEXT                   DEFAULT NULL,
    `category_id`         INT(11)                DEFAULT NULL,
    `tenant_id`           INT(11)                DEFAULT NULL,
    -- timestamps (Location uses $timeStamps = true)
    `created_at`          DATETIME               DEFAULT NULL,
    `updated_at`          DATETIME               DEFAULT NULL,
    -- ownership (Location uses $enableOwnershipFields = true)
    `created_by`          INT(11)                DEFAULT NULL,
    `updated_by`          INT(11)                DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_location_category` (`category_id`),
    KEY `idx_location_tenant`   (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 3. SERVICE CATEGORIES
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pznp_bkntc_service_categories` (
    `id`        INT(11)      NOT NULL AUTO_INCREMENT,
    `name`      VARCHAR(255) NOT NULL DEFAULT '',
    `parent_id` INT(11)               DEFAULT NULL,
    `tenant_id` INT(11)               DEFAULT NULL,
    `created_by` INT(11)              DEFAULT NULL,
    `updated_by` INT(11)              DEFAULT NULL,
    `created_at` INT(11)              DEFAULT NULL,
    `updated_at` INT(11)              DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_service_category_parent` (`parent_id`),
    KEY `idx_service_category_tenant` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 4. STAFF
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pznp_bkntc_staff` (
    `id`                  INT(11)       NOT NULL AUTO_INCREMENT,
    `name`                VARCHAR(255)  NOT NULL DEFAULT '',
    `user_id`             INT(11)                DEFAULT NULL,
    `email`               VARCHAR(255)           DEFAULT NULL,
    `phone_number`        VARCHAR(50)            DEFAULT NULL,
    `about`               TEXT                   DEFAULT NULL,
    `profile_image`       VARCHAR(500)           DEFAULT NULL,
    `locations`           TEXT                   DEFAULT NULL, -- comma-separated location IDs
    `google_access_token` TEXT                   DEFAULT NULL,
    `is_active`           TINYINT(1)    NOT NULL DEFAULT 1,
    `tenant_id`           INT(11)                DEFAULT NULL,
    `profession`          VARCHAR(255)           DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_staff_user`   (`user_id`),
    KEY `idx_staff_tenant` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 5. SERVICES
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pznp_bkntc_services` (
    `id`                     INT(11)        NOT NULL AUTO_INCREMENT,
    `name`                   VARCHAR(255)   NOT NULL DEFAULT '',
    `price`                  DECIMAL(13,2)  NOT NULL DEFAULT 0.00,
    `category_id`            INT(11)                 DEFAULT NULL,
    `is_visible`             TINYINT(1)     NOT NULL DEFAULT 1,
    `duration`               INT(11)        NOT NULL DEFAULT 0,   -- minutes
    `timeslot_length`        INT(11)                 DEFAULT 0,
    `buffer_before`          INT(11)        NOT NULL DEFAULT 0,   -- minutes
    `buffer_after`           INT(11)        NOT NULL DEFAULT 0,   -- minutes
    `notes`                  TEXT                    DEFAULT NULL,
    `image`                  VARCHAR(500)            DEFAULT NULL,
    `is_recurring`           TINYINT(1)     NOT NULL DEFAULT 0,
    `full_period_type`       ENUM('month','week','day','time') DEFAULT NULL,
    `full_period_value`      INT(11)        NOT NULL DEFAULT 0,
    `repeat_type`            ENUM('monthly','weekly','daily')  DEFAULT NULL,
    `recurring_payment_type` ENUM('first_month','full')        DEFAULT NULL,
    `repeat_frequency`       INT(11)        NOT NULL DEFAULT 0,
    `max_capacity`           INT(11)        NOT NULL DEFAULT 1,
    `color`                  VARCHAR(50)             DEFAULT NULL,
    `deposit_type`           ENUM('percent','price')           DEFAULT NULL,
    `deposit`                DECIMAL(13,2)  NOT NULL DEFAULT 0.00,
    `is_active`              TINYINT(1)     NOT NULL DEFAULT 1,
    `hide_price`             TINYINT(1)     NOT NULL DEFAULT 0,
    `hide_duration`          TINYINT(1)     NOT NULL DEFAULT 0,
    `tenant_id`              INT(11)                 DEFAULT NULL,
    `created_by`             INT(11)                 DEFAULT NULL,
    `updated_by`             INT(11)                 DEFAULT NULL,
    `created_at`             INT(11)                 DEFAULT NULL,
    `updated_at`             INT(11)                 DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_service_category` (`category_id`),
    KEY `idx_service_tenant`   (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 6. SERVICE EXTRAS CATEGORIES
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pznp_bkntc_service_extra_categories` (
    `id`        INT(11)      NOT NULL AUTO_INCREMENT,
    `name`      VARCHAR(255) NOT NULL DEFAULT '',
    `parent_id` INT(11)               DEFAULT NULL,
    `tenant_id` INT(11)               DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_extra_cat_tenant` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 7. SERVICE EXTRAS
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pznp_bkntc_service_extras` (
    `id`            INT(11)       NOT NULL AUTO_INCREMENT,
    `service_id`    INT(11)                DEFAULT NULL,
    `name`          VARCHAR(255)  NOT NULL DEFAULT '',
    `image`         VARCHAR(500)           DEFAULT NULL,
    `price`         DECIMAL(13,2) NOT NULL DEFAULT 0.00,
    `duration`      INT(11)       NOT NULL DEFAULT 0,   -- minutes
    `max_quantity`  INT(11)       NOT NULL DEFAULT 1,
    `min_quantity`  INT(11)       NOT NULL DEFAULT 0,
    `is_active`     TINYINT(1)   NOT NULL DEFAULT 1,
    `hide_duration` TINYINT(1)   NOT NULL DEFAULT 0,
    `hide_price`    TINYINT(1)   NOT NULL DEFAULT 0,
    `notes`         TEXT                   DEFAULT NULL,
    `category_id`   INT(11)                DEFAULT NULL,
    `tenant_id`     INT(11)                DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_service_extra_service`  (`service_id`),
    KEY `idx_service_extra_category` (`category_id`),
    KEY `idx_service_extra_tenant`   (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 8. SERVICE STAFF  (pivot: service ↔ staff, with per-staff pricing)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pznp_bkntc_service_staff` (
    `id`           INT(11)       NOT NULL AUTO_INCREMENT,
    `service_id`   INT(11)       NOT NULL,
    `staff_id`     INT(11)       NOT NULL,
    `price`        DECIMAL(13,2)          DEFAULT -1,  -- -1 means "use service price"
    `deposit`      DECIMAL(13,2)          DEFAULT -1,
    `deposit_type` ENUM('percent','price') DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_service_staff_service` (`service_id`),
    KEY `idx_service_staff_staff`   (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 9. CUSTOMER CATEGORIES
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pznp_bkntc_customer_categories` (
    `id`         INT(11)      NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(255) NOT NULL DEFAULT '',
    `color`      VARCHAR(50)           DEFAULT NULL,
    `icon`       VARCHAR(255)          DEFAULT NULL,
    `is_default` TINYINT(1)   NOT NULL DEFAULT 0,
    `note`       TEXT                  DEFAULT NULL,
    `tenant_id`  INT(11)               DEFAULT NULL,
    -- timestamps (CustomerCategory uses $timeStamps = true)
    `created_at` DATETIME              DEFAULT NULL,
    `updated_at` DATETIME              DEFAULT NULL,
    -- ownership (CustomerCategory uses $enableOwnershipFields = true)
    `created_by` INT(11)               DEFAULT NULL,
    `updated_by` INT(11)               DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_customer_cat_tenant` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 10. CUSTOMERS
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pznp_bkntc_customers` (
    `id`            INT(11)      NOT NULL AUTO_INCREMENT,
    `user_id`       INT(11)               DEFAULT NULL,  -- WP user ID
    `first_name`    VARCHAR(255) NOT NULL DEFAULT '',
    `last_name`     VARCHAR(255)          DEFAULT NULL,
    `phone_number`  VARCHAR(50)           DEFAULT NULL,
    `email`         VARCHAR(255)          DEFAULT NULL,
    `birthdate`     DATE                  DEFAULT NULL,
    `notes`         TEXT                  DEFAULT NULL,
    `profile_image` VARCHAR(500)          DEFAULT NULL,
    `gender`        ENUM('male','female') DEFAULT NULL,
    `category_id`   INT(11)               DEFAULT NULL,
    `tenant_id`     INT(11)               DEFAULT NULL,
    `created_by`    INT(11)               DEFAULT NULL,
    `created_at`    INT(11)               DEFAULT NULL,  -- unix timestamp
    PRIMARY KEY (`id`),
    KEY `idx_customer_user`     (`user_id`),
    KEY `idx_customer_category` (`category_id`),
    KEY `idx_customer_tenant`   (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 11. APPOINTMENTS
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pznp_bkntc_appointments` (
    `id`              INT(11)       NOT NULL AUTO_INCREMENT,
    `location_id`     INT(11)                DEFAULT NULL,
    `service_id`      INT(11)       NOT NULL,
    `staff_id`        INT(11)       NOT NULL,
    `customer_id`     INT(11)       NOT NULL,
    `status`          VARCHAR(50)   NOT NULL DEFAULT 'pending',
    -- Unix timestamps for scheduling
    `starts_at`       INT(11)       NOT NULL,
    `ends_at`         INT(11)       NOT NULL,
    `busy_from`       INT(11)       NOT NULL,
    `busy_to`         INT(11)       NOT NULL,
    -- Group booking weight (for capacity)
    `weight`          INT(11)       NOT NULL DEFAULT 1,
    -- Payment
    `payment_id`      VARCHAR(255)           DEFAULT NULL,  -- shared MD5 across cart
    `recurring_id`    VARCHAR(255)           DEFAULT NULL,  -- shared MD5 for recurring set
    `payment_method`  VARCHAR(100)           DEFAULT 'local',
    `payment_status`  VARCHAR(50)            DEFAULT 'not_paid',
    `paid_amount`     DECIMAL(13,2) NOT NULL DEFAULT 0.00,
    -- Meta
    `note`            TEXT                   DEFAULT NULL,
    `locale`          VARCHAR(20)            DEFAULT NULL,
    `client_timezone` VARCHAR(100)           DEFAULT NULL,
    `created_at`      INT(11)                DEFAULT NULL,  -- unix timestamp
    `tenant_id`       INT(11)                DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_appointment_service`   (`service_id`),
    KEY `idx_appointment_staff`     (`staff_id`),
    KEY `idx_appointment_customer`  (`customer_id`),
    KEY `idx_appointment_location`  (`location_id`),
    KEY `idx_appointment_status`    (`status`),
    KEY `idx_appointment_starts`    (`starts_at`),
    KEY `idx_appointment_payment`   (`payment_id`),
    KEY `idx_appointment_recurring` (`recurring_id`),
    KEY `idx_appointment_tenant`    (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 12. APPOINTMENT EXTRAS  (extras selected for an appointment)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pznp_bkntc_appointment_extras` (
    `id`             INT(11)       NOT NULL AUTO_INCREMENT,
    `appointment_id` INT(11)       NOT NULL,
    `extra_id`       INT(11)       NOT NULL,
    `quantity`       INT(11)       NOT NULL DEFAULT 1,
    `price`          DECIMAL(13,2) NOT NULL DEFAULT 0.00,
    `duration`       INT(11)       NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_appt_extra_appointment` (`appointment_id`),
    KEY `idx_appt_extra_extra`       (`extra_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 13. APPOINTMENT PRICES  (itemised price breakdown per appointment)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pznp_bkntc_appointment_prices` (
    `id`                  INT(11)       NOT NULL AUTO_INCREMENT,
    `appointment_id`      INT(11)       NOT NULL,
    `unique_key`          VARCHAR(255)  NOT NULL,   -- e.g. 'base', 'extra_1', 'coupon' …
    `price`               DECIMAL(13,2) NOT NULL DEFAULT 0.00,
    `negative_or_positive` TINYINT(1)   NOT NULL DEFAULT 1,  -- 1 = add, -1 = subtract
    PRIMARY KEY (`id`),
    KEY `idx_appt_price_appointment` (`appointment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 14. TIMESHEET  (weekly schedule – per staff and/or per service)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pznp_bkntc_timesheet` (
    `id`         INT(11) NOT NULL AUTO_INCREMENT,
    `service_id` INT(11)          DEFAULT NULL,
    `staff_id`   INT(11)          DEFAULT NULL,
    `timesheet`  LONGTEXT         DEFAULT NULL,  -- JSON encoded schedule
    `tenant_id`  INT(11)          DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_timesheet_service` (`service_id`),
    KEY `idx_timesheet_staff`   (`staff_id`),
    KEY `idx_timesheet_tenant`  (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 15. SPECIAL DAYS  (override schedule for a specific date)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pznp_bkntc_special_days` (
    `id`         INT(11)     NOT NULL AUTO_INCREMENT,
    `service_id` INT(11)              DEFAULT NULL,
    `staff_id`   INT(11)              DEFAULT NULL,
    `date`       DATE        NOT NULL,
    `timesheet`  LONGTEXT             DEFAULT NULL,  -- JSON encoded day schedule
    `tenant_id`  INT(11)              DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_special_day_service` (`service_id`),
    KEY `idx_special_day_staff`   (`staff_id`),
    KEY `idx_special_day_date`    (`date`),
    KEY `idx_special_day_tenant`  (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 16. HOLIDAYS  (global or per-staff/service day off)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pznp_bkntc_holidays` (
    `id`         INT(11) NOT NULL AUTO_INCREMENT,
    `date`       DATE    NOT NULL,
    `service_id` INT(11)          DEFAULT NULL,
    `staff_id`   INT(11)          DEFAULT NULL,
    `tenant_id`  INT(11)          DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_holiday_date`    (`date`),
    KEY `idx_holiday_service` (`service_id`),
    KEY `idx_holiday_staff`   (`staff_id`),
    KEY `idx_holiday_tenant`  (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 17. CORE STAFF BUSY SLOTS  (external calendar / manual blocks)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pznp_bkntc_core_staff_busy_slots` (
    `id`         INT(11)      NOT NULL AUTO_INCREMENT,
    `staff_id`   INT(11)      NOT NULL,
    `date`       INT(11)      NOT NULL,        -- unix timestamp (day boundary)
    `start_time` INT(11)      NOT NULL,        -- seconds from midnight
    `duration`   INT(11)      NOT NULL DEFAULT 0,
    `notes`      TEXT                  DEFAULT NULL,
    `event_id`   VARCHAR(255)          DEFAULT NULL,  -- external calendar event ID
    `module`     VARCHAR(100)          DEFAULT NULL,  -- e.g. 'google_calendar'
    `tenant_id`  INT(11)               DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_busy_slot_staff`  (`staff_id`),
    KEY `idx_busy_slot_date`   (`date`),
    KEY `idx_busy_slot_tenant` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 18. WORKFLOWS
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pznp_bkntc_workflows` (
    `id`        INT(11)      NOT NULL AUTO_INCREMENT,
    `name`      VARCHAR(255) NOT NULL DEFAULT '',
    `when`      VARCHAR(255)          DEFAULT NULL,  -- trigger event slug
    `data`      LONGTEXT              DEFAULT NULL,  -- JSON config
    `is_active` TINYINT(1)   NOT NULL DEFAULT 1,
    `tenant_id` INT(11)               DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_workflow_tenant` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 19. WORKFLOW ACTIONS  (driver actions attached to a workflow)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pznp_bkntc_workflow_actions` (
    `id`          INT(11)      NOT NULL AUTO_INCREMENT,
    `workflow_id` INT(11)      NOT NULL,
    `driver`      VARCHAR(100) NOT NULL DEFAULT '',  -- e.g. 'email', 'sms', 'webhook'
    `data`        LONGTEXT              DEFAULT NULL, -- JSON driver config
    `is_active`   TINYINT(1)  NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    KEY `idx_workflow_action_workflow` (`workflow_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 20. WORKFLOW LOGS  (execution history per action)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pznp_bkntc_workflow_logs` (
    `id`            INT(11)      NOT NULL AUTO_INCREMENT,
    `workflow_id`   INT(11)               DEFAULT NULL,
    `when`          VARCHAR(255)          DEFAULT NULL,
    `driver`        VARCHAR(100)          DEFAULT NULL,
    `date_time`     DATETIME              DEFAULT NULL,
    `data`          LONGTEXT              DEFAULT NULL,   -- action config snapshot (JSON)
    `event_data`    LONGTEXT              DEFAULT NULL,   -- appointment/event payload (JSON)
    `status`        ENUM('success','error') NOT NULL DEFAULT 'success',
    `error_message` TEXT                  DEFAULT NULL,
    `tenant_id`     INT(11)               DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_workflow_log_workflow` (`workflow_id`),
    KEY `idx_workflow_log_tenant`  (`tenant_id`),
    KEY `idx_workflow_log_status`  (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 21. NOTIFICATIONS  (in-app bell notifications per user)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pznp_bkntc_notifications` (
    `id`          INT(11)      NOT NULL AUTO_INCREMENT,
    `tenant_id`   INT(11)               DEFAULT NULL,
    `user_id`     INT(11)      NOT NULL,
    `type`        VARCHAR(100)          DEFAULT NULL,
    `title`       VARCHAR(500)          DEFAULT NULL,
    `message`     TEXT                  DEFAULT NULL,
    `action_type` VARCHAR(100)          DEFAULT NULL,
    `action_data` TEXT                  DEFAULT NULL,
    `read_at`     DATETIME              DEFAULT NULL,
    `created_at`  DATETIME              DEFAULT NULL,
    `updated_at`  DATETIME              DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_notification_user`   (`user_id`),
    KEY `idx_notification_tenant` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 22. APPEARANCE  (front-end widget theming per tenant)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pznp_bkntc_appearance` (
    `id`        INT(11)      NOT NULL AUTO_INCREMENT,
    `data_key`  VARCHAR(255) NOT NULL,
    `data_value` LONGTEXT    DEFAULT NULL,
    `tenant_id` INT(11)               DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_appearance_key_tenant` (`data_key`, `tenant_id`),
    KEY `idx_appearance_tenant` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 23. DATA  (key-value EAV store attached to any model row)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pznp_bkntc_data` (
    `id`         INT(11)      NOT NULL AUTO_INCREMENT,
    `table_name` VARCHAR(100) NOT NULL,            -- e.g. 'appointments', 'services'
    `row_id`     INT(11)      NOT NULL,
    `data_key`   VARCHAR(255) NOT NULL,
    `data_value` LONGTEXT              DEFAULT NULL,
    `tenant_id`  INT(11)               DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_data_lookup`  (`table_name`, `row_id`, `data_key`),
    KEY `idx_data_tenant`  (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 24. TRANSLATIONS  (i18n overrides stored in DB)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pznp_bkntc_translations` (
    `id`          INT(11)      NOT NULL AUTO_INCREMENT,
    `table_name`  VARCHAR(100) NOT NULL,           -- e.g. 'services', 'options'
    `column_name` VARCHAR(255) NOT NULL,
    `row_id`      INT(11)               DEFAULT NULL,
    `locale`      VARCHAR(20)           DEFAULT NULL,
    `value`       LONGTEXT              DEFAULT NULL,
    `tenant_id`   INT(11)               DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_translation_lookup` (`table_name`, `column_name`, `row_id`, `locale`),
    KEY `idx_translation_tenant` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 25. CART  (temporary booking sessions before confirmation)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pznp_bkntc_cart` (
    `id`         INT(11)      NOT NULL AUTO_INCREMENT,
    `slug`       VARCHAR(255)          DEFAULT NULL,  -- session key
    `active`     TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at` INT(11)               DEFAULT NULL,
    `removed_at` INT(11)               DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_cart_slug`   (`slug`),
    KEY `idx_cart_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- End of schema
-- =============================================================================
