-- TaskDesk database schema
-- Target: MySQL 5.7+ / MariaDB 10+
-- Run this in phpMyAdmin or via:
--   mysql -u root -p < database/schema.sql

DROP DATABASE IF EXISTS taskdesk;
CREATE DATABASE taskdesk
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE taskdesk;


-- Users

CREATE TABLE users (
    user_id       INT AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100)        NOT NULL,
    email         VARCHAR(150)        NOT NULL UNIQUE,
    password      VARCHAR(255)        NOT NULL,
    role          ENUM('admin','developer') NOT NULL DEFAULT 'developer',
    created_at    TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;


-- Tickets
-- Supports parent/child (subtask) relationship via parent_ticket_id.

CREATE TABLE tickets (
    ticket_id         INT AUTO_INCREMENT PRIMARY KEY,
    title             VARCHAR(200)    NOT NULL,
    description       TEXT,
    status            ENUM('open','in_progress','resolved','closed')
                        NOT NULL DEFAULT 'open',
    priority          ENUM('low','medium','high')
                        NOT NULL DEFAULT 'medium',
    parent_ticket_id  INT             NULL,
    assigned_user_id  INT             NULL,
    created_by        INT             NOT NULL,
    due_date          DATE            NULL,
    created_at        TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP
                                      ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_tickets_parent
        FOREIGN KEY (parent_ticket_id) REFERENCES tickets(ticket_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_tickets_assigned_user
        FOREIGN KEY (assigned_user_id) REFERENCES users(user_id)
        ON DELETE SET NULL,
    CONSTRAINT fk_tickets_created_by
        FOREIGN KEY (created_by) REFERENCES users(user_id)
        ON DELETE CASCADE,

    INDEX idx_tickets_status   (status),
    INDEX idx_tickets_priority (priority),
    INDEX idx_tickets_due_date (due_date),
    INDEX idx_tickets_assigned (assigned_user_id)
) ENGINE=InnoDB;



-- Supports threaded replies via parent_comment_id.

CREATE TABLE comments (
    comment_id         INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id          INT             NOT NULL,
    parent_comment_id  INT             NULL,
    user_id            INT             NOT NULL,
    comment_text       TEXT            NOT NULL,
    created_at         TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_comments_ticket
        FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_comments_parent
        FOREIGN KEY (parent_comment_id) REFERENCES comments(comment_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_comments_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE,

    INDEX idx_comments_ticket (ticket_id)
) ENGINE=InnoDB;


-- Ticket history (audit log)
-- One row per significant event so the ticket detail page can show
-- "who changed what, when".

CREATE TABLE ticket_history (
    history_id    INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id     INT             NOT NULL,
    user_id       INT             NULL,
    event_type    ENUM('created','status_changed','priority_changed',
                       'assignee_changed','edited','deleted')
                                  NOT NULL,
    old_value     VARCHAR(150)    NULL,
    new_value     VARCHAR(150)    NULL,
    created_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_history_ticket
        FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_history_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE SET NULL,

    INDEX idx_history_ticket (ticket_id)
) ENGINE=InnoDB;
