<?php
// IF YOU HAVE NOT DONE SO, PLEASE READ THE README FILE FOR DIRECTIONS!!!

/**
 * OpenID-LDAP-PHP
 * An open source PHP-based OpenID IdP package using LDAP as backend.
 *
 * By Zdravko Stoychev <zdravko (at) 5group (dot) com> aka Dako.
 * Copyright 1996-2011 by 5Group & Co. http://www.5group.com/
 * See LICENSE file for more details.
 */

/**
 * LDAP connection settings
 * @name $ldap
 * @global array $GLOBALS['ldap']
 */
$GLOBALS['ldap'] = array (
	# Connection settings
	'primary'		=> 'localhost',
	'fallback'		=> 'localhost',
	'protocol'		=> 3,
	# AD specific
	'isad'			=> false, // are we connecting to Active Directory?
	'lookupcn'		=> false, // should we extract CN after the search?
	# Binding account
	'binddn'		=> '',
	'password'		=> '',
	# User account
	'autodn'		=> true, // extract DN from search result, ignore 'testdn'
	'testdn'		=> 'uid=%s,ou=users,dc=yunohost,dc=org',
	# Searching data
	'searchdn'		=> 'ou=users,dc=yunohost,dc=org',
	'filter'		=> '(uid=%s)',

	# SREG names matching to LDAP attribute names
	'nickname'		=> 'uid',
	'email'			=> 'mail',
	'fullname'		=> array('givenname', 'sn'),
#	'dob'			=> '',
#	'postcode'		=> '',
#	'language'		=> '',
#	'timezone'		=> '',
#	'gender'		=> '',
	'country'		=> 'c',

	# Default SREG values (default server settings)
	'def_language'		=> 'YNH_DFT_LANG',
	'def_postcode'		=> '1000',
	'def_timezone'		=> 'YNH_DFT_TIMEZONE'
);


/**
 * Search for LDAP account by username. Populate $sreg if found
 * string $username
 */
function find_ldap ($username) {
	global $sreg, $ldap, $profile;

        $no = "no";
        $profile['user_found'] = false;

        if ($username != "") {
                $ds = ldap_connect($ldap['primary']) or $ds = ldap_connect($ldap['fallback']);
                if ($ds) {
			ldap_set_option($ds,LDAP_OPT_PROTOCOL_VERSION,$ldap['protocol']);
			if ($ldap['isad'] == true) ldap_set_option($ds,LDAP_OPT_REFERRALS,0);

                        $r = ldap_bind($ds,$ldap['binddn'],$ldap['password']);
            		$sr = ldap_search($ds,$ldap['searchdn'],sprintf($ldap['filter'],$username));
			$info = ldap_get_entries($ds, $sr);

                        if ($info["count"] == 1) {
                                $no = "ok";
                                $profile['user_found'] = true;
				if ($ldap['lookupcn'] == true) $profile['auth_cn'] = $info[0]['cn'][0];
				if ($ldap['autodn'] == true) $ldap['testdn'] = $info['0']['dn'];

				# Populate user information from LDAP - if (array_key_exists('keyname', $ldap))...
				$sreg['nickname'] = $info[0][$ldap['nickname']][0];
				$sreg['email']    = $info[0][$ldap['email']][0];

                                $values = is_array($ldap['fullname']) ? $ldap['fullname'] : array($ldap['fullname']);
                                $fullname = '';
	                        foreach ($values as $vname) {
				        $aname = $info[0][$vname][0];
				        if ($aname != '') $fullname = ($fullname == '' ? $aname : $fullname . ' ' . $aname);
                                }
                                $sreg['fullname'] = $fullname;

				$sreg['country']  = $info[0][$ldap['country']][0];

				# Values not obtained from LDAP
				$sreg['language'] = $ldap['def_language'];
				$sreg['postcode'] = $ldap['def_postcode'];
				$sreg['timezone'] = $ldap['def_timezone'];
                        }
                        ldap_close($ds);
                }
        }
        return $no;
}


/**
 * Perform LDAP bind test with provided username and password
 * string $username, string $password
 */
function test_ldap ($username, $password) {
	global $ldap;
        $no = "no";
        # Ignore empty password as well
        if (($username != "") && ($password != "")) {
                $ds = ldap_connect($ldap['primary']) or $ds = ldap_connect($ldap['fallback']);
                if ($ds) {
			ldap_set_option($ds,LDAP_OPT_PROTOCOL_VERSION,$ldap['protocol']);
			if ($ldap['isad'] == true) ldap_set_option($ds,LDAP_OPT_REFERRALS,0);

                        if ($ldap['autodn'] == true) {
				if (ldap_bind($ds,$ldap['testdn'],$password)) $no = "ok";
			} else {
				if (ldap_bind($ds,sprintf($ldap['testdn'],$username),$password)) $no = "ok";
			}
                        ldap_close($ds);
                }
        }
        return $no;
}

/* notepad here:
  ... This was acheived with this stanza in slapd.conf

  access to attr=userPassword
        by self write
        by anonymous auth
        by * none

  print "<p>Change password ";
  if (ldap_mod_replace ($ldapconn, "uid=".$username.",dc=example,dc=com", 
	array('userpassword' => "{MD5}".base64_encode(pack("H*",md5($newpass))) { 
	print "succeded"; } else { print "failed"; }
  print ".</p>\n";
  

  
+    private String detectActiveDirectory( IRootDSE rootDSE )
+    {
+
+        String result = null;
+
+        // check active directory
+        IAttribute rdncAttribute = rootDSE.getAttribute( "rootDomainNamingContext" );
+        if ( rdncAttribute != null )
+        {
+            IAttribute ffAttribute = rootDSE.getAttribute( "forestFunctionality" );
+            if ( ffAttribute != null )
+            {
+                result = "Microsoft Active Directory 2003";
+            }
+            else
+            {
+                result = "Microsoft Active Directory 2000";
+            }
+        }
+
+        return result;
+    }
  
*/

?>
