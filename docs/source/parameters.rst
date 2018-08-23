.. _section-parameters:

Configuration Parameters
========================

LOCKSSOMatic has many moving parts and many of those parts are configurable. The
configuration options are described below. They are found in
``app/config/parameters.yml`` which will automatically be created during the
composer install/update step.

.. note::

  The ``parameters.yml`` file is not managed by git. It contains passwords and other
  sensitive information that doesn't belong in source control. There is a
  default ``parameters.yml.dist`` file in the git repository. Composer uses that
  file to generate the ``parameters.yml`` file.

The paramters are stored in a YAML file. You should be aware of the formatting
conventions.

The Parameters
--------------

database_host, database_port, database_name, database_user, database_password
  Database connection information. These must be supplied

mailer_transport, mailer_host, mailer_user, mailer_password
  Configuration for outgoing emails. The defaults are probably good for development.

secret
  Symfony hashes each HTTP cookie ID with this value to prevent snooping. The
  default is fine for development.

router.request_context.host, router.request_context.scheme, router.request_context.base_url
  Symfony uses these parameters to generate URLs and for setting HTTP cookie parameters.
  For development,

  host
    localhost
  scheme
    http
  base_url
    /web/app_dev.php

secure_cookies
  If true, HTTP cookies will set to HTTPS ONLY. Use with caution.

lom.download_dir
  Filesystem location for LOCKSSOMatic to store data. Paths are either absolute
  or relative to the project directory. The default ``data/download`` is convenient.

lom.hash_methods
  YAML list of acceptable hash methods to check file integrity.

lom.allowed_ips
  LOCKSSOMatic will only allow access to the LOCKSS configuration files to boxes
  on the network. Additional access can be granted to IP (v4 or v6) addresses
  or CIDRs.

lom.aus_per_titledb
  LOCKSS titledb.xml files can grow very large. This parameter limits the number
  of AUs described by a single .xml file.

lom.boxstatus.subject, lom.boxstatus.sender, lom.boxstatus.contact
  LOCKSSOMatic may send email notifications ot box owners when the boxes are
  unreachable. These parameters configure email notifications.

lom.boxstatus.sizewarning
  LOCKSSOMatic may send a notification email if a LOCKSS Box disk is nearing
  full. This parameter is a number between 0.0 (empty) and 1.0 (full).
