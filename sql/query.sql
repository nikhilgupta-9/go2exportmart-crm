-- create DATABASE
CREATE DATABASE `crm_vwm`;

-- create users table 
CREATE TABLE `crm_vwm`.`users` (`user_id` INT NOT NULL AUTO_INCREMENT , `user_name` VARCHAR(20) NOT NULL , `user_password` VARCHAR(20) NOT NULL , `user_role` VARCHAR(10) NOT NULL , PRIMARY KEY (`user_id`));

-- create admin user
INSERT INTO `users` (`user_id`, `user_name`, `user_password`, `user_role`) VALUES ('1', 'admin', 'admin', 'admin');

-- add another column in table 
ALTER TABLE `users` ADD `operation_grade` VARCHAR(5) NOT NULL AFTER `user_role`;



-- employees query
CREATE TABLE `crm_vwm`.`employees` (`emp Id` INT NOT NULL AUTO_INCREMENT , `user_id` VARCHAR(15) NOT NULL , `user_name` VARCHAR(12) NOT NULL , `user_password` VARCHAR(20) NOT NULL , `user_dob` date NOT NULL , `user_doj` date NOT NULL , `user_num` VARCHAR(10) NOT NULL , `user_mail` VARCHAR(24) NOT NULL , `user_address` VARCHAR(50) NOT NULL , `user_target` VARCHAR(9) NOT NULL , `department` VARCHAR(15) NOT NULL , `line_hr` VARCHAR(12) NOT NULL , `user_role` VARCHAR(15) NOT NULL , `user_grade` VARCHAR(4) NOT NULL , `grade_level` VARCHAR(4) NOT NULL , `Reporting` VARCHAR(12) NOT NULL , PRIMARY KEY (`emp Id`)) ENGINE = InnoDB;

-- employees insert query 
INSERT INTO `employees` (`emp Id`, `user_id`, `user_name`, `user_password`, `user_dob`, `user_doj`, `user_num`, `user_mail`, `user_address`, `user_target`, `department`, `line_hr`, `user_role`, `user_grade`, `grade_level`, `Reporting`) VALUES ('1', 'admin1', 'admin', 'admin', '1999-01-22', '2024-05-11', '8860609626', 'dushyantkumar674@gmail.com', 'Delhi', '0', 'IT', 'None', 'Sr. Developer', 'G1', '1', 'None');


-- customer leads query 
INSERT INTO `customerleads` (`sno`, `customer_num`, `alt_number`, `customer_name`, `cust_company`, `service`, `website`, `cust_address`, `cust_state`, `status`, `matelize`, `pan`, `Aadhar`, `GST`, `MRP`, `amount`, `discount`, `bal_amt`, `pay_mode`, `invoice`, `transaction`, `todaydate`, `month`, `dtstamp`) VALUES ('1', '9879879870', '', 'test', 'test pvt ltd', 'Gold Memebership', 'test.com', 'delhi', 'delhi', 'Follow Up', 'No', 'abcdefghij', '987987987', '987987987987987', '100000', '70000', '0', '30000', 'UPI', 'AAAA000000000', '987987987897897', current_timestamp(), 'May', current_timestamp());


ALTER TABLE `customerleads` ADD `assigned_to` VARCHAR(12) NOT NULL AFTER `alt_number`, ADD `reporting` VARCHAR(12) NOT NULL AFTER `assigned_to`;

UPDATE `customerleads` SET `assigned_to` = 'Naina', `reporting` = 'Amit' WHERE `customerleads`.`sno` = 1;