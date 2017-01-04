-- Create the datbase
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
CREATE DATABASE IF NOT EXISTS `earthboost`
DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `earthboost`;

-- Drop tables
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `groups`;
DROP TABLE IF EXISTS `events`;
DROP TABLE IF EXISTS `members`;
DROP TABLE IF EXISTS `answers`;
DROP TABLE IF EXISTS `emissions`;

-- Create all tables
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(10) unsigned NOT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created_on` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `groups` (
  `id` int(10) unsigned NOT NULL,
  `name` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `label` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `type` varchar(10) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'office',
  `created_on` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `events` (
  `id` int(10) unsigned NOT NULL,
  `group_id` int(10) unsigned NOT NULL,
  `label` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `date_start` datetime NOT NULL,
  `date_end` datetime NOT NULL,
  `created_on` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `members` (
  `id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `group_id` int(10) unsigned NOT NULL,
  `year` smallint(5) unsigned NOT NULL,
  `locale` varchar(5) COLLATE utf8_unicode_ci NOT NULL,
  `locale_percent` tinyint(4) NOT NULL DEFAULT '100',
  `emissions` float unsigned DEFAULT NULL,
  `is_admin` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `is_champion` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `is_standard` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `created_on` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `answers` (
  `id` int(10) unsigned NOT NULL,
  `question_id` varchar(2) COLLATE utf8_unicode_ci NOT NULL,
  `group_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `event_id` int(10) unsigned DEFAULT NULL,
  `year` smallint(5) unsigned NOT NULL,
  `answer` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `select` varchar(20) CHARACTER SET utf32 COLLATE utf32_unicode_ci DEFAULT NULL,
  `created_on` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `emissions` (
  `id` int(10) unsigned NOT NULL,
  `type_id` varchar(2) NOT NULL,
  `group_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `event_id` int(10) unsigned DEFAULT NULL,
  `year` smallint(5) unsigned NOT NULL,
  `value` int(10) unsigned NOT NULL,
  `created_on` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Add all indexes
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);
ALTER TABLE `groups`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `group_id` (`group_id`);
ALTER TABLE `members`
  ADD PRIMARY KEY (`id`),
  ADD KEY `group_id` (`group_id`),
  ADD UNIQUE KEY `user_group_year_locale`
    (`user_id`,`group_id`,`year`,`locale`);
ALTER TABLE `answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `group_id` (`group_id`);
ALTER TABLE `emissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `group_id` (`group_id`);

-- Add increments
ALTER TABLE `users` MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT;
ALTER TABLE `groups` MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT;
ALTER TABLE `events` MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT;
ALTER TABLE `members` MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT;
ALTER TABLE `answers` MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT;
ALTER TABLE `emissions` MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT;