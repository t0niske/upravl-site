-- Посты канала MAX по расписанию: бот публикует одобренные посты, когда наступает их время (cron/run.php).
-- status: draft — черновик, approved — одобрен и ждёт времени, published — опубликован, failed — MAX не принял.
CREATE TABLE channel_posts (
    id {{pk}},
    publish_at DATETIME NOT NULL,
    title VARCHAR(150) NOT NULL DEFAULT '',
    text TEXT NOT NULL,
    status VARCHAR(16) NOT NULL DEFAULT 'draft',
    pin SMALLINT NOT NULL DEFAULT 0,
    mid VARCHAR(64) NULL,
    published_at DATETIME NULL,
    error VARCHAR(300) NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    updated_by VARCHAR(100) NULL
){{table}};

CREATE INDEX channel_posts_due ON channel_posts (status, publish_at);
