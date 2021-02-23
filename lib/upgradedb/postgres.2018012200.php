<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2017 LMS Developers
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

$this->Execute(
    "INSERT INTO uiconfig (section, var, value) VALUES (?, ?, ?)",
    array('userpanel', 'allow_reopen_tickets_newer_than', '0')
);

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2018012200', 'dbversion'));

$this->CommitTrans();
