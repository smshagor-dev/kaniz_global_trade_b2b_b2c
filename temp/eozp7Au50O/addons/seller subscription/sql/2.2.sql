ALTER TABLE `seller_packages` ADD `preorder_product_upload_limit` INT NOT NULL DEFAULT '0' AFTER `product_upload_limit`;

COMMIT;