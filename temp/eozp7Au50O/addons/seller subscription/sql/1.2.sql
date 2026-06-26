CREATE TABLE `seller_package_translations` (
  `id` bigint(20) NOT NULL,
  `seller_package_id` bigint(20) NOT NULL,
  `name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `lang` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

ALTER TABLE `seller_package_translations`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `seller_package_translations`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

COMMIT;
