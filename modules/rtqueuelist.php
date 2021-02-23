<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2018 LMS Developers
 *
 *  Please, see the doc/AUTHORS for more information about authors!
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
 *  $Id$
 */

if (isset($_POST['deleted'])) {
    $filter['deleted'] = $_POST['deleted'];
} elseif (!isset($filter['deleted'])) {
    $filter['deleted'] = true;
}

$SESSION->saveFilter($filter);

$filter['stats'] = true;
$filter['only_accessible'] = false;
$queues = $LMS->GetQueueList($filter);

$layout['pagetitle'] = trans('Queues List');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SESSION->remove('backid');

$SMARTY->assign('filter', $filter);
$SMARTY->assign('queues', $queues);
$SMARTY->assign('error', $error);
$SMARTY->display('rt/rtqueuelist.html');
