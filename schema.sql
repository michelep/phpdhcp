--
-- Struttura della tabella `Devices`
--

DROP TABLE IF EXISTS `Devices`;
CREATE TABLE IF NOT EXISTS `Devices` (
  `MAC` varchar(16) NOT NULL,
  `IP` varchar(16) NOT NULL,
  `PoolID` int(11) NOT NULL,
  `Brand` varchar(32) NOT NULL,
  `Model` varchar(32) NOT NULL,
  `Name` varchar(32) NOT NULL,
  `adminPwd` varchar(16) NOT NULL,
  `userPwd` varchar(16) NOT NULL,
  `Options` text NOT NULL,
  `isEnable` tinyint(1) NOT NULL,
  `addDate` datetime NOT NULL,
  `chgDate` datetime NOT NULL,
  PRIMARY KEY (`MAC`),
  KEY `IP` (`IP`,`PoolID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Voip phisical devices for provisioning';

-- --------------------------------------------------------

--
-- Struttura della tabella `DHCPLeases`
--

DROP TABLE IF EXISTS `DHCPLeases`;
CREATE TABLE IF NOT EXISTS `DHCPLeases` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `PoolID` int(11) NOT NULL,
  `IP` varchar(16) NOT NULL,
  `ChgDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`),
  KEY `PoolID` (`PoolID`),
  KEY `IP` (`IP`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Struttura della tabella `DHCPPools`
--

DROP TABLE IF EXISTS `DHCPPools`;
CREATE TABLE IF NOT EXISTS `DHCPPools` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `IPStart` varchar(16) NOT NULL,
  `IPEnd` varchar(16) NOT NULL,
  `SubnetMask` varchar(16) NOT NULL,
  `Router` varchar(16) NOT NULL,
  `DNSServers` varchar(64) NOT NULL,
  `Domain` varchar(32) NOT NULL,
  `Note` text NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;
