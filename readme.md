## My Github

This was a quick, dirty, and ugly means of showing my branches that had open PRs against them. All other features (not many) were added "just because". Did I mention ugly? Yes, it's ugly.

### Requirements

1. A web server
2. PHP 5.5.9 or newer
3. [composer](https://getcomposer.org/)
4. [npm](https://docs.npmjs.com/cli/install)

### Installation

From a terminal in the web root:

1. composer install
2. npm install
3. ./node_modules/.bin/gulp

### Setup

Generate a [personal token for GitHub](https://github.com/settings/tokens).

Copy `.env.example` to `.env` and fill in the values.

### License

GPLv3

### Screenshots
Personal and Organization Repositories
![](http://alan.direct/drop/2015-11-29_19-13-56.png)

Personal Repository Branches with open PRs by authenticated user 
![](http://alan.direct/drop/2015-11-29_19-14-31.png)

Organization Repository Branches with number of PRs and Issues authenticated user created
![](http://alan.direct/drop/2015-11-29_19-23-19.png)

Activity feed with option for live updates
![](http://alan.direct/drop/2015-11-29_19-20-11.png)



