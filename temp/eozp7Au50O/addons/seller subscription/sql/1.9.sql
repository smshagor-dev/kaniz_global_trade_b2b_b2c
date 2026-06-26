ALTER TABLE `seller_package_payments` CHANGE `payment_details` `payment_details` LONGTEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;
ALTER TABLE `seller_package_payments` CHANGE `payment_method` `payment_method` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL;

COMMIT;