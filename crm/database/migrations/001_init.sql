-- CRM bankrotstvo70.ru: начальная схема (основа — CRM «Авто-Вираж», без SMS, MAX, очереди звонков).
-- SQL общий для SQLite и MySQL. Мигратор подставляет:
--   {{pk}}    — автоинкрементный первичный ключ;
--   {{table}} — параметры таблицы (для MySQL — InnoDB и utf8mb4).
-- Время хранится строкой 'Y-m-d H:i:s' в часовом поясе Томска (Asia/Tomsk).

CREATE TABLE staff (
    id {{pk}},
    login VARCHAR(64) NOT NULL,
    name VARCHAR(100) NOT NULL,
    role VARCHAR(16) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    active SMALLINT NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    last_login_at DATETIME NULL
){{table}};

CREATE UNIQUE INDEX staff_login ON staff (login);

-- Заявки с формы сайта (public/lead.php дописывает их сюда, письмо и MAX уходят как раньше).
CREATE TABLE leads (
    id {{pk}},
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    name VARCHAR(100) NOT NULL DEFAULT '',
    phone VARCHAR(16) NOT NULL,
    phone_raw VARCHAR(64) NULL,
    topic VARCHAR(150) NULL,
    page_url VARCHAR(300) NULL,
    message TEXT NULL,
    quiz VARCHAR(600) NULL,
    source VARCHAR(120) NOT NULL DEFAULT 'Сайт',
    ip VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    status VARCHAR(16) NOT NULL DEFAULT 'new',
    closed_at DATETIME NULL,
    visitor_id VARCHAR(40) NULL,
    visit_id VARCHAR(40) NULL
){{table}};

CREATE INDEX leads_created ON leads (created_at);
CREATE INDEX leads_status ON leads (status, created_at);
CREATE INDEX leads_phone ON leads (phone);
CREATE INDEX leads_visitor ON leads (visitor_id);

-- История заявки: поступила, смена статуса, комментарии.
CREATE TABLE lead_events (
    id {{pk}},
    lead_id INT NOT NULL,
    created_at DATETIME NOT NULL,
    type VARCHAR(24) NOT NULL,
    staff_id INT NULL,
    actor VARCHAR(100) NULL,
    text TEXT NULL,
    FOREIGN KEY (lead_id) REFERENCES leads (id) ON DELETE CASCADE,
    FOREIGN KEY (staff_id) REFERENCES staff (id) ON DELETE SET NULL
){{table}};

CREATE INDEX lead_events_lead ON lead_events (lead_id, id);

-- Журнал действий в админке.
CREATE TABLE audit_log (
    id {{pk}},
    created_at DATETIME NOT NULL,
    staff_id INT NULL,
    actor VARCHAR(100) NULL,
    action VARCHAR(32) NOT NULL,
    lead_id INT NULL,
    details VARCHAR(1000) NULL,
    ip VARCHAR(45) NULL
){{table}};

CREATE INDEX audit_log_created ON audit_log (created_at);

-- Счётчики для лимитов частоты (счётчик заходов, вход в админку).
CREATE TABLE rate_hits (
    id {{pk}},
    bucket VARCHAR(190) NOT NULL,
    created_at DATETIME NOT NULL
){{table}};

CREATE INDEX rate_hits_bucket ON rate_hits (bucket, created_at);
