Tuleap/Gitolite Membership
==========================

This is a client that is used to retrieve the membership of user on a remote Tuleap instance.

### Installing via Composer

Clone the repository, then install dependencies:
```bash
$ composer install
```

You will need to deploy the configuration file in `/etc/tuleap-gitolite-membership.ini`.
Use [config.ini](config.ini) as a template and adapt it to your configuration.

Last but not least please create a directory that will be used to cache credentials:

```bash
$ mkdir /var/cache/tuleap-gitolite-membership/
$ chmod …
```

### Usage

The tool outputs the same information as the old `gl-membership.pl`.

```bash
# Basic usage
$ ./tuleap-gitolite-membership.php retrieve nterray
> site_active tuleap_project_members tuleap_project_admin enalean_project_members

# With a self-signed certificate
$ ./tuleap-gitolite-membership.php --insecure retrieve nterray

# Debug mode
$ ./tuleap-gitolite-membership.php --insecure retrieve nterray -vvv
> [debug] Reading token from cache.
> [debug] Allowing connections to SSL sites without certs
> [debug] Retrieving membership information for "nterray"
> [debug] GET /api/v1/users?query=%7B%22username%22%3A%22nterray%22%7D
> [debug] Raw response from the server: [
>   {
>     "id": 1…

# Display usage information
$ ./tuleap-gitolite-membership.php --help
```

#### Using cache
In order to allow users to work with mirror while the master Tuleap is down (maintenance, …) you can activate cache.

Edit `/etc/tuleap-gitolite-membership.ini` and enable cache (`use_cache`). Cache is not created/updated automatically for now. You have to generate it manually on a regular basis:
```bash
$ ./tuleap-gitolite-membership.php --insecure create-cache
```

See `tuleap-gitolite-membership.php create-cache --help` for details.

### Unit tests

Make sure that dev dependencies (phpunit) has been installed and issue the following command:

```bash
$ vendor/bin/phpunit --coverage-html=/tmp/cov/
```

### Build rpms and run tests

In order to build the packages, you need to run:

```bash
$ make world JOB_NAME=dev WORKSPACE=$PWD
```

The result is in /output
