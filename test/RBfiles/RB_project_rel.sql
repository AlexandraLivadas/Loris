TRUNCATE TABLE `project_rel`;
LOCK TABLES `project_rel` WRITE;
INSERT INTO `project_rel` (`ProjectID`, `SubprojectID`) VALUES (1,1);
INSERT INTO `project_rel` (`ProjectID`, `SubprojectID`) VALUES (1,2);
INSERT INTO `project_rel` (`ProjectID`, `SubprojectID`) VALUES (2,3);
INSERT INTO `project_rel` (`ProjectID`, `SubprojectID`) VALUES (2,4);
INSERT INTO `project_rel` (`ProjectID`, `SubprojectID`) VALUES (3,1);
INSERT INTO `project_rel` (`ProjectID`, `SubprojectID`) VALUES (3,3);
UNLOCK TABLES;