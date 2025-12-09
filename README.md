# svn-auth

This is a module to use [SVN](https://svnbook.red-bean.com/) properties
for Authorization (Authz) purposes, instead of the default access file.
By choosing to do it this way, you can have versioned fine-grained access control
managed by the creator of the file, just like you would with a real filesystem!

The [example](svn-auth.MIndie-Client.example.conf) provided
is for [MIndie-Client](https://github.com/carrvo/mindie-client)
but any Authn is acceptable.
I recommend discovering and exploring [IndieAuth](https://indieweb.org/IndieAuth)
because it will allow you to share files and directories with friends and families
without needing them to register accounts on your system (or account sharing either).

## Anonymous

This module supports a special `*` (to represent `anonymous`) user that can be used to grant anyone
access to a given file.
This user can be added to the SVN property that you configured, giving universal access controlled by
whomever has access to modify the property. Alternatively you can specify `*` as the `external-group`
to enforce that only files set with anonymous can be accessed.

The [example](svn-auth.MIndie-Client.example.conf) (installed to
`/usr/share/doc/package/examples/`; WARNING this example may be outdated,
please see the module for the latest MIndie-Client configuration) provided
shows how you can have a secondary `public` path to the same repository
and redirect them to the secured path for any file or directory
that is not configured to be `anonymous`.

This is due to the limitation of [mod_authnz_external](https://github.com/phokz/mod-auth-external) v3.3.2
and has been fixed in v3.3.3 with the inclusion of `GroupExternalAuthNCheck Off` directive (tested).

## Setup

1. Clone
1. Run `debian-package-dependencies` to install dependent *build* Debian packages
1. Run `make debian-package` to build package locally
1. Run `dpkg -i package/mindie-idp_X.X.X_all.deb` to install package locally
- [Apache HTTPd](https://httpd.apache.org/)
- [PHP](https://www.php.net/)
- [mod_authnz_external](https://github.com/phokz/mod-auth-external)
1. Configure! (Replace `<>` with real values.)
    ```
    Include </path/to/svn-auth.define.conf>

    <Location </svn>>
	    DAV svn
	    SVNParentPath </path/to/parent>
	    AuthType <>
	    AuthName "<>"
	    ErrorDocument 401 </redirect/to/login>
	    AuthExternalContext '{"SVNParentPath":"</path/to/parent>","SVNLocationPath":"</svn>"}'
	    AuthzSendForbiddenOnFailure on
	    GroupExternal svn-auth
	    <RequireAll>
		    Require valid-user
		    <Limit GET HEAD OPTIONS REPORT>
			    Require external-group svn-authz authz:read
		    </Limit>
		    <Limit POST PUT PROPPATCH>
			    Require external-group svn-authz authz:write
		    </Limit>
		    <Limit MERGE DELETE>
		        Require valid-user
		    </Limit>
	    </RequireAll>
    </Location>
    ```

## Usage

### Must include
- `Include </path/to/svn-auth.define.conf>` and `GroupExternal svn-auth` for the Authz to be called
- `AuthType` and its configuration (for your choice of Authn)
- `AuthExternalContext` with a `JSON` string for proper substitutions (and, yes, these values are duplicated in your config)
    - `SVNParentPath` - filesystem path to parent directory of repository - this matches the `SVNParentPath` directive
    - `SVNLocationPath` - webspace path that is parent to the repository - this matches the `Location` directive

### For Authz include
- `Require external-group svn-authz <svn property>` - the SVN property that you set will act as an allowlist of user IDs for the file or directory it is set on - I recommend the values `authz:read` and `authz:write`
- `Require external-group svn-authz <svn property> ParentIfNotExist` - *optionally* use the parent's (or first grandparent's if intermediaries do not exist) permissions when the file does not exist (important for creating new files)
- `Require external-group svn-authz <svn property> SuperWrite` - *optionally* use the immediate parent's permissions if the `<svn property>` is either not set or empty - this helps prevent read-only items who's permissions have been orphaned and cannot be updated (without an administrator logging into the server)

Note: you can use `Require external-group svn-authz <svn property> ParentIfNotExist SuperWrite` to enable both features for the webspace path; **however** only one will take effect for a given request, depending on the circumstances (whether the item exists versus whether the property exists).

### For anonymous include
- `Require external-group * <svn property>` - the SVN property that you set ***MUST*** have `*` as one of its lines -- this enforces that only files set for anonymous access are authorized (you ***MUST*** have another, better protected, URL for accessing any non-anonymous files)

### Optionally, for better user experience
- `AuthzSendForbiddenOnFailure on` - Authz failures will return `403 Forbidden` over `401 Unauthorized` (the latter may re-prompt your Authn)

### Optionally, for redirection
For ease of redirection from public to secure, `Forbidden*.php` files have been included. They accept a `?new=<>` query parameter to replace the starting `public` path with your secure path. It assumes that you have anonymous under `/public/<>` but this can be changed by modifying the file.

### Optionally, for users to bypass
If you have a path that is pre-designated for a user, you can add a bypass such that their credentials will succeed regardless of the `<svn property>` value.

An example is given in [user-override.conf](./user-override.conf) and can be used as is with
```conf
# MUST come BEFORE other Require directives so that it can approve first and bypass
Include mods-available/user-override.conf
```

## License

Copyright 2024 by carrvo

I have not decided on which license to declare as of yet.

