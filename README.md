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

This module supports a special `anonymous` user that can be used to grant anyone
access to a given file.
The [example](svn-auth.MIndie-Client.example.conf) (WARNING this example may be outdated,
please see the module for the latest MIndie-Client configuration) provided
shows how you can have a secondary `public` path to the same repository
and redirect them to the secured path for any file or directory
that is not configured to be `anonymous`.

This is due to the limitation of [mod_authnz_external](https://github.com/phokz/mod-auth-external) v3.3.2
and should be fixed in v3.3.3 with the inclusion of `GroupExternalAuthNCheck Off` directive (untested).

## Setup

1. Install:
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
		    <Limit GET>
			    Require external-group svn-authz authz:read
		    </Limit>
		    <Limit POST>
			    Require external-group svn-authz authz:write
		    </Limit>
	    </RequireAll>
    </Location>
    ```

## Usage

Must include:
- `Include </path/to/svn-auth.define.conf>` and `GroupExternal svn-auth` for the Authz to be called
- `AuthType` and its configuration (for your choice of Authn)
- `AuthExternalContext` with a `JSON` string for proper substitutions (and, yes, these values are duplicated in your config)
    - `SVNParentPath` - filesystem path to parent directory of repository - this matches the `SVNParentPath` directive
    - `SVNLocationPath` - webspace path that is parent to the repository - this matches the `Location` directive

For Authz include:
- `Require external-group svn-authz <svn property>` - the SVN property that you set will act as an allowlist of user IDs for the file or directory it is set on - I recommend the values `authz:read` and `authz:write`
- `Require external-group svn-authz <svn property> ParentIfNotExist` - *optionally* use the parent's (or first grandparent's if intermediaries do not exist) permissions when the file does not exist (important for creating new files)

For anonymous include:
- `Require external-group anonymous <svn property>` - the SVN property that you set ***MUST*** have `anonymous` as one of its lines

Optionally, for better user experience:
- `AuthzSendForbiddenOnFailure on` - Authz failures will return `403 Forbidden` over `401 Unauthorized` (the latter may re-prompt your Authn)

For ease of redirection from public to secure, `Forbidden*.php` files have been included. They accept a `?new=<>` query parameter to replace the starting `public` path with your secure path. It assumes that you have anonymous under `/public/<>` but this can be changed by modifying the file.

## License

Copyright 2024 by carrvo

I have not decided on which license to declare as of yet.

