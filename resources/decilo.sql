
-- ----------------------------
-- Database structure for decilo
-- ----------------------------

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `d_ads`;
CREATE TABLE `d_ads`  (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `company` bigint(20) NOT NULL,
  `impressions` bigint(20) NOT NULL DEFAULT 0,
  `approved` bit(1) NULL DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 9 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

DROP TABLE IF EXISTS `d_cards`;
CREATE TABLE `d_cards`  (
  `id` bigint(20) NOT NULL,
  `number` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `company` bigint(20) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

DROP TABLE IF EXISTS `d_challenges`;
CREATE TABLE `d_challenges`  (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `ip` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `token` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `serverTimestamp` datetime NOT NULL DEFAULT current_timestamp,
  `remoteTimestamp` datetime NOT NULL,
  `success` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 153 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

DROP TABLE IF EXISTS `d_comments`;
CREATE TABLE `d_comments`  (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `content` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `declaredName` varchar(75) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `message` bigint(20) NOT NULL,
  `private` tinyint(4) NOT NULL,
  `likes` bigint(20) NOT NULL DEFAULT 0,
  `created` datetime NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 69 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

DROP TABLE IF EXISTS `d_companies`;
CREATE TABLE `d_companies`  (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `legalName` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `owner` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `identifier` varchar(13) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

DROP TABLE IF EXISTS `d_images`;
CREATE TABLE `d_images`  (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `message` bigint(255) NOT NULL,
  `private` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 4 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

DROP TABLE IF EXISTS `d_images_analyzed`;
CREATE TABLE `d_images_analyzed`  (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `image` bigint(20) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 4 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

DROP TABLE IF EXISTS `d_messages_private`;
CREATE TABLE `d_messages_private`  (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `recipient` bigint(20) NOT NULL,
  `declaredName` varchar(75) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 16 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

DROP TABLE IF EXISTS `d_messages_public`;
CREATE TABLE `d_messages_public`  (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `declaredName` varchar(75) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `likes` bigint(20) NOT NULL DEFAULT 0,
  `nsfw` bit(1) NOT NULL DEFAULT b'0',
  `created` datetime NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 57 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

DROP TABLE IF EXISTS `d_received_hooks`;
CREATE TABLE `d_received_hooks`  (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `body` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 16 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

DROP TABLE IF EXISTS `d_report_reasons`;
CREATE TABLE `d_report_reasons`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reason` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `score` int(255) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 7 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

INSERT INTO `d_report_reasons` (`id`, `reason`, `score`) VALUES (1, 'Promociona o distribuye pornografía infantil', -1);
INSERT INTO `d_report_reasons` (`id`, `reason`, `score`) VALUES (2, 'Promueve el odio o discriminación a un grupo social específico', 9);
INSERT INTO `d_report_reasons` (`id`, `reason`, `score`) VALUES (3, 'Incita a difamar o divulgar información no verificable sobre una persona', 8);
INSERT INTO `d_report_reasons` (`id`, `reason`, `score`) VALUES (4, 'Facilita la venta de sustancias ilícitas', 7);
INSERT INTO `d_report_reasons` (`id`, `reason`, `score`) VALUES (5, 'No siento que sea relevante', 0);
INSERT INTO `d_report_reasons` (`id`, `reason`, `score`) VALUES (6, 'Otra razón', 10);

DROP TABLE IF EXISTS `d_reports`;
CREATE TABLE `d_reports`  (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `message` bigint(20) NOT NULL,
  `private` bit(1) NOT NULL,
  `reason` int(11) NOT NULL,
  `reportedBy` bigint(20) NOT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

DROP TABLE IF EXISTS `d_subscriptions`;
CREATE TABLE `d_subscriptions`  (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `company` bigint(20) NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `active` bit(1) NOT NULL DEFAULT b'1',
  `modified` datetime NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

DROP TABLE IF EXISTS `d_users`;
CREATE TABLE `d_users`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `mailAddress` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp,
  `modified` datetime NOT NULL DEFAULT current_timestamp ON UPDATE CURRENT_TIMESTAMP,
  `allowance` int(11) NOT NULL,
  `killSwitch` datetime NULL DEFAULT NULL,
  `quickStartToken` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `qr` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `theme` tinyint(1) NULL DEFAULT NULL COMMENT 'NULL = auto, 0 = light, 1 = dark',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 49 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

DROP TABLE IF EXISTS `d_wallpapers`;
CREATE TABLE `d_wallpapers`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 69 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (1, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw10.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (2, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw11.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (3, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw12.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (4, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw13.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (5, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw14.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (6, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw15.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (7, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw16.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (8, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw17.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (9, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw18.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (10, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw19.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (11, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw1.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (12, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw20.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (13, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw21.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (14, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw22.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (15, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw23.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (16, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw24.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (17, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw25.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (18, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw26.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (19, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw27.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (20, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw28.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (21, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw29.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (22, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw2.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (23, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw30.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (24, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw31.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (25, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw32.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (26, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw33.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (27, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw34.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (28, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw35.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (29, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw36.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (30, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw37.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (31, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw38.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (32, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw39.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (33, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw3.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (34, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw40.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (35, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw41.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (36, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw42.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (37, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw43.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (38, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw44.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (39, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw45.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (40, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw46.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (41, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw47.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (42, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw48.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (43, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw49.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (44, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw4.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (45, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw50.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (46, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw51.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (47, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw52.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (48, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw53.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (49, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw54.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (50, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw55.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (51, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw56.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (52, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw57.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (53, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw58.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (54, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw59.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (55, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw5.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (56, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw60.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (57, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw61.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (58, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw62.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (59, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw63.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (60, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw64.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (61, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw65.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (62, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw66.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (63, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw67.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (64, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw68.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (65, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw6.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (66, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw7.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (67, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw8.webp');
INSERT INTO `d_wallpapers` (`id`, `url`) VALUES (68, 'https://objectstorage.sa-saopaulo-1.oraclecloud.com/n/gralaj58rsfm/b/decilo/o/wallpapers%2Fw9.webp');

SET FOREIGN_KEY_CHECKS = 1;