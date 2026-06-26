ALTER TABLE `seller_packages` CHANGE `product_upload` `product_upload_limit` INT(11) NOT NULL DEFAULT '0';

COMMIT;