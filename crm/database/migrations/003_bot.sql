-- Бот MAX: собеседники, переписка, защита от повторной доставки событий.

-- Собеседник бота (личный диалог). state — шаг сценария, data — JSON (ответы квиза, черновик записи).
CREATE TABLE bot_users (
    id {{pk}},
    max_user_id VARCHAR(32) NOT NULL,
    chat_id VARCHAR(32) NULL,
    name VARCHAR(100) NOT NULL DEFAULT '',
    source VARCHAR(120) NOT NULL DEFAULT 'MAX-бот',
    state VARCHAR(32) NOT NULL DEFAULT 'menu',
    data TEXT NULL,
    consent_at DATETIME NULL,
    lead_id INT NULL,
    ai_day VARCHAR(10) NULL,
    ai_count INT NOT NULL DEFAULT 0,
    stopped SMALLINT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
){{table}};

CREATE UNIQUE INDEX bot_users_max ON bot_users (max_user_id);
CREATE INDEX bot_users_updated ON bot_users (updated_at);

-- Переписка: in — человек, out — бот (готовые тексты), ai — ответ ИИ.
CREATE TABLE bot_messages (
    id {{pk}},
    bot_user_id INT NOT NULL,
    created_at DATETIME NOT NULL,
    dir VARCHAR(3) NOT NULL,
    text TEXT NULL,
    FOREIGN KEY (bot_user_id) REFERENCES bot_users (id) ON DELETE CASCADE
){{table}};

CREATE INDEX bot_messages_user ON bot_messages (bot_user_id, id);

-- MAX повторяет событие, если не получил ответ вовремя: обработанные запоминаем на двое суток.
CREATE TABLE bot_updates (
    id VARCHAR(64) NOT NULL PRIMARY KEY,
    created_at DATETIME NOT NULL
){{table}};

CREATE INDEX bot_updates_created ON bot_updates (created_at);
