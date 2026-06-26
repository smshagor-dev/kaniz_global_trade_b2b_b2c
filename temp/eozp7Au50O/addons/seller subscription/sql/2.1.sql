ALTER TABLE `seller_package_payments` ADD `amount` DOUBLE(20, 2) NOT NULL AFTER `seller_package_id`;

COMMIT;