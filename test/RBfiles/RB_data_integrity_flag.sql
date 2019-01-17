TRUNCATE TABLE `data_integrity_flag`;
LOCK TABLES `data_integrity_flag` WRITE;
INSERT INTO `data_integrity_flag` (`dataflag_id`, `dataflag_visitlabel`, `dataflag_instrument`, `dataflag_date`, `dataflag_status`, `dataflag_comment`, `latest_entry`, `dataflag_fbcreated`, `dataflag_fbclosed`, `dataflag_fbcomment`, `dataflag_fbdeleted`, `dataflag_userid`) VALUES (1,'V1','bmi','2016-05-05',1,'',1,0,0,0,0,'admin');
INSERT INTO `data_integrity_flag` (`dataflag_id`, `dataflag_visitlabel`, `dataflag_instrument`, `dataflag_date`, `dataflag_status`, `dataflag_comment`, `latest_entry`, `dataflag_fbcreated`, `dataflag_fbclosed`, `dataflag_fbcomment`, `dataflag_fbdeleted`, `dataflag_userid`) VALUES (2,'V2','medical_history','2016-09-08',1,'',0,0,0,0,0,'admin');
INSERT INTO `data_integrity_flag` (`dataflag_id`, `dataflag_visitlabel`, `dataflag_instrument`, `dataflag_date`, `dataflag_status`, `dataflag_comment`, `latest_entry`, `dataflag_fbcreated`, `dataflag_fbclosed`, `dataflag_fbcomment`, `dataflag_fbdeleted`, `dataflag_userid`) VALUES (3,'V2','medical_history','2016-08-08',1,'',1,0,0,0,0,'admin');
INSERT INTO `data_integrity_flag` (`dataflag_id`, `dataflag_visitlabel`, `dataflag_instrument`, `dataflag_date`, `dataflag_status`, `dataflag_comment`, `latest_entry`, `dataflag_fbcreated`, `dataflag_fbclosed`, `dataflag_fbcomment`, `dataflag_fbdeleted`, `dataflag_userid`) VALUES (4,'V3','medical_history','2016-08-08',1,'',0,0,0,0,0,'admin');
INSERT INTO `data_integrity_flag` (`dataflag_id`, `dataflag_visitlabel`, `dataflag_instrument`, `dataflag_date`, `dataflag_status`, `dataflag_comment`, `latest_entry`, `dataflag_fbcreated`, `dataflag_fbclosed`, `dataflag_fbcomment`, `dataflag_fbdeleted`, `dataflag_userid`) VALUES (5,'V3','medical_history','2016-08-10',2,'',0,0,0,0,0,'admin');
INSERT INTO `data_integrity_flag` (`dataflag_id`, `dataflag_visitlabel`, `dataflag_instrument`, `dataflag_date`, `dataflag_status`, `dataflag_comment`, `latest_entry`, `dataflag_fbcreated`, `dataflag_fbclosed`, `dataflag_fbcomment`, `dataflag_fbdeleted`, `dataflag_userid`) VALUES (6,'V3','medical_history','2016-08-15',3,'',0,0,0,0,0,'admin');
INSERT INTO `data_integrity_flag` (`dataflag_id`, `dataflag_visitlabel`, `dataflag_instrument`, `dataflag_date`, `dataflag_status`, `dataflag_comment`, `latest_entry`, `dataflag_fbcreated`, `dataflag_fbclosed`, `dataflag_fbcomment`, `dataflag_fbdeleted`, `dataflag_userid`) VALUES (7,'V3','medical_history','2016-08-16',4,'',1,0,0,0,0,'admin');
INSERT INTO `data_integrity_flag` (`dataflag_id`, `dataflag_visitlabel`, `dataflag_instrument`, `dataflag_date`, `dataflag_status`, `dataflag_comment`, `latest_entry`, `dataflag_fbcreated`, `dataflag_fbclosed`, `dataflag_fbcomment`, `dataflag_fbdeleted`, `dataflag_userid`) VALUES (8,'V6','mri_parameter_form','2016-04-08',1,'',1,0,0,0,0,'admin');
UNLOCK TABLES;