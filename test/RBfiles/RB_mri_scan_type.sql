TRUNCATE TABLE `mri_scan_type`;
LOCK TABLES `mri_scan_type` WRITE;
INSERT INTO `mri_scan_type` (`ID`, `Scan_type`) VALUES (40,'fMRI');
INSERT INTO `mri_scan_type` (`ID`, `Scan_type`) VALUES (41,'flair');
INSERT INTO `mri_scan_type` (`ID`, `Scan_type`) VALUES (44,'t1');
INSERT INTO `mri_scan_type` (`ID`, `Scan_type`) VALUES (45,'t2');
INSERT INTO `mri_scan_type` (`ID`, `Scan_type`) VALUES (46,'pd');
INSERT INTO `mri_scan_type` (`ID`, `Scan_type`) VALUES (47,'mrs');
INSERT INTO `mri_scan_type` (`ID`, `Scan_type`) VALUES (48,'dti');
INSERT INTO `mri_scan_type` (`ID`, `Scan_type`) VALUES (49,'t1relx');
INSERT INTO `mri_scan_type` (`ID`, `Scan_type`) VALUES (50,'dct2e1');
INSERT INTO `mri_scan_type` (`ID`, `Scan_type`) VALUES (51,'dct2e2');
INSERT INTO `mri_scan_type` (`ID`, `Scan_type`) VALUES (52,'scout');
INSERT INTO `mri_scan_type` (`ID`, `Scan_type`) VALUES (53,'tal_msk');
INSERT INTO `mri_scan_type` (`ID`, `Scan_type`) VALUES (54,'cocosco_cls');
INSERT INTO `mri_scan_type` (`ID`, `Scan_type`) VALUES (55,'clean_cls');
INSERT INTO `mri_scan_type` (`ID`, `Scan_type`) VALUES (56,'em_cls');
INSERT INTO `mri_scan_type` (`ID`, `Scan_type`) VALUES (57,'seg');
INSERT INTO `mri_scan_type` (`ID`, `Scan_type`) VALUES (58,'white_matter');
INSERT INTO `mri_scan_type` (`ID`, `Scan_type`) VALUES (59,'gray_matter');
INSERT INTO `mri_scan_type` (`ID`, `Scan_type`) VALUES (60,'csf_matter');
INSERT INTO `mri_scan_type` (`ID`, `Scan_type`) VALUES (61,'nlr_masked');
INSERT INTO `mri_scan_type` (`ID`, `Scan_type`) VALUES (62,'pve');
INSERT INTO `mri_scan_type` (`ID`, `Scan_type`) VALUES (999,'unknown');
INSERT INTO `mri_scan_type` (`ID`, `Scan_type`) VALUES (1000,'NA');
UNLOCK TABLES;