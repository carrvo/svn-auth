#!/usr/bin/php
<?php
// setup
$sub_command = 'svn propget';
$anonymous = 'anonymous';

$context = json_decode(getenv('CONTEXT'), true);
$SVNParentPath = $context['SVNParentPath'];
$SVNLocationPath = $context['SVNLocationPath'];
$location_path = $_SERVER['URI'];
$user = trim(fgets(STDIN));
$groups = trim(fgets(STDIN));
$group_array = explode(' ', $groups);

$anonymous_override = false;
if (strcmp($group_array[0], $anonymous) === 0) {
	error_log("[authnz_external:svn-auth:info] checking for anonymous access");
	$anonymous_override = true;
}	
elseif (strcmp($group_array[0], 'svn-authz') !== 0) {
	error_log("[authnz_external:svn-auth:info] $groups is not supported");
	exit(3);
}
$svn_property = $group_array[1];

$svn_path = preg_replace('/'.preg_quote($SVNLocationPath, '/').'/', $SVNParentPath, $location_path);
// for folder reads it queries each item with !svn/ver/{revision}/ inserted into the path
$svn_path = preg_replace('/!svn\/ver\/\d+\//', '', $svn_path);
$svn_path = preg_replace('/!svn\/rvr\/\d+\//', '', $svn_path);

// special WebDAV URIs
// https://svn.apache.org/repos/asf/subversion/trunk/notes/http-and-webdav/http-protocol-v2.txt
if (str_ends_with($location_path, "/!svn/me")) {
	error_log("[authnz_external:svn-auth:info] special WebDAV override for $user to create a transaction");
	exit(0);
}

// Parent override
if (count($group_array) > 2 && strcmp($group_array[2], 'ParentIfNotExist') === 0) {
	error_log("[authnz_external:svn-auth:info] finding parent");
	$cmd = "svn list 'file://$svn_path'";
	$output=null;
	$retval=null;
	$cmd_ran = exec($cmd, $output, $retval);
	if ($cmd_ran === false || $retval != 0) {
		$parent_pos = strrpos($svn_path, '/', 1); // exclude final slash (/) if child is folder
		if ($parent_pos !== false) {
			fwrite(STDERR, "[authnz_external:svn-auth:info] does not exist, overriding with parent for permissions\n");
			$svn_path = substr($svn_path, 0, $parent_pos);
		}
	}
	error_log("[authnz_external:svn-auth:info] done with parent");
}

// Permissions
$cmd = "$sub_command $svn_property 'file://$svn_path'";
$output=null;
$retval=null;
$cmd_ran = exec($cmd, $output, $retval);

// Results
if ($cmd_ran === false) {
    error_log("[authnz_external:svn-auth:info] SVN failed to run");
    exit(1);
}
if ($retval != 0) {
    error_log("[authnz_external:svn-auth:info] SVN returned with status $retval");
    exit($retval);
}

foreach ($output as $authz) {
	// Grant the user their permissions BEFORE checking anonymous
	// (though this is actually ordered by the property).
	// When anonymous override, we need to skip specific user checks
	if ($anonymous_override === false) {
		if (strcmp($user, $authz) === 0) {
	        	exit(0);
		}
	}
	if (strcmp($anonymous, $authz) === 0){
		error_log("[authnz_external:svn-auth:info] $user is granted anonymous access!");
		exit(0);
	}
}
error_log("[authnz_external:svn-auth:info] $user is not authorized!");
exit(2);
?>

