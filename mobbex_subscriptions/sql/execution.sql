CREATE TABLE IF NOT EXISTS PREFIX_mobbex_execution (
    `uid` VARCHAR(255) NOT NULL PRIMARY KEY,
    `subscription_uid` TEXT NOT NULL,
    `subscriber_uid` TEXT NOT NULL,
    `status` TINYINT NOT NULL,
    `total` DECIMAL(18,2) NOT NULL,
    `date` DATETIME NOT NULL,
    `data` TEXT NOT NULL
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8;