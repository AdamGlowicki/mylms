<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2018 LMS Developers
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

if (!$this->GetOne("SELECT id FROM uiconfig WHERE section = ? AND var = ?", array('userpanel', 'show_last_years'))) {
    $this->Execute(
        "INSERT INTO uiconfig (section, var, value) VALUES (?, ?, ?)",
        array('userpanel', 'show_last_years', '5')
    );
}

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2018091800', 'dbversion'));

$this->CommitTrans();
