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

$this->Execute("ALTER TABLE uiconfig ADD COLUMN userid int(11) DEFAULT NULL,
		ADD FOREIGN KEY (userid) REFERENCES users (id) ON UPDATE CASCADE ON DELETE CASCADE");
$this->Execute("ALTER TABLE uiconfig ADD COLUMN configid int(11) DEFAULT NULL,
		ADD FOREIGN KEY (configid) REFERENCES uiconfig (id) ON UPDATE CASCADE ON DELETE RESTRICT");
$this->Execute("ALTER TABLE uiconfig DROP INDEX var");
$this->Execute("ALTER TABLE uiconfig ADD UNIQUE var (section, var, userid)");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2019080100', 'dbversion'));

$this->CommitTrans();
