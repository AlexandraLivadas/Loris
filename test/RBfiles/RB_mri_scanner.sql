TRUNCATE TABLE `mri_scanner`;
LOCK TABLES `mri_scanner` WRITE;
INSERT INTO `mri_scanner` (`ID`, `Manufacturer`, `Model`, `Serial_number`, `Software`, `CandID`) VALUES (0,NULL,NULL,NULL,NULL,NULL);
INSERT INTO `mri_scanner` (`ID`, `Manufacturer`, `Model`, `Serial_number`, `Software`, `CandID`) VALUES (1,'SIEMENS','TrioTim','35008','syngo MR B15',284119);
INSERT INTO `mri_scanner` (`ID`, `Manufacturer`, `Model`, `Serial_number`, `Software`, `CandID`) VALUES (2,'SIEMENS','TrioTim','35182','syngo MR B15',843091);
INSERT INTO `mri_scanner` (`ID`, `Manufacturer`, `Model`, `Serial_number`, `Software`, `CandID`) VALUES (3,'SIEMENS','TrioTim','35140','syngo MR B15',846734);
INSERT INTO `mri_scanner` (`ID`, `Manufacturer`, `Model`, `Serial_number`, `Software`, `CandID`) VALUES (4,'SIEMENS','TrioTim','35140','syngo MR B17',846734);
INSERT INTO `mri_scanner` (`ID`, `Manufacturer`, `Model`, `Serial_number`, `Software`, `CandID`) VALUES (5,'SIEMENS','TrioTim','35008','syngo MR B17',284119);
INSERT INTO `mri_scanner` (`ID`, `Manufacturer`, `Model`, `Serial_number`, `Software`, `CandID`) VALUES (6,'SIEMENS','TrioTim','35182','syngo MR B17',843091);
INSERT INTO `mri_scanner` (`ID`, `Manufacturer`, `Model`, `Serial_number`, `Software`, `CandID`) VALUES (7,'SIEMENS','TrioTim','35045','syngo MR B15',674923);
INSERT INTO `mri_scanner` (`ID`, `Manufacturer`, `Model`, `Serial_number`, `Software`, `CandID`) VALUES (8,'SIEMENS','TrioTim','35177','syngo MR B17',288024);
UNLOCK TABLES;