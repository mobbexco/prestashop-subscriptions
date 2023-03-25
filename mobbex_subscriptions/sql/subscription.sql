CREATE TABLE IF NOT EXISTS PREFIX_mobbex_subscription (
    `product_id` INT(11) NOT NULL PRIMARY KEY,
    `uid` TEXT NOT NULL,
    `type` TEXT NOT NULL,
    `state` TINYINT NOT NULL,
    `interval` TEXT NOT NULL,
    `name` TEXT NOT NULL,
    `description` TEXT NOT NULL,
    `total` DECIMAL(18,2) NOT NULL,
    `limit` INT(11) NOT NULL,
    `free_trial` INT(11) NOT NULL,
    `signup_fee` DECIMAL(18,2) NOT NULL
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8;