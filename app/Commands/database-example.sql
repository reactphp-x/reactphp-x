-- 数据库操作示例表结构
-- 用于 database-example.php 示例

DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '用户ID',
  `username` varchar(50) DEFAULT NULL COMMENT '用户名（唯一）',
  `name` varchar(100) NOT NULL COMMENT '姓名',
  `email` varchar(100) NOT NULL COMMENT '邮箱',
  `status` tinyint(4) DEFAULT 1 COMMENT '状态：0-禁用，1-启用，2-其他',
  `is_active` tinyint(1) DEFAULT 1 COMMENT '是否活跃：0-否，1-是',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`),
  KEY `idx_email` (`email`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户表';

-- 插入一些测试数据
INSERT INTO `users` (`username`, `name`, `email`, `status`, `is_active`, `created_at`, `updated_at`) VALUES
('admin', '管理员', 'admin@example.com', 1, 1, NOW(), NOW()),
('user1', '测试用户1', 'user1@example.com', 1, 1, NOW(), NOW()),
('user2', '测试用户2', 'user2@example.com', 1, 1, NOW(), NOW()),
('user3', '测试用户3', 'user3@example.com', 0, 0, NOW(), NOW());

