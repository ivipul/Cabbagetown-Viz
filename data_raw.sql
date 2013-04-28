-- phpMyAdmin SQL Dump
-- version 3.3.10.4
-- http://www.phpmyadmin.net
--
-- Host: msql.t1.sagz.in
-- Generation Time: Apr 27, 2013 at 05:14 PM
-- Server version: 5.1.56
-- PHP Version: 5.2.17

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `ubicomp2013`
--
CREATE DATABASE `ubicomp2013` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `ubicomp2013`;

-- --------------------------------------------------------

--
-- Table structure for table `data_raw`
--

CREATE TABLE IF NOT EXISTS `data_raw` (
  `sensorID` smallint(6) DEFAULT NULL,
  `small_particle_count` bigint(20) DEFAULT NULL,
  `large_particle_count` bigint(20) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------
