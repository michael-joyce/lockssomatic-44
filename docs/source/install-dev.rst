.. _section-install-dev:

Installing LOCKSSOMatic for Developers
======================================

This guide assumes that you will be installig LOCKSSOMatic for development or
testing or some other non-production purpose.  Development and experimental versions
of the code can be found in the SFU Library `git repository`_.

Prerequisites
-------------

PHP
  Any version of PHP >= 5.6 should work.

Composer
  PHP dependencies will be installed via Composer.

MySQL
  Any version of MySQL >= 5.7 should work. Other databases may work, but have
  not been tested.

Node, npm, and bower
  Javascript dependencies are managed with Bower, which is an NPM package and
  requries NodeJS to run.

Install
-------

#. Make a recursive clone of the code on your computer. The guide will use the
   web server built into PHP, so location and permissions are not an issue.

   .. code-block:: console

     $ git clone --recursive --depth 1 https://git.lib.sfu.ca/mjoyce/lom-v2.git lom

#. Create a MySQL database and user.

   .. code-block:: console

     mysql> create user lom@localhost;
     mysql> create database lom;
     mysql> grant all on lom.* to lom@localhost;
     mysql> set password for lom@localhost = password('hotpockets');

#. Install the PHP dependencies via Composer.

   .. code-block:: console

     $ cd lom
     $ composer install

   .. todo::

     Describe the parameters.yml file.

#. Install the javascript dependencies via Bower.

   .. code-block:: console

    $ bower install

#. Create the database schema with the Symfony console.

   .. code-block:: console

    $ ./bin/console doctrine:schema:update --force

#. Create an initial user.

   .. code-block:: console
    :linenos:

    $ ./bin/console fos:user:create  <email> <password> <fullname> <institution>
    $ ./bin/console fos:user:promote <email> ROLE_ADMIN

  Line 1 creates the new user, and line 2 grants the user full and complete access
  to the system.

#. Start the PHP web server.

   .. code-block:: console

    $ php -S localhost:9000

   This should start the web server listening on port 9000 at localhost. You should
   be able to access the development version of the LOCKSSOMatic home page at
   http://localhost:9000/web/app_dev.php.

#. If you've got everything going, you should be able to login with the email
   and password in step 6. If it hasn't worked out, check the
   :ref:`section-parameters`.

.. _`git repository`: https://git.lib.sfu.ca/mjoyce/lom-v2
