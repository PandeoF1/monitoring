SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE TABLE `hosts` (
  `uuid` varchar(150) NOT NULL,
  `hostname` text DEFAULT NULL,
  `ip` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


ALTER TABLE `hosts`
  ADD PRIMARY KEY (`uuid`);
COMMIT;
