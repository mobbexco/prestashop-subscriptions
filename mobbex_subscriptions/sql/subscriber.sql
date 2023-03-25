CREATE TABLE IF NOT EXISTS PREFIX_mobbex_subscriber (
    `cart_id` INT(11) NOT NULL PRIMARY KEY,
    `uid` TEXT NOT NULL,
    `subscription_uid` TEXT NOT NULL,
    `state` TINYINT NOT NULL,
    `test` TINYINT NOT NULL,
    `name` TEXT NOT NULL,
    `email` TEXT NOT NULL,
    `phone` TEXT NOT NULL,
    `identification` TEXT NOT NULL,
    `customer_id` INT(11) NOT NULL,
    `source_url` TEXT NOT NULL,
    `control_url` TEXT NOT NULL,
    `register_data` TEXT NOT NULL,
    `start_date` DATETIME NOT NULL,
    `last_execution` DATETIME NOT NULL,
    `next_execution` DATETIME NOT NULL
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8;