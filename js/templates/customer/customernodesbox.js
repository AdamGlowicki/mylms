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

function setstatus(access) {
	if(access)
		document.custnodeslist.action = '?m=nodeset&access=1';
	else
		document.custnodeslist.action = '?m=nodeset';

	document.custnodeslist.submit();
}

function setwarning(warning) {
	if(warning)
		document.custnodeslist.action = '?m=nodewarn&warning=1';
	else
		document.custnodeslist.action = '?m=nodewarn';

	document.custnodeslist.submit();
}

function setgroup(act) {
	document.custnodeslist.action = '?m=nodegroup&action=' + act + '&groupid=' + $('#groupselect').val();
	document.custnodeslist.submit();
}

$(function() {
	$('.delete-node').click(function() {
		confirmDialog($t("Are you sure, you want to remove node '$a' from database?", $(this).prev().val()), this).done(function () {
			location.href = $(this).attr('href');
		});
		return false;
	});
});