.. _section-curling:

Testing LOCKSSOMatic with Curl
==============================

If your goal is to test the network interaction of LOCKSSOMatic, then the
defaults are probably sufficient. You can load up a default network configuration
and use the examples below to interact with the LOCKSSOMatic API.

.. note::

  The examples below assume that you are :ref:`using the PHP built-in web server <section-install-dev>`
  running on port 9000. If your setup is different, you will need to adjust
  the URLs.

Load a Default Configuration
----------------------------

This step is optional. It will clear all data out of your database so use it
with caution. Keep backups.

If you choose to skip this step, the UUIDs in URLs and XML documents will be
different, and you will need to adjust as necessary.

.. code-block:: console
  :linenos:

  $ ./bin/console doctrine:schema:drop --force
  $ ./bin/console doctrine:schema:create
  $ ./bin/console doctrine:fixtures:load -n

Line 1 drops all tables from the database - use with caution. Line 2 creates
tables with the fresh new table smell. Line 3 loads those new tables with a
bunch of default data.

At this point you'll be able to login with user name ``admin@example.com`` and
password ``supersecret``. It's a good password. It has also done all the
:ref:`section-setup` stuff for you with just enough testing information to be
useful for development.

The LOCKSSOMatic API is built on the `SWORD v2`_ specification (as much as possible at least),
which is built on the `Atom Publishing Protocol`_ standard. Familiarity with Atom
and SWORD is recommended from this point on.

Get a Service Document
----------------------

Clients that interact with LOCKSSOMatic via the API all start by requesting a
service document, so start with that. In the examples that follow, the UUID
``29125DE2-E622-416C-93EB-E887B2A3126C`` is associated with a LOCKSSOMatic Content
Provider that already exists in the database.

.. code-block:: console
  :caption: cURL command

  $ curl --header On-Behalf-Of:29125DE2-E622-416C-93EB-E887B2A3126C http://localhost:9000/web/app_dev.php/api/sword/2.0/sd-iri

.. code-block:: xml
  :linenos:
  :caption: XML service document response

  <?xml version="1.0" ?>
  <service xmlns:dcterms="http://purl.org/dc/terms/"
      xmlns:sword="http://purl.org/net/sword/"
      xmlns:atom="http://www.w3.org/2005/Atom"
      xmlns:lom="http://lockssomatic.info/SWORD2"
      xmlns="http://www.w3.org/2007/app">

      <sword:version>2.0</sword:version>

      <!-- sword:maxUploadSize is the maximum file size in content element, measured in kB (1,000 bytes). -->
      <sword:maxUploadSize>10000</sword:maxUploadSize>
      <lom:uploadChecksumType>SHA1 MD5</lom:uploadChecksumType>
      <workspace>
          <atom:title>LOCKSSOMatic</atom:title>
          <collection href="http://localhost:9000/web/app_dev.php/api/sword/2.0/col-iri/29125DE2-E622-416C-93EB-E887B2A3126C">
              <lom:pluginIdentifier id="com.example.text"/>
              <atom:title>Test Provider 1</atom:title>
              <accept>application/atom+xml;type=entry</accept>
              <sword:mediation>true</sword:mediation>
          </collection>
      </workspace>
  </service>

The XML response describes the LOCKSSOMatic deposit processing and interaction.

- Line 11 sets the maximum size of deposits in the LOCKSS network, in kb. In this
  example the maximum is 10Mb.

- Line 12 lists the checkum algorithms that LOCKSSOMatic will accept to verify
  deposits.

- Line 15 provides the SWORD Collection URI. Deposits are created by HTTP POSTing
  to this location.

- Line 18 specifies the content mime-type for the HTTP POST.

If the content provider's UUID is malformed or does not match one known to LOCKSSOMatic
an error document will be returned instead.

.. code-block:: console
  :caption: cURL command

  $ curl --header On-Behalf-Of:cheese http://localhost:9000/web/app_dev.php/api/sword/2.0/sd-iri

.. code-block:: xml
  :linenos:
  :caption: XML error response

  <sword:error xmlns="http://www.w3.org/2005/Atom"
               xmlns:sword="http://purl.org/net/sword/">
      <summary code='404'>Content provider not found.</summary>
      <detail>
  #0 /Users/mjoyce/Sites/lom2/src/AppBundle/Controller/SwordController.php(112): AppBundle\Controller\SwordController-&gt;getProvider(&#039;CHEESE&#039;)
  #1 /Users/mjoyce/Sites/lom2/vendor/symfony/symfony/src/Symfony/Component/HttpKernel/HttpKernel.php(151): AppBundle\Controller\SwordController-&gt;serviceDocumentAction(Object(Symfony\Component\HttpFoundation\Request))
  #2 /Users/mjoyce/Sites/lom2/vendor/symfony/symfony/src/Symfony/Component/HttpKernel/HttpKernel.php(68): Symfony\Component\HttpKernel\HttpKernel-&gt;handleRaw(Object(Symfony\Component\HttpFoundation\Request), 1)
  #3 /Users/mjoyce/Sites/lom2/vendor/symfony/symfony/src/Symfony/Component/HttpKernel/Kernel.php(200): Symfony\Component\HttpKernel\HttpKernel-&gt;handle(Object(Symfony\Component\HttpFoundation\Request), 1, true)
  #4 /Users/mjoyce/Sites/lom2/web/app_dev.php(29): Symfony\Component\HttpKernel\Kernel-&gt;handle(Object(Symfony\Component\HttpFoundation\Request))
  #5 {main}
      </detail>
      </sword:error>

The ``<detail>`` element is not normally available to clients.

Create a Deposit
----------------

The SWORD service document above provides enough information to begin creating
deposits in LOCKSSOMatic. To do so, send an HTTP POST to the collection URI with
the deposit metadata encoded in XML in the body.

In the example below I've saved the deposit metadata in :file:`data/create.xml`.

.. literalinclude:: data/create.xml
  :language: xml
  :caption: Deposit metadata from :file:`data/create.xml`
  :linenos:

The cURL command to HTTP POST the data is

.. code-block:: console
  :caption: cURL command

  $ curl --data @data/create.xml http://localhost:9000/web/app_dev.php/api/sword/2.0/col-iri/29125DE2-E622-416C-93EB-E887B2A3126C

.. code-block:: xml
  :linenos:
  :caption: XML deposit response

  <entry xmlns="http://www.w3.org/2005/Atom"
         xmlns:sword="http://purl.org/net/sword/">

      <sword:treatment>Content URLs deposited to Network Test, collection Test Provider 1.</sword:treatment>

      <content src="http://localhost:9000/web/app_dev.php/api/sword/2.0/cont-iri/29125DE2-E622-416C-93EB-E887B2A3126C/066D5E90-03F7-469E-A231-C67FB8D6109F/state"/>

      <!-- Col-IRI. -->
      <link rel="edit-media" href="http://localhost:9000/web/app_dev.php/api/sword/2.0/col-iri/29125DE2-E622-416C-93EB-E887B2A3126C" />

      <!-- SE-IRI (can be same as Edit-IRI) -->
      <link rel="http://purl.org/net/sword/terms/add" href="http://localhost:9000/web/app_dev.php/api/sword/2.0/cont-iri/29125DE2-E622-416C-93EB-E887B2A3126C/066D5E90-03F7-469E-A231-C67FB8D6109F/edit" />

      <!-- Edit-IRI -->
      <link rel="edit" href="http://localhost:9000/web/app_dev.php/api/sword/2.0/cont-iri/29125DE2-E622-416C-93EB-E887B2A3126C/066D5E90-03F7-469E-A231-C67FB8D6109F/edit" />

      <!-- In LOCKSS-O-Matic, the State-IRI will be the EM-IRI/Cont-IRI with the string '/state' appended. -->
      <link rel="http://purl.org/net/sword/terms/statement" type="application/atom+xml;type=feed"
            href="http://localhost:9000/web/app_dev.php/api/sword/2.0/cont-iri/29125DE2-E622-416C-93EB-E887B2A3126C/066D5E90-03F7-469E-A231-C67FB8D6109F/state" />
  </entry>

The XML response is a SWORD Deposit Receipt. It provides a few API endpoints for
interacting with the deposit via LOCKSSOMatic.

- Line 6 contains the SWORD Statement URL. HTTP GETs to that URL will fetch
  descriptions of the current processing state of the URL, and can be used to
  determine if the deposit has been harvested and preserved by the LOCKSS network.

- Line 15 shows the SWORD Edit URL. HTTP POSTs to that URL will be used to update
  the deposit metadata in LOCKSSOMatic. This is useful if the content changes and
  the resulting checksum is different. This URL is duplicated in a number of
  places including the ``Location:`` HTTP response header.

Check a Deposit Status
----------------------

Now that LOCKSSOMatic knows about that one deposit it is time to check on its
status. An HTTP get on the Statement URL should do it.

.. code-block:: console
  :caption: cURL command

  $ curl -v http://localhost:9000/web/app_dev.php/api/sword/2.0/cont-iri/29125DE2-E622-416C-93EB-E887B2A3126C/066D5E90-03F7-469E-A231-C67FB8D6109F/state

.. code-block:: xml
  :linenos:
  :caption: XML response with SWORD statement

  <atom:feed xmlns:sword="http://purl.org/net/sword/terms/"
             xmlns:atom="http://www.w3.org/2005/Atom"
             xmlns:lom="http://lockssomatic.info/SWORD2">

      <atom:category scheme="http://purl.org/net/sword/terms/state"
                     term="inProgress"
                     label="State">
          LOCKSS boxes have not completed harvesting the content.
      </atom:category>
      <atom:entry>
          <atom:category scheme="http://purl.org/net/sword/terms"
                         term="http://purl.org/net/sword/terms/originalDeposit"
                         label="Original Deposit"/>
          <sword:depositedOn>Thu Aug 23 22:25:22 UTC 2018</sword:depositedOn>
          <lom:agreement></lom:agreement>
          <sword:originalDeposit href="http://localhost:9000/web/app_dev.php/api/sword/2.0/cont-iri/29125DE2-E622-416C-93EB-E887B2A3126C/066D5E90-03F7-469E-A231-C67FB8D6109F/11186563486_8796f4f843_o_d.jpg/original" />
      </atom:entry>
  </atom:feed>

- Line 6 describes this deposit as "inProgress". Possible states are "inProgress"
  and "Complete".

- Line 8 says the same thing as line 6, but in a friendly way.

- Line 16 provides the Original Deposit URL, which can be used to get the
  deposit content back from the LOCKSS network.

Update a Deposit
----------------

Deposit metadata stored in LOCKSSOMatic can be updated with HTTP PUT requests
on the Edit URI. I've stored the updated metadata in :file:`data/edit.xml`.

.. literalinclude:: data/edit.xml
  :language: xml
  :caption: Updated metadata from :file:`data/edit.xml`
  :linenos:

The cURL command to post the update is

.. code-block:: console
  :caption: cURL command

  $ curl -X PUT --data @data/edit.xml http://localhost:9000/web/app_dev.php/api/sword/2.0/cont-iri/29125DE2-E622-416C-93EB-E887B2A3126C/066D5E90-03F7-469E-A231-C67FB8D6109F/edit

.. code-block:: xml

  <entry xmlns="http://www.w3.org/2005/Atom"
         xmlns:sword="http://purl.org/net/sword/">

      <sword:treatment>Content URLs deposited to Network Test, collection Test Provider 1.</sword:treatment>

      <content src="http://localhost:9000/web/app_dev.php/api/sword/2.0/cont-iri/29125DE2-E622-416C-93EB-E887B2A3126C/066D5E90-03F7-469E-A231-C67FB8D6109F/state"/>

      <!-- Col-IRI. -->
      <link rel="edit-media" href="http://localhost:9000/web/app_dev.php/api/sword/2.0/col-iri/29125DE2-E622-416C-93EB-E887B2A3126C" />

      <!-- SE-IRI (can be same as Edit-IRI) -->
      <link rel="http://purl.org/net/sword/terms/add" href="http://localhost:9000/web/app_dev.php/api/sword/2.0/cont-iri/29125DE2-E622-416C-93EB-E887B2A3126C/066D5E90-03F7-469E-A231-C67FB8D6109F/edit" />

      <!-- Edit-IRI -->
      <link rel="edit" href="http://localhost:9000/web/app_dev.php/api/sword/2.0/cont-iri/29125DE2-E622-416C-93EB-E887B2A3126C/066D5E90-03F7-469E-A231-C67FB8D6109F/edit" />

      <!-- In LOCKSS-O-Matic, the State-IRI will be the EM-IRI/Cont-IRI with the string '/state' appended. -->
      <link rel="http://purl.org/net/sword/terms/statement" type="application/atom+xml;type=feed"
            href="http://localhost:9000/web/app_dev.php/api/sword/2.0/cont-iri/29125DE2-E622-416C-93EB-E887B2A3126C/066D5E90-03F7-469E-A231-C67FB8D6109F/state" />
  </entry>

The XML response is again a SWORD Deposit Receipt, identical to the one shown
above.


.. _`SWORD v2`: http://swordapp.github.io/SWORDv2-Profile/SWORDProfile.html
.. _`Atom Publishing Protocol`: https://tools.ietf.org/html/rfc5023
