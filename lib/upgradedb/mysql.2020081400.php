<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2019 LMS Developers
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License Version 2 as
 *  published by the Free Software Foundation.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
 *  USA.
 *
 */

$this->BeginTrans();

$this->Execute("
    CREATE TABLE customernotes (
        id int(11) NOT NULL auto_increment,
        userid int(11) DEFAULT NULL,
        customerid int(11) NOT NULL,
        dt int(11) NOT NULL,
        message text NOT NULL,
        PRIMARY KEY (id),
        CONSTRAINT customernotes_userid_fkey
            FOREIGN KEY (userid) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
        CONSTRAINT customernotes_customerid_fkey
            FOREIGN KEY (customerid) REFERENCES customers (id) ON DELETE CASCADE ON UPDATE CASCADE
    )
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2020081400', 'dbversion'));

$this->CommitTrans();
