CREATE TABLE `employees` (
  `emp Id` int(11) NOT NULL,
  `user_id` varchar(15) NOT NULL,
  `user_name` varchar(12) NOT NULL,
  `user_password` varchar(20) NOT NULL,
  `user_dob` date NOT NULL,
  `user_doj` date NOT NULL,
  `user_num` varchar(10) NOT NULL,
  `user_mail` varchar(50) NOT NULL,
  `user_address` varchar(50) NOT NULL,
  `user_target` varchar(9) NOT NULL,
  `department` varchar(15) NOT NULL,
  `line_hr` varchar(12) NOT NULL,
  `user_role` varchar(15) NOT NULL,
  `user_grade` varchar(4) NOT NULL,
  `grade_level` varchar(4) NOT NULL,
  `Reporting` varchar(12) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`emp Id`, `user_id`, `user_name`, `user_password`, `user_dob`, `user_doj`, `user_num`, `user_mail`, `user_address`, `user_target`, `department`, `line_hr`, `user_role`, `user_grade`, `grade_level`, `Reporting`) VALUES
(1, 'admin1', 'admin', 'admin', '1999-01-22', '2024-05-11', '8860609626', 'dushyantkumar674@gmail.c', 'Delhi', '0', 'IT', 'None', 'Sr. Developer', 'G1', '1', 'None');


--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`emp Id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `emp Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;
