#
# Son of Service
# Copyright (C) 2003 by Andrew Ziem.  All rights reserved.
# Licensed under the GNU General Public License.  See COPYING for details.
#
# MySQL data structures
#
# $Id: mysql-structure.sql,v 1.17 2003/11/24 16:09:40 andrewziem Exp $
#

CREATE TABLE volunteers (
        volunteer_id int NOT NULL AUTO_INCREMENT PRIMARY KEY,
	
	entity_type CHAR NOT NULL DEFAULT 'I', # I=individual, G=group, O=organization
	
	organization varchar(60) NOT NULL,

	prefix varchar(20) NOT NULL,
        first varchar(20) NOT NULL,
        middle varchar(20) NOT NULL,
        last varchar(40) NOT NULL,
	suffix varchar(10) NOT NULL,

        street varchar(40) NOT NULL,
        city varchar(30) NOT NULL,
        state varchar(10) NOT NULL, # or provience
        postal_code varchar(10) NOT NULL,
	country varchar(30) NOT NULL,

        phone_home varchar(20) NOT NULL,
        phone_work varchar(20) NOT NULL,
        phone_cell varchar(20) NOT NULL,
        email_address varchar(45) NOT NULL,

        hours_life decimal(10,2),
        hours_ly decimal(10,2),	
        hours_ytd decimal(10,2),	
	hours_life_percentile decimal(3,2), # not yet implemented
	hours_ly_percentile decimal(3,2),		
	hours_ytd_percentile decimal(3,2),	
        first_volunteered date,

        dt_added datetime,
	uid_added int,
	dt_modified datetime,
	uid_modified int,	
	
	INDEX(first),
	INDEX(last),
	INDEX(organization),
	INDEX(street),
	INDEX(city),
	INDEX(postal_code),

        );
	
CREATE TABLE availability (
    availability_id  int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    volunteer_id int NOT NULL,
    day_of_week tinyint, # 1 = Sunday, 7 = Saturday
    start_time enum("Morning", "Afternoon", "Evening", "Night"),
    end_time   enum("Morning", "Afternoon", "Evening", "Night"),

    dt_added datetime,
    uid_added int,
    dt_modified datetime,
    uid_modified int,	

    INDEX (volunteer_id),
    INDEX (day_of_week),
    INDEX (start_time),
    INDEX (end_time)
);

CREATE TABLE strings (
    string_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    s varchar(255) not null,
    lang varchar(6),
    type enum('extended', 'relationship', 'skill', 'work'),
    foreign_id INT,
    
);

# describe each skill of each volunteer
CREATE TABLE volunteer_skills (
    volunteer_skill_id int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    volunteer_id INT NOT NULL,
    string_id INT NOT NULL,
    skill_level TINYINT NOT NULL,     # 1 = none, 2 = amatuer, 3 = some, 4 = pro, 5 = expert
#    PRIMARY KEY (volunteer_skill_id), 
    INDEX (volunteer_id), 
    INDEX (string_id), 
    INDEX (skill_level),
    UNIQUE (volunteer_id, string_id)
) COMMENT = "each skill of each volunteer";

CREATE TABLE notes (
    note_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY UNIQUE,

    dt DATETIME,
    reminder_date DATE, # NULL if not a reminder
    acknowledged BOOL, # refers to reminders
    uid_assigned INT, # user_id, if assigned to someone
    volunteer_id INT, # volunteer ID
    message TEXT NOT NULL,
    quality TINYINT, # -1 = bad, 0 = neutral, 1 = good
    uid_added INT, # user ID of he who added note
    uid_modified INT,
    dt_modified DATETIME,

    INDEX (reminder_date),
    INDEX (volunteer_id)
) COMMENT = "users' reminders and notes about volunteers";


CREATE TABLE work (
    work_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,

    date DATE,
    hours DECIMAL(6,2), # for example, work of 6.25 hours
    volunteer_id INT,
    memo TEXT,
    quality TINYINT, # -1 negative, 0 neutral, 1 positive
    category_id INT,

    dt_added DATETIME,
    uid_added INT,
    dt_modified DATETIME,
    uid_modified INT,	

    index (volunteer_id),
    index (date)
);

CREATE TABLE users (
        user_id int NOT NULL AUTO_INCREMENT PRIMARY KEY UNIQUE,

        personalname varchar(50),
        username varchar(20) UNIQUE,
	volunteer_id INT,
        password varchar(35),
        email varchar(50),	

        memo text,
        last_login datetime, # YYYY-MM-DD HH:MM:SS format

        access_admin bit, # change users, export/import files, etc.
        access_change_vol bit, # change volunteers

	INDEX (username(5)),
	INDEX (password(5))
);

CREATE TABLE relationships (
	relationship_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	
	volunteer1_id INT NOT NULL,
	volunteer2_id INT NOT NULL,	
	
	string_id INT,
	
	UNIQUE(volunteer1_id, volunteer2_id, string_id)
);

# SOS modifies its own extended table
CREATE TABLE extended (
	extended_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	volunteer_id INT NOT NULL,

	INDEX(volunteer_id),	
	UNIQUE(volunteer_id)
) COMMENT = "user-defined custom data fields";

CREATE TABLE extended_meta (
	extended_meta_id MEDIUMINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	
	code VARCHAR(50) NOT NULL,
	label VARCHAR(50),
	description VARCHAR(100),
	size1 MEDIUMINT UNSIGNED,
	size2 MEDIUMINT UNSIGNED,
	size3 MEDIUMINT UNSIGNED,
	fieldtype ENUM ('integer', 'decimal', 'string', 'textarea', 'date'),
	displayposition MEDIUMINT NOT NULL,

# to do: validation
# to do: required, not required
	
	volunteer_read BOOL,
	volunteer_write BOOL,	
	
	UNIQUE (code)
	
);

# log not used yet
CREATE TABLE log (
        log_id int NOT NULL AUTO_INCREMENT PRIMARY KEY,

        user_id int,
        level int,
        message tinytext,
        dt datetime 
);
