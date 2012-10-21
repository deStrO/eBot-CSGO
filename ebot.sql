-- phpMyAdmin SQL Dump
-- version 3.3.9
-- http://www.phpmyadmin.net
--
-- Serveur: localhost
-- Généré le : Dim 21 Octobre 2012 à 16:18
-- Version du serveur: 5.5.8
-- Version de PHP: 5.3.4

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Base de données: `ebotv2`
--

-- --------------------------------------------------------

--
-- Structure de la table `maps`
--

CREATE TABLE IF NOT EXISTS `maps` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `match_id` bigint(20) NOT NULL,
  `map_name` varchar(50) DEFAULT NULL,
  `score_1` bigint(20) DEFAULT NULL,
  `score_2` bigint(20) DEFAULT NULL,
  `current_side` varchar(255) DEFAULT NULL,
  `status` mediumint(9) DEFAULT NULL,
  `maps_for` varchar(255) DEFAULT NULL,
  `nb_ot` bigint(20) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `match_id_idx` (`match_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `maps_score`
--

CREATE TABLE IF NOT EXISTS `maps_score` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `map_id` bigint(20) NOT NULL,
  `type_score` varchar(255) DEFAULT NULL,
  `score1_side1` bigint(20) DEFAULT NULL,
  `score1_side2` bigint(20) DEFAULT NULL,
  `score2_side1` bigint(20) DEFAULT NULL,
  `score2_side2` bigint(20) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `map_id_idx` (`map_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `matchs`
--

CREATE TABLE IF NOT EXISTS `matchs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `ip` varchar(50) DEFAULT NULL,
  `server_id` bigint(20) DEFAULT NULL,
  `team_a` varchar(255) DEFAULT NULL,
  `team_b` varchar(255) DEFAULT NULL,
  `status` smallint(6) DEFAULT NULL,
  `score_a` bigint(20) DEFAULT NULL,
  `score_b` bigint(20) DEFAULT NULL,
  `max_round` mediumint(9) DEFAULT NULL,
  `rules` varchar(200) DEFAULT NULL,
  `config_full_score` tinyint(1) DEFAULT NULL,
  `config_ot` tinyint(1) DEFAULT NULL,
  `config_knife_round` tinyint(1) DEFAULT NULL,
  `config_switch_auto` tinyint(1) DEFAULT NULL,
  `config_auto_change_password` tinyint(1) DEFAULT NULL,
  `config_password` varchar(50) DEFAULT NULL,
  `config_heatmap` tinyint(1) DEFAULT NULL,
  `enable` tinyint(1) DEFAULT NULL,
  `current_map` bigint(20) DEFAULT NULL,
  `force_zoom_match` tinyint(1) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `server_id_idx` (`server_id`),
  KEY `current_map_idx` (`current_map`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `players`
--

CREATE TABLE IF NOT EXISTS `players` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `match_id` bigint(20) NOT NULL,
  `map_id` bigint(20) NOT NULL,
  `player_key` varchar(255) DEFAULT NULL,
  `team` varchar(255) DEFAULT NULL,
  `ip` varchar(255) DEFAULT NULL,
  `steamid` varchar(255) DEFAULT NULL,
  `first_side` varchar(255) DEFAULT NULL,
  `current_side` varchar(255) DEFAULT NULL,
  `pseudo` varchar(255) DEFAULT NULL,
  `nb_kill` bigint(20) DEFAULT '0',
  `assist` bigint(20) DEFAULT '0',
  `death` bigint(20) DEFAULT '0',
  `point` bigint(20) DEFAULT '0',
  `hs` bigint(20) DEFAULT '0',
  `defuse` bigint(20) DEFAULT '0',
  `bombe` bigint(20) DEFAULT '0',
  `tk` bigint(20) DEFAULT '0',
  `nb1` bigint(20) DEFAULT '0',
  `nb2` bigint(20) DEFAULT '0',
  `nb3` bigint(20) DEFAULT '0',
  `nb4` bigint(20) DEFAULT '0',
  `nb5` bigint(20) DEFAULT '0',
  `nb1kill` bigint(20) DEFAULT '0',
  `nb2kill` bigint(20) DEFAULT '0',
  `nb3kill` bigint(20) DEFAULT '0',
  `nb4kill` bigint(20) DEFAULT '0',
  `nb5kill` bigint(20) DEFAULT '0',
  `pluskill` bigint(20) DEFAULT '0',
  `firstkill` bigint(20) DEFAULT '0',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `match_id_idx` (`match_id`),
  KEY `map_id_idx` (`map_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `players_heatmap`
--

CREATE TABLE IF NOT EXISTS `players_heatmap` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `match_id` bigint(20) NOT NULL,
  `map_id` bigint(20) NOT NULL,
  `event_name` varchar(50) DEFAULT NULL,
  `event_x` bigint(20) DEFAULT NULL,
  `event_y` bigint(20) DEFAULT NULL,
  `event_z` bigint(20) DEFAULT NULL,
  `player_id` bigint(20) DEFAULT NULL,
  `player_team` varchar(20) DEFAULT NULL,
  `attacker_x` bigint(20) DEFAULT NULL,
  `attacker_y` bigint(20) DEFAULT NULL,
  `attacker_z` bigint(20) DEFAULT NULL,
  `attacker_team` varchar(20) DEFAULT NULL,
  `round_id` bigint(20) DEFAULT NULL,
  `round_time` bigint(20) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `match_id_idx` (`match_id`),
  KEY `map_id_idx` (`map_id`),
  KEY `player_id_idx` (`player_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `player_kill`
--

CREATE TABLE IF NOT EXISTS `player_kill` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `match_id` bigint(20) NOT NULL,
  `map_id` bigint(20) NOT NULL,
  `killer_name` varchar(100) DEFAULT NULL,
  `killer_id` bigint(20) DEFAULT NULL,
  `killed_name` varchar(100) DEFAULT NULL,
  `killed_id` bigint(20) DEFAULT NULL,
  `weapon` varchar(100) DEFAULT NULL,
  `headshot` tinyint(1) DEFAULT NULL,
  `round_id` bigint(20) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `match_id_idx` (`match_id`),
  KEY `map_id_idx` (`map_id`),
  KEY `killer_id_idx` (`killer_id`),
  KEY `killed_id_idx` (`killed_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `round`
--

CREATE TABLE IF NOT EXISTS `round` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `match_id` bigint(20) NOT NULL,
  `map_id` bigint(20) NOT NULL,
  `event_name` varchar(255) DEFAULT NULL,
  `event_time` bigint(20) DEFAULT NULL,
  `killer_id` bigint(20) DEFAULT NULL,
  `killed_id` bigint(20) DEFAULT NULL,
  `round_id` bigint(20) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `match_id_idx` (`match_id`),
  KEY `map_id_idx` (`map_id`),
  KEY `killer_id_idx` (`killer_id`),
  KEY `killed_id_idx` (`killed_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `round_summary`
--

CREATE TABLE IF NOT EXISTS `round_summary` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `match_id` bigint(20) NOT NULL,
  `map_id` bigint(20) NOT NULL,
  `bomb_planted` tinyint(1) DEFAULT NULL,
  `bomb_defused` tinyint(1) DEFAULT NULL,
  `bomb_exploded` tinyint(1) DEFAULT NULL,
  `win_type` varchar(255) DEFAULT NULL,
  `team_win` varchar(255) DEFAULT NULL,
  `ct_win` tinyint(1) DEFAULT NULL,
  `t_win` tinyint(1) DEFAULT NULL,
  `best_killer` bigint(20) DEFAULT NULL,
  `best_killer_fk` tinyint(1) DEFAULT NULL,
  `best_action_type` text,
  `best_action_param` text,
  `round_id` bigint(20) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `match_id_idx` (`match_id`),
  KEY `map_id_idx` (`map_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `servers`
--

CREATE TABLE IF NOT EXISTS `servers` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `ip` varchar(50) NOT NULL,
  `rcon` varchar(50) NOT NULL,
  `hostname` varchar(100) NOT NULL,
  `tv_ip` varchar(100) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

--
-- Contraintes pour les tables exportées
--

--
-- Contraintes pour la table `maps`
--
ALTER TABLE `maps`
  ADD CONSTRAINT `maps_match_id_matchs_id` FOREIGN KEY (`match_id`) REFERENCES `matchs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `maps_score`
--
ALTER TABLE `maps_score`
  ADD CONSTRAINT `maps_score_map_id_maps_id` FOREIGN KEY (`map_id`) REFERENCES `maps` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `matchs`
--
ALTER TABLE `matchs`
  ADD CONSTRAINT `matchs_current_map_maps_id` FOREIGN KEY (`current_map`) REFERENCES `maps` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `matchs_server_id_servers_id` FOREIGN KEY (`server_id`) REFERENCES `servers` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `players`
--
ALTER TABLE `players`
  ADD CONSTRAINT `players_map_id_maps_id` FOREIGN KEY (`map_id`) REFERENCES `maps` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `players_match_id_matchs_id` FOREIGN KEY (`match_id`) REFERENCES `matchs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `players_heatmap`
--
ALTER TABLE `players_heatmap`
  ADD CONSTRAINT `players_heatmap_map_id_maps_id` FOREIGN KEY (`map_id`) REFERENCES `maps` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `players_heatmap_match_id_matchs_id` FOREIGN KEY (`match_id`) REFERENCES `matchs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `players_heatmap_player_id_players_id` FOREIGN KEY (`player_id`) REFERENCES `players` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `player_kill`
--
ALTER TABLE `player_kill`
  ADD CONSTRAINT `player_kill_killed_id_players_id` FOREIGN KEY (`killed_id`) REFERENCES `players` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `player_kill_killer_id_players_id` FOREIGN KEY (`killer_id`) REFERENCES `players` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `player_kill_map_id_maps_id` FOREIGN KEY (`map_id`) REFERENCES `maps` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `player_kill_match_id_matchs_id` FOREIGN KEY (`match_id`) REFERENCES `matchs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `round`
--
ALTER TABLE `round`
  ADD CONSTRAINT `round_killed_id_players_id` FOREIGN KEY (`killed_id`) REFERENCES `players` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `round_killer_id_players_id` FOREIGN KEY (`killer_id`) REFERENCES `players` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `round_map_id_maps_id` FOREIGN KEY (`map_id`) REFERENCES `maps` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `round_match_id_matchs_id` FOREIGN KEY (`match_id`) REFERENCES `matchs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `round_summary`
--
ALTER TABLE `round_summary`
  ADD CONSTRAINT `round_summary_map_id_maps_id` FOREIGN KEY (`map_id`) REFERENCES `maps` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `round_summary_match_id_matchs_id` FOREIGN KEY (`match_id`) REFERENCES `matchs` (`id`) ON DELETE CASCADE;
