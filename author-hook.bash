#!/bin/bash

# POST-COMMIT HOOK
#
# The post-commit hook is invoked after a commit.  Subversion runs
# this hook by invoking a program (script, executable, binary, etc.)
# named 'post-commit' (for which this file is a template) with the 
# following ordered arguments:
#
#   [1] REPOS-PATH   (the path to this repository)
#   [2] REV          (the number of the revision just committed)
#   [3] TXN-NAME     (the name of the transaction that has become REV)

REPOS="$1"
REV="$2"
TXN="$3"

SVN=/usr/bin/svn
SVNLOOK=/usr/bin/svnlook
AUTHOR=$($SVNLOOK author -r "$REV" "$REPOS")
# Credit to https://remarkablemark.org/blog/2020/10/19/bash-string-newline/
NL=$'\n'

# Based upon https://stackoverflow.com/a/30010928

if [ ! -d /tmp/ ];
then
    exit 1
fi

if [ ! -w /tmp/ ];
then
    exit 1
fi

function checkauthz() {
    local prop="$1"
    if [ "$prop" = "" ];
    then
        return 0
    fi
    local file="$2"
    local auth=$($SVN propget "$prop" $file 2>/dev/null || return 1)
    local check=$($SVN propget "$prop" $file 2>/dev/null | grep "^$AUTHOR$" || return 1)
    if [ "$check" = "" ];
    then
        auth="$auth$NL$AUTHOR"
        $SVN propset "$prop" "$auth" $file || return 1
    fi
    return 0
}

pushd 2>/dev/null

maxAttempts=1000
# Attempt to create a random directory until it works. Abort if we reach $maxAttempts
for i in {1..$maxAttempts};
do
    attempt="/tmp/svnauth_$SRANDOM"
    mkdir $attempt || continue
    $SVN co "file://$REPOS" $attempt --force 1>&2 || continue
    cd $attempt
    for f in $($SVNLOOK changed -r "$REV" "$REPOS" | sed -E "s/^\w+\s+//g" || break);
    do
        checkauthz "$SVNAuthzAdmin" $f || break
        checkauthz "$SVNAuthzWrite" $f || break
        checkauthz "$SVNAuthzRead" $f || break
    done
    break
done

$SVN commit -m "adding author $AUTHOR to rev $REV" 1>&2 || break
popd 2>/dev/null
rm -rf $attempt
exit 0

